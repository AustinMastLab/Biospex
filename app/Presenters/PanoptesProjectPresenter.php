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

/**
 * Class PanoptesProjectPresenter
 */
class PanoptesProjectPresenter extends Presenter
{
    /**
     * Return icon.
     *
     * @return string
     */
    public function url()
    {
        $url = $this->classifyReplace();

        return $this->model->panoptes_workflow_id === null ? '#' :
            '<a href="'.$url.'" data-hover="tooltip" title="'.t('Participate').'" aria-label="'.t('Participate').'" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-keyboard" aria-hidden="true"></i></a>';
    }

    public function projectIcon()
    {
        $url = $this->projectReplace();

        return $this->model->panoptes_workflow_id === null ? '#' :
            '<a href="'.$url.'" data-hover="tooltip" title="'.t('Participate').'" aria-label="'.t('Participate').'" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-keyboard" aria-hidden="true"></i></a>';
    }

    public function projectIconLrg()
    {
        $url = $this->projectReplace();

        return $this->model->panoptes_workflow_id === null ? '#' :
            '<a href="'.$url.'" data-hover="tooltip" title="'.t('Participate').'" aria-label="'.t('Participate').'" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-keyboard fa-2x" aria-hidden="true"></i></a>';
    }

    public function projectLink()
    {
        $url = $this->projectReplace();

        return $this->model->panoptes_workflow_id === null ? '#' :
            '<a href="'.$url.'" title="'.t('Participate').'" target="_blank">'.t('Click here to participate').'</a>';
    }

    public function urlLrg()
    {
        $url = $this->classifyReplace();

        return $this->model->panoptes_workflow_id === null ? '#' :
            '<a href="'.$url.'" data-hover="tooltip" title="'.t('Participate').'" aria-label="'.t('Participate').'" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-keyboard fa-2x" aria-hidden="true"></i></a>';
    }

    /**
     * Return participation url.
     *
     * @return mixed
     */
    private function classifyReplace()
    {
        $urlString = str_replace('PROJECT_SLUG', $this->model->slug, config('zooniverse.participate_url'));

        return str_replace('WORKFLOW_ID', $this->model->panoptes_workflow_id, $urlString);
    }

    /**
     * Return project url.
     *
     * @return mixed
     */
    private function projectReplace()
    {
        return str_replace('PROJECT_SLUG', $this->model->slug, config('zooniverse.project_url'));
    }
}
