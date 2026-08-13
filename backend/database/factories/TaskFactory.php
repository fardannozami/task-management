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
    protected static ?string $inProgressTitle;
    protected static ?string $completedTitle;
    protected static ?string $pendingTitle;
    protected static array $usedTitles = [];

    public function definition(): array
    {
        $titles = [
            'Design user authentication flow',
            'Implement task CRUD endpoints',
            'Set up database migrations',
            'Create task dashboard UI',
            'Write unit tests for services',
            'Integrate email notifications',
            'Optimize database queries',
            'Implement file upload feature',
            'Add task filtering and sorting',
            'Create REST API documentation',
            'Fix login session timeout bug',
            'Refactor controller logic',
            'Implement task search feature',
            'Add role-based access control',
            'Create task export functionality',
            'Implement real-time notifications',
            'Add task priority levels',
            'Create task comment threads',
            'Implement task assignment logic',
            'Add task due date reminders',
            'Create task history log',
            'Implement task bulk actions',
            'Add task category labels',
            'Create task template feature',
            'Implement task progress tracking',
            'Add task dependency support',
            'Create task recurrence feature',
            'Implement task sharing',
            'Add task rating system',
            'Create task analytics dashboard',
        ];

        $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        $status = fake()->randomElement($statuses);
        $priority = fake()->randomElement($priorities);

        if ($status === 'in_progress') {
            $title = fake()->unique()->randomElement(array_filter($titles, fn($t) => !in_array($t, self::$usedTitles)));
            self::$usedTitles[] = $title;
            return [
                'title' => $title,
                'description' => fake()->paragraph(),
                'status' => $status,
                'priority' => $priority,
                'assigned_user_id' => User::inRandomOrder()->value('id'),
                'created_by' => User::inRandomOrder()->value('id'),
                'due_date' => fake()->dateTimeBetween('+1 week', '+2 months'),
            ];
        }

        if ($status === 'completed') {
            $title = fake()->unique()->randomElement(array_filter($titles, fn($t) => !in_array($t, self::$usedTitles)));
            self::$usedTitles[] = $title;
            return [
                'title' => $title,
                'description' => fake()->paragraph(),
                'status' => $status,
                'priority' => $priority,
                'assigned_user_id' => User::inRandomOrder()->value('id'),
                'created_by' => User::inRandomOrder()->value('id'),
                'due_date' => fake()->dateTimeBetween('-1 month', '-1 week'),
            ];
        }

        $title = fake()->unique()->randomElement(array_filter($titles, fn($t) => !in_array($t, self::$usedTitles)));
        self::$usedTitles[] = $title;
        return [
            'title' => $title,
            'description' => fake()->paragraph(),
            'status' => $status,
            'priority' => $priority,
            'assigned_user_id' => User::inRandomOrder()->value('id'),
            'created_by' => User::inRandomOrder()->value('id'),
            'due_date' => fake()->dateTimeBetween('+1 week', '+2 months'),
        ];
    }
}
