<?php

namespace Tests;

use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * ⚠️ Zera o estado estatico entre um caso e outro.
     *
     * Sem isto o teste seguinte herda o dono e o grupo do anterior — e os
     * guardioes de isolamento passam por heranca, provando o contrario do que
     * eles existem para provar.
     */
    protected function setUp(): void
    {
        parent::setUp();

        ContextoDoUsuario::limpar();
        GrupoCorrente::limpar();
    }
}
