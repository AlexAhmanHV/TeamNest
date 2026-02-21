<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(TaskStatus::values()),
            'priority' => fake()->randomElement(TaskPriority::values()),
            'due_date' => fake()->optional()->dateTimeBetween('-10 days', '+10 days'),
            'assigned_to_user_id' => null,
            'created_by_user_id' => User::factory(),
        ];
    }
}
