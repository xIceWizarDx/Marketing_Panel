<?php

namespace App\Http\Requests\Acesso;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EntrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Confere as credenciais.
     *
     * @throws ValidationException
     */
    public function autenticar(): void
    {
        $this->garantirQueNaoEstaBloqueado();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('lembrar'))) {
            RateLimiter::hit($this->chaveDeTentativas());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Conta desativada nao entra — e a mesma mensagem de credencial errada,
        // pra nao revelar a quem tenta invadir que o email existe.
        if (! $this->user()->ativo) {
            Auth::guard('web')->logout();

            RateLimiter::hit($this->chaveDeTentativas());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->chaveDeTentativas());
    }

    /**
     * Trava tentativas repetidas de adivinhar a senha (forca bruta).
     *
     * @throws ValidationException
     */
    public function garantirQueNaoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->chaveDeTentativas(), 5)) {
            return;
        }

        event(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->chaveDeTentativas());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $segundos,
                'minutes' => ceil($segundos / 60),
            ]),
        ]);
    }

    /** Contador por email + IP: uma pessoa nao trava a conta da outra. */
    public function chaveDeTentativas(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
