<?php

namespace Database\Factories;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskChecklist>
 */
class TaskChecklistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'image_path' => null,
            'due_date' => today()->addDays(10),
            'status' => 'todo',
        ];
    }

    /** 期限切れ（期限を過ぎていて未完了） */
    public function overdue(): static
    {
        return $this->state(fn () => ['due_date' => today()->subDay(), 'status' => 'todo']);
    }

    /** 期限間近（期限まで3日以内で未完了） */
    public function dueSoon(): static
    {
        return $this->state(fn () => ['due_date' => today()->addDays(TaskChecklist::DUE_SOON_DAYS), 'status' => 'doing']);
    }

    /** 完了 */
    public function done(): static
    {
        return $this->state(fn () => ['status' => 'done']);
    }
}
