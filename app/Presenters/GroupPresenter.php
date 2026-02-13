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
 * Class GroupPresenter
 */
class GroupPresenter extends Presenter
{
    public function groupProjectIcon()
    {
        // <i class="fas fa-users"></i>
        $route = route('admin.groups.show', [$this->model]);

        $ariaLabel = e(t('View group: %s (group %s)', (string) $this->model->title, (string) $this->model->uuid ?? (string) $this->model->id));

        return '<a href="'.$route.'" 
            data-hover="tooltip" 
            title="'.e(t('View group')).'"
            aria-label="'.$ariaLabel.'">
            <i class="fas fa-users" aria-hidden="true"></i></a>';
    }

    public function groupProjectIconLrg()
    {
        // <i class="fas fa-users"></i>
        $route = route('admin.groups.show', [$this->model]);

        $ariaLabel = e(t('View group: %s (group %s)', (string) $this->model->title, (string) $this->model->uuid ?? (string) $this->model->id));

        return '<a href="'.$route.'" 
            data-hover="tooltip" 
            title="'.e(t('View group')).'"
            aria-label="'.$ariaLabel.'">
            <i class="fas fa-users fa-2x" aria-hidden="true"></i></a>';
    }

    public function groupShowIcon()
    {
        $ariaLabel = e(t('View group: %s (group %s)', (string) $this->model->title, (string) $this->model->uuid ?? (string) $this->model->id));

        return '<a href="'.route('admin.groups.show', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('View group')).'"
            aria-label="'.$ariaLabel.'">
            <i class="fas fa-eye" aria-hidden="true"></i></a>';
    }

    public function groupEditIcon()
    {
        $ariaLabel = e(t('Edit group: %s (group %s)', (string) $this->model->title, (string) $this->model->uuid ?? (string) $this->model->id));

        return '<a href="'.route('admin.groups.edit', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('Edit group')).'"
            aria-label="'.$ariaLabel.'">
            <i class="fas fa-edit" aria-hidden="true"></i></a>';
    }

    public function groupEditIconLrg()
    {
        $ariaLabel = e(t('Edit group: %s (group %s)', (string) $this->model->title, (string) $this->model->uuid ?? (string) $this->model->id));

        return '<a href="'.route('admin.groups.edit', [$this->model]).'" 
            data-hover="tooltip" 
            title="'.e(t('Edit group')).'"
            aria-label="'.$ariaLabel.'">
            <i class="fas fa-edit fa-2x" aria-hidden="true"></i></a>';
    }

    public function groupDeleteIcon()
    {
        $ariaLabel = e(t('Delete group: %s (group %s)', (string) $this->model->title, (string) $this->model->uuid ?? (string) $this->model->id));

        return '<a href="'.route('admin.groups.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.e(t('Delete group')).'"
            aria-label="'.$ariaLabel.'"
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete group')).'?" data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt" aria-hidden="true"></i></a>';
    }

    public function groupDeleteIconLrg()
    {
        $ariaLabel = e(t('Delete group: %s (group %s)', (string) $this->model->title, (string) $this->model->uuid ?? (string) $this->model->id));

        return '<a href="'.route('admin.groups.destroy', [
            $this->model,
        ]).'" class="prevent-default"
            title="'.e(t('Delete group')).'"
            aria-label="'.$ariaLabel.'"
            data-hover="tooltip"        
            data-method="delete"
            data-confirm="confirmation"
            data-title="'.e(t('Delete group')).'?" data-content="'.e(t('This will permanently delete the record and all associated records.')).'">
            <i class="fas fa-trash-alt fa-2x" aria-hidden="true"></i></a>';
    }

    public function groupInviteIcon()
    {
        $route = route('admin.invites.create', [$this->model]);

        $ariaLabel = e(t('Invite users to group: %s (group %s)', (string) $this->model->title, (string) $this->model->uuid ?? (string) $this->model->id));

        return '<a href="#" class="prevent-default" data-toggle="modal" data-url="'.$route.'" 
                    data-target="#global-modal" data-size="modal-lg" data-dismiss="modal" data-toggle="modal"
                    data-title="'.e(t('Invite users to group')).'"
                    data-hover="tooltip" title="'.e(t('Invite users to group')).'"
                    aria-label="'.$ariaLabel.'">
                    <i class="fas fa-user-plus" aria-hidden="true"></i></a>';
    }

    public function groupInviteIconLrg()
    {
        $route = route('admin.invites.create', [$this->model]);

        $ariaLabel = e(t('Invite users to group: %s (group %s)', (string) $this->model->title, (string) $this->model->uuid ?? (string) $this->model->id));

        return '<a href="#" class="prevent-default" data-toggle="modal" data-url="'.$route.'" 
                    data-target="#global-modal" data-size="modal-lg" data-dismiss="modal" data-toggle="modal"
                    data-title="'.e(t('Invite users to group')).'"
                    data-hover="tooltip" title="'.e(t('Invite users to group')).'"
                    aria-label="'.$ariaLabel.'">
                    <i class="fas fa-user-plus fa-2x" aria-hidden="true"></i></a>';
    }
}
