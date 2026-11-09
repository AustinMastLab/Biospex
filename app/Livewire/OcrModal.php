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

namespace App\Livewire;

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Livewire component for handling OCR text modal functionality.
 *
 * This component manages the display of OCR text in a modal window,
 * allowing users to view the OCR content for grid cells.
 */
class OcrModal extends Component
{
    /**
     * The OCR text content to be displayed in the modal.
     *
     * @var string
     */
    public $ocrText = '';

    #[On('openOcrModal')]
    /**
     * Opens the OCR modal and sets the content.
     *
     * @param  string|null  $cellContent  The OCR text content to display
     */
    public function openOcrModal(?string $cellContent = null): void
    {
        $this->ocrText = $cellContent;
        $this->dispatch('open-modal', id: 'ocr-modal');
    }

    /**
     * Render the OCR modal component view.
     */
    public function render(): View
    {
        return view('livewire.ocr-modal');
    }
}
