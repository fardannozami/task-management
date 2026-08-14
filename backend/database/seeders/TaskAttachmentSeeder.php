<?php

namespace Database\Seeders;

use App\Models\TaskAttachment;
use Illuminate\Database\Seeder;

class TaskAttachmentSeeder extends Seeder
{
    public function run(): void
    {
        TaskAttachment::factory()->count(10)->create();
    }
}
