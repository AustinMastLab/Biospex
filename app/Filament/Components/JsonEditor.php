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

namespace App\Filament\Components;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;

class JsonEditor extends CodeEditor
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->language(Language::Json);
        $this->columnSpanFull();

        // Validate that the input is valid JSON
        $this->rule(function () {
            return function (string $attribute, mixed $value, \Closure $fail) {
                if (is_string($value) && ! blank($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail("The {$this->getLabel()} must be a valid JSON string.");
                    }
                }
            };
        });

        // Format array data from database for the UI
        $this->formatStateUsing(function ($state) {
            if (is_string($state)) {
                $decoded = json_decode($state, associative: true);

                return json_last_error() === JSON_ERROR_NONE ? json_encode($decoded, JSON_PRETTY_PRINT) : $state;
            }

            return is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state;
        });

        // Handle saving back as an array
        $this->dehydrateStateUsing(function (?string $state) {
            if (blank($state)) {
                return null;
            }
            $decoded = json_decode($state, associative: true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
        });

        // Keep Livewire state in sync
        $this->afterStateUpdated(function (?string $state, $set, $component) {
            $set($component->getStatePath(), $state ? json_decode($state, associative: true) : null);
        });
    }
}
