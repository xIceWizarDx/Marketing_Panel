<?php

namespace App\Console\Commands;

use App\Enums\Plataforma;
use App\Publicadores\RegistroDePublicadores;
use App\Support\Midia\EspecificacaoDaRede;
use Illuminate\Console\Command;

/**
 * ⭐ A tabela de estado das redes, **gerada do código**.
 *
 * ⛔ Ela existia escrita à mão em quatro lugares — o índice de
 * `planos-de-redes/`, o topo de cada plano e o `CLAUDE.md` — e **as quatro
 * envelheceram**. Uma dizia que o YouTube faltava credencial depois de ele já
 * estar publicando; outra listava o Pinterest como "em estudo" com o publicador
 * pronto; a terceira esquecia o Threads.
 *
 * ⚠️ **Tabela escrita à mão sobre estado que muda é dívida com juros:** cada
 * rede nova exige lembrar de quatro arquivos, e o dia em que alguém esquecer é
 * o dia em que a documentação começa a mentir. Aqui a resposta vem de quem
 * sabe — o registro de publicadores, o enum e a especificação — e não tem como
 * divergir.
 */
class SituacaoDasRedes extends Command
{
    protected $signature = 'redes:situacao {--md : Sai em Markdown, para colar na documentação}';

    protected $description = 'Mostra, a partir do código, em que pé está cada rede';

    public function __construct(
        private readonly RegistroDePublicadores $publicadores,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $linhas = array_map($this->linhaDe(...), Plataforma::cases());

        if ($this->option('md')) {
            $this->line('| Rede | Publicador | Conecta hoje | Regras de mídia | Situação |');
            $this->line('|---|---|---|---|---|');

            foreach ($linhas as $l) {
                $this->line('| '.implode(' | ', $l).' |');
            }

            return self::SUCCESS;
        }

        $this->table(['Rede', 'Publicador', 'Conecta hoje', 'Regras de mídia', 'Situação'], $linhas);

        /*
         * ⚠️ O resumo que responde a pergunta que se faz de verdade: "quantas
         * dá para usar agora?". Contar publicador escrito daria um número maior
         * e mais bonito — e mentiroso, porque publicador sem credencial não
         * publica nada.
         */
        $conectaveis = count($this->publicadores->plataformasConectaveis());
        $escritos = count($this->publicadores->plataformasDisponiveis());

        $this->newLine();
        $this->info("{$escritos} rede(s) com publicador escrito; {$conectaveis} conectável(is) neste servidor agora.");

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function linhaDe(Plataforma $rede): array
    {
        $temPublicador = $this->publicadores->disponivel($rede);

        return [
            $rede->rotulo(),
            $temPublicador ? 'sim' : '—',
            /*
             * ⭐ A coluna que mais importa, e a que mais divergia do papel:
             * publicador escrito não quer dizer botão na tela. Sem credencial
             * configurada — ou sem endereço público, no caso do Threads — a
             * rede não conecta, e a tela já sabe disso.
             */
            $this->publicadores->podeConectar($rede) ? 'sim' : ($temPublicador ? 'falta configurar' : '—'),
            $rede->temEspecificacao() ? $this->limites($rede) : '—',
            $rede->situacao()->rotulo(),
        ];
    }

    private function limites(Plataforma $rede): string
    {
        $spec = EspecificacaoDaRede::de($rede);
        $mb = (int) round($spec->tamanhoMaximoBytes / 1024 / 1024);

        return "{$spec->duracaoMaxima}s · {$mb} MB";
    }
}
