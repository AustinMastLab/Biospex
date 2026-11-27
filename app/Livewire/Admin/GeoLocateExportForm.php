<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
 * biospex@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Livewire\Admin;

use App\Jobs\GeoLocateExportJob;
use App\Models\Expedition;
use App\Models\GeoLocateForm;
use App\Services\Actor\GeoLocate\GeoLocateFormService;
use App\Services\Permission\CheckPermission;
use App\Traits\WithModalNotices;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * The GeoLocate Export Form (in the global modal): which saved form to start from, the CSV source, the form's name, and
 * which CSV column fills each GeoLocate field. Save stores the form for the expedition; Export makes the GeoLocate file
 * from the saved form.
 */
class GeoLocateExportForm extends Component
{
    use WithModalNotices;

    /**
     * The GeoLocate fields every form must fill.
     */
    public const REQUIRED_FIELDS = ['County', 'Country', 'Locality', 'ScientificName', 'StateProvince'];

    #[Locked]
    public int $expeditionId;

    #[Locked]
    public int $groupId;

    /**
     * The Upload Reconciled With User Opinion form, opened in the same modal.
     */
    #[Locked]
    public string $uploadUrl = '';

    /**
     * The saved form to start from, or '' for a new form.
     */
    public string $formId = '';

    public string $source = '';

    public string $name = '';

    /**
     * @var array<int, array{geo: string, csv: string}>
     */
    public array $fields = [];

    /**
     * GeoLocate field value or index => label (config('geolocate.fields_file')); a label with * is required.
     *
     * @var array<int|string, string>
     */
    #[Locked]
    public array $geoOptions = [];

    /**
     * The chosen source CSV's columns.
     *
     * @var array<int, string>
     */
    #[Locked]
    public array $csvOptions = [];

    #[Locked]
    public bool $userReconciled = false;

    #[Locked]
    public bool $expertReconciled = false;

    #[Locked]
    public bool $expertReview = false;

    /**
     * Whether the expedition has a saved form, so it can be exported.
     */
    #[Locked]
    public bool $hasSavedForm = false;

    #[Locked]
    public bool $exportDisabled = true;

    /**
     * Why the form could not be loaded (for example, no reconciled CSV yet).
     */
    #[Locked]
    public string $loadError = '';

    public function mount(Expedition $expedition): void
    {
        $this->expeditionId = $expedition->id;
        $service = $this->formService();
        $service->loadExpeditionRelations($expedition);
        $this->groupId = $expedition->project->group->id;
        $this->uploadUrl = route('admin.reconcile-user-form.edit', [$expedition]);

        try {
            $form = $service->getFormData($expedition);
        } catch (Throwable $throwable) {
            $this->loadError = $throwable->getMessage();

            return;
        }

        $this->applyForm($form);
        $this->hasSavedForm = $form['fields'] !== null;
        $this->formId = (string) ($expedition->geoLocateDataSource->geoLocateForm->id ?? '');
        $this->exportDisabled = $service->checkExportDisabled($expedition, $form);
    }

    /**
     * Start from another saved form (its name and fields), or a new form (keeping the fields, clearing the name).
     */
    public function updatedFormId(string $formId): void
    {
        $this->resetErrorBag();

        if ($formId === '') {
            $this->name = '';

            return;
        }

        if (! GeoLocateForm::where('group_id', $this->groupId)->whereKey($formId)->exists()) {
            $this->formId = '';

            return;
        }

        $this->applyForm($this->formData(['formId' => (int) $formId, 'source' => $this->source]));
    }

    /**
     * Read the new source CSV's columns, and clear any chosen column it doesn't have.
     */
    public function updatedSource(string $source): void
    {
        $this->resetErrorBag();

        if (! in_array($source, $this->sources(), true)) {
            $this->source = 'reconciled';
        }

        $request = ['source' => $this->source] + ($this->formId === '' ? [] : ['formId' => (int) $this->formId]);
        $this->csvOptions = $this->formData($request)['csv'];

        foreach ($this->fields as $index => $field) {
            if (! in_array($field['csv'], $this->csvOptions, true)) {
                $this->fields[$index]['csv'] = '';
            }
        }
    }

    public function addField(): void
    {
        $this->fields[] = ['geo' => '', 'csv' => ''];
    }

    public function removeField(): void
    {
        if (count($this->fields) > 1) {
            array_pop($this->fields);
        }
    }

    /**
     * Save the form for the expedition (and the group's saved forms).
     */
    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'source' => ['required', Rule::in($this->sources())],
            'fields' => 'required|array|min:1',
            'fields.*.geo' => ['required', Rule::in($this->geoValues())],
            'fields.*.csv' => ['required', Rule::in($this->csvOptions)],
        ], [
            'fields.*.geo.required' => t('Choose a GeoLocate field in every row.'),
            'fields.*.csv.required' => t('Choose a CSV column in every row.'),
        ], [
            'name' => t('form name'),
            'source' => t('CSV source'),
            'fields.*.geo' => t('GeoLocate field'),
            'fields.*.csv' => t('CSV column'),
        ]);

        $chosen = array_column($this->fields, 'geo');
        if (count($chosen) !== count(array_unique($chosen))) {
            $this->addError('fields', t('GeoLocate Export fields may not contain duplicate values.'));

            return;
        }

        $missing = array_diff(self::REQUIRED_FIELDS, $chosen);
        if ($missing !== []) {
            $this->addError('fields', t('GeoLocate requires the fields: %s', implode(', ', $missing)));

            return;
        }

        $expedition = $this->expedition();
        if (! CheckPermission::handle('updateProject', $expedition->project->group)) {
            $this->notice(t('You do not have permissions for this action.'));

            return;
        }

        try {
            $this->formService()->saveForm([
                'group_id' => $this->groupId,
                'name' => $this->name,
                'source' => $this->source,
                'fields' => array_values($this->fields),
            ], $expedition);
        } catch (Throwable $throwable) {
            $this->notice($throwable->getMessage());

            return;
        }

        $this->finish(t('GeoLocate export form saved.'));
    }

    /**
     * Make the GeoLocate export file from the saved form; the user gets an email when it is ready.
     */
    public function export(): void
    {
        $expedition = $this->expedition();
        if (! CheckPermission::handle('updateProject', $expedition->project->group)) {
            $this->notice(t('You do not have permissions for this action.'));

            return;
        }

        if (! $this->hasSavedForm || $this->exportDisabled) {
            $this->notice(t('Save the form before exporting.'));

            return;
        }

        GeoLocateExportJob::dispatch($expedition, Auth::user());

        $this->finish(t('GeoLocate export job scheduled for processing. You will receive an email when file has been created.'));
    }

    /**
     * The CSV sources this expedition has.
     *
     * @return array<int, string>
     */
    private function sources(): array
    {
        return array_values(array_filter([
            'reconciled',
            $this->expertReconciled && $this->expertReview ? 'reconciled-with-expert' : null,
            $this->userReconciled ? 'reconciled-with-user' : null,
        ]));
    }

    /**
     * The value each GeoLocate option submits: its key, or for a numbered option its label without the *.
     *
     * @return array<int|string, string>
     */
    private function geoValues(): array
    {
        $values = [];
        foreach ($this->geoOptions as $key => $label) {
            $values[$key] = is_numeric($key) ? str_replace('*', '', $label) : (string) $key;
        }

        return $values;
    }

    /**
     * Take the form's name, source, columns, flags and fields (or the required fields for a new form).
     *
     * @param  array<string, mixed>  $form  GeoLocateFormService::getFormData()
     */
    private function applyForm(array $form): void
    {
        $this->name = (string) $form['name'];
        $this->source = (string) $form['source'];
        $this->geoOptions = $form['geo'];
        $this->csvOptions = $form['csv'];
        $this->userReconciled = (bool) $form['user_reconciled'];
        $this->expertReconciled = (bool) $form['expert_reconciled'];
        $this->expertReview = (bool) $form['expert_review'];
        $this->fields = array_values(array_map(
            fn (array $field) => ['geo' => (string) ($field['geo'] ?? ''), 'csv' => (string) ($field['csv'] ?? '')],
            $form['fields'] ?? array_map(fn (string $geo) => ['geo' => $geo, 'csv' => ''], self::REQUIRED_FIELDS),
        ));
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    private function formData(array $request): array
    {
        $expedition = $this->expedition();
        $service = $this->formService();
        $service->loadExpeditionRelations($expedition);

        return $service->getFormData($expedition, $request);
    }

    private function expedition(): Expedition
    {
        return Expedition::with('project.group')->findOrFail($this->expeditionId);
    }

    private function formService(): GeoLocateFormService
    {
        return app(GeoLocateFormService::class);
    }

    public function render(): View
    {
        return view('livewire.admin.geo-locate-export-form', [
            'savedForms' => GeoLocateForm::where('group_id', $this->groupId)->get(['id', 'name', 'created_at']),
            'sources' => $this->sources(),
            'geoValues' => $this->geoValues(),
        ]);
    }
}
