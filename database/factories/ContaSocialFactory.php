<?php

namespace Database\Factories;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContaSocialFactory extends Factory
{
    protected $model = ContaSocial::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'plataforma' => Plataforma::Bluesky,
            'identificador_externo' => 'did:plc:'.fake()->regexify('[a-z0-9]{16}'),
            'nome_exibicao' => fake()->userName().'.bsky.social',
            'status' => StatusConta::Ativa,
        ];
    }

    public function daPlataforma(Plataforma $plataforma): static
    {
        return $this->state(fn () => ['plataforma' => $plataforma]);
    }

    public function doUsuario(Usuario $usuario): static
    {
        return $this->state(fn () => ['usuario_id' => $usuario->id]);
    }

    public function expirada(): static
    {
        return $this->state(fn () => ['status' => StatusConta::Expirada]);
    }

    public function comCredencial(string $token = 'token-de-teste'): static
    {
        return $this->afterCreating(fn (ContaSocial $conta) => $conta->credencial()->create([
            'access_token' => $token,
            'refresh_token' => 'refresh-de-teste',
            'expira_em' => now()->addDays(30),
        ]));
    }
}
