<?php

namespace Database\Seeders;

use App\Enums\Papel;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Contas de acesso.
     *
     * ⚠️ **Duas realidades diferentes, de propósito.**
     *
     * No ambiente local as credenciais são curtas e óbvias, porque o objetivo é
     * não travar quem está desenvolvendo. **Fora dele, não existe padrão**: o
     * e-mail e a senha têm que vir do `.env`, e o seeder recusa rodar sem eles.
     *
     * Antes havia um padrão único para todo ambiente — o que significa que uma
     * instalação em servidor ficaria com senha conhecida se ninguém lembrasse de
     * definir as variáveis. Padrão fraco que só aparece em produção é o pior
     * tipo: nada avisa, e funciona.
     */
    public function run(): void
    {
        app()->environment('local')
            ? $this->contasDeDesenvolvimento()
            : $this->contaDeAdministracao();
    }

    /** Curtas e fáceis de lembrar. Só existem na máquina de quem desenvolve. */
    private function contasDeDesenvolvimento(): void
    {
        $this->criar('admin@admin.com', 'Administrador', '1234', Papel::Admin);
        $this->criar('teste@teste.com', 'Cliente de Teste', '1234', Papel::Cliente);
    }

    /** @throws RuntimeException quando falta a credencial no ambiente */
    private function contaDeAdministracao(): void
    {
        $email = env('SEED_ADMIN_EMAIL');
        $senha = env('SEED_ADMIN_SENHA');

        if (! $email || ! $senha) {
            throw new RuntimeException(
                'Defina SEED_ADMIN_EMAIL e SEED_ADMIN_SENHA no .env antes de semear fora do ambiente local. '.
                'Não existe senha padrão aqui de propósito.'
            );
        }

        $this->criar($email, env('SEED_ADMIN_NOME', 'Administrador'), $senha, Papel::Admin);
    }

    private function criar(string $email, string $nome, string $senha, Papel $papel): void
    {
        // `firstOrCreate`: semear de novo não pode derrubar a senha de quem já
        // trocou a dela.
        Usuario::firstOrCreate(
            ['email' => $email],
            [
                'nome' => $nome,
                'password' => Hash::make($senha),
                'papel' => $papel,
                'ativo' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
