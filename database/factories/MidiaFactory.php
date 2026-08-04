<?php

namespace Database\Factories;

use App\Enums\TipoMidia;
use App\Models\Midia;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Midia>
 */
class MidiaFactory extends Factory
{
    protected $model = Midia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Dono sempre explícito: a factory roda fora de sessão, e deixar o
            // escopo adivinhar é justamente o que o guardião de isolamento proíbe.
            'usuario_id' => Usuario::factory(),
            'tipo' => TipoMidia::Video,
            'nome_original' => 'corte-'.fake()->numberBetween(1, 999).'.mp4',
            'caminho' => 'midias/'.fake()->uuid().'.mp4',
            'mime_type' => 'video/mp4',
            'tamanho_bytes' => fake()->numberBetween(2_000_000, 90_000_000),
            'duracao_segundos' => fake()->numberBetween(5, 89),
            'largura' => 1080,
            'altura' => 1920,
            'laudo' => null,
        ];
    }

    public function imagem(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoMidia::Imagem,
            'nome_original' => 'foto-'.fake()->numberBetween(1, 999).'.jpg',
            'caminho' => 'midias/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'tamanho_bytes' => fake()->numberBetween(200_000, 6_000_000),
            'duracao_segundos' => null,
            'largura' => 1080,
            'altura' => 1350,
        ]);
    }

    /** Vídeo deitado — recusado pelo perfil canônico (9:16). */
    public function deitado(): static
    {
        return $this->state(fn () => ['largura' => 1920, 'altura' => 1080]);
    }

    public function doUsuario(Usuario $usuario): static
    {
        return $this->state(fn () => ['usuario_id' => $usuario->id]);
    }
}
