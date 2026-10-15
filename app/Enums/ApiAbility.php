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

namespace App\Enums;

/**
 * The Sanctum abilities a v1 API token can carry.
 *
 * The v1 route middleware and the API Tokens admin page both read this list, so add new abilities here.
 */
enum ApiAbility: string
{
    /** Older ability; only the WeDigBio dashboard route accepts it. */
    case PanoptesPusherRead = 'panoptes-pusher:read';

    /** Older ability; only the WeDigBio dashboard route accepts it. */
    case PanoptesPusherCreate = 'panoptes-pusher:create';

    /** Read the WeDigBio dashboard endpoint. */
    case WeDigBioDashboardRead = 'wedigbio-dashboard:read';

    /** Older ability; only the WeDigBio dashboard route accepts it. */
    case LambdaUpdate = 'lambda:update';

    /** Read public projects and expeditions. */
    case PublicRead = 'public:read';

    /**
     * Build the Sanctum `ability:` middleware for the given abilities, or for every ability when none are given.
     */
    public static function middleware(self ...$abilities): string
    {
        $abilities = $abilities ?: self::cases();

        return 'ability:'.implode(',', array_map(fn (self $ability): string => $ability->value, $abilities));
    }

    /**
     * Checkbox options for the admin page, keyed by ability value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $ability): array => [$ability->value => $ability->value])
            ->all();
    }

    /**
     * What a token with this ability can reach.
     */
    public function description(): string
    {
        return match ($this) {
            self::WeDigBioDashboardRead => 'WeDigBio dashboard',
            self::PanoptesPusherRead, self::PanoptesPusherCreate, self::LambdaUpdate => 'WeDigBio dashboard (older ability; prefer wedigbio-dashboard:read)',
            self::PublicRead => 'Public projects and expeditions',
        };
    }

    /**
     * Checkbox descriptions for the admin page, keyed by ability value.
     *
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $ability): array => [$ability->value => $ability->description()])
            ->all();
    }
}
