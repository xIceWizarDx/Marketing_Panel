<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/** Marca o email como confirmado (chegou pelo link assinado do email). */
class VerificarEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $destino = route($request->user()->papel->rotaInicial()).'?verificado=1';

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($destino);
        }

        if ($request->user()->markEmailAsVerified()) {
            /** @var MustVerifyEmail $usuario */
            $usuario = $request->user();

            event(new Verified($usuario));
        }

        return redirect()->intended($destino);
    }
}
