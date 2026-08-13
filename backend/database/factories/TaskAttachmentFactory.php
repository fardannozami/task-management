<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    public function definition(): array
    {
        $fileNames = [
            'requirements.pdf',
            'design_mockup.fig',
            'api_specs.json',
            'database_schema.sql',
            'user_flow_diagram.png',
            'sprint_plan.xlsx',
            'meeting_notes.docx',
            'architecture_diagram.svg',
            'test_plan.md',
            'deployment_guide.pdf',
            'changelog.md',
            'wireframe_sketch.png',
            'api_collection.json',
            'performance_report.xlsx',
            'security_audit.pdf',
        ];

        $mimeTypes = [
            'application/pdf',
            'image/png',
            'application/json',
            'text/plain',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'image/svg+xml',
            'text/markdown',
        ];

        return [
            'task_id' => Task::inRandomOrder()->value('id'),
            'file_name' => fake()->unique()->randomElement($fileNames),
            'file_path' => fake()->filePath(),
            'file_size' => fake()->numberBetween(1024, 10485760),
            'mime_type' => fake()->randomElement($mimeTypes),
            'uploaded_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
