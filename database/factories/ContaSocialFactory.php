<?php

namespace Database\Factories;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Models\Usuario;
use App\Services\GrupoService;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContaSocialFactory extends Factory
{
    protected $model = ContaSocial::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'grupo_id' => Grupo::factory(),
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

    /**
     * ⚠️ Cai no grupo que o dono JA TEM, nao num grupo novo.
     *
     * Duas coisas dependem disso. Grupo novo a cada conta apontaria contas do
     * mesmo cliente para grupos diferentes, e a trava de envio recusaria uma
     * publicacao que na vida real e trivial. E grupo de outro dono faria o
     * teste de isolamento passar dando a impressao de que a trava funciona.
     */
    public function doUsuario(Usuario $usuario): static
    {
        return $this->state(fn () => [
            'usuario_id' => $usuario->id,
            'grupo_id' => app(GrupoService::class)->garantirPrincipal($usuario)->id,
        ]);
    }

    public function doGrupo(Grupo $grupo): static
    {
        return $this->state(fn () => [
            'usuario_id' => $grupo->usuario_id,
            'grupo_id' => $grupo->id,
        ]);
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
