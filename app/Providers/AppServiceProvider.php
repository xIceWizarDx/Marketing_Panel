<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->apertarOEloquent();
        $this->definirRegraDeSenha();
        $this->apontarLinksDosEmails();
        $this->limitarOContinuarConectado();
    }

    /**
     * "Continuar conectado" vale 30 dias, não os 400 dias do padrão do Laravel.
     *
     * O ativo guardado aqui é o token da rede social do cliente: quem tem a
     * sessão publica no nome dele. 400 dias significa que um aparelho esquecido
     * em qualquer lugar continua sendo porta de entrada por mais de um ano.
     * Com 30 dias, quem usa toda semana nunca é desconectado.
     */
    private function limitarOContinuarConectado(): void
    {
        Auth::guard('web')->setRememberDuration(60 * 24 * 30);
    }

    /**
     * Faz o Eloquent gritar em vez de errar em silencio.
     *
     * Sem isso, atribuir um campo fora do $fillable e simplesmente IGNORADO, e a
     * pessoa descobre que o dado nao salvou semanas depois. Ligado em todo
     * ambiente — inclusive producao — de proposito: e melhor um erro visivel do
     * que um dado perdido.
     */
    private function apertarOEloquent(): void
    {
        Model::shouldBeStrict();
        Model::preventSilentlyDiscardingAttributes();
    }

    /**
     * Senha minima do projeto: 12 caracteres e nao pode ter aparecido em
     * vazamento conhecido (checagem no Have I Been Pwned, so em producao pra
     * nao depender de rede em teste).
     */
    private function definirRegraDeSenha(): void
    {
        Password::defaults(function () {
            $regra = Password::min(12);

            return $this->app->isProduction()
                ? $regra->uncompromised()
                : $regra;
        });
    }

    /**
     * O Laravel monta os links dos emails com nomes de rota em ingles
     * (`password.reset`, `verification.verify`). As nossas se chamam
     * `senha.redefinir` e `verificacao.confirmar` — o desvio fica aqui, em um
     * lugar so, coberto pelos testes de acesso.
     */
    private function apontarLinksDosEmails(): void
    {
        ResetPassword::createUrlUsing(
            fn ($usuario, string $token) => route('senha.redefinir', [
                'token' => $token,
                'email' => $usuario->getEmailForPasswordReset(),
            ])
        );

        VerifyEmail::createUrlUsing(
            fn ($usuario) => URL::temporarySignedRoute(
                'verificacao.confirmar',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $usuario->getKey(),
                    'hash' => sha1($usuario->getEmailForVerification()),
                ]
            )
        );
    }
}
