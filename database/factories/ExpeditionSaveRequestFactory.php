<?php

namespace Database\Factories;

use App\Models\Expedition;
use App\Models\ExpeditionSaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpeditionSaveRequest>
 */
class ExpeditionSaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expedition_id' => Expedition::factory(),
            'user_id' => User::factory(),
            'operation' => 'create',
            'subject_ids' => [],
            'revision' => 1,
            'status' => 'pending',
            'failed_at' => null,
        ];
    }
}
