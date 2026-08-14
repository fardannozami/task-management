<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = [
            'admin@example.com',
            'manager@example.com',
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
        ];

        static $index = 0;

        return [
            'name' => fake()->name(),
            'email' => $names[$index++ % count($names)],
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => match (true) {
                str_contains($names[($index - 1) % count($names)], 'admin') => 'admin',
                str_contains($names[($index - 1) % count($names)], 'manager') => 'manager',
                default => 'user',
            },
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
