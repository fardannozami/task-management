<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected static int $counter = 0;

    public function definition(): array
    {
        $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        $status = fake()->randomElement($statuses);
        $priority = fake()->randomElement($priorities);
        $user = User::query()->inRandomOrder()->first() ?? User::factory()->create();

        return [
            'title' => 'Task '.(++self::$counter).' - '.fake()->word(),
            'description' => fake()->paragraph(),
            'status' => $status,
            'priority' => $priority,
            'assigned_user_id' => $user->id,
            'created_by' => $user->id,
            'due_date' => fake()->dateTimeBetween('+1 week', '+2 months'),
        ];
    }
}
