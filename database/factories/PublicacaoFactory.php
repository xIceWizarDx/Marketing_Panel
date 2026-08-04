<?php

namespace Database\Factories;

use App\Enums\StatusPublicacao;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Models\Usuario;
use App\Services\GrupoService;
use Illuminate\Database\Eloquent\Factories\Factory;

class PublicacaoFactory extends Factory
{
    protected $model = Publicacao::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'grupo_id' => Grupo::factory(),
            'midia_id' => Midia::factory(),
            'titulo' => fake()->sentence(4),
            'legenda' => fake()->sentence(10),
            'hashtags' => ['corte', 'shorts'],
            'status' => StatusPublicacao::Rascunho,
        ];
    }

    /** Publicacao ja disparada — deixou de ser rascunho. */
    public function enviada(): static
    {
        return $this->state(fn () => [
            'enviada_em' => now(),
            'status' => StatusPublicacao::Processando,
        ]);
    }

    public function doUsuario(Usuario $usuario): static
    {
        return $this->state(fn () => [
            'usuario_id' => $usuario->id,
            // Mesmo cuidado da midia: o grupo e o que o dono ja tem.
            'grupo_id' => app(GrupoService::class)->garantirPrincipal($usuario)->id,
            'midia_id' => Midia::factory()->doUsuario($usuario),
        ]);
    }
}
