<?php

/*
 * Copyright (C) 2014 - 2025, Biospex
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

namespace App\Services\Actor\GeoLocate;

use App\Enums\ActorExpeditionState;
use App\Models\Download;
use App\Models\Expedition;
use App\Models\GeoLocateForm;
use App\Services\Csv\AwsS3CsvService;
use App\Services\Helpers\GeneralService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Storage;

/**
 * GeoLocateFormService provides various methods to handle GeoLocate forms,
 * including loading relations, building forms, checking states, saving data,
 * and parsing related files. It interacts with external storage and services.
 */
class GeoLocateFormService
{
    /**
     * Reconcile file source.
     */
    private string $source;

    /**
     * User reconciled file exists.
     */
    private bool $userReconciledFileExists;

    /**
     * Expert reconciled file exists.
     */
    private bool $expertReconciledFileExists;

    /**
     * Expert review has been started or completed.
     */
    private bool $expertReviewExists;

    /**
     * Constructor method to initialize dependencies.
     *
     * @param  GeoLocateForm  $geoLocateForm  Instance of GeoLocateForm for geolocation-related functionalities.
     * @param  AwsS3CsvService  $awsS3CsvService  Instance of AwsS3CsvService for handling CSV operations on AWS S3.
     * @param  GeneralService  $generalService  Instance of GeneralService for general-purpose service methods.
     */
    public function __construct(
        protected GeoLocateForm $geoLocateForm,
        protected AwsS3CsvService $awsS3CsvService,
        protected GeneralService $generalService,
        protected Download $download,
    ) {}

    /**
     * Load related models for the given expedition.
     *
     * @param  Expedition  $expedition  The expedition instance to load relations for, passed by reference.
     */
    public function loadExpeditionRelations(Expedition &$expedition): void
    {
        $expedition->load([
            'project.group',
            'geoLocateExport',
            'zooActorExpedition',
            'geoActorExpedition',
            'geoLocateDataSource' => function ($query) {
                $query->with('geoLocateForm', 'download');
            },
        ]);
    }

    /**
     * Retrieve form data based on expedition and request parameters.
     *
     * @param  Expedition  $expedition  The expedition instance to retrieve form data for.
     * @param  array  $request  Optional request parameters for additional data handling.
     * @return array The resulting form data.
     *
     * @throws FileNotFoundException
     */
    public function getFormData(Expedition $expedition, array $request = []): array
    {
        $this->setUserReconciledVar($expedition);

        $this->setExpertExistVars($expedition);

        $this->setSource($expedition, $request);

        return isset($expedition->geoLocateDataSource->geoLocateForm) ||
                isset($request['formId']) ? $this->existingForm($expedition, $request) : $this->newForm($expedition);
    }

    /**
     * Build and render form fields for the given expedition and form data.
     *
     * @param  Expedition  $expedition  The expedition object used to build form fields.
     * @param  array  $form  The array of form data to be used.
     * @return string The rendered HTML of the form fields.
     *
     * @throws \Throwable
     */
    public function buildFormFields(Expedition $expedition, array $form): string
    {
        $disabled = $this->checkExportDisabled($expedition, $form);

        return view('admin.geolocate.partials.form-fields', compact('expedition', 'form', 'disabled'))->render();
    }

    /**
     * Check if the export is disabled for the given expedition and form data.
     *
     * @param  Expedition  $expedition  The expedition instance being checked.
     * @param  array  $form  The form data containing export details.
     * @return bool Returns true if the export is disabled, false otherwise.
     */
    public function checkExportDisabled(Expedition $expedition, array $form): bool
    {
        if ($expedition->geoLocateForm === null) {
            return true;
        }

        return is_null($expedition->geoLocateExport) ? $form['exported'] : $form['exported'] &&
            $this->generalService->downloadFileExists($expedition->geoLocateExport->file, $expedition->geoLocateExport->type, $expedition->geoLocateExport->actor_id);
    }

    /**
     * Generate a new form structure based on the given expedition data.
     *
     * @param  Expedition  $expedition  The expedition instance used to generate the form structure.
     * @return array An associative array representing the new form structure.
     *
     * @throws FileNotFoundException
     */
    public function newForm(Expedition $expedition): array
    {
        return [
            'group_id' => $expedition->project->group->id,
            'name' => '',
            'source' => $this->source,
            'entries' => old('entries', 1),
            'fields' => null,
            'user_reconciled' => $this->userReconciledFileExists,
            'expert_reconciled' => $this->expertReconciledFileExists,
            'expert_review' => $this->expertReviewExists,
            'exported' => $expedition->geoActorExpedition->state !== ActorExpeditionState::NotStarted,
            'geo' => $this->getGeoLocateFields(),
            'csv' => $this->getCsvHeader($expedition),
            'created_at' => '',
        ];
    }

    /**
     * Generate an array containing the existing geo-location form data for the given expedition.
     *
     * @param  Expedition  $expedition  The expedition instance associated with the form.
     * @param  array  $request  An optional array containing request parameters, including the formId.
     * @return array An associative array with data about the geo-location form and its related fields.
     *
     * @throws FileNotFoundException
     */
    public function existingForm(Expedition $expedition, array $request = []): array
    {
        $geoLocateForm = isset($request['formId']) ? $this->findGeoLocateFormById($request['formId']) : null;

        $form = isset($geoLocateForm->fields) ? $geoLocateForm : $expedition->geoLocateDataSource->geoLocateForm;

        $entries = count($form->fields);

        return [
            'group_id' => $form->group_id,
            'name' => $form->name,
            'source' => $this->source,
            'entries' => $entries,
            'fields' => $form->fields,
            'user_reconciled' => $this->userReconciledFileExists,
            'expert_reconciled' => $this->expertReconciledFileExists,
            'expert_review' => $this->expertReviewExists,
            'exported' => $expedition->geoActorExpedition->state !== ActorExpeditionState::NotStarted,
            'geo' => $this->getGeoLocateFields(),
            'csv' => $this->getCsvHeader($expedition),
            'created_at' => $form->created_at,
        ];
    }

    /**
     * Retrieve the geolocate fields from a configuration file.
     *
     * @return array The decoded geolocate fields as an associative array.
     *
     * @throws FileNotFoundException
     */
    private function getGeoLocateFields(): array
    {
        return json_decode(File::get(config('geolocate.fields_file')), true);
    }

    /**
     * Set the reconciled file source based on the expedition's geolocation data or provided request parameters.
     *
     * @param  Expedition  $expedition  The expedition instance containing geolocation data.
     * @param  array  $request  Optional. An array of request parameters that may include a source key.
     */
    public function setSource(Expedition $expedition, array $request = []): void
    {
        if (isset($request['source'])) {
            $this->source = $request['source'];

            return;
        }

        if (isset($expedition->geoLocateDataSource->download)) {
            $this->source = match ($expedition->geoLocateDataSource->download->type) {
                'reconciled-with-user' => $this->userReconciledFileExists ? 'reconciled-with-user' : 'reconciled',
                'reconciled-with-expert' => ($this->expertReconciledFileExists && $this->expertReviewExists) ? 'reconciled-with-expert' : 'reconciled',
                default => 'reconciled',
            };

            return;
        }

        $this->source =
            $this->userReconciledFileExists ? 'reconciled-with-user' :
                (($this->expertReconciledFileExists && $this->expertReviewExists) ? 'reconciled-with-expert' : 'reconciled');
    }

    /**
     * Sets the userReconciledFileExists property to indicate whether the reconciled file exists for the given expedition.
     *
     * @param  Expedition  $expedition  The expedition whose reconciled file existence needs to be checked.
     */
    public function setUserReconciledVar(Expedition $expedition): void
    {
        $this->userReconciledFileExists = Storage::disk('s3')->exists(config('zooniverse.directory.reconciled-with-user').'/'.$expedition->id.'.csv');
    }

    /**
     * Sets the properties to indicate the existence of the expert reconciled file
     * and expert review for the given expedition.
     *
     * @param  Expedition  $expedition  The expedition whose expert-related files and reviews need to be checked.
     */
    public function setExpertExistVars(Expedition $expedition): void
    {
        $this->expertReconciledFileExists = Storage::disk('s3')
            ->exists(config('zooniverse.directory.reconciled-with-expert').'/'.$expedition->id.'.csv');
        $this->expertReviewExists = $expedition->zooActorExpedition->expert;
    }

    /**
     * Retrieves the CSV header for the given expedition based on the source type.
     * Filters out any headers with the value 'subject_id'.
     *
     * @param  Expedition  $expedition  The expedition for which the CSV header needs to be retrieved.
     * @return array An array of header fields from the CSV file, excluding the 'subject_id' field.
     */
    private function getCsvHeader(Expedition $expedition): array
    {
        // Default is reconcile
        $csvFilePath = match ($this->source) {
            'reconciled-with-expert' => config('zooniverse.directory.reconciled-with-expert').'/'.$expedition->id.'.csv',
            'reconciled-with-user' => config('zooniverse.directory.reconciled-with-user').'/'.$expedition->id.'.csv',
            default => config('zooniverse.directory.reconciled').'/'.$expedition->id.'.csv',
        };

        $key = 'geolocate_csv_header:'.$this->source.':'.md5($csvFilePath);
        $tags = ['geolocate_forms', 'csv_headers'];

        return Cache::tags($tags)->remember($key, 14440 * 60, function () use ($csvFilePath) {
            $this->awsS3CsvService->createBucketStream(config('filesystems.disks.s3.bucket'), $csvFilePath, 'r');
            $this->awsS3CsvService->createCsvReaderFromStream();
            $this->awsS3CsvService->csv->setHeaderOffset();

            $array = $this->awsS3CsvService->csv->getHeader();

            return array_values(array_filter($array, function ($e) {
                return $e !== 'subject_id';
            }));
        });
    }

    /**
     * Save the form data and associate it with the expedition.
     *
     * @param  array  $request  The form data to be saved.
     * @param  Expedition  $expedition  The expedition instance to associate with the form data.
     */
    public function saveForm(array $request, Expedition $expedition): void
    {
        $download = $this->getDownload($expedition, $request);

        $hash = md5(json_encode([$request['group_id'], $request['name'], $request['fields']]));

        $attributes = [
            'group_id' => $request['group_id'],
            'name' => $request['name'],
            'hash' => $hash,
        ];
        $values = [
            'group_id' => $request['group_id'],
            'name' => $request['name'],
            'hash' => $hash,
            'fields' => $request['fields'],
        ];

        $geoLocateForm = $this->geoLocateForm->updateOrCreate($attributes, $values);
        $expedition->geoLocateDataSource()->updateOrCreate([
            'project_id' => $expedition->project_id,
            'expedition_id' => $expedition->id,
        ], [
            'project_id' => $expedition->project_id,
            'expedition_id' => $expedition->id,
            'geo_locate_form_id' => $geoLocateForm->id,
            'download_id' => $download->id,
        ]);
    }

    /**
     * Retrieves a GeoLocateForm by its unique identifier.
     *
     * @param  int  $id  The unique identifier of the GeoLocateForm to be retrieved.
     * @return GeoLocateForm The GeoLocateForm instance corresponding to the provided identifier.
     */
    public function findGeoLocateFormById(int $id): GeoLocateForm
    {
        return $this->geoLocateForm->find($id);
    }

    /**
     * Retrieves a download record associated with the specified expedition and request parameters.
     *
     * @param  Expedition  $expedition  The expedition for which the download record is being retrieved.
     * @param  array  $request  Additional request parameters to filter the download, such as the source type.
     * @return Download|null The download record if found, or null if no matching record exists.
     */
    public function getDownload(Expedition $expedition, array $request = []): ?Download
    {
        return $this->download->where('expedition_id', $expedition->id)
            ->where('actor_id', config('zooniverse.actor_id'))
            ->where('type', $request['source'])->first();
    }
}
