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
 * Class UserPresenter
 */
class UserPresenter extends Presenter
{
    /**
     * Return full name or email of user.
     *
     * @return mixed|string
     */
    public function fullNameOrEmail()
    {
        $fullName = $this->model->getFilamentName();

        return empty($fullName) ? $this->model->email : $fullName;
    }

    /**
     * Return email icon.
     *
     * @return string
     */
    public function email()
    {
        return $this->model->email === null ? '' :
            (function () {
                $email = (string) $this->model->email;
                $name = (string) ($this->model->getFilamentName() ?: $email);
                $aria = e(t('Contact user: %s (%s)', $name, $email));

                return '<a href="mailto:'.$email.'" 
            data-hover="tooltip"
            title="'.e(t('Contact user')).'"
            aria-label="'.$aria.'">
            <i class="far fa-envelope" aria-hidden="true"></i></a>';
            })();
    }
}
