<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{-- viewport-fit=cover libera env(safe-area-inset-*) na barra inferior do celular --}}
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#0f172a">

        <title inertia>{{ config('app.name') }}</title>

        {{-- Aplica o tema salvo ANTES de pintar a tela: sem isso o modo escuro
             pisca branco a cada carregamento. --}}
        <script>
            (function () {
                try {
                    var salva = localStorage.getItem('aparencia') || 'sistema';
                    var escuro = salva === 'escuro'
                        || (salva === 'sistema' && window.matchMedia('(prefers-color-scheme: dark)').matches);

                    document.documentElement.classList.toggle('dark', escuro);
                    document.documentElement.dataset.theme = escuro ? 'dark' : 'light';
                } catch (e) {
                    // localStorage bloqueado (janela anonima) — segue no tema claro.
                }
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
