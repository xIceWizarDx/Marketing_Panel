# O produto

Painel que publica vídeo curto vertical (9:16) e imagem em várias redes sociais de uma vez —
e **prova que publicou**, relendo o post na rede e guardando o link.

## ⛔ O que ele NÃO é: um lugar para guardar arquivo

Isto é um **caminho de publicação com prova**. O vídeo existe pelo tempo do envio: assim que o
último destino termina, o arquivo **sai do disco na hora** — sem carência, sem prazo (DEC-59).
Fica o registro inteiro: miniatura, laudo, links e prova.

Por quê: o cliente já tem o arquivo, ninguém aqui compra armazenamento, e assumir custo aberto de
disco por estimativa própria é a conta que só aparece na fatura. Revisitar só com **VPS real +
cliente pagante real pedindo** — aí é demanda, não suposição.

Na prática isso significa que **enviar e publicar são o mesmo gesto** (não existe tela de mídias),
que **o compositor não sugere arquivo nenhum**, e que republicar em outra rede leva o **texto**
pronto e pede o vídeo de novo. Reenviar o mesmo arquivo o devolve ao mesmo registro, com o
histórico — a assinatura do conteúdo reconhece (DEC-58).

> ⚠️ **O nome comercial ainda não está decidido.** Por isso ele não aparece em lugar nenhum do
> código nem da documentação (regra **0.N**). O nome exibido vem de `APP_NAME` no `.env`.
> **Este arquivo é o único lugar que registra os caminhos reais** — se algo for renomeado,
> é aqui que se corrige.

## Caminhos reais

| O quê | Onde |
|---|---|
| Projeto | `c:\xampp\htdocs\MarketingPanel` |
| Repositório | `github.com/xIceWizarDx/Marketing_Panel` |
| Casca antiga (só referência de paleta) | `c:\xampp\htdocs\Marketing-Panel-old` |

## ⚠️ Dependências do sistema (não vêm com `composer install`)

**FFmpeg** — dois programas do sistema operacional, instalados **fora** do projeto:

| | O que faz | Sem ele |
|---|---|---|
| `ffprobe` | **Lê** o vídeo e diz o que tem dentro: duração, resolução, proporção, codec, áudio, tamanho | O laudo de mídia não funciona — sobraria adivinhar pela extensão do arquivo, e extensão mente |
| `ffmpeg` | **Mexe** no vídeo: recodifica o áudio quando a rede exige | Vídeo fora da spec não tem conserto: só resta recusar |

O laudo é o **diferencial nº 1** do produto: dizer **antes** de agendar o que vai acontecer com
o arquivo, em vez de o cliente descobrir que o vídeo foi degradado depois de publicado.

**Precisa estar instalado na sua máquina E no servidor — separadamente.** São ~100 MB por
executável, não entram no repositório.

### O que isso exige da hospedagem

**Qualquer VPS serve** — com acesso root, `apt install ffmpeg` resolve. O que **não** serve é
hospedagem **compartilhada/gerenciada**, que não deixa instalar pacote do sistema.

🔴 **A pegadinha acontece mesmo em VPS:** painéis de gerenciamento (aaPanel, cPanel, Plesk)
sobem o PHP com `proc_open`, `exec` e `shell_exec` dentro de `disable_functions`, por padrão.
O PHP precisa de **`proc_open`** para chamar o FFmpeg — sem ele nada funciona, e a mensagem de
erro **não menciona FFmpeg**, fala de função desabilitada.
**Solução:** remover `proc_open` do `disable_functions` no `php.ini` (as outras podem continuar
desligadas). Conferir com:

```bash
php -r 'echo function_exists("proc_open") ? "ok" : "BLOQUEADO", PHP_EOL;'
```

**Sobre o porte da máquina:** o `ffprobe` só lê os primeiros bytes do arquivo — é instantâneo e
não pesa. O `ffmpeg` só é acionado para **recodificar áudio** (o vídeo passa intacto, DEC-33),
então um VPS modesto dá conta. Fosse converter vídeo, aí sim CPU seria o gargalo — e é
justamente por isso que a decisão é não converter.

**Instalar**

| Sistema | Como |
|---|---|
| Windows | Baixar o pacote *essentials* em <https://ffmpeg.org/download.html>, descompactar em `C:\tools\ffmpeg` |
| Debian/Ubuntu | `sudo apt install ffmpeg` |
| macOS | `brew install ffmpeg` |

Depois, informe os caminhos no `.env` (deixe `ffprobe`/`ffmpeg` se estiverem no PATH):

```dotenv
FFPROBE_CAMINHO="C:/tools/ffmpeg/bin/ffprobe.exe"
FFMPEG_CAMINHO="C:/tools/ffmpeg/bin/ffmpeg.exe"
```

**Conferir a qualquer momento** — rodar depois de todo deploy em máquina nova:

```bash
php artisan midia:verificar
```

Ele diz em uma linha se está tudo certo e, se faltar algo, ensina o passo a passo.

## Como rodar

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan midia:verificar   # confere o FFmpeg

php artisan serve     # aplicação
npm run dev           # assets (2º terminal)
```

Contas locais: `admin@admin.com` e `teste@teste.com` — senha `1234` nas duas.

São curtas de propósito, para não travar quem desenvolve. **O seeder só cria essas contas
no ambiente local**; fora dele ele exige `SEED_ADMIN_EMAIL` e `SEED_ADMIN_SENHA` no `.env` e
recusa rodar sem elas — senha padrão que só aparece em servidor é o pior tipo de falha,
porque nada avisa e tudo funciona.

## Onde está a informação

| Preciso de… | Vou em |
|---|---|
| **As regras de desenvolvimento** | [`CLAUDE.md`](CLAUDE.md) — leitura obrigatória |
| Por que o produto existe, para quem | `documentação-inicial/19-modelo-de-negocio.md` |
| Plano, decisões e histórico | `documentação-inicial/05-plano-de-acao.md` |
| Nome de tabela, coluna, enum, rota | `documentação-inicial/06-glossario-canonico.md` |
| O que cada rede exige para aprovar | `documentação-inicial/09-regras-das-redes.md` |

## Stack

Laravel 12 · React 19 + Inertia · Tailwind v4 · Pest · SQLite (dev) / MariaDB (prod)
