<?php

namespace Database\Factories;

use App\Models\TaskChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 業務の Factory（docs/02_database-design/migration-plan.md「3. 初期データ」）
 * 期限の色分け（F-13）を確認できるよう、overdue() / dueSoon() / done() の状態を用意する
 *
 * @extends Factory<TaskChecklist>
 */
class TaskChecklistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'due_date' => today()->addDays(fake()->numberBetween(TaskChecklist::DUE_SOON_DAYS + 1, 30)),
            'status' => fake()->randomElement(['todo', 'doing']),
        ];
    }

    /** 期限切れ：期限を過ぎていて未完了 */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => today()->subDays(fake()->numberBetween(1, 10)),
            'status' => fake()->randomElement(['todo', 'doing']),
        ]);
    }

    /** 期限間近：期限まで3日以内で未完了 */
    public function dueSoon(): static
    {
        return $this->state(fn () => [
            'due_date' => today()->addDays(fake()->numberBetween(0, TaskChecklist::DUE_SOON_DAYS)),
            'status' => fake()->randomElement(['todo', 'doing']),
        ]);
    }

    /** 完了 */
    public function done(): static
    {
        return $this->state(fn () => [
            'status' => 'done',
        ]);
    }
}
