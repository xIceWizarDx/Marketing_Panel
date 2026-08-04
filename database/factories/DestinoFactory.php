<?php

namespace Database\Factories;

use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Publicacao;
use Illuminate\Database\Eloquent\Factories\Factory;

class DestinoFactory extends Factory
{
    protected $model = Destino::class;

    public function definition(): array
    {
        return [
            'publicacao_id' => Publicacao::factory(),
            'conta_social_id' => ContaSocial::factory(),
            'status' => StatusDestino::Pendente,
            'tentativas_feitas' => 0,
        ];
    }

    public function noEstado(StatusDestino $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
