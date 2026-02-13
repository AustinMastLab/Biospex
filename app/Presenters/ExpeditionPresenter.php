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

namespace App\Presenters;

use Storage;

/**
 * Class ExpeditionPresenter
 */
class ExpeditionPresenter extends Presenter
{
    /**
     * Check if logo file exists or return default.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function showMediumLogo()
    {
        // Check for new Livewire logo_path with medium variant first (check S3 for new uploads)
        if (! empty($this->model->logo_path)) {
            // Try medium variant path on S3 first
            $mediumPath = str_replace('/logos/original/', '/logos/medium/', $this->model->logo_path);
            if (Storage::disk('s3')->exists($mediumPath)) {
                return Storage::disk('s3')->url($mediumPath);
            }

            // Try original path on S3 as fallback
            if (Storage::disk('s3')->exists($this->model->logo_path)) {
                return Storage::disk('s3')->url($this->model->logo_path);
            }
        }

        return config('config.missing_expedition_logo');
    }

    /**
     * Check if logo file exists or return default (original size).
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function showLogo()
    {
        // Check for new Livewire logo_path first (check S3 for new uploads)
        if (! empty($this->model->logo_path) && Storage::disk('s3')->exists($this->model->logo_path)) {
            // Generate a temporary signed URL for private S3 files (valid for 1 hour)
            return Storage::disk('s3')->url($this->model->logo_path);
        }

        // Return default missing logo
        return config('config.missing_expedition_logo');
    }

    public function expeditionShowIcon()
    {
        $ariaLabel = e(t('View expedition: %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.expeditions.show', [$this->model]).'"
            data-hover="tooltip"
            title="'.e(t('View expedition')).'"
            aria-label="'.$ariaLabel.'">
            <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function expeditionShowIconLrg()
    {
        $ariaLabel = e(t('View expedition: %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.expeditions.show', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('View expedition')).'"
            aria-label="'.$ariaLabel.'">
            <i class="fas fa-eye fa-2x" aria-hidden="true"></i></a>';
    }

    public function expeditionToolsIconLrg()
    {
        $ariaLabel = e(t('Expedition tools for %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="" class="prevent-default"
                       data-dismiss="modal"
                       data-toggle="modal"
                       data-target="#global-modal"
                       data-size="modal-lg"
                       data-url="'.route('admin.expeditions.tools', [$this->model]).'"
                       data-hover="tooltip"
                       data-title="'.e(t('Expedition tools')).'"
                       aria-label="'.$ariaLabel.'"><i class="fas fa-tools fa-2x" aria-hidden="true"></i></a>';
    }

    public function expeditionDownloadIconLrg()
    {
        $route = route('admin.downloads.index', [$this->model]);
        $ariaLabel = e(t('Download expedition files for %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="#" class="prevent-default" 
                data-toggle="modal" 
                data-title="'.e(t('Download expedition files')).'" 
                data-url="'.$route.'"
                data-dismiss="modal" data-toggle="modal" data-target="#global-modal" data-size="modal-xl" 
                data-hover="tooltip" 
                title="'.e(t('Download expedition files')).'"
                aria-label="'.$ariaLabel.'"><i class="fas fa-file-download fa-2x" aria-hidden="true"></i></a>';
    }

    public function expeditionEditIcon()
    {
        $ariaLabel = e(t('Edit expedition: %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.expeditions.edit', [$this->model]).'" data-hover="tooltip" title="'.e(t('Edit expedition')).'" aria-label="'.$ariaLabel.'">
        <i class="fas fa-edit" aria-hidden="true"></i></a>';
    }

    public function expeditionEditIconLrg()
    {
        $ariaLabel = e(t('Edit expedition: %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.expeditions.edit', [$this->model]).'" 
        data-hover="tooltip" 
        title="'.e(t('Edit expedition')).'"
        aria-label="'.$ariaLabel.'">
        <i class="fas fa-edit fa-2x" aria-hidden="true"></i></a>';
    }

    public function expeditionCloneIcon()
    {
        $ariaLabel = e(t('Clone expedition: %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.expeditions.clone', [$this->model]).'" 
        data-hover="tooltip" 
        title="'.e(t('Clone expedition')).'"
        aria-label="'.$ariaLabel.'">
        <i class="fas fa-clone" aria-hidden="true"></i></a>';
    }

    public function expeditionCloneIconLrg()
    {
        $ariaLabel = e(t('Clone expedition: %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.expeditions.clone', [$this->model]).'" 
        data-hover="tooltip" 
        title="'.e(t('Clone expedition')).'"
        aria-label="'.$ariaLabel.'">
        <i class="fas fa-clone fa-2x" aria-hidden="true"></i></a>';
    }

    public function expeditionDeleteIcon()
    {
        $ariaLabel = e(t('Delete expedition: %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.expeditions.delete', [$this->model]).'" 
            class="prevent-default"
            title="'.e(t('Delete expedition')).'"
            aria-label="'.$ariaLabel.'"
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete expedition')).'?" data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt" aria-hidden="true"></i></a>';
    }

    public function expeditionDeleteIconLrg()
    {
        $ariaLabel = e(t('Delete expedition: %s (expedition %s)', (string) $this->model->title, (string) $this->model->uuid));

        return '<a href="'.route('admin.expeditions.delete', [$this->model]).'" 
            class="prevent-default"
            title="'.e(t('Delete expedition')).'"
            aria-label="'.$ariaLabel.'"
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete expedition')).'?" 
            data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return return ocr lrg icon.
     *
     * @return string
     */
    public function expeditionOcrBtn()
    {
        return '<a href="'.route('admin.expeditions.ocr', [$this->model]).'" 
            class="prevent-default btn btn-primary rounded-0 mb-1 mt-1"
            data-method="post"
            data-confirm="confirmation"
            data-title="'.t('Reprocess Subject OCR').'?" 
            data-content="'.t('This action will reprocess all ocr for the Expedition.').'">
            '.t('Reprocess Subject OCR').'</a>';
    }

    /**
     * Return expedition link.
     *
     * @return string
     */
    public function titleLink()
    {
        return '<a href="'.route('admin.expeditions.show', [$this->model]).'">'.$this->model->title.'</a>';
    }

    /**
     * Accessible alt text for expedition logo images.
     */
    public function logoAlt(): string
    {
        return t('Logo for expedition: %s', $this->model->title);
    }

    public function completed()
    {
        return $this->model->completed ? 'Completed' : 'In Progress';
    }
}
