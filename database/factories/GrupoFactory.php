<?php

namespace Database\Factories;

use App\Models\Grupo;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Grupo> */
class GrupoFactory extends Factory
{
    protected $model = Grupo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'nome' => fake()->randomElement(['Notícias', 'Novelas', 'Esportes', 'Bastidores']),
        ];
    }

    public function doUsuario(Usuario $usuario): static
    {
        return $this->state(fn () => ['usuario_id' => $usuario->id]);
    }
}
