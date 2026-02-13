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
 * Class ProjectPresenter
 */
class ProjectPresenter extends Presenter
{
    /**
     * Accessible alt text for project logo images.
     */
    public function logoAlt(): string
    {
        return t('Logo for project: %s', $this->model->title);
    }

    /**
     * Check if logo file exists or return default.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function showLogo()
    {
        // Check for new Livewire logo_path first (check S3 for new uploads)
        if (! empty($this->model->logo_path) && Storage::disk('s3')->exists($this->model->logo_path)) {
            return Storage::disk('s3')->url($this->model->logo_path);
        }

        return config('config.missing_project_logo');
    }

    /**
     * Build link to banner.
     *
     * @return string
     */
    public function bannerFileName()
    {
        return $this->model->banner_file ?? 'banner-trees.jpg';
    }

    /**
     * Build link to banner.
     *
     * @return string
     */
    public function bannerFileUrl()
    {
        $banner = $this->model->banner_file;

        return $banner === null ? '/images/habitat-banners/banner-trees.jpg' : '/images/habitat-banners/'.$banner;
    }

    /**
     * Return project home button
     *
     * @return string
     */
    public function projectPageIcon()
    {
        $route = route('front.projects.show', [$this->model->slug]);
        $ariaLabel = e(t('Project public page for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->slug == null ? '' : '<a href="'.$route.'"
                data-hover="tooltip"
                title="'.e(t('Project public page')).'"
                aria-label="'.$ariaLabel.'"><i class="fas fa-project-diagram" aria-hidden="true"></i></a>';
    }

    /**
     * Return project home button
     *
     * @return string
     */
    public function projectPageIconLrg()
    {
        $route = route('front.projects.show', [$this->model->slug]);
        $ariaLabel = e(t('Project public page for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->slug == null ? '' : '<a href="'.$route.'"
                target="_blank"
                rel="noopener noreferrer"
                data-hover="tooltip"
                title="'.e(t('Project public page')).'"
                aria-label="'.$ariaLabel.'"><i class="fas fa-project-diagram fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return project admin icon
     *
     * @return string
     */
    public function projectAdminIconLrg()
    {
        $route = route('admin.projects.show', [$this->model]);
        $ariaLabel = e(t('Show project admin page: %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->id == null ? '' : '<a href="'.$route.'"
                data-hover="tooltip"
                title="'.e(t('Show project admin page')).'"
                aria-label="'.$ariaLabel.'"><i class="fas fa-project-diagram fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return project events small icon
     *
     * @return string
     */
    public function projectEventsIcon()
    {
        $route = route('front.projects.show', [$this->model->slug]);
        $ariaLabel = e(t('Events for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->events_count == null ? '' : '<a href="'.$route.'#events" data-hover="tooltip" title="'.e(t('Events')).'" aria-label="'.$ariaLabel.'">
                    <i class="far fa-calendar-alt" aria-hidden="true"></i></a>';
    }

    /**
     * Return project events large icon
     *
     * @return string
     */
    public function projectEventsIconLrg()
    {
        $route = route('front.projects.show', [$this->model->slug]);
        $ariaLabel = e(t('Events for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->events_count == null ? '' : '<a href="'.$route.'#events" data-hover="tooltip" title="'.e(t('Events')).'" aria-label="'.$ariaLabel.'">
                    <i class="far fa-calendar-alt fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return organization icon
     *
     * @return string
     */
    public function organizationIcon()
    {
        $ariaLabel = e(t('Organization website for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->organization_website == null ? '' : '<a href="'.$this->model->organization_website.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.e(t('Organization')).'" aria-label="'.$ariaLabel.'">
                    <i class="fas fa-building" aria-hidden="true"></i></a>';
    }

    /**
     * Return organization lrg icon
     *
     * @return string
     */
    public function organizationIconLrg()
    {
        $ariaLabel = e(t('Organization website for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->organization_website == null ? '' : '<a href="'.$this->model->organization_website.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.e(t('Organization')).'" aria-label="'.$ariaLabel.'">
                    <i class="fas fa-building fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return twitter small icon
     *
     * @return string
     */
    public function twitterIcon()
    {
        $ariaLabel = e(t('Twitter for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->twitter == null ? '' : '<a href="'.$this->model->twitter.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.e(t('Twitter')).'" aria-label="'.$ariaLabel.'">
                    <i class="fab fa-twitter" aria-hidden="true"></i></a>';
    }

    /**
     * Return twitter large icon
     *
     * @return string
     */
    public function twitterIconLrg()
    {
        $ariaLabel = e(t('Twitter for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->twitter == null ? '' : '<a href="'.$this->model->twitter.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.e(t('Twitter')).'" aria-label="'.$ariaLabel.'">
                    <i class="fab fa-twitter fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return facebook small icon
     *
     * @return string
     */
    public function facebookIcon()
    {
        $ariaLabel = e(t('Facebook for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->facebook == null ? '' : '<a href="'.$this->model->facebook.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.e(t('Facebook')).'" aria-label="'.$ariaLabel.'">
                    <i class="fab fa-facebook" aria-hidden="true"></i></a>';
    }

    /**
     * Return facebook large icon
     *
     * @return string
     */
    public function facebookIconLrg()
    {
        $ariaLabel = e(t('Facebook for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->facebook == null ? '' : '<a href="'.$this->model->facebook.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.e(t('Facebook')).'" aria-label="'.$ariaLabel.'">
                    <i class="fab fa-facebook fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return blog small icon
     *
     * @return string
     */
    public function blogIcon()
    {
        $ariaLabel = e(t('Blog for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->blog_url == null ? '' : '<a href="'.$this->model->blog_url.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.e(t('Blog')).'" aria-label="'.$ariaLabel.'">
                    <i class="fab fa-blogger-b" aria-hidden="true"></i></a>';
    }

    /**
     * Return blog large icon
     *
     * @return string
     */
    public function blogIconLrg()
    {
        $ariaLabel = e(t('Blog for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->blog_url == null ? '' : '<a href="'.$this->model->blog_url.'" target="_blank" rel="noopener noreferrer" data-hover="tooltip" title="'.e(t('Blog')).'" aria-label="'.$ariaLabel.'">
                    <i class="fab fa-blogger-b fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return contact small icon
     *
     * @return string
     */
    public function contactEmailIcon()
    {
        $ariaLabel = e(t('Contact %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->contact_email == null ? '' : '<a href="mailto:'.$this->model->contact_email.'" data-hover="tooltip" title="'.e(t('Contact')).'" aria-label="'.$ariaLabel.'">
                    <i class="fas fa-envelope" aria-hidden="true"></i></a>';
    }

    /**
     * Return contact large icon
     *
     * @return string
     */
    public function contactEmailIconLrg()
    {
        $ariaLabel = e(t('Contact %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return $this->model->contact_email == null ? '' : '<a href="mailto:'.$this->model->contact_email.'" data-hover="tooltip" title="'.e(t('Contact')).'" aria-label="'.$ariaLabel.'">
                    <i class="fas fa-envelope fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return expedition icon on project home page.
     *
     * @return string
     */
    public function projectExpeditionsIcon()
    {
        $ariaLabel = e(t('Expeditions for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="#expeditions" data-hover="tooltip" title="'.e(t('Expeditions')).'" aria-label="'.$ariaLabel.'"><i class="fas fa-binoculars" aria-hidden="true"></i></a>';
    }

    /**
     * Return expedition icon on project home page.
     *
     * @return string
     */
    public function projectExpeditionsIconLrg()
    {
        $ariaLabel = e(t('Expeditions for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="#expeditions" data-hover="tooltip" title="'.e(t('Expeditions')).'" aria-label="'.$ariaLabel.'"><i class="fas fa-binoculars fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return return explore project subjects icon.
     *
     * @return string
     */
    public function projectExploreIconLrg()
    {
        $ariaLabel = e(t('Explore project subjects for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.project-subjects.index', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('Explore project subjects')).'"
            aria-label="'.$ariaLabel.'"><i class="fas fa-table fa-2x"></i></a>';
    }

    /**
     * Return view project icon.
     */
    public function projectShowIcon()
    {
        $ariaLabel = e(t('View project: %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.projects.show', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('View project')).'"
            aria-label="'.$ariaLabel.'"><i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    /**
     * Return view project icon.
     */
    public function projectShowIconLrg()
    {
        $ariaLabel = e(t('View project: %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.projects.show', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('View project')).'"
            aria-label="'.$ariaLabel.'"><i class="fas fa-eye fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return return advertise project icon.
     */
    public function projectAdvertiseIconLrg()
    {
        return '';
        /* Disabled until Austin wants to bring it back.
        return '<a href="'.route('admin.advertises.index', [$this->model]).'"
            data-hover="tooltip"
            title="'.t('Download Advertisement Manifest').'"
            aria-label="'.t('Download Advertisement Manifest').'">
            <i class="fas fa-ad fa-2x" aria-hidden="true"></i>
        </a>';
        */
    }

    /**
     * Return return statistics project icon.
     */
    public function projectStatisticsIconLrg()
    {
        $ariaLabel = e(t('Project statistics for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.project-stats.index', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('Project statistics')).'"
            aria-label="'.$ariaLabel.'"><i class="fas fa-chart-bar fa-2x"></i></a>';
    }

    /**
     * Return return edit project icon.
     */
    public function projectEditIcon()
    {
        $ariaLabel = e(t('Edit project: %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.projects.edit', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('Edit project')).'"
            aria-label="'.$ariaLabel.'"><i class="fas fa-edit" aria-hidden="true"></i></a>';
    }

    /**
     * Return return edit project icon.
     */
    public function projectEditIconLrg()
    {
        $ariaLabel = e(t('Edit project: %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.projects.edit', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('Edit project')).'"
            aria-label="'.$ariaLabel.'"><i class="fas fa-edit fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return return clone project icon.
     */
    public function projectCloneIcon()
    {
        $ariaLabel = e(t('Clone project: %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.projects.clone', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('Clone project')).'"
            aria-label="'.$ariaLabel.'"><i class="fas fa-clone" aria-hidden="true"></i></a>';
    }

    /**
     * Return return clone project icon.
     */
    public function projectCloneIconLrg()
    {
        $ariaLabel = e(t('Clone project: %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.projects.clone', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('Clone project')).'"
            aria-label="'.$ariaLabel.'"><i class="fas fa-clone fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return return delete project icon.
     */
    public function projectDeleteIcon()
    {
        $ariaLabel = e(t('Delete project: %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.projects.destroy', [$this->model]).'" class="prevent-default"
            title="'.e(t('Delete project')).'"
            aria-label="'.$ariaLabel.'" 
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete project')).'?" data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt" aria-hidden="true"></i></a>';
    }

    /**
     * Return return delete project icon.
     */
    public function projectDeleteIconLrg()
    {
        $ariaLabel = e(t('Delete project: %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.projects.destroy', [$this->model]).'" class="prevent-default"
            title="'.e(t('Delete project')).'"
            aria-label="'.$ariaLabel.'" 
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete project')).'?" data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return return clone project icon.
     */
    public function projectImportIconLrg()
    {
        $ariaLabel = e(t('Import project subjects for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="#" class="prevent-default" 
                    data-url="'.route('admin.imports.index', [$this->model]).'" 
                    data-dismiss="modal" data-toggle="modal" data-target="#global-modal" data-size="modal-lg"
                    data-title="'.e(t('Import project subjects')).'"
                    data-hover="tooltip" title="'.e(t('Import project subjects')).'"
                    aria-label="'.$ariaLabel.'">
                    <i class="fas fa-file-import fa-2x"></i></a>';
    }

    /**
     * Return return ocr lrg icon.
     */
    public function projectOcrIconLrg()
    {
        $ariaLabel = e(t('Reprocess subject OCR for %s (project %s)', (string) $this->model->title, (string) $this->model->slug));

        return '<a href="'.route('admin.projects.ocr', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.e(t('Reprocess subject OCR')).'" 
            aria-label="'.$ariaLabel.'"
            data-hover="tooltip"        
            data-method="post"
            data-confirm="confirmation"
            data-title="'.e(t('Reprocess subject OCR')).'?" data-content="'.e(t('This action will reprocess all ocr for the Project.')).'">
            <i class="fas fa-redo-alt fa-2x"></i></a>';
    }

    /**
     * Return project link.
     */
    public function titleLink()
    {
        return '<a href="'.route('admin.projects.show', [$this->model]).'">'.e((string) $this->model->title).'</a>';
    }
}
