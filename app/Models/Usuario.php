<?php

namespace App\Models;

use App\Enums\Papel;
use App\Services\GrupoService;
use Carbon\CarbonInterface;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * ⭐ Todo usuário nasce com um grupo (DEC-69).
     *
     * ⚠️ Mora no model, e não no controller de cadastro, porque há **três**
     * portas por onde um usuário nasce: o cadastro, o seeder e a factory dos
     * testes. Espalhar por três lugares garantiria esquecer um — e o esquecido
     * viraria um cliente sem grupo, que é um beco sem saída silencioso: as telas
     * não dão erro, elas mentem.
     *
     * O admin também ganha um. É uma linha, e custa menos que um `if` de papel
     * mais o defeito do dia em que um papel mudar.
     */
    protected static function booted(): void
    {
        static::created(fn (self $usuario) => app(GrupoService::class)->garantirPrincipal($usuario));
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

    /**
     * @return HasMany<Grupo, $this>
     *
     * ⚠️ Sem escopo de dono: e a relacao do PROPRIO usuario, e o comando de
     * migracao a usa por fora da sessao.
     */
    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
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
