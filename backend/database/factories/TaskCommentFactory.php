<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskComment>
 */
class TaskCommentFactory extends Factory
{
    public function definition(): array
    {
        $comments = [
            'Please review the updated requirements.',
            'I have pushed the latest changes to the branch.',
            'Can we schedule a quick sync about this?',
            'The deadline has been moved to next Friday.',
            'I found a potential issue in the implementation.',
            'Looks good to me, approved.',
            'We need more clarification on this point.',
            'Added some unit tests for the new feature.',
            'This is blocking the frontend work.',
            'Merged the PR, please verify.',
        ];

        return [
            'task_id' => Task::inRandomOrder()->value('id'),
            'user_id' => User::inRandomOrder()->value('id'),
            'comment' => fake()->unique()->randomElement($comments),
        ];
    }
}
