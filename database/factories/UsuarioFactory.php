<?php

namespace Database\Factories;

use App\Enums\Papel;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    /** Hash calculado uma vez so — bcrypt e lento de proposito. */
    protected static ?string $senha;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$senha ??= Hash::make('senha-de-teste'),
            'papel' => Papel::Cliente,
            'ativo' => true,
            'fuso_horario' => 'America/Sao_Paulo',
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['papel' => Papel::Admin]);
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['ativo' => false]);
    }

    public function semEmailVerificado(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
