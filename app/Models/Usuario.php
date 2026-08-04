<?php

namespace App\Models;

use App\Enums\Papel;
use Carbon\CarbonInterface;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, HasUlids, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'email',
        'password',
        'google_id',
        'papel',
        'ativo',
        'fuso_horario',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'papel' => Papel::class,
            'ativo' => 'boolean',
        ];
    }

    /**
     * Colunas que o HasUlids preenche sozinho na criacao.
     * A chave primaria continua sendo o `id` auto-incremento — o ULID e so
     * a face publica.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** As rotas expoem o ULID; o id sequencial nunca sai do servidor (0.M). */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function ehAdmin(): bool
    {
        return $this->papel === Papel::Admin;
    }

    /**
     * Converte um instante do banco (UTC) para o fuso em que esta pessoa le.
     *
     * Ponto unico da conversao: nenhuma tela formata data por conta propria —
     * senao a mesma publicacao aparece com dois horarios diferentes em telas
     * diferentes, e ninguem descobre qual esta certo.
     */
    public function noSeuFuso(?CarbonInterface $instante): ?CarbonInterface
    {
        return $instante?->copy()->setTimezone($this->fuso_horario);
    }
}
