# Contrato de desenvolvimento

> **Este arquivo é lido no início de toda sessão. As regras abaixo são absolutas
> e valem para cada linha escrita neste projeto — sem exceção, sem "só desta vez".**
>
> Em caso de dúvida entre este arquivo e qualquer outro, **este vence**.
> Detalhamento completo em [`documentação-inicial/05-plano-de-acao.md`](documentação-inicial/05-plano-de-acao.md) §SEC.0.

---

## ⚫ As inegociáveis (violar = dano real)

1. **NUNCA** assinar commit com `Co-Authored-By: Claude` ou qualquer menção a IA.
   *(A saída tem que parecer do Gabriel. Risco de demissão.)*
2. **Segredos nunca no repositório.** Token OAuth e `.env` fora do git, `encrypted` no banco,
   **nunca na tela** — nem para o admin impersonando.
3. **Zona proibida: EmpiresCloud e advance_prime.** Projetos do empregador. Não abrir, não
   copiar, não citar como referência aqui.
4. **Publicar de verdade numa rede social real exige OK explícito.** O app posta em contas
   reais de gente real. Teste sempre em rascunho/privado primeiro.

---

## 🔴 SEC.0 — o contrato

| | Regra | Na prática |
|---|---|---|
| **0.A** | Zero ação real sem OK | Dev local (SQLite), prod MariaDB. Proibido sem confirmação: dropar banco, `git push --force`, versionar segredo, tocar `storage/` |
| **0.B** | Registro contínuo | decidir → executar → validar → **registrar no LOG** → próxima. Não registrou, não aconteceu |
| **0.C** | Saneamento radical | Zero código morto, zero gambiarra "pra depois", zero comentário histórico. Renomeou? renomeia **tudo** + consumidores no mesmo commit. **Grep-zero é critério de conclusão** |
| **0.D** | Vocabulário único | Um nome por conceito em código/banco/enum/teste. O texto da tela é camada separada (DEC-18) |
| **0.E** | Descrição positiva | Código e doc falam só do que existe **agora**. Histórico vive no git e no LOG |
| **0.F** | Zero drift | Um conceito = um nome técnico em rota, controller, service, model, tabela, coluna e teste |
| **0.G** | Simplicidade | Banco único, papéis numa tabela só. **Proibido cedo:** billing, multiempresa. *(Workspace saiu da lista em 2026-08-04 — ver a ressalva abaixo)* |
| **0.H** | Camadas limpas | Controller magro → Service → Action → Job. Cada rede num `Publicador` isolado |
| **0.I** | Testes + validar no real | Pest em todo fluxo crítico. Achado importante se **confere**, nunca se presume |
| **0.J** | Idioma PT-BR | Tudo do negócio em português. Só o sufixo do framework fica em inglês |
| **0.K** | Fácil manutenção | Convenção Laravel padrão, sem abstração especulativa. **Meta: dev novo entende em 1 dia** |
| **0.L** | Autonomia por reversibilidade | O critério **não** é "grande vs pequeno" — é **quanto custa desfazer** |
| **0.M** | Segurança em camadas | O ativo mais valioso **não é o código, é o token da rede do cliente** |
| **0.N** | Nome do produto nunca no código | O nome vive **só** em `APP_NAME` (`.env`). Proibido escrevê-lo em código, comentário, teste, dado de teste **ou documentação** — a doc diz "o produto"/"o painel" |

### 📌 Ressalva de 0.G — **grupo**, o workspace que passou a existir (2026-08-04)

A regra proibia inventar estrutura antes de existir problema. **O problema apareceu:** quem produz
duas linhas de conteúdo (notícias e novelas) tem duas redes de canais, e o compositor mostrava as
duas pilhas juntas. Marcar a caixinha errada publica no lugar errado — e publicação não tem desfazer.

Existe **um** agrupamento, chamado **grupo**, e ele é deliberadamente magro:
- **Não** tem cobrança, limite, convite nem membro. Grupo é de um dono só.
- **Não** tem Global Scope (ver 0.M). É filtro explícito de tela.
- **Não** vive na URL: é modo de sessão.

⛔ **Billing e multiempresa continuam proibidos.** Detalhes em
[`documentação-inicial/15-plano-grupo.md`](documentação-inicial/15-plano-grupo.md).

---

## 🔀 0.L — quando agir e quando parar

| 🟢 Faço direto (desfaz com um commit) | 🔴 Confirmo antes (caro ou impossível desfazer) |
|---|---|
| Escrever/refatorar código, testes, telas | **Publicar de verdade** em conta real |
| Nomear coisas dentro de um arquivo | Apagar arquivo, dado ou migration já rodada |
| Layout, CSS, componentes | **Contrato do `Publicador`** (trava as 15 redes) |
| Tentar uma abordagem e trocar | Modelo de dados **depois** que houver dado real |
| Instalar dependência conhecida | Gastar dinheiro |
| **Corrigir bug que eu mesmo introduzi** | Submeter auditoria/review a plataforma |
| Documentar decisão | Qualquer coisa que toque a **zona do empregador** |

**Execução contínua (0.0):** vou fase a fase sem pedir permissão entre sub-passos.
Paro só em: publicar de verdade · decisão de produto ambígua · credencial externa necessária ·
falha externa.

### Protocolo anti-erro (nasceu de erros reais neste projeto)

1. **Confrontar com o real antes de afirmar.** Regra de plataforma, limite, comportamento de
   API: verificar na fonte. *(Eu endureci 6 regras de 6 por não fazer isso.)*
2. **Mudou num lugar, muda em todos** — no mesmo passo, com grep-zero.
3. **Perguntar o que não dá pra deduzir** (contrato de trabalho, preferência de negócio).
4. **Preferir duplicar a abstrair cedo.**
5. **Bloco pequeno + verificação** — não levas gigantes onde o erro se esconde.
6. **Dizer o que não sei.** Incerteza declarada vale mais que confiança inventada.

---

## 🛡️ 0.M — segurança (as que mais pegam)

- **Isolamento — já construído, use assim:** toda tabela de cliente usa a trait
  **`PertenceAoUsuario`** (Global Scope + carimbo de `usuario_id` + relação). **Nunca escrever
  `where('usuario_id', ...)` no controller** — dá a impressão de que a proteção mora ali.
  Id alheio = **404**, nunca 403 *(403 confirma que existe)*.
  | Situação | O que fazer |
  |---|---|
  | Requisição web | nada — dono = usuário da sessão (inclui impersonação) |
  | **Job / comando** | `ContextoDoUsuario::definir($usuarioId)` **antes** de consultar |
  | Admin / seeder / relatório | `ContextoDoUsuario::semEscopo(fn () => ...)` |

  ⚠️ Sem dono definido, a consulta **lança exceção** — de propósito. O worker da fila não tem
  sessão; filtrar por `usuario_id IS NULL` devolveria lista vazia sem erro, e `Queue::fake()`
  esconderia isso em todos os testes.
- **Dono é segurança; grupo é organização — e os mecanismos são diferentes de propósito.**
  Dono usa Global Scope que **lança**. Grupo usa **filtro explícito** na consulta da tela.
  ⛔ **Não existe trait `PertenceAoGrupo` nem Global Scope de grupo:** job, comando e conciliação
  não têm grupo corrente, e um scope que lançasse aí derrubaria o motor inteiro.
  ⚠️ Consequência prática: em `join` cru, o filtro de grupo **soma** ao `whereIn` escopado que já
  existe — nunca substitui. Trocar um pelo outro troca a trava de segurança por uma preferência
  de tela.
- **Impersonação:** toda sessão registrada · tarja sempre visível · token nunca exibido ·
  **nunca pode trocar senha do cliente nem desconectar rede**.
- **Arquivos:** validar pelo **conteúdo** (magic bytes/`ffprobe`), nunca pela extensão · fora
  da raiz pública · nome gerado por nós · servidor jamais executa nada da pasta de mídia.
- **URL pública temporária** (buraco que abrimos de propósito p/ Instagram e TikTok): assinada,
  curta, imprevisível, expira, serve só o arquivo.
- **Autorização por Policy**, não por `if` espalhado. Validação **sempre** no servidor.
- **Log de segurança separado do técnico** (`storage/logs/seguranca.log`, 180 dias).
  Escrever só via `RegistroDeSeguranca` — nunca token, senha ou cookie.
- **Log de acesso nunca bloqueia exclusão de conta** (DEC-44): FK `nullOnDelete` + cópia do
  ULID. *Dado pessoal se apaga; registro de acesso sobrevive anonimizado.*

---

## 🧭 Convenções que evitam drift

- **Chave canônica ≠ rótulo de tela (DEC-18).** A chave (`admin`, `publicada`) vive no banco e
  nunca muda; o texto visível fica em `lang/pt_BR/rotulos.php` e pode ser reescrito à vontade.
- **Território do framework (INTOCÁVEL, fica em inglês):** `password`, `remember_token`,
  `email_verified_at`, `created_at`/`updated_at`, tabelas `sessions`/`cache`/`jobs`/
  `password_reset_tokens`, **`sessions.user_id`**, e o prefixo `use` dos hooks React.
  *(Renomear coluna de auth exige override frágil — foi a classe do bug `senha_hash`.)*
- **Papel ≠ escopo (DEC-43).** Papel diz *de que lado você está*; escopo diz *quanto você
  enxerga*. Papel novo = case no enum + responder `ehOperador()` e `rotaInicial()` (são `match`
  sem `default` de propósito) + rótulo. "Só vê a carteira dele" é **escopo**, é módulo à parte.
- **ULID nas rotas.** O id sequencial nunca sai do servidor.
- **Derivado nunca vira coluna** (proporção = `largura/altura` é accessor).
- **0.N — o nome do produto não existe dentro do projeto.** O nome comercial **ainda não está
  decidido**, e mesmo depois de decidido ele pode mudar (marca registrada, domínio ocupado,
  reposicionamento). Por isso:
  - **Única fonte:** `APP_NAME` no `.env` → chega ao React por `nomeDoApp` (prop do Inertia).
    O componente `<Marca />` é o único lugar que o desenha.
  - **Proibido escrever o nome** em código, comentário, string, teste, seed, e-mail de teste,
    nome de classe, rota, tabela ou coluna. Dado de teste usa domínio genérico
    (`@teste.com`, `@example.com`) — nunca um derivado do nome do produto.
  - **Na documentação também:** escrever **"o produto"**, **"o painel"**, **"a plataforma"**.
    O nome só pode aparecer em `.env`, no `README` e no nome do repositório.
  - **Verificação:** `grep -rniE "<nome>" app/ routes/ resources/ database/ tests/ lang/ config/`
    tem que voltar **zero**.

  > **Quando o nome trava de verdade:** no momento em que o app for **registrado nas
  > plataformas** (Google, Meta, TikTok). Ali o nome entra na tela de consentimento que o
  > usuário final vê, e mudar depois da verificação pode custar nova submissão. **Decidir o
  > nome antes de abrir o primeiro cadastro de desenvolvedor** — dentro do código, o custo é
  > zero até lá.

---

## 🗂️ Onde consultar

| Preciso de… | Vou em |
|---|---|
| Por que o produto existe, para quem, preço | `documentação-inicial/19-modelo-de-negocio.md` |
| Regras SEC.0 completas, DECs, fases, LOG | `documentação-inicial/05-plano-de-acao.md` |
| Nome de tabela, coluna, enum, rota | `documentação-inicial/06-glossario-canonico.md` |
| O que cada rede exige para aprovar | `documentação-inicial/09-regras-das-redes.md` |
| Como a tela deve se comportar | `documentação-inicial/14-telas.md` |
| Referência de layout dos concorrentes | `documentação-inicial/18-referencias-de-layout.md` |
| **Implementar uma rede** | `planos-de-redes/<rede>/` — plano + cópia local da doc oficial |

### 🌐 Antes de integrar QUALQUER rede

**Ordem obrigatória: consultar a documentação oficial → planejar → executar → revisar → testar.**
Nunca escrever integração de memória.

Isto virou regra porque custou caro: o publicador do YouTube foi escrito de lembrança da API, e
a consulta depois achou **7 divergências** — entre elas cortar a descrição por caractere quando
o limite é em **bytes** (toda legenda com acento seria recusada) e tratar erro passageiro de
servidor como sessão expirada. No Bluesky, o limite era de **grafemas**, não caracteres.

Cada rede tem sua pasta em `planos-de-redes/<rede>/`, com o plano e a **cópia local** do que a
documentação dizia na data da consulta. O que não estiver documentado entra marcado como
**não verificado** — nunca como certeza.

**Antes de dar por pronto:** serve à promessa? · camadas certas? · grep-zero? · testes verdes
incluindo isolamento? · tudo em PT-BR? · **nome do produto fora do código e da doc (0.N)?** ·
LOG atualizado? · commit sem menção a IA?

---

## ⚙️ Comandos

```bash
php artisan serve          # aplicação
npm run dev                # assets em desenvolvimento (2º terminal)
./vendor/bin/pest          # testes
./vendor/bin/pint          # formatação PHP
npm run lint               # ESLint
php artisan migrate:fresh --seed
php artisan midia:verificar # confere ffprobe/ffmpeg
```

## 🎬 FFmpeg — dependência do SISTEMA, não do projeto

`ffprobe` (lê o vídeo, sustenta o **laudo de mídia**) e `ffmpeg` (recodifica quando a rede
exige) **não vêm com `composer install`**. São programas do sistema operacional, instalados
separadamente na máquina de dev **e** no servidor. ~100 MB cada, fora do repositório.

- **Caminho configurável** em `config/midia.php` → `FFPROBE_CAMINHO`/`FFMPEG_CAMINHO` no `.env`.
  Nunca depender só do PATH: em hospedagem, o usuário do PHP costuma ter PATH diferente, e o
  recurso falha em produção funcionando em dev.
- **Chamar sempre com tempo limite** (`config('midia.tempo_limite_inspecao')`) — arquivo
  corrompido pendura o `ffprobe` e trava a requisição.
- **Ausência degrada, não quebra:** sem `ffprobe`, o upload continua funcionando e a tela avisa
  que o laudo está indisponível. Nunca estourar erro técnico na cara do usuário.
- **Instalado aqui:** `C:\tools\ffmpeg\bin` (v8.1.2), já no PATH do usuário.
- ⚠️ **Hospedagem:** qualquer **VPS** serve (root instala). Não serve compartilhada/gerenciada.
  🔴 **Pegadinha que acontece mesmo em VPS:** painel (aaPanel/cPanel/Plesk) põe `proc_open` em
  `disable_functions` — e o Symfony Process depende dele. O erro não menciona FFmpeg, fala de
  função desabilitada. Liberar `proc_open` no `php.ini`. Entra na decisão da **Fase I**.

Contas locais: `admin@admin.com` e `teste@teste.com` — senha `1234` nas duas.

São curtas de propósito, para não travar quem desenvolve. **O seeder só cria essas contas
no ambiente local**; fora dele ele exige `SEED_ADMIN_EMAIL` e `SEED_ADMIN_SENHA` no `.env` e
recusa rodar sem elas — senha padrão que só aparece em servidor é o pior tipo de falha,
porque nada avisa e tudo funciona.

> **Nome do produto:** vive **só** em `APP_NAME` no `.env`. Nada no código escreve o nome —
> renomear o produto é trocar uma linha. Ver DEC-45.
