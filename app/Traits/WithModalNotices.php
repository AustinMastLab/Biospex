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

namespace App\Traits;

/**
 * For a Livewire form inside the global modal (common/modal.blade.php): show a notice, and close the modal when done.
 */
trait WithModalNotices
{
    /**
     * Show a success notice and close the modal.
     */
    protected function finish(string $message): void
    {
        $this->notice($message, 'success');
        $this->js("closeModal('global-modal')");
    }

    /**
     * Show a notice (window.notify in resources/js/common.js), keeping the modal open.
     */
    protected function notice(string $message, string $type = 'warning'): void
    {
        $icon = $type === 'success' ? 'check-circle' : 'exclamation-circle';
        $this->js('window.notify('.json_encode($icon).', '.json_encode($message).', '.json_encode($type).')');
    }
}
