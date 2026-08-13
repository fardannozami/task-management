<?php

namespace Database\Seeders;

use App\Models\TaskComment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskCommentSeeder extends Seeder
{
    public function run(): void
    {
        TaskComment::factory()->count(10)->create();
    }
}
