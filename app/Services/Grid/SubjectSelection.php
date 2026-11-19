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

namespace App\Services\Grid;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The subjects chosen in the subject grid while an expedition form is open, kept on the server under a token.
 *
 * An expedition can hold thousands of subjects, too many to send back and forth with every grid request, so the
 * grid keeps the ids here and the form posts only the token. Each token belongs to the user who started it.
 */
class SubjectSelection
{
    private const int TTL_SECONDS = 43200;

    /**
     * Start a selection for the user, optionally with subjects already chosen, and return its token.
     *
     * @param  array<int, string>  $subjectIds
     */
    public function start(int $userId, array $subjectIds = []): string
    {
        $token = (string) Str::uuid();
        $this->put($userId, $token, $subjectIds);

        return $token;
    }

    /**
     * The chosen subject ids, or an empty list for an unknown or expired token.
     *
     * @return array<int, string>
     */
    public function ids(int $userId, string $token): array
    {
        return Cache::get($this->key($userId, $token), []);
    }

    /**
     * Whether the token is the user's and has not expired.
     */
    public function exists(int $userId, string $token): bool
    {
        return Cache::has($this->key($userId, $token));
    }

    public function count(int $userId, string $token): int
    {
        return count($this->ids($userId, $token));
    }

    /**
     * Add or remove subjects and return how many are chosen.
     *
     * @param  array<int, string>  $subjectIds
     */
    public function change(int $userId, string $token, array $subjectIds, bool $chosen): int
    {
        $current = $this->ids($userId, $token);
        $subjectIds = array_map('strval', $subjectIds);

        $ids = $chosen
            ? array_values(array_unique([...$current, ...$subjectIds]))
            : array_values(array_diff($current, $subjectIds));

        $this->put($userId, $token, $ids);

        return count($ids);
    }

    /**
     * Remove every chosen subject.
     */
    public function clear(int $userId, string $token): void
    {
        $this->put($userId, $token, []);
    }

    /**
     * @param  array<int, string>  $subjectIds
     */
    private function put(int $userId, string $token, array $subjectIds): void
    {
        Cache::put($this->key($userId, $token), array_values(array_map('strval', $subjectIds)), self::TTL_SECONDS);
    }

    private function key(int $userId, string $token): string
    {
        return "subject-selection:{$userId}:{$token}";
    }
}
