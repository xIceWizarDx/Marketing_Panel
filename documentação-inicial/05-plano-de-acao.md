# PLANO DE AÇÃO — o produto

> Guia de execução. Documento vivo — o LOG é atualizado a cada passo.

---

## 🎯 O NEGÓCIO (o que este plano serve)

> ### O painel que publica vídeo curto em várias redes — e **PROVA** que publicou.

**A dor validada** *(4 fontes independentes — doc 20)*: o painel diz "publicado" e não
publicou · a conexão quebra em silêncio · o vídeo é degradado ou recusado · Shorts é mal
suportado por **todas** as ferramentas.

**A frase de venda:** *"Se falhar, a gente conserta antes de você perceber — e te mostra o
link provando que subiu."*

**Quem compra:** social media e agência com vários clientes — gente que já perdeu (ou quase)
cliente por post que não subiu. **Preço-alvo: R$ 49–79/marca** (mercado: R$ 25–55).

### ⚖️ O filtro de escopo — 3 perguntas. Falhou uma, não entra:
1. **Serve à promessa?** (publicar com prova · avisar antes de quebrar · não estragar o vídeo)
2. **Cabe num dev solo?** (construir **e manter**)
3. **Sobrevive à auditoria?** (não viola política de nenhuma plataforma)

📘 Molde completo: **[`19-modelo-de-negocio.md`](19-modelo-de-negocio.md)** ·
evidências: [`20-evidencias-do-mercado.md`](20-evidencias-do-mercado.md)

---

## 🗂️ Onde consultar o quê

| Preciso de… | Doc |
|---|---|
| **o negócio** — promessa, público, preço, filtro de escopo | **19** |
| evidência de mercado — o que usuários relatam | 20 |
| requisitos do produto | 01 |
| **nomes canônicos** — tabela, coluna, enum, rota | **06** |
| **regras das plataformas** — o que o código precisa cumprir | **09** |
| trâmites e conformidade — o papelório | 08 |
| telas, uma a uma, com comportamento mobile | 14 |
| ordem de construção — o que evita retrabalho | 15 |
| como trabalhar comigo — falhas e proteções | 16 |
| referências de layout (Buffer, Publer, bundle.social) | 18 |
| **o que cada rede entrega de métrica**, e o que ela não entrega | **17** |
| **o módulo Meta** — Facebook, Instagram e Threads, e a ordem entre eles | **21** |

---

## 🧱 Stack

**Laravel 13** (back) + **React 19** (front), colados por **Inertia** — **monolito**, sem API
REST separada nem SPA à parte. **TypeScript · Tailwind + shadcn/ui · Pest.**
**Banco: MariaDB em produção, SQLite em dev/teste** (DEC-22). Fila com driver de banco.
Storage local com URL pública temporária pra mídia (DEC-07).
*(Nasceu no starter Laravel 12; sobe pro 13 na Fase 0 — o 12 perde bug fixes em 13/08/2026.)*

---

## 🔴 SEC.0 — REGRAS ABSOLUTAS (contrato do projeto)

- **0.A — SEGURANÇA / ZERO AÇÃO REAL SEM OK.** Desenvolvimento **local** (SQLite);
  produção em **host público com MariaDB** (DEC-20/21/22).
  PROIBIDO sem confirmação: apagar/dropar banco, `git push --force`, versionar segredos
  (`.env` e tokens **sempre** fora do repo). **Publicar de verdade numa rede social real
  exige OK explícito** (o app posta em contas reais — testar sempre em rascunho/privado
  primeiro). **Toda sessão de impersonação é registrada** (quem, quando, quem foi acessado).
  Zona proibida: **EmpiresCloud e advance_prime** — projetos separados, não misturar.
- **0.B — REGISTRO CONTÍNUO.** Ordem: decidir → executar → validar → **registrar no LOG** →
  próxima ação. Não registrou, não aconteceu.
- **0.C — SANEAMENTO RADICAL.** Zero código morto, zero "gambiarra pra depois", zero
  comentário histórico ("antes era X"). Renomeou? renomeia **tudo** + consumidores no
  **mesmo commit**. Grep-zero é critério de conclusão.
- **0.D — VOCABULÁRIO ÚNICO (técnico).** Um nome por conceito no **código, banco, enum e
  teste** — zero sinônimo espalhado. A **tela** usa rótulos separados (DEC-18): chave
  canônica fixa embaixo, texto visível livre em cima. Glossário na seção "Vocabulário".
- **0.E — DESCRIÇÃO POSITIVA.** Código/doc fala só do que existe **agora**. Histórico vive
  no git + neste LOG, nunca em prosa de código.
- **0.F — ZERO DRIFT (técnico).** Um conceito = **um nome técnico** em tudo (rota, controller,
  service, model, tabela, coluna, teste) — sem sinônimo. O **rótulo de tela** é camada à parte
  (DEC-18). Auditar (grep-zero do termo antigo) ao fim de cada fase e em **todo** rename.
- **0.G — SIMPLICIDADE (anti-over-engineering).** **Banco único** com separação lógica
  (**nada de multi-database**). **Dois papéis numa tabela só de usuários** (**nada de
  multi-guard elaborado**). Foco em **short (9:16) + imagem**. **Proibido trazer cedo:**
  billing/planos, workspaces, multiempresa. Cada camada serve ao MVP.
- **0.H — CAMADAS LIMPAS.** Controller **magro** → **Service** (regra de negócio) → **Action**
  (uma operação) → **Job** (assíncrono/fila). Integração de cada rede isolada num **Publicador**
  por plataforma, com interface única. Nada de lógica de negócio no controller.
- **0.I — TESTES + VALIDAR NO REAL.** Pest. Todo fluxo crítico (conectar conta, publicar,
  fila/retry, **isolamento entre clientes**, impersonação) tem teste. Achado importante
  validado contra o real (dados/resposta da API), nunca presumido.
- **0.J — IDIOMA PT-BR.** **Tudo** que é do negócio em português (tabela, coluna, model,
  rota, variável, enum, UI, teste, mensagem). O andaime do Laravel mantém o sufixo padrão
  da comunidade (`Controller`/`Service`/`Job`/`Request`/`Model`). Ver DEC-15.
- **0.K — FÁCIL MANUTENÇÃO.** Convenção Laravel padrão (não reinventar estrutura).
  Componentes **shadcn** padrão. Funções curtas, um propósito. **Sem abstração especulativa**
  — só o que o MVP usa. Nomes de domínio em PT-BR = o código fala a língua do negócio.
  **Meta: um dev novo entende em 1 dia.**
- **0.M — SEGURANÇA EM CAMADAS, DESDE A PRIMEIRA LINHA.** O ativo mais valioso aqui **não é o
  código, é o token da rede social do cliente** — quem o tem, publica no nome dele. Segurança
  entra na fundação porque **retrofit de segurança é caro e nunca fica completo**.

  **Camada 1 — Segredos (o cofre)**
  - Token com cast `encrypted`, **chave fora do banco** (`.env`) — vazar o dump não basta pra usar
  - ⛔ **Nunca** em log, mensagem de erro, resposta de API, exportação de dados ou tela — **nem
    para o admin impersonando**
  - `.env` fora do repo · segredo do app só no servidor · rotação de token tratada (o de
    renovação muda a cada uso em algumas redes)

  **Camada 2 — Isolamento (um cliente nunca alcança o outro)**
  - `usuario_id` + Global Scope em toda tabela de cliente
  - Filha (`credenciais`, `destinos`, `tentativas`) **só via relação do pai** — nunca query solta
  - **Rota aninhada com scoped binding**; acesso a id alheio = **404**, não 403 *(403 confirma
    que existe)*
  - **Teste-guardião de isolamento é obrigatório** — cliente A tentando ler/editar B

  **Camada 3 — Entrada (quem entra e como)**
  - Rate limit em login, cadastro e recuperação de senha
  - **E-mail confirmado obrigatório** antes de qualquer ação
  - **2FA** disponível (vem no starter) — e **obrigatório no papel admin**
  - Sessão: regenerar id no login, expirar por inatividade, invalidar nas outras sessões ao
    trocar senha

  **Camada 4 — Impersonação (o superpoder)**
  - Toda sessão registrada (quem, quando, quem foi acessado)
  - Banner **sempre visível** — impossível esquecer que está impersonando
  - **Token nunca exibido**, nem aqui
  - ⚠️ **Impersonação nunca pode trocar a senha do cliente nem desconectar redes** — se
    pudesse, o log de auditoria não protegeria ninguém

  **Camada 5 — Arquivos (a superfície de ataque mais esquecida)**
  - Validar tipo pelo **conteúdo real** (magic bytes/`ffprobe`), **nunca pela extensão**
  - Guardar **fora da raiz pública**, com nome gerado por nós (nunca o nome do usuário)
  - Servidor configurado pra **jamais executar** nada da pasta de mídia
  - Limite de tamanho **antes** do upload chegar (nginx/PHP)

  **Camada 6 — A URL pública temporária (o buraco que ABRIMOS de propósito)**
  - Instagram e TikTok exigem baixar a mídia por URL pública (DEC-07/27) — é exposição que
    **nós criamos**, então tem que ser controlada:
  - **Assinada, curta (minutos), imprevisível** (id aleatório longo, nunca sequencial)
  - **Só existe durante a janela de publicação** — expira depois
  - Serve **só o arquivo**, sem listar diretório

  **Camada 7 — Aplicação (o básico que não pode faltar)**
  - CSRF (Laravel) · saída escapada (React) · **nunca SQL cru com input** · cabeçalhos de
    segurança (CSP, HSTS, X-Content-Type-Options) · HTTPS obrigatório
  - **Validação sempre no servidor** — a do navegador é conveniência, não defesa
  - Autorização por **Policy**, não por `if` espalhado

  **Camada 8 — Cadeia e operação**
  - Dependências travadas no lock + auditoria periódica
  - Log **de segurança** (login, falha de login, impersonação, revogação) separado do log técnico
  - **Backup testado** — backup que nunca foi restaurado não é backup
  - Plano de incidente pronto (doc 08): vazou token → **revogar todos na origem**, em massa
- **0.N — O NOME DO PRODUTO NÃO EXISTE DENTRO DO PROJETO.** O nome comercial **ainda não está
  decidido** — e mesmo depois de decidido pode mudar (marca já registrada, domínio ocupado,
  reposicionamento). Tratar o nome como **conteúdo**, nunca como identificador.
  - **Fonte única:** `APP_NAME` no `.env` → `VITE_APP_NAME` apenas repassa → chega ao React
    como `nomeDoApp` (prop do Inertia). O componente `<Marca />` é **o único** que o desenha.
  - **Proibido escrever o nome** em código, comentário, string, teste, seed, e-mail de teste,
    nome de classe, rota, tabela ou coluna. Dado de teste usa domínio genérico
    (`@teste.com`, `@example.com`) — nunca um derivado do nome do produto.
  - **Na documentação também:** escrever **"o produto"**, **"o painel"**, **"a plataforma"**.
  - **Exceção única — o `README`.** É o **um** arquivo que registra os caminhos reais (pasta,
    repositório, casca antiga de referência). Renomear = corrigir ali e mais nada.
  - **Verificação (0.C):** `grep -rniE "<nome>" app/ routes/ resources/ database/ tests/ lang/
    config/ documentação-inicial/` tem que voltar **zero**.

  > **O prazo real não é o código — é a primeira submissão às plataformas.** Ao registrar o app
  > no Google/Meta/TikTok, o nome entra na **tela de consentimento que o usuário final vê** e
  > passa a fazer parte do que foi auditado; mudar depois pode custar nova submissão. **Decidir
  > o nome antes de abrir o primeiro cadastro de desenvolvedor.** *(Conferir as regras exatas de
  > re-verificação de cada plataforma na hora — protocolo 0.L#1.)*
  > ⚠️ Doc 08 §Google: o nome **não pode conter "YouTube"/"You-Tube"** — checar ao decidir.
- **0.L — AUTONOMIA COM CRITÉRIO DE REVERSIBILIDADE.** O Gabriel deu autonomia total. O
  critério pra usá-la **não** é "mudança grande vs pequena" — é **quanto custa desfazer**.

  | 🟢 **Faço direto** (desfaz com um commit) | 🔴 **Confirmo antes** (caro ou impossível desfazer) |
  |---|---|
  | Escrever/refatorar código, testes, telas | **Publicar de verdade** em conta real |
  | Nomear coisas dentro de um arquivo | Apagar arquivo, dado ou migration já rodada |
  | Layout, CSS, componentes | **Contrato do `Publicador`** (trava as 15 redes) |
  | Tentar uma abordagem e trocar | **Modelo de dados** depois que tiver dado real |
  | Instalar dependência conhecida | Gastar dinheiro (X cobra por post) |
  | Corrigir bug que eu mesmo introduzi | Submeter auditoria/review a plataforma |
  | Documentar decisão | Qualquer coisa que toque a **zona do empregador** |
  | | `git push --force`, mexer em `storage/` |

  **Protocolo anti-erro caro** (nasceu dos erros que eu cometi nesta conversa):
  1. **Confrontar com o real antes de afirmar.** Regra de plataforma, limite, comportamento de
     API: verificar na fonte **ou** contra ferramenta aprovada que já faz aquilo. *(Eu endureci
     6 regras de 6 por não fazer isso.)*
  2. **Mudou num lugar, muda em todos.** Alterou decisão? Varrer os documentos/código que
     dependem dela **no mesmo passo** — grep-zero (0.C/0.F). *(O doc 01 ficou horas
     contradizendo o plano.)*
  3. **Perguntar o que não dá pra deduzir.** Contrato de trabalho, o que a empresa vende,
     preferência de negócio — eu não tenho como saber. *(Quase levei a um conflito com o
     empregador.)*
  4. **Preferir duplicar a abstrair cedo.** Duas classes parecidas custam uma tarde; abstração
     errada custa reescrever 15.
  5. **Bloco pequeno + verificação.** Entregar em pedaços que cabem numa revisão, não em
     levas gigantes onde o erro se esconde.
  6. **Dizer o que não sei.** Incerteza declarada vale mais que confiança inventada.
- **0.0 — AUTONOMIA + EXECUÇÃO CONTÍNUA.** Executo fase a fase sem pedir permissão entre
  sub-passos. Paro só em: **(1)** publicar de verdade numa rede real; **(2)** decisão de
  produto ambígua; **(3)** credencial/config externa necessária (ex.: app no Google Cloud);
  **(4)** falha externa.

### ⚫ Regras críticas (herdadas, inegociáveis)
- **NUNCA** assinar commit com `Co-Authored-By: Claude` ou qualquer menção a IA.
- Segredos (tokens OAuth, `.env`) **NUNCA** no repositório; **encriptados** no banco;
  **nunca exibidos na tela** — nem para o admin impersonando.

---

## 🟦 DECISÕES DE ARQUITETURA (travadas)

- **DEC-01 — Banco único, separação lógica.** Um banco. Cada dado de cliente carimbado com
  `usuario_id` + **Global Scope** que filtra pelo usuário atual (ou pelo impersonado). Mesmo
  isolamento do multi-database, sem o peso dele. **Sem multi-database.**
- **DEC-02 — Uma tabela `usuarios` com papel.** Papel `admin` ou `cliente` na mesma tabela.
  Sem tabelas/guards separados. Impersonação = admin assume o `id` de um cliente.
- **DEC-03 — Dois painéis.** Painel **admin** (gestão + impersonar) e painel **cliente**
  (conectar redes, publicar). Layout e rotas separados por papel.
- **DEC-04 — Impersonação COMPLETA para o admin.** Admin entra e age como o cliente.
  Salvaguardas obrigatórias: **log de auditoria** de cada sessão + **token nunca na tela**.
  Impersonação "só-leitura" de suporte fica para **depois** (fora do escopo agora).
- **DEC-05 — Cadastro por dois caminhos.** **Admin cria** contas **e** o cliente pode
  **auto-cadastrar-se**: (a) **e-mail + código de confirmação** — a conta nasce
  não-confirmada e só libera após confirmar; a pendente expira (fluxo padrão do starter);
  (b) **conta Google** (Socialite) — o Google confirma o e-mail e cuida da senha, a conta
  nasce confirmada. O login Google usa o **mesmo projeto Google Cloud** do YouTube (Fase 3).
  ⚠️ **Login social (entrar no app) ≠ `contas_sociais` (onde se publica)** — coisas diferentes.
- **DEC-06 — Stack:** **Laravel 12** (hoje) + React 19 + Inertia (monolito) + TS +
  Tailwind/shadcn + Pest + **MariaDB (produção) / SQLite (dev e teste — DEC-22)**.
  🔄 **Revisada 30/07:** o Laravel 13 exige **PHP 8.3+** e a máquina tem **8.2.24** — e o
  Apache do XAMPP roda como serviço do Windows (parar exige administrador). Como o **L12 só
  perde correções de bug** em 13/08/2026 e **mantém correções de segurança até 2027**, e o
  projeto não vai a produção nesse prazo, **o upgrade não é necessário agora**.
  📌 **Fica para quando o PHP for atualizado** por outro motivo. *(PHP 8.4.24 VS17 x64 já
  baixado e verificado como compatível com o Apache; o EmpiresCloud não usa `imap`, a única
  extensão removida no 8.4.)*
- **DEC-07 — Mídia em disco local**, via camada de storage do Laravel (troca pra nuvem
  depois sem mexer no código). **Corrigido em DEC-48:** o complemento antigo dizia que o
  Instagram baixaria a mídia via cURL, com o app expondo cada arquivo por URL pública
  temporária. **Não é o que fazemos** — esse caminho exigiria deixar o arquivo do cliente
  acessível na internet aberta. As três redes recebem **upload direto de bytes**.
- **DEC-08 — Fila com driver de banco** + **worker com ≥2 processos** (`--queue=` listando as
  filas por plataforma). Redis só se a escala pedir. (`ShouldBeUnique`/locks atômicos
  funcionam com cache database/file — confirmado.) *(Revisado na revisão adversarial: com
  **1** worker, "fila por plataforma" não isola nada — um upload de 300MB pro YouTube
  seguraria todos os outros destinos atrás dele; filas nomeadas viram só prioridade.)*
  **`retry_after` da fila e `$timeout` do job maiores que o maior upload** (default 90s
  causa re-entrega dupla sem crash nenhum).
- **DEC-09 — Valida mídia, não converte.** Cliente sobe já em 9:16; o app valida e recusa o
  incompatível. **Perfil canônico (07 §6):** vídeo MP4 (H.264+AAC) 9:16, **3–180s** e ≤300MB
  — até 90s passa nas 4 redes; 91–180s desabilita o Facebook (aviso por destino); imagem
  **JPEG** (IG rejeita PNG; YouTube não recebe imagem). Conversão (FFmpeg) fica pra depois.
- **DEC-10 — Várias contas por rede** permitidas desde o início (ex.: 2 canais no YouTube).
- **DEC-11 — Legenda por destino.** Schema preparado pra legenda/hashtags por rede
  (`destinos.legenda_override`); a UI começa "igual pra todas" + botão de personalizar.
- **DEC-12 — Escopo de conteúdo:** shorts (9:16) + imagens. Outros formatos = futuro.
- **DEC-13 — Ordem das redes (revisada 28/07 — decisão do Gabriel).** O critério deixou de ser
  "importância" e passou a ser **o que fecha sem depender de terceiros**:
  **1º Instagram + Facebook** (uso próprio = **sem App Review**) + **Bluesky** (zero
  burocracia, testa o motor de graça) → **2º YouTube** (código pronto + auditoria **em
  paralelo**) → **3º TikTok** (construído e testado em privado; libera quando aprovar) →
  **depois:** Threads · LinkedIn · Pinterest · Mastodon · Discord · X (⚠️ **pago por post**).
  **Fora do escopo:** Slack (chat interno) · Reddit (plano grátis **proíbe uso comercial**) ·
  Google Business (**a API rejeita vídeo**).
- **DEC-36 — Construir tudo, liberar conforme aprova.** Cada rede é construída e **testada em
  modo privado** assim que der; a liberação pública é **decisão à parte**, destravada pela
  aprovação. Isso separa **"estar pronto"** de **"estar liberado"** — o código nunca fica
  esperando fila. ⚠️ Duas travas: **(a)** a tela do TikTok precisa nascer **certa** (o que mais
  reprova é UX, e a rejeição vem genérica — refazer custa outra rodada); **(b)** submeter a
  auditoria do TikTok é **assumir que é produto** — eles exigem app real para público amplo e
  **rejeitam ferramenta de uso próprio**.
- **DEC-14 — Caminho pra vender acesso** (planos + limites + cobrança) é **camada por
  cima** desta base, na fase SaaS futura — **não muda** a arquitetura de hoje. (O
  auto-cadastro já entra no MVP — DEC-05.)
- **DEC-15 — Idioma: PT-BR no domínio, convenção Laravel na estrutura.** Tabelas, colunas,
  models, rotas, variáveis, enums, telas em português (`usuarios`, `publicacoes`,
  `ContaSocial`, `/publicacoes`). O andaime do framework usa o sufixo padrão da comunidade —
  `PublicacaoController`, `PublicacaoService`, `PublicarDestinoJob` — porque traduzir o
  sufixo quebraria a convenção e a manutenção. **Inclui renomear o `User` do starter para
  `Usuario`/`usuarios` na Fase 0.** **Colunas/tabelas do framework mantêm o nome padrão**
  (`password`, `email_verified_at`, `remember_token`, `password_reset_tokens`, `jobs`…) —
  renomeá-las exige override frágil (classe do bug `senha_hash` do EmpiresCloud).
- **DEC-16 — Ordem de construção.** Primeiro o **esqueleto do admin** (login, criar cliente,
  impersonar) — só o mínimo pra destravar o cliente. Depois o **fluxo do cliente** (mídia →
  YouTube → motor de publicação), onde está o valor. O **painel admin encorpa por último**
  (listagens, métricas, gestão), quando já há o que gerenciar. **Não** construir o admin
  inteiro antes de o cliente publicar.
- **DEC-17 — 🔄 REVISADA (28/07): navegação por TAREFA, rede como FILTRO.** *(A versão
  anterior — um item de sidebar por rede — foi descartada quando o escopo subiu para 15 redes:
  a sidebar viraria 20 linhas, e cada tela seria duplicada, uma vez global e uma por rede.
  Validado observando o bundle.social, que suporta 15 redes e organiza exatamente assim.)*
  **Sidebar fixa em 5 itens, independente de quantas redes existam:**
  `📤 Publicar · 📋 Publicações · 🎬 Mídias · 🔌 Redes · ⚙️ Minha conta`
  - **A rede é filtro**, não menu: em Publicações há chips (`Todas · YouTube · Instagram…`) e
    uma coluna de plataforma.
  - **"Redes" é UMA tela** — grade de cartões (um por rede) com **semáforo do token** (nosso
    diferencial, DEC-32); clicar abre as configurações daquela rede.
  - **Rede nova = um cartão a mais**, nunca um item de menu a mais.
  - No back, os módulos continuam **auto-contidos** (um `Publicador` por rede) e
    **compartilham os componentes** de tela — nada de duplicar (0.C/0.K).
- **DEC-38 — Mobile é requisito, não adaptação.** Layout detalhado para celular no mesmo nível
  do EmpiresCloud, com os padrões dele: **tabela vira card expansível** (resumo visível +
  detalhes ao expandir), **ações no cabeçalho do card** (nunca somem no mobile), **barra de
  filtros fixa no topo**, sidebar vira **gaveta**, e navegação principal alcançável com o
  polegar. Toda tela do doc 14 especifica **o que muda no celular**.
- **DEC-18 — Estratégia anti-drift de nomes** (a dor nº1 do EmpiresCloud — **prevenir na
  origem**, não só limpar depois). Cinco peças:
  1. **Glossário é a fonte única.** Nenhum nome de entidade/coluna/enum nasce no código sem
     estar em **`06-glossario-canonico.md`**. Decide-se uma vez, lá.
  2. **Nomear pelo conceito estável, nunca pelo valor do momento** — `papel` (não um cargo
     específico), `destino` genérico (não `post_youtube`, que quebra ao virar multi-rede).
  3. **Referência sempre por `id`**, nunca por nome/slug — renomear rótulo jamais quebra
     relacionamento no banco (e é mais rápido).
  4. **Enum centralizado** = fonte única dos valores (`Papel`, `Plataforma`, `StatusDestino`).
  5. **Rótulo ≠ chave (só nos enums que aparecem na tela).** A chave é canônica e fixa no
     código/banco/enum/teste (`Papel::CLIENTE`, valor `cliente`) — **nunca muda**. O texto
     visível vem de um **arquivo de rótulos** (`cliente → "Cliente"`) e muda à vontade,
     **1 linha**, sem tocar o código. *Canônico rígido embaixo, rótulo livre em cima.*
  **Rename da chave técnica**, se inevitável: **atômico** num commit + **grep-zero** do nome
  antigo (0.C/0.F). **Travar os nomes cedo — na modelagem (Fase 1)**, quando custa quase nada.
> ⚠️ **DEC-20 REVISADA em 28/07 (fim do dia):** o caminho agora é **uso próprio primeiro,
> plataforma depois**. O produto **nasce** para o Gabriel usar nas contas dele — o que dispensa
> App Review da Meta e encurta semanas. **Virar plataforma aberta é decisão futura**, tomada
> com o sistema já rodando. As exigências abaixo (verificação, App Review, Business
> Verification) passam a valer **a partir do momento dessa decisão** — não antes. *A Fase I
> continua útil (domínio, política, projeto Google), mas sem urgência de papelório de
> plataforma.*

- **DEC-20 — 🔓 PLATAFORMA ABERTA (quando for a hora).** O produto é uma
  **plataforma multi-cliente**: qualquer pessoa se cadastra e conecta **as próprias contas**
  de rede social. Não é ferramenta de uso próprio. **Consequências obrigatórias — todas no
  caminho crítico:** verificação OAuth do Google + auditoria de compliance do YouTube;
  **App Review + Business Verification da Meta** (documentos da empresa); audit do TikTok;
  **domínio + host público + política de privacidade + termos de uso**; e atenção à **quota
  de 100 uploads/dia, que é POR PROJETO** (não por cliente) — pedir aumento antes de escalar.
  Todo texto de "uso pessoal / 1 usuário << 100 / folga total" está **revogado**.
- **DEC-21 — Infraestrutura pública é pré-requisito, não detalhe.** O dev continua local,
  mas a plataforma exige **domínio próprio + hospedagem + HTTPS + SMTP**, porque: o Google só
  verifica app com site e política de privacidade no domínio; o Instagram **baixa a mídia por
  URL pública** ao publicar; o TikTok exige domínio verificado; e o cliente precisa alcançar o
  app. Entra como **Fase I** (abaixo), iniciada **já**.
- **DEC-22 — Banco: MariaDB/MySQL em produção, SQLite só em dev/teste.** Com clientes reais e
  workers concorrentes (fila + web escrevendo juntos), o travamento de escrita do SQLite vira
  risco. O código não muda (mesmo Eloquent); é só configuração de conexão. *(Substitui o
  "SQLite" da DEC-01/0.A no ambiente de produção — o isolamento lógico por `usuario_id`
  continua idêntico.)*
- **DEC-31 — 🎯 POSICIONAMENTO: "o painel que PROVA que publicou".** *(Pesquisa 28/07 —
  doc 13.)* Descoberta que sustenta o produto: TikTok e YouTube aceitam upload de forma
  **assíncrona** — a moderação roda **depois**. Todo painel que marca "publicado" ao receber
  HTTP 200 está marcando **"aceito para processamento"**, e **nenhum dos 9 concorrentes relê o
  post pra confirmar**. No Metricool, "enviado" oficialmente **não** significa publicado.
  **Nós vendemos o contrário: verificação, prova e honestidade.** Não somos "mais um
  agendador" — somos garantia de entrega. Preço-alvo **R$ 49–79/marca** (mLabs: R$ 29,90),
  porque o cliente compra **risco eliminado**, não conveniência.
- **DEC-32 — Diferenciais do MVP (os 4 de esforço baixo, doc 13).** Entram já:
  **(1) Status honesto** — vocabulário que nunca mente; proibido "publicado" sem link
  verificado. **(2) Monitor proativo de token** — semáforo por conta + aviso 7 dias antes de
  vencer (ninguém no mercado faz; todos só reagem depois da falha). **(3) Laudo de mídia** —
  `ffprobe` + tabela por rede, mostrando o que vai acontecer com o arquivo antes de agendar.
  **(4) WhatsApp** — alerta de falha, token vencendo e **aprovação do cliente** (nenhum
  concorrente global faz — o mercado deles não usa WhatsApp assim).
  **Fase 2:** verificação pós-publicação com permalink + **relatório de prova de entrega**
  white-label (é o que a agência **repassa no preço dela**).
- **DEC-33 — Vídeo: validar, corrigir só o necessário, e ser transparente.** O Buffer admite na
  doc que **não dá pra desligar a transcodificação** (teto 1080p → 4K vertical é rebaixado em
  silêncio); a mLabs não converte e **rejeita**. Ficamos no meio: **passthrough** quando o
  arquivo já está na spec, recompressão **só da faixa problemática**, e **laudo antes/depois**.
  ⚠️ **Aceitar HEVC do iPhone** — Buffer e Metricool recusam, mas a Meta **aceita oficialmente**.
  *(Honestidade: qualidade de vídeo sozinha não vende — é reforço, não bandeira.)*
- **DEC-34 — Stories do Instagram automático de verdade.** A mLabs **descontinuou** o automático
  (empurrou pro modo "abra o app no horário") e há **cancelamento documentado no Reclame Aqui**
  por isso — enquanto a API do Instagram **suporta** `media_type=STORIES` em conta Business.
  Automático para Story de vídeo/imagem puro; modo notificado **só** quando houver figurinha ou
  enquete (limite real da API).
- **DEC-41 — Confiança na permissão é diferencial.** *(Doc 18 — teste real das 3 ferramentas.)*
  Usuários relatam medo documentado: *"toda plataforma pediu permissão total, **inclusive para
  apagar vídeos** — se você investe muito no seu canal, isso é assustador"* — a ponto de
  contatarem o fundador de uma delas pra perguntar, e **mesmo assim acharem arriscado**.
  **Nossa resposta:** pedir **o escopo mínimo** (já é regra — YT-F04), **explicar em português
  o que cada permissão faz** na tela de conectar, e **deixar explícito o que NÃO pedimos**.
  Barato de fazer, e fala direto com quem vende confiabilidade.
- **DEC-42 — Salvar rascunho automático.** Relato de perda de rascunho por atualização de
  página. Custo baixo, evita a pior sensação possível no produto: **perder trabalho**.
- **DEC-45 — O nome comercial fica pendente, e o projeto é construído para isso (0.N).**
  O Gabriel levantou o risco: falamos muito um nome que ainda não foi decidido. **Medido em vez
  de presumido:** o nome estava em **3 pontos** do código (um fallback no `app.tsx` e dois
  e-mails de teste) e **11** na documentação. Os 3 do código foram eliminados — a fonte agora é
  só `APP_NAME`. A documentação passou a dizer "o produto"/"o painel", e os caminhos reais
  (pasta, repositório, casca antiga) foram concentrados **num `README` novo**, o único arquivo
  a corrigir num rename. **Custo de renomear hoje: uma linha do `.env` + o `README`.**
  **O prazo que importa** não é o código, é a **primeira submissão às plataformas** — ali o nome
  vira parte do que foi auditado.
- **DEC-46 — Escopo mínimo no YouTube, mesmo custando conveniência.**
  `videos.update` e `videos.delete` pedem **os mesmos escopos** — não existe pedir "editar" sem
  levar "apagar". **Fica o mínimo** (`youtube.upload` + `youtube.readonly`).
  **O que isso custa:** vídeo enviado antes da auditoria **fica privado**, e não conseguimos
  torná-lo público depois — a pessoa muda à mão no YouTube Studio.
  **Por que mesmo assim:** o medo de *"esse aplicativo pode apagar meus vídeos"* é a queixa
  documentada nº 1 da pesquisa (doc 20) — gente que **desistiu de conectar ferramenta** por
  causa disso. Trocar confiança por conveniência é péssimo negócio para um produto que vende
  justamente confiança.
  **E o custo é de janela, não permanente:** antes da auditoria o YouTube é ambiente de teste;
  depois dela, tudo que sair já sai público. O incômodo existe uma vez só.
  **Se um dia doer de verdade:** pedir o escopo maior **só para quem quiser**, com a explicação
  do que muda — nunca por padrão, nunca escondido.
  ⚠️ **A tela precisa dizer isso ANTES de conectar**, não deixar descobrir depois.
- **DEC-44 — Log de acesso nunca bloqueia o direito de eliminação.** Toda tabela de log nasce
  com FK **`nullOnDelete` + cópia do ULID** do envolvido. A pessoa é apagada; o evento fica,
  rastreável por um identificador opaco. `restrict` em log é armadilha: transforma "recebi
  suporte uma vez" em "não posso mais sair da plataforma".
  **Corolário para as tabelas do cliente** (`midias`, `publicacoes`, `contas_sociais`…):
  continuam `restrict` — mas então **apagar conta é um serviço**, que remove o que é do dono na
  ordem certa e só depois o usuário. Nunca cascata silenciosa, nunca `delete()` solto.
- **DEC-43 — Cargos: o enum já aguenta; o que se trava agora é a separação operador ×
  cliente.** `comercial` e `suporte` **vão existir**, mas não entram como enum vazio agora —
  papel sem tela e sem limite herda o alcance do admin, e foi exatamente assim que
  `suporte`/`dev` passaram a ver o **MRR da plataforma inteira** no EmpiresCloud.
  **O que foi feito agora (barato):** `Papel::ehOperador()` e `rotaInicial()` viraram `match`
  **sem `default`** — papel novo sem resposta explode na hora; as rotas de `/admin` usam
  `Papel::listaDeOperadores()` em vez de `admin` escrito na mão (papel de operador novo vale
  nelas sozinho); impersonação passou a barrar **qualquer operador**, não só `admin`;
  teste-guardião cobra lado + tela inicial + rótulo de todo case.
  **O que fica pra depois (caro e proposital):** as telas do comercial e — principalmente —
  a **carteira** (o comercial só vê os clientes dele). Isso é **escopo, não papel**: exige
  coluna de dono + Global Scope + teste de isolamento, e é módulo próprio.
  **Custo real de adicionar `comercial` quando quiser:** 1 case + 1 rótulo + responder as duas
  perguntas. Zero arquivo de rota tocado.
- **DEC-40 — Plataforma nasce nos parâmetros ideais; automação vem depois, por decisão.**
  *(Doc 01 §8.1.)* O painel é construído **conforme, aprovável e vendável** — nada de recurso
  que comprometa auditoria. As automações são **atualizações posteriores**, ligadas **quando o
  Gabriel pedir**, nunca por padrão. Já mapeado:
  **✅ Pode sem ressalva:** IA de legenda/hashtag (texto visível antes de publicar) · agendamento
  · **publicação automática nas contas do próprio dono** (quem configurou o fluxo consentiu) ·
  radar de tendências · sugestão de conteúdo. *(Com clientes, cada um aprova o próprio conteúdo.)*
  **🟡 Fora do painel:** baixar/cortar vídeo — **se existir, mora numa ferramenta separada**,
  sem credencial de API (não é API Client, logo fora da política de desenvolvedor).
  **❌ Nunca:** automatizar views, curtidas e inscrições — *"é falsificação"* (decisão do Gabriel).
- **DEC-39 — Corte de vídeo com IA: requisito futuro, com restrição já travada.** *(Doc 01 §8.2.)*
  Vídeo longo → IA acha os melhores momentos → cortes 9:16 com legenda → fluxo de publicação.
  ⚠️ **Entrada por ARQUIVO, nunca por link** — baixar do YouTube é vedado por política
  (YT-C02 / III.E.1), **inclusive o próprio vídeo**, e por link exigiria raspagem (III.E.6).
  **Forma (a decidir):** **(A)** ferramenta **separada**, sem credencial de API — não é API
  Client, logo **fora do alcance das políticas de desenvolvedor**, e pode existir antes de
  qualquer aprovação; **(B)** integrada ao painel — aí tudo que ela faz passa a responder pelo
  API Client, inclusive na auditoria.
  **Custo é o fator decisivo:** transcrição + análise + renderização é processamento pago por
  vídeo — é **produto**, não recurso. Só depois do painel de pé.
- **DEC-35 — Futuro: vender a publicação como API em pt-BR.** Para quem monta automação em
  n8n/Make e hoje só tem opção gringa (em dólar, sem nota fiscal). Referência de mercado:
  **US$ 149/mês por perfil** — a 1/5 disso em reais ainda é 10× o ticket brasileiro, e roda no
  **mesmo motor**. ⚠️ **Verificar antes** se não esbarra na proibição do YouTube de
  sublicenciar acesso à API (III.G.1).
- **DEC-24 — UM projeto Google pra plataforma inteira + controle de quota.** ⚠️ Criar um
  projeto por cliente pra somar quota é **proibido e faz o Google suspender todos**. Logo o
  teto de **100 uploads/dia é do projeto**, somando todos os clientes → o motor precisa de
  **contador diário de quota** e estado **`aguardando_janela`** (republica no dia seguinte),
  além de nunca prometer "publicação instantânea ilimitada". Aumento vem da auditoria.
- **DEC-25 — Publicação sempre com aprovação explícita do cliente.** O YouTube **proíbe**
  automatizar publicação sem consentimento específico e **proíbe alterar o texto do cliente**
  (truncar/anexar) sem ele aprovar. Consequências: (a) a tela de publicar tem confirmação
  explícita; (b) agendamento exige aceite daquele conteúdo específico; (c) **título/legenda
  gerados por IA (Fase 8) precisam ser revistos e aprovados antes de ir ao ar** — nada de
  robô postando sozinho. *Ajusta a visão de "robô de tendências": ele sugere, o cliente aprova.*
- **DEC-26 — Retenção: token fica, dado do YouTube expira em 30 dias.** O refresh token pode
  ficar enquanto o cliente for ativo; **metadados/métricas do YouTube só podem ser guardados
  30 dias** (depois: apagar ou revalidar). ⚠️ **Proíbe "histórico eterno de métricas"** — a
  Fase 8 (métricas) precisa nascer com expurgo. Purga após revogação: **7 dias** (não 30).
- **DEC-27 — Mídia em URL pública HTTPS, sem redirect, em domínio verificado.** *(Revisa a
  DEC-07.)* O TikTok **só publica imagem por URL** (não existe upload de arquivo pra foto) e
  exige domínio verificado; o Instagram baixa a mídia por cURL. **URL assinada que redireciona
  NÃO funciona** — a mídia precisa ser servida direto, em domínio verificado. Verificar o
  domínio/CDN da mídia junto com o do app.
- **DEC-28 — Integração direta; atalhos avaliados e descartados (28/07/2026).**
  **n8n NÃO resolve:** as credenciais são sempre suas — todas as auditorias continuam; o
  OAuth gerenciado dele **não cobre YouTube**; **não existe conector nativo de Instagram nem
  de TikTok**; e a licença dele **proíbe** exatamente nosso caso (produto cujo valor vem
  substancialmente do n8n) — exigiria licença Embed (relatos de ~US$ 50 mil/ano).
  **Intermediário (Ayrshare/bundle.social) resolve Meta e TikTok, mas NÃO o YouTube com
  segurança:** as políticas do YouTube proíbem literalmente *"sell, purchase, lease, lend,
  convey, redistribute, or sublicense"* os serviços da API, e exigem **um projeto por
  cliente de API**. Somam-se: o contrato do Ayrshare **proíbe usar o serviço pra construir
  produto similar/concorrente** (é o que o nosso produto é) e limita responsabilidade a
  **US$ 50**; os tokens **não migram** entre provedores (trocar = todos os clientes
  reconectam); e o app do provedor suspenso **derruba 100% dos clientes de uma vez**.
  **Margem no Brasil não fecha** no provedor caro: Ayrshare ~R$ 102/perfil vs. mLabs
  vendendo a R$ 49,90 — prejuízo abaixo de ~300 clientes. *(bundle.social, a US$ 100/mês com
  contas ilimitadas, é a exceção viável — mas o impedimento do YouTube permanece.)*
  ✅ **Uso legítimo do intermediário:** validar rápido / uso próprio, **não** como base do
  produto comercial.
- **DEC-37 — Sistema fluido (tipografia e layout).** Nada de tamanho fixo nem de "quebra" por
  breakpoint: **tudo escala continuamente com a tela**, por interpolação linear com `clamp()`.
  **Fórmula:** `clamp(mínimo, intercepto + inclinação·100vw, máximo)`, onde
  `inclinação = (máx − mín) ÷ (larguraMáx − larguraMín) × 100` e
  `intercepto = mín − inclinação × (larguraMín ÷ 100)`.
  **Mesmas proporções do EmpiresCloud** — três faixas, valores idênticos:

  | Faixa | Fonte base | Sidebar | Recolhida |
  |---|---|---|---|
  | **Mobile** (< 768px) | `14px` fixo *(maior de propósito — leitura no toque)* | `0` (vira gaveta) | `0` |
  | **Tablet** (768–1279px) | `13px` fixo | `clamp(180px, 100px + 6.25vw, 200px)` | `62px` |
  | **Desktop** (≥ 1280px) | `clamp(13px, 9px + 0.3125vw, 15px)` → 13px@1280 · 15px@1920 | `clamp(200px, 120px + 6.25vw, 240px)` → 200@1280 · 240@1920 | `68px` |

  **Aplicar também em:** espaçamento de container e no tamanho da **prévia do vídeo 9:16**
  (elemento central da nossa tela), usando a mesma fórmula.
  ⚠️ **Cuidado herdado:** com fonte base em 13px, o `text-xs` do Tailwind cai pra ~9,7px —
  **não usar em informação crítica** (status de publicação, link de prova).
  **Também vale trazer:** a **barra de progresso** fina no topo durante a navegação (feedback
  barato que faz o app parecer rápido) e o respeito a `prefers-reduced-motion` em toda animação.
- **DEC-23 — Identidade visual: paleta do projeto antigo (referência, não cópia).** Reaproveitar a paleta do
  a casca antiga do projeto (`resources/css/app.css`): **slate + índigo**, com tema claro e
  escuro já definidos. Encaixa direto — o antigo usa **os mesmos nomes de variável** do
  starter (`--background`, `--primary`, `--accent`, `--sidebar-*`…), então é trocar valores,
  **sem mexer em componente**. Base: fundo `#fafafa` / texto `#1e293b` / primária `#0f172a` /
  **destaque índigo `#4f46e5`** / erro `#dc2626`; escuro: fundo `#0b1220` / texto `#f8fafc`.
  **Correção ao portar:** no antigo o `--accent` vira cinza (`#1f2937`) no tema escuro — a
  cor da marca sumia; manter o índigo nos dois temas (usar `#6366f1` no escuro, como o
  `--ring` já fazia). Converter pro formato do starter na Fase 0.
- **DEC-19 — Confiabilidade e avisos (pesquisa 27/07, doc 07 §9).** No **MVP**:
  **healthcheck da fila** (Supervisor autorestart + `spatie/laravel-health` QueueCheck com
  job-sentinela — worker morto em silêncio é a pior falha possível deste produto),
  **backup automático** (banco + `storage/app` pra fora da máquina),
  **Notificações Laravel** (canais `mail` + `database`/sininho: `PublicacaoConcluida`/
  `PublicacaoFalhou`) e **status ao vivo via `usePoll`** do Inertia (polling 3–5s, reload
  parcial). **Pós-MVP (1º pacote):** Web Push + PWA instalável (andam juntos — iPhone só
  recebe push com PWA instalada) e **"cancelar antes de publicar"** (delay ~60s + botão —
  o único desfazer real). **Descartados:** Reverb/WebSocket (peso operacional sem retorno
  aqui), i18n (produto PT-BR), activity log genérico (as `tentativas` já são o histórico),
  push via SaaS externo.

---

## 📓 SEC.0.LOG (estado vivo — append)
- 2026-07-27 — Projeto criado (Laravel 12 + React/Inertia/Tailwind/Pest/SQLite), repo
  próprio (caminhos no README; casca antiga só como referência de paleta). Requisitos + pesquisa feitos.
  Escopo travado: shorts (9:16) + imagens; ordem YouTube→FB→IG→TikTok.
- 2026-07-27 — **Decisões de arquitetura travadas (DEC-01..15):** dois painéis (admin +
  cliente), banco único com separação lógica por `usuario_id`, uma tabela `usuarios` com
  papel, impersonação completa pro admin (+ log de auditoria, token nunca na tela), admin
  cria o cliente, storage local, fila em banco, valida-mas-não-converte mídia, várias contas
  por rede, legenda por destino, **idioma PT-BR no domínio + fácil manutenção**. Plano reescrito.
- 2026-07-27 — **Auto-cadastro entra no MVP (DEC-05 revisada):** e-mail com código de
  confirmação (fluxo padrão do starter) + login Google (Socialite), além de o admin criar
  contas. **Ordem de construção travada (DEC-16):** esqueleto admin → fluxo cliente → admin
  encorpa.
- 2026-07-27 — **DEC-17 travada:** arquitetura modular por rede (cada rede = módulo
  auto-contido: Publicações + Conexões + Configurações dela) + globais (Publicar/todas,
  Visão geral, Configurações gerais/Minha conta). Componentes compartilhados, sem duplicar telas.
- 2026-07-27 — **DEC-18 travada (anti-drift de nomes):** glossário fonte única + nomear pelo
  conceito estável + referência por `id` + enum centralizado + rótulo≠chave nos enums visíveis
  (chave canônica fixa, texto de tela livre num arquivo de rótulos). Regras 0.D/0.F ajustadas
  pra deixar claro que o zero-drift é **técnico**; a tela é camada à parte.
- 2026-07-27 — **`06-glossario-canonico.md` criado** (fonte única de nomes): convenções,
  papéis, 6 enums + máquinas de estado com escritor único, 9 tabelas coluna a coluna,
  rótulos back→front (uma fonte), rotas canônicas, mecânica de impersonação
  (`UsuarioAtual::efetivo()`), pastas e testes-guardiões. Correções sincronizadas no plano:
  colunas de framework mantêm nome padrão (`email_verified_at`/`password` — lição
  `senha_hash`); vínculo Google = `google_id`; Fase 1 ganhou `configuracoes_rede`.
- 2026-07-27 — **Pesquisa profunda (15 agentes, 107 achados, 6 críticos verificados em doc
  oficial — todos confirmados) consolidada em `07-pesquisa-2026-verificada.md`.** Mudanças
  aplicadas: **DEC-06 → Laravel 13** (L12 perde bug fixes 13/08/2026); DEC-07 + URL pública
  temporária p/ IG; DEC-09 + perfil canônico (vídeo 3–180s ≤300MB; imagem JPEG; YouTube sem
  imagem); **DEC-19 criada** (healthcheck fila + backup + notificações mail/sininho +
  usePoll no MVP; Web Push+PWA e cancelar-antes no pós-MVP; Reverb/i18n/activitylog
  descartados); Fase 3 ganhou os **2 gates do Google** (verificação OAuth + auditoria de
  compliance — sem ela vídeo fica privado); Fase 5 migrou pra **Instagram API with Instagram
  Login (sem Facebook Page)** + gate App Review/Business Verification; Fase 6 ganhou audit
  ~5–10 dias + UX obrigatória do TikTok + duração dinâmica por criador; Fase 7 refeita com
  fontes reais (Google Trends API alpha — aplicar já); Fase 8 ganhou push/PWA/cancelar/
  agendamento nativo. Quota YouTube atualizada: videos.insert = 1 unit, 100 uploads/dia.
- 2026-07-27 — **Revisão adversarial (8 lentes, 113 achados, 33 graves).** ⚠️ A fase de
  refutação por agentes **não rodou** (limite de sessão) — os achados foram verificados
  **manualmente por grep** nos trechos citados. Correções aplicadas:
  **(motor)** máquina `StatusDestino` ganhou `enviando → pendente` + **watchdog de órfão**
  (worker morto deixava destino "Publicando…" pra sempre) e retry deixou de ser transição
  ilegal; **anti-double-post** via `handle_externo` gravado antes do efeito irreversível +
  `retry_after`/`$timeout` maiores que o upload; notificação/recálculo só em estado terminal.
  **(schema)** `StatusConta` ganhou `desconectada` (o MUST "desconectar conta" não tinha
  estado); **todas as FKs ganharam regra de deleção** (nenhuma tinha — DELETE de mídia
  orfanava publicação); `destinos.opcoes` pras escolhas por post exigidas pelo TikTok;
  `midias.arquivada_em`. **(isolamento)** rota de reprocessar virou aninhada com scoped
  binding (filha sem Global Scope era resolvida solta). **(DEC-08)** ≥2 workers — com 1, a
  "fila por plataforma" não isolava nada. **(vocabulário)** `descricao`→`legenda` nos docs;
  status "reconectar" corrigido pra chave `expirada` + rótulo. **(fases)** gates deixaram de
  ser "em paralelo ao código" (exigem demo funcionando) e a Definição de Pronto ganhou
  critério de gate; Fase 0 sinaliza o gate do Google com fallback. **(doc 01)** reescrito —
  descrevia produto single-user, contradizendo DEC-02/03/05/19.
  **🔴 EM ABERTO (decisão do Gabriel):** uso próprio × plataforma aberta (doc 01 §10) e
  hospedagem/domínio — os dois bloqueiam a Fase 3.
- 2026-07-28 — **Pesquisa de trâmites de plataforma aberta (4 dimensões) → `08-tramites-e-
  conformidade.md`.** Achados que viraram decisão: **DEC-24** (um projeto Google só — clonar
  projeto pra somar quota é proibido e suspende tudo; teto de 100 uploads/dia é do projeto →
  status `aguardando_janela`), **DEC-25** (publicar exige aprovação explícita do cliente; o
  YouTube proíbe automação sem consentimento e alterar o texto dele — a IA da Fase 8 sugere,
  o cliente aprova), **DEC-26** (token fica, dado do YouTube expira em 30 dias → proíbe
  histórico eterno de métricas; purga em 7 dias após revogação), **DEC-27** (mídia em URL
  pública HTTPS **sem redirect** em domínio verificado — URL assinada com redirect não
  funciona; revisa a DEC-07). Glossário ganhou `logs_acesso` (6 meses, Marco Civil),
  `incidentes_seguranca` (5 anos) e `pedidos_titular`. Fase I ganhou os documentos internos.
  ⚠️ Prazos reais: auditoria do YouTube **sem SLA** (semanas a meses); Meta 4–8 semanas;
  TikTok = **duas** aprovações (2–4 semanas a segunda). **Não fechar contrato com cliente
  pagante antes das aprovações.**
- 2026-07-31 — **🎨 Logos reais + cards quadrados + KPI por rede (195 testes verdes).**
  **Logos:** pacotes `simple-icons` e `bootstrap-icons`, empacotados com o projeto. **Nada de
  CDN nem de imagem baixada solta** — rede externa vira dependência que some quando menos se
  espera, e arquivo de origem duvidosa não sobrevive a auditoria de plataforma. O
  `simple-icons` cobre 11 das 14; **LinkedIn e Google pediram remoção daquele acervo**, então
  vêm do `bootstrap-icons`, que os mantém. Tudo versionado no `package.json`.
  **Cards viraram quadrados de verdade** (`aspect-square`, grade de 3 a 7 colunas). O que os
  deixava largos era a lista de contas dentro da carta — ela **foi para um detalhe em modal**.
  A carta agora carrega só o essencial: logo, nome e o número.
  **⭐ Viraram KPI ao mesmo tempo.** O número em destaque é o de **posts confirmados no ar**
  naquela rede — não o de envios feitos. Contar envio seria contar promessa, e é exatamente o
  que o produto critica (DEC-31). Um ponto colorido no canto avisa quando alguma conta daquela
  rede precisa de atenção. No topo, o resumo: contas conectadas e total confirmado no ar.
  **Cuidado de isolamento:** `destinos` **não tem dono próprio** — o filtro do KPI vem pela
  conta, que tem. Há teste garantindo que o número de um cliente não soma no do outro.
- 2026-07-31 — **📚 DOCUMENTAÇÃO OFICIAL LIDA E TODAS AS CORREÇÕES APLICADAS (246 testes verdes).**
  Gabriel cobrou a ordem certa: *consultar a documentação → planejar → executar → revisar →
  testar*. Eu tinha escrito o publicador do YouTube **de memória** — a mesma falha que já tinha
  endurecido 6 regras de plataforma erradas. Criada a pasta **`planos-de-redes/`**, uma por
  rede, com plano + **cópia local da documentação oficial**.
  **A especificação legível por máquina foi o achado do método:** o documento de descoberta do
  Google (503 KB, revisão 20260729) e os lexicons do AT Protocol. As páginas de prosa em HTML
  são montadas por JavaScript e não servem para baixar; a spec, sim — e é ela que tem os
  números exatos.
  **🔴 A pior descoberta foi uma VIOLAÇÃO DE POLÍTICA no meu código.** As Políticas do
  Desenvolvedor proíbem *"modificar valores fornecidos pelo usuário (truncar, anexar, alterar)
  sem consentimento explícito"* — e o publicador cortava **título, legenda e tags em silêncio**.
  A pessoa escrevia 120 caracteres, publicavam 100, e ela descobria olhando o vídeo no ar.
  Risco de reprovação na auditoria **e** exatamente a meia-verdade que o produto critica.
  **Agora recusa e avisa antes**, com quanto sobra — como o laudo já fazia com o vídeo.
  **Conceito novo — `Medida`:** cada rede conta o texto do seu jeito, e os três dão números
  muito diferentes. `👨‍👩‍👧‍👦` = **1 grafema · 7 caracteres · 25 bytes**. O Bluesky conta
  **grafemas** (eu usava caracteres — recusava texto válido); a descrição do YouTube é medida em
  **bytes** ("coração" gasta 9, não 7 — legenda com acento estouraria depois do upload inteiro).
  **Outras correções contra a fonte oficial:** só **404** é sessão vencida (5xx preserva o que já
  subiu — eu recomeçava do zero) · **10** motivos de recusa, não 6 · `uploaderAccountClosed` e
  `uploaderAccountSuspended` **derrubam a CONTA**, não o vídeo · 6 motivos de falha traduzidos ·
  Bluesky aceita **só `video/mp4`** (o `.mov` do iPhone era recusado depois do upload) · teto do
  Bluesky é **100 MB**, não os 50 que eu tinha chutado.
  **Achados que viraram recurso:** `autoLevels=false` + `stabilize=false` explícitos (o YouTube
  pode alterar a imagem, e isso contraria o DEC-33) · `notifySubscribers` **desligado por
  padrão** (vem ligado na API — publicar em lote notificaria a cada corte) ·
  `containsSyntheticMedia` e `selfDeclaredMadeForKids` como escolha da pessoa ·
  **`publishAt`: o YouTube agenda sozinho** · **`definition: hd|sd` = a rede admitindo se
  degradou o vídeo**, que nenhum concorrente mostra.
  **Conformidade:** links dos Termos e da Privacidade na tela (exigência literal) · comando
  **`youtube:reconferir`** diário (a política exige atualizar o dado **e** confirmar a
  autorização a cada 30 dias — uma consulta resolve as duas e alimenta o semáforo) ·
  desconectar **apaga o dado do titular** e preserva o evento (DEC-44) · modal de consentimento
  antes do OAuth, porque a pessoa sai da tela e não voltaria para ler.
  **⭐ 3 bugs que só apareceram ao testar:** cota estourada na abertura da sessão virava
  retentativa genérica (queimaria as 3 tentativas contra uma cota que só volta amanhã) · eu
  perguntava "quanto já subiu?" em sessão recém-criada, uma ida à rede desperdiçada em todo
  envio · a máquina de estados **não previa `enviando → aguardando_janela`**, e a cota só é
  descoberta ao chamar a API.
  **Decisão pendente do Gabriel:** `videos.update` e `videos.delete` pedem **os mesmos escopos**
  — não há como pedir "editar" sem levar "apagar". Com o escopo mínimo de hoje, vídeo publicado
  antes da auditoria **fica privado para sempre** do nosso lado.
- 2026-07-31 — **🌐 As 14 redes do mapa entraram na tela (193 testes verdes).** Gabriel pediu
  todas as propostas do doc 10. Entraram **14**; ficaram de fora **duas, de propósito**:
  **Reddit** (o plano gratuito proíbe uso comercial) e **Slack** (é chat interno, não rede de
  publicação) — mostrar o que a pesquisa já descartou seria prometer o que não vai existir.
  **Conceito novo — `SituacaoDaRede`,** porque "em breve" não dava conta: o YouTube tem caminho
  definido, o Pinterest ainda é ideia. Agora são três estados: **Pronta para usar** (Bluesky),
  **Aguardando aprovação** (LinkedIn, YouTube, Instagram, Facebook, Threads, TikTok) e
  **Em estudo** (as outras 7). Chamar tudo de "em breve" seria prometer o que ninguém decidiu.
  **Consequência no laudo:** só entram nele as redes com **regra pesquisada**. Rede em estudo
  apareceria como "aceita"/"não aceita" sem ninguém ter conferido nada — o laudo perderia
  exatamente o que o torna útil. `EspecificacaoDaRede::de()` **lança exceção** para rede em
  estudo, e há teste travando isso. LinkedIn e Threads ganharam spec (limites do doc 10).
  **Visual:** cartas quase quadradas em grade de até 5 colunas (2 no celular), selo centralizado
  — a versão anterior era retangular demais e parecia formulário. Rede indisponível não tem
  botão: só um texto, que não convida a clicar. O parágrafo explicativo do rodapé saiu — a
  própria carta já diz o estado.
- 2026-07-31 — **🔌 Conexões refeita como GRADE DE REDES (191 testes verdes).** Gabriel apontou
  o print `bundle-social/03-social-accounts-topo.png` — e ele tinha razão: a referência
  organiza melhor. Eu tinha feito **um formulário com uma lista solta de contas**; a
  referência mostra **uma carta por rede**, e o estado de todas se lê num relance.
  **Refeito:** grade de cartas (uma por `Plataforma`), cada uma com o selo da rede, as contas
  conectadas embaixo com o semáforo (DEC-32), e a ação no rodapé. Conectar virou modal, com a
  explicação do DEC-41 dentro. O botão "Conectar outra" deixa explícito que dá pra ter
  **várias contas por rede** (DEC-10) — antes isso não aparecia em lugar nenhum.
  **O que mudou de decisão:** antes eu escondia as redes ainda não aprovadas, para não
  "prometer o que não entrega". A referência mostrou o melhor equilíbrio: **mostrar todas**,
  com as indisponíveis apagadas e o botão **desabilitado dizendo "Aguardando aprovação"**.
  Assim a pessoa vê o alcance do produto sem levar um 404 — mostrar e explicar é mais honesto
  que esconder.
  **Selo de marca próprio** (letra + cor) em vez do logotipo oficial: logotipo tem regra de
  uso (margem, fundo, proporção) e vira risco jurídico se aplicado errado.
- 2026-07-31 — **🌐 Traduções pt_BR + campo de senha com "ver" (190 testes verdes).**
  Gabriel relatou "auth failed" na tela e "coloco a senha certa e diz que é inválida".
  **Medido antes de opinar:** o login **funcionava** (302 → painel, confirmado por curl). O
  defeito era outro, e pior que inglês: **o Laravel 11+ não traz os arquivos de tradução**, e
  como `APP_FALLBACK_LOCALE` também é `pt_BR`, a tela mostrava a **chave crua** —
  literalmente `auth.failed`. Quando ele errou algumas vezes e bateu no limite de tentativas,
  apareceu `auth.throttle` cru, **indistinguível de "senha inválida"** — daí a impressão de
  que a credencial certa era recusada.
  **Criados** `lang/pt_BR/{auth,validation,passwords}.php`, com `attributes` traduzindo os
  nomes dos campos ("Preencha **o e-mail**", não "Preencha email") e tom sem pânico
  (mensagem única para senha errada e conta desativada — diferenciar entregaria quais e-mails
  existem).
  **`CampoSenha`** criado e aplicado em **11 campos** de 7 telas: digitar senha às cegas é a
  causa nº 1 de "minha senha está certa e diz que não está", principalmente no celular. O
  botão fica fora da ordem de tabulação, para não atrapalhar quem usa teclado.
  **Guardião novo** (`TraducoesTest`): quebra se qualquer mensagem voltar a vazar a chave crua,
  e cobre justamente o caso do limite de tentativas.
- 2026-07-30 — **🖥️ MÓDULO 3 — TELAS: o fluxo fecha ponta a ponta pelo navegador (184 testes).**
  **Conexões:** conectar Bluesky por senha de aplicativo (validada **antes** de guardar —
  senha errada salva viraria conta que parece conectada e falha na hora de publicar),
  semáforo de saúde por conta (DEC-32), desconectar **preservando a linha** (o histórico
  aponta pra ela). A tela explica **o que pedimos e o que não pedimos** (DEC-41). Só aparece
  a rede que publica de verdade hoje.
  **Publicar:** escolhe mídia, escreve uma vez, marca as contas; painel lateral mostra
  **antes de enviar** o que cada rede fará com o arquivo e conta os caracteres.
  **Publicações:** cada destino com o próprio estado, e o **link "ver o post" só aparece
  depois da conciliação** — enquanto isso a tela diz "Processando na rede".
  **Decisão:** `EnvioDePublicacao` separado do `PublicacaoService`. Um decide *o que enviar e
  para onde*; o outro é dono da máquina de estados. Juntos, a regra de negócio poderia mexer
  em status — o que a máquina existe para impedir. Conta desconectada e legenda longa demais
  são barradas **antes de a publicação existir**: deixar passar criaria destino que já nasce
  condenado. Menu liberado (a marca `emBreve` saiu de Publicar, Publicações e Conexões).
- 2026-07-30 — **⚙️ MÓDULO 3 — MOTOR DE PUBLICAÇÃO: núcleo pronto, 160 testes verdes.**
  **O que existe agora:** 5 tabelas (`contas_sociais`, `credenciais`, `publicacoes`, `destinos`,
  `tentativas`), a máquina de estados completa, o contrato `Publicador`, o `PublicadorBluesky`,
  os dois jobs e o watchdog agendado.
  **⭐ DEC-31 virou código e está travado por teste.** O caminho é
  `pendente → enviando → processando → publicado`, e **não existe atalho**: teste garante que
  `pendente → publicado` lança exceção, e que `marcarPublicado` **sem o link** também lança.
  `processando` significa *"a rede aceitou, ainda não confirmamos"* — o job de conciliação relê
  o post e **só então** grava a prova. Há teste do caso que os concorrentes chamam de sucesso:
  a rede aceita e depois a moderação apaga → aqui vira **falha com motivo**, não "publicado".
  **`PublicacaoService` é escritor único.** Ninguém mais toca `status`. `Publicado` é terminal;
  retry ≠ falha (volta pra fila sem mexer no agregado nem alarmar); agregado da publicação
  recalculado **só em estado terminal**, com lock do pai.
  **Anti-double-post:** job idempotente (só trabalha destino `pendente`), `timeout` de 900s
  maior que o maior upload, `handle_externo` para retomada, UNIQUE por (publicação, conta).
  **Watchdog** a cada 5 min devolve à fila destino travado — sem ele a tela diz "Enviando…"
  para sempre.
  **⭐ 3 bugs meus que os guardiões pegaram** — o valor deles ficou provado na prática:
  (1) o `PublicacaoService` lia `contaSocial`/`publicacao` pelo escopo do dono e **estouraria no
  worker**, que não tem sessão; (2) publicação com destinos já enviando continuava aparecendo
  como **rascunho** (o agregado deduzia do status anterior em vez de `enviada_em`); (3) o
  serviço tinha um atalho "mesmo estado = no-op" que deixaria um **envio duplicado passar em
  silêncio** — removido: idempotência é decidida pelo job, que confere o estado antes de agir.
  **Bluesky escolhido para começar (DEC-29)** porque autentica por senha de aplicativo e
  **não depende de auditoria** — dá pra publicar de verdade hoje. ⚠️ Os limites dele
  (300 caracteres, 50 MB, 180s) estão marcados no código para **revalidar antes do primeiro
  post real**. As outras 4 redes lançam erro explícito em vez de ficarem "na fila" para sempre.
  **Falta no módulo:** telas de conectar conta, compor e acompanhar publicação; e o
  `ffmpeg` executando a recodificação que o laudo já promete.
- 2026-07-30 — **🔍 REVISÃO pós-Módulo 2 — 3 achados, todos corrigidos.** Gabriel deu autonomia
  para decidir; verifiquei cada suspeita contra o real antes de mexer (0.L#1).
  **1. 🔴 "Continuar conectado" não era derrubado ao trocar a senha estando logado.** Redefinir
  pelo e-mail regenerava o `remember_token`; trocar a senha pelo painel, **não**. Consequência
  real: notebook perdido, a pessoa troca a senha pelo celular achando que resolveu — e o
  notebook **continua entrando** pelo cookie. Corrigido, com dois testes.
  **2. 🔴 Cinco itens do menu davam 404.** A barra lateral listava Publicar, Publicações,
  Conexões, Impersonações e Plataforma — telas que ainda não existem. Item de menu que quebra é
  pior que item ausente. Criada a marca `emBreve`: o item aparece apagado, com selo, e não é
  clicável; no celular ele nem entra na barra de baixo (espaço escasso). **Ao construir a tela,
  apagar a marca.**
  **3. Cookie de "continuar conectado" durava 400 dias** (padrão do Laravel, confirmado no
  vendor: `forever()` = 576000 min). Para um produto que guarda token de rede social — quem tem
  a sessão publica no nome do cliente — é tempo demais. **Decidido: 30 dias.** Quem usa toda
  semana nunca é desconectado; aparelho esquecido deixa de ser porta de entrada por mais de um
  ano.
  **Junto, fechado o último item do admin:** tela de **Impersonações** (histórico de acessos de
  suporte, só leitura). Não é enfeite — é a resposta a *"quem entrou na minha conta?"*, direito
  do titular na LGPD. Mostra acesso em andamento e exibe "conta removida" quando o cliente
  apagou a conta (DEC-44 em ação). **131 testes verdes.**
  *(Verificado e descartado como não-problema: `usuario_id` fora do `$fillable` de `Midia` —
  as factories do Laravel usam `Model::unguarded`, e o código de produção preenche pela trait.)*
- 2026-07-30 — **✅ MÓDULO 2 (MÍDIA) ENTREGUE — 122 testes verdes, upload validado no navegador.**
  **1. Fundação do isolamento (o mais importante, e o motivo de vir primeiro).** `midias` é a
  primeira tabela com dono, então o padrão nasceu com **1 tabela** em vez de ser retrofitado em
  9: trait `PertenceAoUsuario` + `EscopoDoUsuario` (Global Scope) + `ContextoDoUsuario`.
  ⭐ **A armadilha do worker foi resolvida de frente:** sem dono definido a consulta **lança
  exceção**, em vez de virar `WHERE usuario_id IS NULL` — que devolveria lista vazia sem erro e
  só apareceria como "sumiu tudo". Job declara o dono com `ContextoDoUsuario::definir()`;
  varredura de propósito usa `semEscopo()`, com `finally` para uma exceção lá dentro não deixar
  a aplicação inteira sem isolamento. **10 testes** de isolamento, incluindo criar/ler/alterar/
  apagar dado alheio e o comportamento durante impersonação.
  **2. ⭐ Laudo de mídia (DEC-32/33) — o diferencial nº 1, funcionando.** `InspetorDeMidia`
  (ffprobe) + `EspecificacaoDaRede` com os limites das 4 redes do perfil canônico (07 §6).
  Todo achado traz **o que está fora E o que será feito** — achado sem providência quebra o
  teste, de propósito: "deu erro" e nada mais foi a experiência que os concorrentes entregam.
  Diz **"passa intacto, sem perder qualidade"** quando o codec serve (o oposto de recodificar
  tudo por padrão), aceita **HEVC do iPhone** (Buffer e Metricool recusam), e barra 91–180s
  **só no Facebook**. **Ausência do ffprobe degrada, não quebra:** upload segue e a tela avisa.
  **16 testes**, sendo 4 contra vídeos reais gerados pelo próprio ffmpeg (`tests/Fixtures/`).
  **3. Upload com as regras de arquivo da 0.M Camada 5.** Validação por **conteúdo**
  (`mimetypes`, que lê o arquivo — a regra `mimes`, que parece igual, olha a extensão);
  arquivo **fora da raiz pública** (`storage/app/private`); nome **gerado por nós** (o nome
  enviado nunca vira caminho — teste com `../../../` prova); download só por rota que confere o
  dono, com `nosniff`; falha ao registrar apaga o arquivo do disco. **14 testes.**
  **4. Ponta solta do Módulo 1 fechada:** lista de clientes do admin com busca, contador de
  mídias, tirar/devolver acesso, e o **botão de impersonar** — a impersonação existia mas só
  tinha rota. **9 testes.**
  **Fica para depois, de propósito:** miniatura do vídeo (exige extrair quadro com ffmpeg) e a
  recodificação em si (o laudo já promete; quem executa é o motor, Fase 3).
- 2026-07-30 — **🎬 FFmpeg instalado e documentado (pré-requisito do Módulo 2).** `ffprobe` e
  `ffmpeg` **não são bibliotecas do projeto** — são programas do sistema, e essa confusão é
  justamente o que faria o módulo de mídia travar na hora errada. Instalados em
  `C:\tools\ffmpeg` (v8.1.2, pacote *essentials*) e adicionados ao PATH do usuário.
  **Ligados ao projeto sem depender do PATH:** `config/midia.php` +
  `FFPROBE_CAMINHO`/`FFMPEG_CAMINHO` no `.env` — em hospedagem o usuário do PHP costuma ter
  PATH diferente, e o recurso falharia em produção funcionando em dev. Criado o comando
  **`php artisan midia:verificar`**, que responde em uma linha se o servidor consegue
  inspecionar vídeo e, se faltar, ensina o passo a passo — sem ele a falta só apareceria como
  "comando não encontrado" na cara de quem sobe o servidor.
  **Consequência anotada na Fase I:** a hospedagem **precisa permitir executar programa
  externo** — plano compartilhado barato costuma proibir, então tem que ser VPS.
  Documentado em README (seção própria), CLAUDE.md e nas Fases I e 2.
- 2026-07-30 — **🏷️ Nome do produto extirpado do projeto (0.N + DEC-45).** Gabriel: *"não
  paramos de falar o termo, mas o nome ainda não foi decidido — tenho medo de depois ter que
  mudar tudo"*. Medido antes de opinar: **3 ocorrências no código** (fallback do `app.tsx` +
  2 e-mails de seed) e **11 na documentação**. Todas eliminadas. Agora a fonte é **só**
  `APP_NAME` no `.env`; o `app.tsx` não tem mais fallback com o nome; e-mails de teste passaram
  a `@local.test`; a doc diz "o produto"/"o painel"; **`README` criado** como único lugar com
  os caminhos reais (pasta, repositório, casca antiga). Regra virou **0.N** no CLAUDE.md e no
  SEC.0, com grep-zero como verificação. **Custo de renomear hoje: uma linha do `.env` + o
  `README`.** Anotado que **o prazo real é a primeira submissão às plataformas** — o nome entra
  na tela de consentimento auditada — e que o doc 08 já proíbe nome com "YouTube".
  75 testes verdes depois da troca.
- 2026-07-30 — **🐞 BUG ACHADO E CORRIGIDO + 3 camadas baratas-agora (DEC-44).** Ao revisar
  "o que é barato hoje e caro depois", achei um defeito **que eu mesmo tinha introduzido**:
  `logs_impersonacao` com FK `restrict` (como o glossário mandava) fazia com que **um cliente
  que já tivesse recebido suporte nunca mais conseguisse apagar a própria conta** — 500 em
  produção, e um log bloqueando o direito de eliminação da LGPD (art. 18). Reproduzido em
  teste antes de corrigir. **Regra nova, que vale pra toda tabela de log:** *dado pessoal se
  apaga; registro de acesso sobrevive anonimizado* → FK `nullOnDelete` **+ cópia do ULID**
  (sem o ULID sobra uma linha de nulos, que não responde "quantos acessos houve naquela
  conta?"). Glossário §Regras de deleção corrigido — ele estava se contradizendo com a
  própria tela, que promete que a conta some.
  **Junto, três camadas que custam pouco agora e muito depois:**
  **(1) Canal de log de segurança separado** (0.M cumprido) — `storage/logs/seguranca.log`,
  180 dias (Marco Civil art. 15), com `RegistroDeSeguranca` como porta única e uma lista de
  chaves proibidas (senha/token/cookie viram `[removido]` mesmo se alguém passar por engano).
  Ligado em entrar · sair · trocar senha · apagar conta · impersonar · sair da impersonação.
  **(2) Cabeçalhos de segurança + cookie HTTPS** — `nosniff`, `X-Frame-Options: DENY`,
  `Referrer-Policy`, `Permissions-Policy`, HSTS só em produção; `session.secure` passou a ter
  default **por ambiente** (o padrão do Laravel é `null`, que deixa a sessão trafegar em HTTP
  se ninguém lembrar da variável no servidor). São itens que a auditoria da Meta/TikTok
  confere, e apertar depois obriga a testar o site inteiro de novo.
  **(3) `usuarios.fuso_horario`** — uma coluna enquanto a tabela está vazia. O banco guarda
  UTC e `Usuario::noSeuFuso()` é o **ponto único** de conversão; sem isso, a mesma publicação
  aparece com horários diferentes em telas diferentes e ninguém sabe qual está certo. O Brasil
  tem 4 fusos e o produto inteiro gira em torno de "que horas isso foi publicado".
  **75 testes verdes.** Deliberadamente fora: CSP (precisa do inventário de assets, entra com
  o módulo de mídia) e rodar a suíte contra MariaDB (entra junto com a infra da Fase I).
- 2026-07-30 — **DEC-43 criada (cargos).** Gabriel levantou que vamos precisar de
  representante comercial e afins. Confirmado que o enum aguenta, mas travada agora a parte
  cara-depois: **papel × escopo**. `Papel::ehOperador()`/`rotaInicial()` viraram `match` sem
  `default` (papel novo sem resposta explode na hora, em vez de herdar o alcance do admin —
  a falha do EmpiresCloud, onde `suporte`/`dev` viam o MRR da plataforma); rotas de `/admin`
  passaram a usar `Papel::listaDeOperadores()`; impersonação agora barra **qualquer operador**;
  guardião cobra lado + tela inicial + rótulo de todo case. Glossário §3 ganhou a distinção e
  o roteiro de "como nasce um papel novo". `comercial`/`suporte` **não** foram adicionados
  ainda — papel sem tela e sem limite é vazamento esperando acontecer. 65 testes verdes.
  Também renomeado `adminPorTras` → `adminResponsavel` (conotação indevida; grep-zero).
- 2026-07-30 — **✅ MÓDULO 1 (ACESSO) ENTREGUE — roda em `localhost`, 63 testes verdes.**
  **Base:** `.env` em pt_BR (`APP_LOCALE`/`FALLBACK`/`FAKER`), `APP_TIMEZONE=UTC` mantido de
  propósito (guarda em UTC, converte por usuário — o Brasil tem 4 fusos). **DEC-06 revisada:
  fica no Laravel 12** — o PHP do XAMPP não sobe agora e L12 atende o módulo inteiro.
  **Identidade visual:** paleta slate+índigo do projeto antigo (DEC-23), com **correção** — no
  escuro o `--accent` era cinza `#1f2937` e a marca sumia; ficou índigo `#6366f1`. Sistema
  fluido do EmpiresCloud (DEC-37) nas proporções exatas: fonte `clamp(13px, 9px + 0.3125vw, 15px)`,
  sidebar `clamp(200px, 120px + 6.25vw, 240px)` (tablet 180→200, celular fonte 14px fixa).
  Variáveis `--saude-*` criadas para o semáforo de conexão (DEC-32).
  **Fundação:** tabela `usuarios` (`nome`/`papel`/`ativo`/`google_id`/`ulid` único), enum
  `Papel` + `lang/pt_BR/rotulos.php` (DEC-18 em uso real), **ULID na rota** — o id sequencial
  nunca sai do servidor. `password` nullable (conta nasce por Google ou convite).
  **Tudo em PT-BR:** controllers (`Acesso/`, `MinhaConta/`, `Admin/`), rotas (`/entrar`,
  `/cadastrar`, `/esqueci-a-senha`, `/painel`, `/admin/painel`, `/minha-conta/*`), páginas
  React, hooks e componentes. **Território do framework respeitado** (lição `senha_hash`):
  `password`, `remember_token`, `email_verified_at` e **`sessions.user_id`** ficam em inglês —
  esta última é cravada no `DatabaseSessionHandler` e renomeá-la quebraria a gravação de sessão
  (verificado no vendor antes de decidir). Os 4 pontos em que o Laravel exige nome inglês têm
  desvio declarado **em um lugar só**: `redirectGuestsTo`/`redirectUsersTo` no `bootstrap/app.php`,
  `ResetPassword`/`VerifyEmail::createUrlUsing()` no `AppServiceProvider`, e
  `RequirePassword::using('senha.confirmar')` na rota.
  **Segurança na base (0.M):** `Model::shouldBeStrict()` **em todo ambiente** (o erro de
  mass-assign aparece no teste, não em produção); senha mínima de **12** caracteres +
  `uncompromised()` em produção; `password_timeout` de 3h → **30 min**; conta desativada
  recebe **a mesma mensagem** de senha errada (não revela que o e-mail existe); "esqueci a
  senha" responde igual para e-mail que não existe; papel enviado no formulário é **ignorado**
  no cadastro; `HandleInertiaRequests` monta o usuário **campo a campo** — coluna nova não
  vaza pro navegador sozinha.
  **Impersonação (DEC-04):** `ImpersonacaoService` é **escritor único** da sessão;
  `logs_impersonacao` grava quem/em quem/quando/IP e fecha na saída; exige **confirmação de
  senha**; barra impersonar outro admin, impersonação aninhada e ULID inexistente; durante a
  impersonação o papel efetivo é o do **cliente** (o admin não entra em `/admin`); tarja fixa
  de "Modo suporte" com botão de voltar. Rota de saída fica **fora** do grupo `papel:admin` —
  senão o admin ficaria preso na conta do cliente.
  **Interface:** sidebar própria (a do shadcn cravava a largura inline e atropelava o sistema
  fluido) — no celular vira **barra inferior** com `env(safe-area-inset-bottom)` e alvo de
  toque de 56px. Navegação por **tarefa** (DEC-17), 5 itens no cliente e 4 no admin.
  **63 testes** cobrindo entrada, cadastro, senha, perfil e os 4 guardiões (papel, rótulos,
  rotas, vazamento, impersonação) — incluindo um que varre **todas** as rotas de painel
  cobrando o middleware `papel:` e outro que barra nome de rota em inglês do starter.
  **Validado no navegador de verdade** (curl com cookies, sessão em banco): login → painel por
  papel → impersonar → tarja → sair → registro fechado.
  **Faltou de propósito:** login Google (DEC-05) e tela de listagem de clientes do admin —
  entram no Módulo 2, junto com a mídia.
- 2026-07-30 — **Negócio modelado e plano remodelado.** Criados **`19-modelo-de-negocio.md`**
  (o molde: dor validada → proposta → posicionamento → público → preço → filtro de escopo) e
  **`20-evidencias-do-mercado.md`** (destilado de dois relatos reais; transcrições brutas
  apagadas após extração — 0.C). O plano ganhou **o negócio no topo** e um **índice de
  consulta**; a Definição de Pronto ganhou o item 0 (*serve à promessa?*).
  **Validação decisiva:** um usuário testou Metricool, Buffer e Hootsuite com **o nosso caso de
  uso exato** — as três falham em Shorts (*"sem miniatura, sem descrição, sem tags"*) e a
  solução dele terminou **parcialmente manual**. Degradação de vídeo confirmada em **4 fontes**.
  E a desculpa de *"limitação de API"* da Metricool foi **provada falsa** (o Buffer publicou o
  mesmo arquivo). **DEC-41** (permissão mínima e explicada — medo documentado) e **DEC-42**
  (salvar rascunho automático) criadas. **40 prints organizados** em `referencias-layout/`.
- 2026-07-28 — **`14-telas.md` criado** (especificação tela a tela, com o comportamento mobile
  de cada) + **DEC-17 revisada** (navegação por **tarefa**, rede vira **filtro** — a sidebar
  fica em 5 itens fixos mesmo com 15 redes; a versão "um item por rede" foi descartada porque
  duplicava telas e estourava o menu; validado observando o bundle.social, que suporta 15
  redes e organiza assim) + **DEC-38 criada** (mobile é requisito, não adaptação: tabela vira
  card expansível, ações no cabeçalho do card, filtros fixos, sidebar vira gaveta).
  Doc 08 ganhou o **prazo real da auditoria do YouTube** (sem SLA; semanas é o rotineiro, com
  caso documentado de **5 meses** — planejar 2–3 meses e não ficar parado).
- 2026-07-28 — **🔄 ORDEM REVISADA (decisão do Gabriel): uso próprio primeiro, plataforma
  depois.** O critério das fases deixou de ser "importância da rede" e passou a ser **o que
  fecha sem depender de terceiros**. **DEC-13 reordenada** · **DEC-20 revisada** (as exigências
  de plataforma só valem a partir da decisão de virar produto) · **DEC-36 criada** (construir
  tudo, liberar conforme aprova — separa "estar pronto" de "estar liberado").
  **Descoberta que encurta semanas:** Instagram e Facebook **em conta própria dispensam App
  Review**; o YouTube pra uso próprio precisa só da **auditoria de compliance** (a verificação
  OAuth não). **O TikTok é o único que exige virar produto** — eles rejeitam ferramenta de uso
  próprio, então ele é construído e testado em privado, e submetido só quando for a hora.
  Fases reorganizadas: **3** (motor + Bluesky + IG + FB) · **4** (YouTube) · **5** (TikTok) ·
  **6** (demais redes) · **7** (virar plataforma).
- 2026-07-28 — **Pesquisa de diferenciais → `13-diferenciais.md`** (22 fortes · 15 promissores ·
  8 commodity). Achado que virou tese: **HTTP 200 ≠ publicado** — TikTok e YouTube moderam
  depois, e **nenhum dos 9 concorrentes relê o post pra confirmar**. **DEC-31** (posicionamento
  "o painel que prova que publicou", R$ 49–79/marca), **DEC-32** (4 diferenciais baratos no MVP:
  status honesto · monitor de token · laudo de mídia · WhatsApp), **DEC-33** (vídeo: passthrough
  + corrigir só o necessário + aceitar HEVC do iPhone), **DEC-34** (Stories automático — a mLabs
  desligou e perdeu cliente por isso), **DEC-35** (futuro: API de publicação em pt-BR com NF).
  **Glossário alterado:** `StatusDestino` ganhou o estado **`processando`** e a regra de que é
  **proibido marcar `publicado` sem reler o post e obter o link** — o motor passa a ser honesto
  por construção. Fases 2 e 4 atualizadas.
- 2026-07-28 — **Regras compiladas: `09-regras-das-redes.md` com 317 regras** (YouTube 108 ·
  Meta 115 · TikTok 94 · Brasil 13), extraídas das políticas oficiais, cada uma com código e
  caixa de marcação. Achados que mudam produto: **botão único "publicar em todas" é proibido
  pelo YouTube** (a ação dele precisa ser identificável e separada — YT-A07); proibido
  **agregar/comparar dados entre clientes** (YT-E14); proibido **cortar o texto do cliente**
  (YT-B05); proibido **marca d'água do painel no vídeo** (TK-D01); TikTok exige **textos
  literais em inglês na tela** (TK-A15..A24).
- 2026-07-28 — **Redes adicionais mapeadas → `10-redes-adicionais.md` (12 redes).**
  **DEC-29:** criada a **Fase 3A** com **Bluesky + LinkedIn (perfil)** como rede piloto —
  **zero aprovação, zero custo**, publicam 9:16 e imagem; o motor fica testado em produção em
  dias enquanto as auditorias correm. **DEC-30:** **Threads entra na mesma submissão** de
  IG/FB (carona: mesmo app, mesma verificação; fluxo idêntico ao IG; limites mais generosos).
  DEC-13 reordenada. Descartados: Slack (chat interno), Reddit (free tier proíbe uso
  comercial), Google Business (**API não aceita vídeo**). Avaliar depois: Pinterest (nativo
  9:16), LinkedIn Página (alto valor, alta barreira), X (sem aprovação, mas **pago por post** —
  US$ 0,015, ou US$ 0,20 com link).
- 2026-07-28 — **🔓 DECIDIDO: PLATAFORMA ABERTA (DEC-20).** Clientes se cadastram e conectam
  as próprias contas. Consequências travadas: **DEC-21** (domínio + host público + política
  de privacidade + termos = pré-requisito) e **DEC-22** (MariaDB em produção, SQLite só em
  dev — workers concorrentes + clientes reais). Criada a **Fase I (infra e trâmites)**, que
  começa **já** e roda em paralelo às fases 0–2, separando o que independe de código
  (domínio, política, verificação da empresa na Meta, projeto Google, aumento de quota) do
  que exige integração pronta (verificação OAuth, auditoria YouTube, App Review, audit
  TikTok). Revogados nos docs 02/03/05/07 os textos de "uso pessoal / 1 << 100 / ok pra nós /
  folga total"; doc 01 §10 reescrito com o quadro de trâmites.
- 2026-08-05 — **Métricas de rede levantadas nas 9 APIs e entregues no recorte possível →
  `17-plano-metricas.md` (DEC-93..98).** A consulta à documentação oficial devolveu uma matriz
  **desigual**, e é ela que define o tamanho: o **número de agora** sai hoje em YouTube e Bluesky
  sem pedir permissão nova a ninguém; o **gráfico ao longo do tempo não sai** (DEC-93) — exige
  `yt-analytics.readonly`, e mexer na submissão de um app **em verificação** recomeça a fila que
  trava o produto; exige máquina ligada todo dia, e dia desligado é buraco permanente porque
  nenhuma rede devolve "quantos seguidores eu tinha em tal dia"; e nas outras redes ou não existe
  ou é **proibido guardar** (o Pinterest diz literalmente *"call the API each time"*). Travadas:
  **DEC-94** um bloco por rede, nunca tabela comparativa — coluna igual obriga a inventar célula, e
  *visualização* não é a mesma coisa em duas redes; **DEC-95** `null` é resposta com frase própria e
  **zero é outra coisa** (quatro situações virariam 0: a rede não tem o número, o dono escondeu, a
  rede não calculou, não lemos); **DEC-96** métrica fora do contrato do `Publicador` — interface
  `LeitorDeMetricas` à parte, porque amarrar o contador à prova faria a **prova** depender de uma
  cota que pode acabar; **DEC-97** sem histórico, coluna sobrescrita — as Políticas do YouTube
  proíbem métrica derivada dos dados deles, e *"ganhou 12 inscritos hoje"* por subtração é
  exatamente isso; **DEC-98** ler métrica não pode derrubar publicação.
  ⛔ **Defeito pré-existente corrigido antes de tudo (DEC-98):** `ReconferirContasDoYoutube`
  transformava **qualquer** resposta fora do 2xx em lista vazia, e lista vazia marcava a conta como
  `Erro` — o que **bloqueia publicar** até reconectar na mão. Um 403 de cota ou um 500 do Google
  desligavam a publicação de todos os clientes do YouTube. Nasceu `ErroDoGoogle`, que separa a falha
  que passa da que fica pelo `error.errors[].reason`.
  ⚠️ **Correção à pesquisa, confirmada no código:** os contadores do Bluesky **não** vêm de graça na
  conciliação. Ela usa `com.atproto.repo.getRecord` (lê o repositório do autor, prova mais forte);
  os contadores vivem no AppView, em `getPosts`. É uma chamada a mais, e a conciliação não muda.
  Entregue: colunas `seguidores`/`metricas_lidas_em` em `contas_sociais` e
  `visualizacoes`/`curtidas`/`comentarios`/`compartilhamentos`/`metricas_lidas_em` em `destinos`
  (todas anuláveis, **fora do `fillable`** — escrita só por máquina); leitores de YouTube e Bluesky;
  comando `metricas:atualizar` diário às 05:10 (separado do `youtube:reconferir` das 04:20, que
  consome a mesma cota); contador na janela da rede e ao lado da prova, na linha da publicação.
  Cópias locais da documentação em `planos-de-redes/youtube/documentacao/05-estatisticas.md` e
  `planos-de-redes/bluesky/documentacao/02-contadores.md`. 386 testes verdes (20 novos).
  **Confirmado no real:** a conta do YouTube conectada foi lida com o escopo `youtube.readonly` que
  o app **já pede** — nenhuma permissão nova.
- 2026-08-06 — **Gráfico de comparação entre posts + Visão geral reorganizada + botão único de
  grupo.** (a) **Gráfico:** *"Seus posts no X, por Y"* — barras na medida compartilhada, **um por
  rede** e cada uma na medida que ELA publica (`Plataforma::metricaDeComparacao()`: YouTube por
  visualização, Bluesky por curtida, porque o Bluesky não tem visualização). Sai da lista inteira do
  grupo, não da página aberta, e só na aba "Tudo" — gráfico que ignora o filtro ao lado de uma lista
  que o obedece são dois números para o mesmo fato. Teto de 8 barras; menos de 2 não vira gráfico.
  ⭐ **Zero em tudo é ESTADO com frase, não gráfico vazio:** no YouTube é o esperado enquanto a
  auditoria não passa (vídeo privado não recebe visualização), e sem a frase a tela pareceria
  quebrada justamente quando está certa. Reusa `BarraDeEntrega` — nenhum código de gráfico novo, e a
  troca por biblioteca continua sendo um arquivo só (DEC-92).
  (b) **Visão geral:** faixa de indicadores no topo (tamanho fixo, zero em cinza) e corpo em duas
  colunas com trilho fixo de 320px à direita para avisos e redes. ⛔ **O bloco "Como está" foi
  removido**: repetia os três números da faixa por extenso, e era placar somado desde sempre, que só
  sobe — o que não subiu é tarefa, não placar. `fraseDaEntrega()` saiu junto (grep-zero).
  (c) **Gerenciar grupos:** três ícones por linha viraram **uma engrenagem** com menu escrito —
  *Renomear · Editar conexões · Excluir*, com o motivo do bloqueio escrito embaixo. ⚠️ *Editar
  conexões* ganhou `preserveState`, que é o que faz a janela de grupos continuar aberta por baixo do
  catálogo: sem ele o Inertia remonta a página, o seletor do topo remonta junto e leva a janela
  embora no meio do gesto. 392 testes verdes (6 novos, do gráfico).
- 2026-08-06 — **Barra lateral refinada + tema ao alcance de um clique.**
  ⭐ **A regra nova é o EIXO DOS ÍCONES:** todo ícone da barra vive num compartimento de largura
  fixa derivada da largura recolhida (`max(calc(var(--sidebar-width-collapsed) - 1rem), 3rem)`), e o
  centro desse compartimento é o mesmo aberta ou fechada — o ícone **não anda para o lado** enquanto
  a largura anima. Antes o item trocava `justify-center` por `padding` conforme o estado, e a coluna
  inteira tremia no meio da transição. Vale para os itens de menu, o seletor de tema e o avatar.
  ⚠️ **O rótulo deixou de ser removido do DOM ao recolher** — ele é *cortado* pela borda
  (`whitespace-nowrap` + `overflow-hidden`). Removido, sumia de uma vez no primeiro quadro e voltava
  de uma vez no último: lia como piscada, não como deslizamento.
  ⭐ **O cabeçalho inteiro virou o botão de recolher**, com `ChevronsLeft`/`ChevronsRight`; o botão
  "Recolher menu" do rodapé saiu (era o mesmo gesto, longe da borda que se mexe). Recolhido, a seta
  ganha um empurrãozinho de 3px a cada 1,6s (`.dica-de-expandir`) — a barra fechada é uma coluna de
  ícones e nada nela dizia que ela abre. ⛔ Só na seta de expandir, e desligada em
  `prefers-reduced-motion`: animação infinita é a primeira a incomodar quem pediu menos movimento.
  ⚠️ O cabeçalho é a única parte **fora** do eixo, de propósito: em 68px, símbolo (32px) e seta não
  cabem com o símbolo preso no eixo. Ele é bloco de marca, não item de menu.
  Entregue também: `components/ui/tooltip.tsx` (o `title` do navegador demora quase um segundo, não
  segue o tema e não existe no toque) — dica só quando a barra está recolhida, porque com ela aberta
  o nome já está escrito ao lado; e **seletor de tema no rodapé da barra**, lendo e escrevendo o
  mesmo `useAparencia` de Minha conta → Aparência, sem estado paralelo. ⛔ Menu com as três opções,
  não botão que cicla: ciclar obriga a clicar até acertar e nunca deixa "do sistema" ser escolhido
  de propósito. ⚠️ Ele ficou na **barra do topo, à direita do seletor de grupo** — e só como ícone:
  os dois dividem a mesma linha, e o grupo é o que precisa ser encontrado sem procurar, porque
  publicar no grupo errado não desfaz e trocar de tema desfaz com um clique.
  ⛔ **Defeito de 0.N corrigido de passagem:** a letra do símbolo da marca estava **escrita à mão**
  no componente — um pedaço do nome do produto morando no código, que ficaria para trás no dia da
  renomeação com o símbolo dizendo uma letra e o nome ao lado dizendo outra. Agora é a inicial de
  `nomeDoApp`, calculada na hora. 392 testes verdes.
- 2026-08-06 — **⛔ BUG: conectar um canal que já vive em outro grupo dizia "conectado" e não
  mostrava nada.** Achado por relato ("conectei o YouTube no grupo Teste e não aparece") e
  confirmado no banco: **nenhuma conta nova havia sido criada**. O mecanismo é a UNIQUE
  `(usuario_id, plataforma, identificador_externo)`, que **não inclui o grupo de propósito** (é ela
  que impede o mesmo canal de existir em dois grupos): o `updateOrCreate` da conexão encontrava o
  registro do outro grupo, atualizava nome e situação e **deixava o `grupo_id` como estava** — e o
  `creating` que carimba o grupo corrente só roda na criação. A pessoa autorizava na rede, voltava,
  lia que deu certo, e o grupo continuava vazio. Nada avisava, e o banco estava certo.
  ⛔ **A correção não é mover o canal por conta própria:** mover leva o canal para longe do grupo
  onde o histórico dele nasceu, e isso é ação explícita com janela e aviso (DEC-77) — fazer escondido
  durante um "conectar" é exatamente o acidente que o grupo existe para evitar. Nasceu
  `CanalDeUmGrupoSo`, que recusa nomeando o canal e o grupo onde ele está.
  ⚠️ O defeito era dos **dois** caminhos de conexão (YouTube e Bluesky), por isso a trava vive em um
  lugar só. Reconectar no mesmo grupo continua passando — é o caminho de renovar autorização vencida,
  e travá-lo quebraria o semáforo (DEC-32). 396 testes verdes (4 novos).
- 2026-08-06 — **Documentação do Threads consultada e módulo Meta organizado →
  `21-plano-meta.md` (DEC-99..105) + `planos-de-redes/threads/`.** Facebook e Instagram já estavam
  lidos (31/07) e com publicador escrito; a lacuna era o Threads, que não tinha nada.
  ⛔ **A DEC-30 estava errada na parte do fluxo.** Ela dizia que o Threads pega carona com "fluxo
  idêntico ao IG". Ele tem **janela de autorização própria** (`threads.net/oauth/authorize`),
  **servidor próprio** (`graph.threads.net`), **permissões próprias** (`threads_*`) e **não aceita
  envio de arquivo** — só URL pública. Sobra de carona o mesmo aplicativo Meta e a mesma conta de
  desenvolvedor; **não sobra o código**. Conectar o Instagram não acende o Threads.
  ⛔ **Segunda correção de documento:** o `CLAUDE.md` descreve a URL pública temporária como aberta
  *"p/ Instagram e TikTok"* — o Instagram **não usa**, porque a escolha do Login do Facebook deu
  upload direto justamente para evitar expor o arquivo. **O Threads é a primeira rede do produto
  que realmente precisa desse buraco** (DEC-100), e por isso ele fica desligado enquanto não houver
  servidor alcançável pela internet (DEC-101) — a Meta não enxerga `localhost`.
  Travadas ainda: **DEC-102** renovação obrigatória com janela de 24 h a 60 dias (é a única rede do
  produto com morte definitiva por inatividade); **DEC-103** o segundo passo não dorme segurando
  worker; **DEC-104** o limite de texto é 500 **bytes UTF-8**, não caracteres — dez emojis comem 40
  bytes e a legenda estoura sem parecer; **DEC-105** ligar Facebook e Instagram de verdade **antes**
  de escrever o Threads, porque os dois publicadores nunca rodaram contra a rede e a revisão do
  Facebook já achou sete divergências na leitura.
- 2026-08-06 — **Fase 2 e Fase 3 do plano 21 entregues.** (a) **URL temporária da mídia** — rota
  assinada, fora do grupo autenticado, 15 minutos, servindo só o arquivo. É o **único endereço do
  produto que serve arquivo sem sessão**, e existe porque o Threads não aceita envio: ele recebe uma
  URL e vem BUSCAR a mídia, com um servidor da Meta que nunca terá login aqui. ⛔ Por isso a
  assinatura é a trava inteira. ⚠️ Um dos 9 guardiões documenta o que **parece furo e não é**: o
  endereço serve mídia de qualquer dono, e tem que servir — não há sessão para o escopo usar, e com
  escopo a consulta **lançaria exceção**. O teste existe para ninguém "consertar" isso e quebrar a
  integração. (b) **`ConexaoComThreads` + `threads:renovar`** (diário, 04:50, com 15 dias de folga).
  ⛔ **Dois achados que só apareceram escrevendo:** o campo dos escopos concedidos é `permissions`,
  **não** a string `scope` do padrão OAuth — ler o errado recusaria toda conexão válida; e
  `refresh_token` fica **nulo**, porque o Threads renova o próprio token longo apresentando ele
  mesmo, o que faz `expira_em` significar **o prazo de morte da conta** e não o de um token que se
  renova sozinho. 420 testes verdes (24 novos).
- 2026-08-07 — **Aplicativo criado na Meta, com os três casos de uso** (Threads · Página ·
  Instagram) e o **Login do Facebook para Empresas**, que é o que libera enviar o arquivo direto.
  ⛔ Recusados de propósito os casos de uso de **Vídeo ao Vivo** e **oEmbed**: a análise cobra
  vídeo de demonstração por permissão pedida, e permissão sem uso reprova a submissão inteira.
  ⚠️ Ao vivo multiplataforma foi avaliado e **fica fora do plano**: exige servidor de mídia
  (uma transmissão 1080p para 4 redes são ~24 Mbps saindo continuamente) e **quebra a tese** — ao
  vivo não tem post para reler, então não há prova. Endereço público resolvido por **túnel**, que é
  suficiente para testar e insuficiente para o resto. O que falta do lado da Meta está listado em
  `21-plano-meta.md` — inclusive **redefinir a chave secreta**, que foi exposta numa captura durante
  a configuração.

---

## 🔤 Vocabulário canônico
**Fonte única: [`06-glossario-canonico.md`](06-glossario-canonico.md)** — convenções de
nomenclatura, papéis, enums com máquinas de estado, tabelas coluna a coluna, mecânica de
rótulos, rotas, estrutura de pastas e testes-guardiões. **Nenhum nome nasce no código sem
estar lá (DEC-18).** Este plano não duplica a lista — uma fonte só.

---

## 🗺️ FASES

### 🔴 Fase I — Infra e trâmites (COMEÇA JÁ, roda em paralelo a tudo)
**Por que primeiro:** como plataforma aberta (DEC-20), os trâmites levam **semanas** e são o
caminho crítico. **Estes itens NÃO dependem de código** — trave-os enquanto as fases 0–2
acontecem. *(O que exige código pronto — vídeo demo, screencast, audit — está marcado nas
fases das redes.)*
- [ ] **Domínio próprio** + hospedagem + HTTPS + SMTP (DEC-21).
      ⚠️ **A hospedagem tem que permitir rodar programa externo** — o laudo de mídia depende do
      `ffprobe`/`ffmpeg`, que são binários do sistema, não bibliotecas PHP.
      **Qualquer VPS serve** (com acesso root dá pra instalar). O que **não** serve é
      hospedagem compartilhada/gerenciada, que não deixa instalar pacote do sistema.
      🔴 **A pegadinha real acontece mesmo em VPS:** painéis de gerenciamento (aaPanel, cPanel,
      Plesk) sobem o PHP com `proc_open`, `exec` e `shell_exec` dentro de `disable_functions`,
      por segurança. Sem `proc_open` o Symfony Process **não roda** — e o erro não fala de
      FFmpeg, fala de função desabilitada. **Solução:** liberar `proc_open` no `php.ini` (as
      outras podem seguir desligadas). **Conferir com `php artisan midia:verificar` no servidor
      antes de considerar a hospedagem aprovada.**
- [ ] **Decidir o nome comercial** (0.N) — trava na 1ª submissão às plataformas, porque entra na
      tela de consentimento auditada. Conferir se não contém "YouTube" (proibido pelo Google).
- [ ] **Política de privacidade** e **termos de uso** publicados no domínio — exigidos pelo
      Google, pela Meta e pela LGPD (o produto guarda tokens e publica em nome de terceiros).
- [ ] **URL de exclusão de dados** (a Meta exige um endereço pra pedidos de exclusão).
- [ ] **Projeto no Google Cloud** + credencial OAuth (destrava o login Google já na Fase 0).
- [ ] **Business Verification da Meta** (documentos da empresa) — **não precisa do app
      pronto**, começar agora porque demora.
- [ ] **Verificação de domínio** (Search Console p/ Google; verificação de URL p/ TikTok).
- [ ] Contas de desenvolvedor: Meta (app), TikTok (app).
- [ ] **Pedir aumento da quota** do YouTube (100 uploads/dia é por projeto — some com
      ~30 clientes ativos).
- [ ] **Documentos internos** (1 página cada): plano de resposta a incidente · registro
      simplificado de tratamento · matriz de retenção.
**Entregável:** domínio no ar com política/termos, apps criados nas 3 plataformas, empresa
verificada na Meta — tudo pronto pra submeter os reviews assim que o código existir.
> 📘 **Checklist completo e executável: [`08-tramites-e-conformidade.md`](08-tramites-e-conformidade.md)**
> — inclui as 6 armadilhas que matam o projeto depois de pronto, o conteúdo obrigatório da
> política/termos, os documentos da Meta e o que cada submissão exige.

### Fase 0 — Fundação (base + auth + esqueleto do admin)
**Objetivo:** projeto de pé, com cadastro funcionando e o admin conseguindo criar cliente e
impersonar. **(DEC-16: só o esqueleto do admin, não o painel inteiro.)**
> **⚠️ Depende da Fase I:** o **login com Google** precisa do app no Google Cloud (o mesmo do
> YouTube). **Fallback:** entregar a Fase 0 com **cadastro por e-mail** e ligar o botão do
> Google assim que a credencial existir — a fase **não fica bloqueada** por isso.
> **Cadastro aberto (DEC-20)** exige desde já: confirmação de e-mail obrigatória (conta não
> confirmada não entra), rate limit no cadastro/login e aceite dos termos no registro.
- [ ] **Subir o projeto pro Laravel 13** (DEC-06 revisada) + `APP_LOCALE=pt_BR`.
- [ ] **Paleta** (DEC-23): variáveis de cor claro + escuro, corrigindo o `--accent` do tema
      escuro pra manter o índigo.
- [ ] **Sistema fluido** (DEC-37): fonte base, sidebar e containers com `clamp()`; barra de
      progresso no topo; `prefers-reduced-motion` respeitado.
- [ ] **Componentes visuais de referência do projeto antigo** — `platform-card`,
      `platform-icon`, `media-picker`, `stepper`, `empty-state`, `sparkline`: **reescrever**
      com o vocabulário PT-BR (DEC-15/18), usando o antigo só como referência visual. As
      páginas antigas (`content-creator`, `content-calendar`…) são **referência de layout**,
      não código a copiar — foram feitas pro modelo antigo, em inglês.
- [ ] Renomear o `User` do starter para `Usuario`/`usuarios` + colunas `papel` (admin/cliente),
      `google_id` e `ativo` (colunas do framework — `password`, `email_verified_at`,
      `remember_token` — mantêm o nome padrão; DEC-15).
- [ ] **Cadastro (DEC-05):** registro/login do starter em PT-BR; **e-mail com código de
      confirmação** (só libera após confirmar; pendente expira); **login com Google**
      (Socialite, mesmo projeto Google Cloud do YouTube); reset de senha.
- [ ] **Dois painéis** (admin + cliente): layouts + rotas separados por papel + middleware
      (um não acessa a área do outro).
- [ ] **Esqueleto do admin:** criar cliente + **impersonação completa** (admin → cliente) +
      **`logs_impersonacao`** + banner "modo impersonação" + botão sair.
- [ ] Estrutura de camadas: `app/Services`, `app/Actions`, `app/Jobs`, `app/Publicadores`.
- [ ] `enum Plataforma` + contrato `Publicador` (`publicar(Destino): Resultado`).
**Entregável:** cliente se auto-cadastra (e-mail confirmado ou Google); admin loga, cria
cliente, impersona e volta — tudo registrado.

### Fase 1 — Modelagem (com isolamento)
**Objetivo:** o banco que sustenta tudo, já isolado por cliente.
- [ ] Migrations: `contas_sociais`, `credenciais`, `midias`, `publicacoes`, `destinos`,
      `tentativas`, `configuracoes_rede`, `logs_impersonacao` — **exatamente como no
      glossário (06), coluna a coluna**.
- [ ] **`usuario_id`** em todas as tabelas de cliente + **Global Scope** (filtra pelo usuário
      atual/impersonado) — o coração do isolamento (DEC-01).
      ⚠️ **Bug clássico (doc 15):** o worker da fila **não tem sessão** — o escopo vira
      `WHERE usuario_id IS NULL` e `Queue::fake()` esconde isso em **todos** os testes. O job
      carrega o `usuario_id` explicitamente, e há teste que **roda o job de verdade**.
- [ ] **Segurança da fundação (0.M):** `encrypted` nas credenciais com chave no `.env` ·
      **Policy** no lugar de `if` espalhado · **ULID público** nas rotas (nunca id sequencial) ·
      log de segurança separado do técnico.
- [ ] Tokens com cast `encrypted`; `destinos` com status próprio + `legenda_override`.
- [ ] **Travar os nomes no glossário antes de criar as tabelas (DEC-18);** enums que aparecem
      na tela (`Papel`, `Plataforma`, `StatusDestino`) com **chave canônica + arquivo de
      rótulos** separado.
- [ ] Teste de isolamento: cliente A **nunca** enxerga dado do cliente B.
**Entregável:** schema migrado + isolamento provado por teste.

### Fase 2 — Mídia (upload)
**Objetivo:** subir o conteúdo, validado.
- [ ] Upload de vídeo **9:16** e imagem (storage local) + metadados.
- [ ] **Validação pelo perfil canônico (07 §6):** vídeo MP4 H.264+AAC 9:16, 3–180s, ≤300MB;
      imagem **JPEG**.
- [ ] **Pré-requisito: `ffprobe`/`ffmpeg` instalados** — são programas do **sistema**, não vêm
      no `composer install`, e precisam existir na máquina de dev **e** no servidor.
      Caminho em `config/midia.php` (`FFPROBE_CAMINHO` no `.env`) — **nunca depender só do
      PATH**, que muda conforme o usuário que roda o PHP. Conferir com `php artisan
      midia:verificar`. **Ausência degrada, não quebra:** upload segue funcionando e a tela
      avisa que o laudo está indisponível. *(Instalado localmente em `C:\tools\ffmpeg`, v8.1.2.)*
- [ ] ⭐ **Laudo de mídia (DEC-32/33)** — `ffprobe` no upload mostrando codec, resolução,
      duração e bitrate **contra a regra de cada rede**, e o que vai acontecer com o arquivo
      ("passa intacto" vs "vamos recodificar o áudio"). Ataca a causa nº1 de falha **antes**
      dela acontecer.
- [ ] ⭐ **Aceitar HEVC do iPhone** (DEC-33) — Buffer e Metricool recusam; a Meta aceita.
- [ ] Avisos por duração: ≤90s = todas as redes; 91–180s = Facebook indisponível; >60s =
      elegível a monetização no TikTok (o formulário mostra, não bloqueia).
- [ ] Biblioteca de mídia do cliente.
**Entregável:** subir um short/imagem válido e vê-lo salvo (só o dono enxerga).

### ⭐ Fase 3 — Motor + primeiras redes (o que fecha sem depender de ninguém)
**Objetivo:** publicar de verdade, em produção, **na primeira semana** — sem esperar aprovação.
> **DEC-13/36:** estas três não têm fila. Instagram e Facebook, **em conta própria, dispensam
> App Review**; Bluesky não tem processo nenhum. Juntas, provam o motor inteiro (fila, retry,
> status, conciliação) enquanto as auditorias de YouTube e TikTok correm em paralelo.
- [ ] **Motor de publicação** (ver Fase 4 — é aqui que ele nasce): 1 job por destino, fila por
      plataforma, idempotência, retry, **conciliação com prova**.
- [ ] `PublicadorBluesky` — sem aprovação; vídeo até 3 min; ⚠️ **imagem ≤ 1 MB** (comprimir).
- [ ] `PublicadorInstagram` + `PublicadorFacebook` — **conta própria** (acesso padrão);
      container → aguardar `FINISHED` → publicar; mídia por **URL pública temporária**.
- [ ] Módulos na sidebar (DEC-17) + os componentes comuns que as próximas redes reusam.
**Entregável:** você publicando de verdade nas suas contas, com prova de entrega.

### Fase 4 — YouTube (código pronto, auditoria em paralelo) ⭐
**Objetivo:** publicar um short no YouTube, de verdade, do início ao fim.
> **Para uso próprio, é só UM gate:** a **auditoria de compliance** (sem ela o vídeo sai
> privado). A **verificação OAuth não é necessária** — o teto de 100 usuários vitalícios sobra
> pra uma pessoa. *(Ela volta a ser obrigatória se virar plataforma — Fase 7.)*
> A auditoria exige a integração **funcionando** (pedem demonstração), então a ordem é:
> *código pronto e testado publicando privado → submeter → esperar*. Enquanto espera, siga
> para a Fase 5.
> **(a)** verificação OAuth do scope `youtube.upload` (sensível: domínio verificado +
> privacy policy + vídeo demo; ~10 dias); **(b)** **auditoria de compliance do projeto de
> API** — sem ela, todo vídeo enviado fica **travado como privado**. Enquanto o app estiver
> em "Testing", o refresh token **expira em 7 dias** (conviver durante o dev, resolver antes
> do uso real). **Quota: 100 uploads/dia é POR PROJETO, somando todos os clientes** (DEC-20)
> — pedir aumento na Fase I e monitorar consumo; não é "folga total" numa plataforma.
- [ ] **(externo/OK)** criar o app no Google Cloud + OAuth (passo do Gabriel) + disparar os
      2 gates acima.
- [ ] Conectar conta do YouTube (OAuth; `access_type=offline` + `prompt=consent` p/ garantir
      refresh token) → guardar credencial encriptada.
- [ ] `PublicadorYoutube`: upload resumável (google/apiclient, chunks 256KB, retomada 308) +
      título/descrição/hashtags; acompanhar `processingStatus` via `videos.list`.
- [ ] Publicar **em privado/teste primeiro** (0.A) → gravar resultado (link/erro).
- [ ] Refresh de token (serializado com lock); `invalid_grant` → status `expirada`
      (rótulo de tela "Reconectar" — DEC-18: chave ≠ rótulo).
- [ ] **Módulo YouTube** na sidebar (DEC-17): Publicações · Conexões · Configurações — com
      os componentes comuns que as próximas redes vão reusar.
**Entregável:** um short publicado no YouTube com status e link registrados.

### Fase 4.1 — Motor de publicação (construído junto com a Fase 3)
**Objetivo:** o coração — "1 clique → várias redes".
- [ ] Criar publicação: mídia + legenda + hashtags + **escolher contas de destino**.
- [ ] **Publicar** → **1 job por destino**, **fila por plataforma** (≥2 workers — DEC-08),
      **retry com backoff** (`ThrottlesExceptions` em 429) modelado como
      `enviando → pendente` (glossário §4), **anti-double-post** via `handle_externo`
      gravado **antes** do efeito irreversível.
- [ ] **Watchdog de órfão** (destino `enviando` sem tentativa ativa há > N min) — sem ele o
      motor trava "Publicando…" pra sempre quando o worker morre.
- [ ] ⭐ **Conciliação pós-publicação (DEC-31/32)** — job que consulta a rede até estado
      terminal, **relê o post**, guarda o **permalink como prova** e só aí marca "No ar".
      **É o coração do diferencial.**
- [ ] ⭐ **Monitor de token** — semáforo por conta + aviso **7 dias antes** de vencer.
- [ ] ⭐ **Alertas por WhatsApp** (falha, token vencendo) — DEC-32.
- [ ] **Status por destino** (sucesso c/ link / falha c/ motivo) + **reprocessar**.
- [ ] **Status ao vivo** na tela da publicação (`usePoll` 3–5s, reload parcial — DEC-19).
- [ ] **Notificações** `PublicacaoConcluida`/`PublicacaoFalhou` (mail + sininho — DEC-19)
      — disparam **só em estado terminal**, nunca em tentativa intermediária.
- [ ] **Healthcheck da fila** (Supervisor + QueueCheck) + **backup automático** (DEC-19).
- [ ] Histórico das publicações.
**Entregável:** publicar em N contas de uma vez, com histórico, avisos e reprocessamento —
e o motor **se auto-vigia** (worker morto dispara alerta).

### Fase 6 — Demais redes (uma por vez, conforme quiser)
Cada uma é **uma classe nova** — dias, não semanas, porque o motor já existe.
- [ ] **Threads** — carona no App Review da Meta (fluxo idêntico ao Instagram, reaproveita código)
- [ ] **LinkedIn (perfil)** — permissão self-service, liberada em minutos
- [ ] **Mastodon** · **Discord** — sem aprovação
- [ ] **Pinterest** — nativamente 9:16, dois portões de acesso
- [ ] **X** — ⚠️ **pago por post** (US$ 0,015; **US$ 0,20 com link**) — só se a conta fechar
- [ ] **LinkedIn (Página)** — alta barreira, alto valor

### 🔓 Fase 7 — Virar plataforma (quando decidir)
> É aqui que entram as exigências da **DEC-20**: verificação OAuth do Google, App Review +
> Business Verification da Meta, auditoria pública do TikTok, e o papelório da Fase I.
- [ ] Threads/Instagram/Facebook para **contas de terceiros** (mesma submissão)
- [ ] Auto-cadastro aberto · planos · cobrança

### (histórico) Facebook + Instagram + **Threads** para terceiros
> **DEC-30:** **Threads entra no MESMO App Review** do IG/FB (mesmo app, mesma verificação de
> empresa, mesma leva de screencasts) — custo marginal de aprovação **quase zero**. Tecnicamente
> é a mais barata: fluxo **idêntico ao do Instagram** (reaproveita o código) e limites mais
> generosos (vídeo 5 min / 1 GB, 9:16 recomendado na doc). **Submeter junto, nunca depois.**
- [ ] `PublicadorThreads` + escopos `threads_basic` e `threads_content_publish` na mesma
      submissão. *(Antes da aprovação já dá pra construir e testar 100% em contas convidadas.)*
> **⚠️ Gate externo (07 §3-4):** App Review + Business Verification da Meta (Advanced
> Access) pra publicar em contas de terceiros — dias a semanas; iniciar cedo.
- [ ] **Instagram via "Instagram API with Instagram Login"** (07 §3): OAuth direto no IG,
      **sem Facebook Page**; scopes `instagram_business_basic` +
      `instagram_business_content_publish`; conta profissional (Business/Creator).
- [ ] **URL pública temporária da mídia** durante o job do IG (a Meta faz cURL — DEC-07);
      túnel no dev local.
- [ ] `PublicadorInstagram`: container → poll `status_code` até `FINISHED` → publish
      (container expira em 24h; limite 100 posts/24h). Reels 3s–15min; foto **JPEG 4:5–1.91:1**.
- [ ] `PublicadorFacebook`: Reels via `video_reels` (3 fases, upload direto, **3–90s**,
      ~30/24h) + foto via `/photos`; permissões `pages_manage_posts` + `pages_show_list` +
      `pages_read_engagement`; page token de longa duração (não expira por tempo).
- [ ] **Módulos FB e IG** na sidebar (DEC-17), reusando os componentes comuns do módulo YouTube.
**Entregável:** publicar short/imagem no FB e IG.

### Fase 5 — TikTok (constrói e testa agora; libera quando aprovar)
> **DEC-36:** o código nasce completo e é **testado em modo privado** desde já (não auditado =
> `SELF_ONLY`, máx 5 contas/24h — suficiente pra validar o fluxo inteiro). A **liberação
> pública** vem depois da auditoria.
> ⚠️ **Duas travas:** a tela precisa nascer **certa** (é UX que reprova, e a rejeição vem
> genérica) · submeter a auditoria é **assumir que é produto** — eles **rejeitam ferramenta de
> uso próprio** (*"apps limitados a uso de teste não são aceitáveis"*).
- [ ] OAuth (`socialiteproviders/tiktok` — reavaliar manutenção) + Content Posting API
      (Direct Post, chunks 5–64MB, rate limit 6 req/min por token).
- [ ] **UX obrigatória do TikTok** (auditada!): `creator_info` ao abrir a tela de publicar,
      privacidade **sem default**, disclosure comercial, confirmação de música — elementos
      específicos do módulo TikTok (DEC-17 absorve).
- [ ] **Duração máxima dinâmica por criador** (`max_video_post_duration_sec`) — validar por
      conta na hora de publicar, não por constante.
- [ ] `PublicadorTiktok`. (Fotos ficam pra quando houver domínio verificado — só PULL_FROM_URL.)
- [ ] **Módulo TikTok** na sidebar (DEC-17).
**Entregável:** publicar no TikTok (privado até o audit; público depois dele).

### Fase 7 — Radar de tendências (futuro)
> Fontes **reais** confirmadas (07 §7): YouTube Data API (`chart=mostPopular` — hoje reflete
> charts de Música/Filmes/Gaming) + **Google Trends API oficial (alpha — APLICAR ACESSO
> DESDE JÁ)** + hashtag search do IG (30/7 dias) + métricas das próprias contas. TikTok
> Creative Center é só navegador; Research API vedada a uso comercial; Meta sem opção
> comercial. **Sem scraping.**
- [ ] Aplicar ao alpha da Google Trends API (custo zero, fila de acesso).
- [ ] Painel de tendências por nicho → vira ideia de conteúdo.

### Fase 8 — SaaS / vender acesso (futuro)
> **DEC-14:** camada **por cima** da base — não mexe na arquitetura de hoje.
- [ ] **Web Push + PWA instalável** (1º pacote pós-MVP — DEC-19): o sininho vira aviso no
      celular mesmo com o site fechado.
- [ ] **"Cancelar antes de publicar"** (delay ~60s + botão — DEC-19).
- [ ] **Agendamento** (no YouTube, usar o `publishAt` nativo; nas demais, fila com delay).
- [ ] Planos + limites (gating) + cobrança (assinatura/fatura).
- [ ] Impersonação "só-leitura" de suporte · export de dados (LGPD).
- [ ] Métricas por publicação (snapshot) · geração de conteúdo por IA.

---

## ✅ Definição de "pronto" (por fase)
0. **Serve à promessa** (filtro do doc 19) · **0.5. Camadas de segurança da 0.M cumpridas**
   *(nenhum segredo em log · isolamento com teste verde · arquivo validado por conteúdo ·
   URL de mídia expirando)* ·
1. Código nas camadas certas (0.H) · 2. Sem código morto / grep-zero (0.C) ·
3. Testes do fluxo crítico verdes, **incluindo isolamento** (0.I) · 4. Tudo em PT-BR (0.J) ·
5. LOG atualizado (0.B) · 6. Commit limpo, sem menção a IA.

**Regras das redes:** toda fase de rede só fecha com os itens de
[`09-regras-das-redes.md`](09-regras-das-redes.md) daquela plataforma marcados — são
exigências de aprovação, não opcionais.

**Critério de gate (fases de rede)** — sem isso a fase fecha "verde" publicando só em
privado e ninguém percebe: **Fase 3** = vídeo **público** no ar; **Fase 5** = publicar em
conta que **não é a do dono**; **Fase 6** = post **público** no TikTok (pós-audit).

---

## 🚦 O que NÃO fazer agora (lembrete 0.G)
Multi-database · billing/planos · workspaces/multiempresa · inbox de comentários · outros
formatos (vídeo longo/carrossel/texto) · conversão de proporção · agendamento · WebSocket/
Reverb · i18n · activity log genérico · web push (é o 1º pacote pós-MVP, não MVP) — **tudo
isso é fase futura, não MVP.**

---

### 2026-07-31 — Revisão da configuração do YouTube

Revisão linha a linha do que já estava pronto, contra a documentação oficial. **8 defeitos**,
detalhados em `planos-de-redes/youtube/achados.md` (R-1 a R-8). Os três que mais custariam:

- **R-2** — gravávamos os escopos *pedidos*, não os *concedidos*. A documentação do Google exige
  verificar. A pessoa podia desmarcar o envio, ficar com a conta verde no painel e descobrir só
  no primeiro vídeo.
- **R-3** — em modo de Testes o Google **encerra a autorização a cada 7 dias**. Sem explicar
  isso, a queda semanal pareceria defeito nosso.
- **R-6** — as mensagens de erro em português nunca chegavam à tela: o retorno do Google é um
  GET externo, e o redirecionamento automático mandaria a pessoa de volta ao site do Google.

Todos corrigidos e travados por teste. **255 testes verdes.**

**DEC-47 — a qualidade entregue passa a ser gravada.** `contentDetails.definition` era lido na
conciliação e descartado. É a rede *admitindo* que degradou o vídeo: enviamos 1080×1920, e se ela
responde `sd`, isso é a plataforma dizendo — não suposição nossa. Nova coluna
`destinos.qualidade_entregue`.

**Verificado no servidor real:** sem credencial o cartão mostra "Falta configurar"; com
credencial, "Conectar" leva ao Google com o endereço de retorno exato, `access_type=offline`,
`prompt=consent` e **sem `force-ssl`** (DEC-41, escopo mínimo).

**Só falta a credencial do Google Cloud** — passo a passo em
`planos-de-redes/youtube/como-configurar.md`.

---

### 2026-07-31 — Facebook e Instagram (Meta)

Documentação oficial **baixada e lida** antes de qualquer linha de código, como manda a regra —
em `planos-de-redes/instagram/documentacao/` e `planos-de-redes/facebook/documentacao/`,
obtidas da fonte markdown que a própria Meta publica.

**DEC-48 — uma conexão acende as duas redes.** A conta do Instagram fica pendurada numa Página do
Facebook, e o login é o mesmo. O caminho alternativo (Login do Instagram) foi **descartado**: ele
só aceita vídeo por **URL pública**, o que exigiria expor os arquivos dos clientes na internet
aberta — trocar uma integração por um vazamento. O Login do Facebook permite envio direto do
arquivo. Detalhado em `planos-de-redes/meta-compartilhado.md`.

**DEC-49 — o limite de 90 s do Facebook recusa antes de enviar.** É o teto mais curto de todas as
redes (o Instagram aceita o mesmo arquivo por 15 min). Descobrir isso depois de subir 300 MB
seria o pior momento possível.

**Achados que mudaram o código** (`achados.md` de cada rede):
- criar o container do Instagram **não publica** — sem o segundo passo, o post não existe
- `processing_phase: completed` do Facebook **não** é publicado — conciliar pela fase errada
  marcaria publicado cedo demais
- `is_transient` vem na resposta: a rede **diz** se vale tentar de novo, e no YouTube tivemos que
  deduzir isso do código HTTP (e erramos duas vezes)
- `2207027` e `2207008` **não são falhas** — são "espere"
- o token da Página **não expira**, mas a troca pelo token longo tem que ser na conexão
- ⭐ o Instagram informa `copyright_check_status` **antes** de publicar

**🔴 Bug antigo encontrado na revisão: a retentativa do motor estava morta.**
`devolverParaFila` mudava o estado para `pendente` e nenhum job era criado — o destino ficava
parado para sempre. Valia para **todas as redes**. Corrigido e travado por teste.

**283 testes verdes.** Falta a credencial da Meta, que só o Gabriel pode criar.

**Revisão contra a documentação (mesma data).** Reli o código recém-escrito linha a linha contra
os arquivos baixados. **7 defeitos**, detalhados em `planos-de-redes/facebook/achados.md`
(F-R1 a F-R7). Os três que quebrariam de verdade:

- **F-R2** — pedíamos `permalink_url`, campo que não existe no nó Video. O Graph API não devolve
  nulo nesse caso: **recusa a chamada inteira**. Toda publicação do Facebook seria marcada como
  falha sem ter falhado.
- **F-R3** — a retomada do envio estava **inventada**: um POST vazio que a documentação não
  descreve. O mesmo erro de escrever de cabeça que já tinha custado caro no YouTube.
- **F-R4** — rejeitávamos Páginas da experiência nova, que devolvem `PROFILE_PLUS_CREATE_CONTENT`
  em vez de `CREATE_CONTENT`. Diria "você não tem permissão" numa Página recém-criada pela
  própria pessoa.

**289 testes verdes**, tipos e lint limpos.

---

### 2026-08-01 — YouTube: fechando o que estava invisível

Foco só no YouTube, a pedido. Três coisas que o motor sabia e a tela não mostrava:

**A qualidade entregue agora aparece.** Era gravada desde ontem e não era exibida em lugar
nenhum — trabalho feito com valor zero. Quando o YouTube responde `sd` para um vídeo que subiu em
1080, a publicação mostra *"a rede entregou em baixa"*: é a plataforma admitindo que degradou, com
a palavra dela.

**O laudo diz se o vídeo atende ao que o Shorts pede.** ⚠️ Com uma ressalva importante: **a API
não fala de Shorts** — não está no contrato, é comportamento do produto YouTube. A central de
ajuda só publica "até 3 minutos e vertical". Então o laudo afirma o que é verificável no arquivo
e **não promete** que o vídeo vira Short.

**O aviso do vídeo privado saiu da tela de conexões para a de publicar.** Quem conectou semana
passada já esqueceu, e descobrir isso depois de publicar é a surpresa que o produto existe para
evitar — só que do nosso lado.

**Lacuna fechada: a referência de erros do `videos.insert`.** 14 erros do envio, nenhum tratado
(R-9 a R-11 em `planos-de-redes/youtube/achados.md`). Um deles, `mediaBodyRequired`, estava indo
para recusa definitiva sendo falha de transporte — descartaria um envio que a próxima tentativa
faria.

**293 testes verdes.** Falta só a credencial do Google Cloud.

---### 2026-08-03 — Credenciais locais e o padrão fraco que só apareceria em produção

As contas de desenvolvimento passaram a ser `admin@admin.com` e `teste@teste.com`, senha `1234`
nas duas — curtas de propósito, para não travar quem desenvolve. Substituem as antigas em
`@local.test`, que ninguém decorava.

**E fechou um risco que já existia.** O seeder criava o administrador com e-mail e senha padrão
em **qualquer ambiente**: quem instalasse em servidor sem definir `SEED_ADMIN_EMAIL` e
`SEED_ADMIN_SENHA` ficaria com credencial conhecida, sem nenhum aviso. Agora as contas curtas só
existem em ambiente local, e **fora dele o seeder recusa rodar** sem as variáveis definidas.

Senha padrão que só aparece em produção é o pior tipo de falha: nada avisa, e tudo funciona.

A regra 0.N deixou de mandar um domínio específico (`@local.test`) e passou a dizer o que
realmente importa: dado de teste usa domínio genérico e nunca um derivado do nome do produto.

**Drift corrigido no DEC-08.** O texto dizia que o Instagram baixaria a mídia por **URL pública
temporária**, com o app expondo cada arquivo durante o job. Não é o que fazemos — esse caminho
exigiria deixar o arquivo do cliente acessível na internet aberta, e foi justamente por isso que
o DEC-48 escolheu o Login do Facebook, que aceita upload direto. A documentação descrevia uma
arquitetura que a gente tinha rejeitado.

---

### 2026-08-03 — Primeiro teste real do YouTube: o servidor não falava com ninguém

A conexão foi até o fim no Google e voltou sem criar conta nenhuma. O log do servidor mostrou o
retorno chegando e **demorando 20 segundos** — exatamente o nosso tempo limite.

**Causa:** o PHP estava sem pacote de certificados (`curl.cainfo` e `openssl.cafile` vazios no
`php.ini`). Sem isso ele não valida nenhum certificado HTTPS, e **toda** chamada externa falha —
Google, Meta, Bluesky, todas. Resolvido baixando o `cacert.pem` oficial e apontando as duas
diretivas. Registrado em `planos-de-redes/youtube/como-configurar.md`, porque vai reaparecer no
servidor.

**E rendeu uma correção de produto.** A tela dizia *"não conseguimos falar com o Google agora,
tente de novo em instantes"* — conselho inútil contra um problema que nunca passa sozinho, e que
ainda insinuava que o defeito era da conta da pessoa.

Nasceu o `FalhaDeConexao`: falha de certificado agora diz que **o servidor** não valida
certificados, que **não é problema da conta**, e o que precisa ser configurado. Oscilação de rede
continua dizendo "tente de novo", que aí é o conselho certo.

Os dois casos chegam como a mesma exceção, e tratá-los igual é o tipo de coisa que faz a pessoa
perder uma hora olhando para o lugar errado.

---

### 2026-08-03 — Uma pasta por cliente, e os avisos viraram toast

**DEC-50 — cada cliente tem a própria pasta de mídia.** Antes tudo caía em
`midias/AAAA/MM/`, misturado: o isolamento existia **só no banco**, e bastaria um defeito de
caminho para um cliente alcançar o vídeo de outro. Agora o caminho é
`midias/<ulid do dono>/AAAA/MM/`.

Três ganhos além do isolamento: apagar a conta apaga tudo dela sem varrer registro por registro
(arquivo órfão é dado pessoal que ninguém enxerga — LGPD); backup e mudança de servidor movem um
cliente por vez; e o diretório não vira um saco com milhares de arquivos.

⚠️ **ULID no caminho, nunca o `id` sequencial** — o caminho pode vazar num log, e o sequencial
entrega quantos clientes existem e quem chegou primeiro. Guardar mídia **sem dono definido** agora
lança exceção: pasta genérica seria justamente o órfão que este desenho evita.

Feito com **zero mídia gravada**, então não houve o que migrar. Depois do primeiro cliente real
isso teria custado uma migração de arquivos.

**Os avisos viraram toast, em três arquivos com um motivo de mudança cada:**

- `lib/avisos.ts` — a fila e as regras (quanto tempo fica, quantos cabem)
- `hooks/use-avisos.ts` — lê o recado que veio do servidor
- `components/avisos.tsx` — só desenha (cor, ícone, canto da tela)

A fila não sabe que existe Inertia, e o desenho não sabe de onde veio a mensagem. Também dá para
avisar de dentro do navegador (`avisar.sucesso('Copiado.')`), sem passar pelo servidor.

⚠️ **Erro não some sozinho.** Sucesso desaparece em 5s — quem acabou de agir já sabe o que fez.
Erro fica até a pessoa fechar: sumir antes de ela terminar de ler deixa o defeito sem explicação e
sem como recuperar o texto. E só o erro usa `role="alert"`, que interrompe o leitor de tela —
interromper por um "salvo" seria atrapalhar.

---

### 2026-08-03 — ⭐ Primeira publicação real, e a tela passou a acompanhar o motor

**O YouTube publicou de verdade.** Ciclo completo em 37 segundos: fila → sessão de envio (com o
handle guardado antes do primeiro byte) → arquivo enviado → **conciliação** relendo o vídeo na
plataforma → `publicado`, com link.

`https://www.youtube.com/watch?v=hUAndQxSBJQ` — e a rede respondeu **`sd`** na qualidade, num
vídeo de 478 px de largura. A plataforma admitindo que degradou, com a palavra dela. O recurso
funcionou na primeira publicação real.

**DEC-51 — a tela acompanha o motor buscando de novo, não por WebSocket.** O motor é assíncrono, e
a tela era uma foto do instante em que foi aberta: a pessoa ficava olhando "na fila" achando que
travou.

Empurrar por WebSocket (Reverb) exigiria um processo permanente no servidor, com porta e
monitoramento próprios — mais uma peça que cai em silêncio e leva junto uma funcionalidade que
ninguém percebe ter parado. O ciclo do motor leva dezenas de segundos; buscar de 4 em 4 chega na
mesma hora, com uma peça a menos para manter.

Duas travas evitam o desperdício: **só enquanto há algo em andamento** (terminou, para sozinho, e
em repouso não custa nada) e **só com a aba à vista** (volta a buscar assim que a pessoa retorna).
A recarga é parcial — só as props que mudam, não a página inteira.

⚠️ "Em andamento" é definido **por exclusão** (nem publicado, nem falhado). Listar os estados
intermediários um a um faria um estado novo ser tratado como pronto em silêncio, e a tela pararia
de atualizar bem no caso que ninguém previu.

---

### 2026-08-04 — Armazenamento: decidido adiar, com o critério registrado

> 🚫 **DEC-52 e DEC-53 estão REVOGADAS** — ver a entrada *"O produto não guarda nada"*, no fim
> deste log. As duas partiam de que o produto guardaria os vídeos; ele não guarda. A conta de
> custo abaixo continua correta, e é justamente ela que mostra que **custo nunca foi o
> argumento**: o que decidiu foi identidade de produto.

**DEC-52 — não há política de retenção agora.** A conta foi refeita: 100 clientes publicando todo
dia por um ano ocupam ~720 GB, o que custa ~R$ 60/mês em armazenamento de objetos — cerca de **1%
da receita**. Degradar a experiência do cliente para economizar 1% não se justifica.

E a proporção **não piora com escala**: armazenamento e receita crescem pela mesma variável
(número de clientes). O que piora com escala é o custo de ficar fora do ar — exatamente onde
servidor caseiro é mais fraco. Se um dia a conta apertar, a saída é servidor dedicado alugado, não
máquina em casa. Máquina própria só se justifica por **CPU** (recodificação de vídeo), nunca por
disco.

**Critério para revisitar sem achismo:** armazenamento acima de **5% da receita mensal**.

**DEC-53 — retenção existe, mas para INADIMPLENTE.** Para quem paga era exagero; para quem parou
de pagar é a resposta certa, e vale pela assimetria: o vídeo pesa ~20 MB, o registro dele ~40 KB.
Dá para jogar fora o caro e manter o barato.

Parou de pagar → painel somente leitura, ainda dá para baixar as mídias. Carência de 30 dias,
avisado por e-mail. Depois, os vídeos saem; ficam miniatura, laudo, links e prova. Voltou a pagar →
destrava com o histórico inteiro.

Cliente que volta e encontra painel vazio não volta duas vezes. E guardar dado pessoal para sempre,
sem finalidade, é problema de LGPD por si só — prazo definido com aviso é o que a lei espera.

⚠️ Depende do módulo de assinaturas, que **não existe** (hoje só há `usuarios.ativo`). Entra antes
do lançamento.

---

### 2026-08-04 — O produto não guarda nada (DEC-54 a DEC-62)

⭐ **A tese, sem meio-termo:** isto é um **caminho de publicação com prova**, não um lugar onde se
guardam arquivos. O vídeo existe pelo tempo do envio. Terminou, sai.

As decisões nasceram nos planos 11, 12 e 13 e vêm consolidadas aqui porque este é o log canônico.

#### ⛔ Por que NÃO guardar — o argumento que decidiu

Guardar é uma boa ideia. Só não é uma **necessidade**, e é aí que a conversa acaba:

1. **O cliente já tem o arquivo.** Ele saiu do celular dele, está no computador dele, no
   WhatsApp dele. Nós seríamos a terceira cópia — a que ele menos precisa.
2. **A conta de R$ 60/mês é palpite, e palpite de armazenamento erra para cima.** "100 clientes
   × 1 vídeo por dia" é um cenário inventado; o real escala de formas que não dá para prever hoje.
   Assumir custo aberto com base em estimativa própria é o tipo de decisão que só aparece na fatura.
3. **Não existe produto que pague por isso.** Ninguém compra armazenamento aqui, porque não
   vendemos armazenamento. Construir o que sustenta um plano que não existe é trabalho caro e
   inteiramente descartável.
4. **Não há infraestrutura para isso.** Nem VPS de verdade, nem cliente pagante — os dois
   pré-requisitos de qualquer conversa séria sobre disco.

⭐ **Quando revisitar (critério, não achismo):** VPS real + cliente pagante real + esse cliente
**pedindo** armazenamento. Aí é demanda, não suposição — e aí vira uma feature com dono, e
provavelmente com preço. Antes disso, guardar é resolver um problema que ninguém tem.

⚠️ Isto **não** fecha a porta: o dia em que existir um editor de vídeo aqui dentro, guardar passa a
ter função — e função é o único critério que este projeto usa para deixar um arquivo no disco.

**DEC-54 — o arquivo vive enquanto tem função, não por prazo.** Enquanto houver destino agendado,
enviando ou passível de nova tentativa, o vídeo fica. É o que impede quebrar agendamento: uma
publicação marcada para daqui a um mês precisa do arquivo daqui a um mês.

**DEC-55 — carência curta depois que tudo resolve.** 🚫 **REVOGADA pela DEC-59.** Meia decisão é
pior que nenhuma: a carência gastava disco, criava expectativa de acervo e ainda obrigava a
explicar um prazo que não deveria existir. Republicar passou a reaproveitar o texto (DEC-61), e o
motivo da carência desapareceu junto.

**DEC-56 — a miniatura é sempre nossa.** ~40 KB contra ~20 MB. É o único ponto que funciona nos
quatro estados de um vídeo (privado, público, removido, rejeitado) — o YouTube não devolve
miniatura de vídeo privado, e os nossos sobem privados. Ela **nunca** é apagada.

**DEC-57 — indexar serve para assistir, nunca para reconhecer.** Player da rede depende de vídeo
público; o histórico não pode depender de uma condição que não controlamos.

**DEC-58 — a assinatura do conteúdo identifica o reenvio, e vira parte da prova.** Bytes idênticos,
mesmo com outro nome, voltam ao **mesmo registro** — com o histórico inteiro. Por dono: reaproveitar
entre contas cruzaria dado de clientes diferentes, a única coisa que este projeto não pode errar.

**DEC-59 — o arquivo sai quando o último destino termina.** Sem espera, sem prazo. Ele existia para
subir; subiu, acabou a função. Quem apaga é o **motor**, no instante em que a publicação vira
terminal — o comando diário virou rede de segurança, não a regra. Esperar por ele guardaria o vídeo
por até 24 horas sem motivo nenhum.

**DEC-60 — o compositor não sugere nada.** Não há lista de vídeos anteriores. Quem publica envia o
arquivo ali, naquele momento. A ausência de biblioteca é a promessa aparecendo na tela.

**DEC-61 — republicar reaproveita o TEXTO, não o arquivo.** Título, legenda e hashtags vêm prontos;
o vídeo é reenviado. É o preço honesto de não guardar — e a DEC-58 devolve tudo ao mesmo registro.
O botão vale **sempre**, não só enquanto o arquivo estiver aqui.

**DEC-62 — prazo só para abandono.** Quem envia e desiste no meio deixa um arquivo sem dono. Isso
não é carência, é limpeza de lixo: existe só para o arquivo sobreviver enquanto a pessoa escreve a
legenda (`MIDIA_LIMPAR_ABANDONADO_EM_DIAS`, padrão 1).

⚠️ **O que ISSO custa, dito na cara:** quem publicou no YouTube hoje e resolver publicar no
Instagram amanhã precisa **enviar o vídeo de novo**. Não dá para baixar de volta: a API do YouTube
não tem método de download, e o vídeo indexado depende de ser público — os nossos sobem privados.
Esse é o preço, e ele é dito na própria janela em vez de aparecer como surpresa.

---

### 2026-08-04 — Conexões deixa de ser tela (DEC-63 a DEC-65)

**DEC-63 — Conexões não é uma tela; é uma seção da Visão geral.** As duas respondiam à mesma
pergunta — *"como está tudo?"* — em lugares diferentes: a Visão geral avisava "3 conexões estão
para vencer" e mandava para outra tela, onde a mesma informação aparecia de novo. Quem abria o
painel para ter certeza precisava passar pelas duas.

⭐ **A regra que separa os casos:** *Publicar* é uma **ação** — tem começo, meio e um resultado,
e modal serve. *Conexões* é **estado**, e estado pertence à tela que a pessoa abre primeiro.

⚠️ **A grade fica visível, não dentro de um modal.** Clicar numa rede já abre um modal (contas,
conectar, desconectar); abrir isso a partir de outro empilharia duas camadas para uma tarefa só. E
o semáforo do token (DEC-32) é o diferencial — atrás de um clique, vira algo que se descobre depois
de a publicação já ter sido perdida.

**DEC-64 — a rota `/conexoes` sai inteira.** Saneamento radical: sem tela, não há rota, nem método
no controller, nem item de menu, nem teste que a abra. Tudo o que apontava para lá aponta para
`/painel` — inclusive o retorno do OAuth, que é onde a resposta precisa aparecer: a pessoa
autoriza no Google e volta vendo o semáforo da conta que acabou de conectar.

**DEC-65 — o resumo das redes tem fonte única (`ResumoDasRedes`).** Duas montagens do mesmo array
divergiriam, e a divergência apareceria como número diferente para o mesmo fato em telas
diferentes — o defeito que mais rápido faz alguém parar de confiar no painel.

⭐ **O menu do cliente ficou com dois itens:** Visão geral e Publicações. Publicar e conectar são
ações, e ação não é lugar para onde ir.

---

### 2026-08-04 — A forma padrão do painel é o quadrado (DEC-66 e DEC-67)

**DEC-66 — quadrado de lado FIXO, e retângulo só quando ele é a forma certa.** O painel usava
faixas largas com um número dentro: a linha inteira gasta para exibir três dígitos, e o olho
varrendo da esquerda à direita para lê-los.

⚠️ **Fixo, não elástico.** Grade que estica devolve quadrado quando há sete itens e retângulo
quando há dois — o painel muda de cara conforme o conteúdo, e a pessoa não reconhece a própria
tela. Com lado fixo e quebra de linha, três quadros ou onze desenham o mesmo ritmo. Vive em
`components/quadro.tsx`.

⛔ **Retângulo continua existindo onde é a forma certa:** aviso com frase e ação, e lista de
passos — texto quer largura. O que não existe é retângulo **por acidente de grade**.

**DEC-67 — "Suas redes" mostra só as conectadas; o catálogo mora num modal.** Mostrar as catorze
de uma vez enchia a tela de coisa que não é da pessoa, e o que é dela ficava do mesmo tamanho de
um "em estudo". Escolher rede acontece uma vez; olhar as suas, todo dia — então o catálogo inteiro
fica atrás do quadro `+`.

⚠️ Conta **desconectada** não conta como conectada: a linha dela sobrevive porque o histórico
aponta para ela, mas quem desconectou não quer a rede de volta na tela. O que já foi publicado
continua em Publicações, com os links.

⭐ **O servidor continua mandando o catálogo inteiro** — quem filtra é a tela. Filtrar no servidor
deixaria o modal de conectar sem o que oferecer.

**DEC-68 — publicação aparece SÓ na tela de Publicações.** A Visão geral mostrava uma prévia das
últimas cinco. Era a mesma lista com outra moldura — e lista duplicada envelhece: um dia uma passa
a mostrar o que a outra não mostra (um filtro novo, uma coluna nova, um status novo), e aí nenhuma
das duas é confiável.

⭐ **O que fica na Visão geral são os NÚMEROS**, que é coisa diferente: eles respondem *"como
está"*, não *"o que eu publiquei"*. O caminho até a lista de verdade vira um *ver publicações* ao
lado do título — um link em vez de uma cópia.

---

### 2026-08-04 — O alerta que nunca desligava (bug encontrado na tela)

**⛔ O aviso "sua conexão está para vencer" aparecia SEMPRE, e não queria dizer nada.**

`Credencial::venceEmBreve()` comparava `expira_em` com "vence nos próximos 7 dias". Só que no
YouTube o `expira_em` guarda o **token de acesso**, que dura **1 hora** e é renovado sozinho pelo
`TokenDoGoogle`. Uma data que está sempre a 1 hora de distância está sempre dentro de 7 dias — o
alerta ligava no primeiro login e não desligava nunca mais.

⭐ **A pergunta certa não é "quando vence?", é "ainda dá para renovar?".** Tendo `refresh_token`, o
vencimento de hora em hora é encanamento, não notícia. Quando a renovação falha de verdade
(`invalid_grant`), a conta vira `expirada` e quem avisa é o **status** — não o relógio.

⚠️ **Alerta que nunca desliga é pior que alerta nenhum:** ensina a pessoa a ignorar a faixa
amarela, e no dia do problema de verdade ela não olha. O bloco de pendências foi construído
justamente com a regra de sumir quando não há nada (DEC-31 em espírito), e este bug a furava.

**Duas correções que vieram junto:**

**O aviso não dizia de qual rede era.** *"A conexão de Gabriel Ferreira de Moraes está para
vencer"* nomeia o canal e mais nada — e nome de canal do YouTube costuma ser nome de pessoa, o que
faz o aviso parecer outra coisa. Agora diz a rede, a conta e a consequência.

**A ação do aviso apontava para `/painel#redes`.** Era link para a tela onde a pessoa já estava,
com uma âncora suja na barra de endereço para rolar até um bloco visível. Virou botão: abre o
detalhe daquela rede ali mesmo. ⚠️ Quando o aviso cobre redes diferentes, **não há ação** — escolher
uma seria decidir por conta própria qual é o problema; quem aponta é o ponto colorido na grade.

---

### 2026-08-04 — Grupo: a rede de canais de uma linha de conteúdo (DEC-69 a DEC-80)

Plano completo em [`15-plano-grupo.md`](15-plano-grupo.md).

⚠️ **Isto contraria a regra 0.G do contrato**, que proibia workspaces cedo. A regra não estava
errada — ela impedia inventar estrutura antes de existir problema. O problema apareceu, e a
**ressalva foi escrita no `CLAUDE.md` no mesmo commit**. Billing e multiempresa continuam proibidos.

**DEC-69 — grupo é a rede de canais de uma linha de conteúdo, e o grupo É seus canais.** Quem
produz notícias e novelas tem dois trios de canais; o compositor mostrava os seis juntos, e a única
coisa separando um do outro era a atenção no momento de marcar a caixinha. Grupo não é pasta vazia
que depois se enche: sem canal, não tem o que ser.

⭐ **O nome saiu por eliminação, e a escolha economizou um bloco inteiro de trabalho.** "Rede" é
rede social na UI toda; "perfil" é a tela de Minha conta; "marca" desenha o **nome do produto**
(0.N) e ainda é termo auditado do TikTok (`marca_propria`) e a unidade de preço; "projeto" significa
o próprio software em 34 pontos da documentação. Adotar "marca" teria exigido seis renomeações em
cascata, inclusive em texto que a auditoria das plataformas leu.

**DEC-70 — uma conta social pertence a UM grupo só.** Canal em dois grupos traz de volta o risco de
publicar no lugar errado. Adotar o mais apertado agora é barato; afrouxar depois também. O inverso
exigiria mexer em dado existente.

**DEC-71 — grupo é MODO, não filtro.** Filtro se esquece de marcar; modo se está dentro. O seletor
fica sempre visível — inclusive dentro do compositor, que cobre a tela inteira justamente durante o
gesto que não desfaz.

**DEC-72 — o grupo corrente vive na SESSÃO, nunca na URL.** É preferência de visualização, não
recorte compartilhável (diferente da aba de Publicações, que vive na URL de propósito). Trocar de
grupo é `POST`: com `GET`, o prefetch do navegador trocaria o modo sozinho.

**DEC-73 — ⭐ o grupo de uma publicação vem das CONTAS escolhidas, e o servidor recusa contas de
grupos diferentes.** A sessão só decide o que a tela mostra; a verdade vem das contas. É esta trava
que torna impossível uma aba velha publicar no grupo errado, e ela sobrevive a qualquer defeito de
interface.

⚠️ Junto veio a correção de um defeito antigo: conta que não é do dono era **descartada em
silêncio** da lista de destinos. Passa a lançar. Filtrar calado é a implementação errada desta
mesma decisão.

**DEC-74 — dono é SEGURANÇA, grupo é ORGANIZAÇÃO.** Dono tem Global Scope que **lança** sem
contexto. Grupo tem filtro **explícito** na consulta da tela. ⛔ **Não existe trait
`PertenceAoGrupo` nem Global Scope de grupo:** job, comando e conciliação não têm grupo corrente.

⚠️ Consequência que quase passou batido: nas consultas com `join` cru, o `whereIn` escopado é a
**única** coisa que aplica o escopo de dono (o Global Scope não acompanha um `->join()`). O filtro
de grupo **soma** a ele — trocar um pelo outro substituiria uma trava de segurança por uma
preferência de tela.

**DEC-75 — `publicacoes.grupo_id` é gravado e imutável; toda contagem sai dele.** É exceção
consciente à regra "derivado nunca vira coluna": deduzir pelo canal faria o número histórico de um
grupo mudar sozinho quando alguém reorganizasse os canais, e número que muda retroativamente não
serve para decidir nada.

**DEC-76 — arquivar grupo é soft delete, e só vale para grupo sem canal.** Nunca o último. Arquivar
com canais deixaria canal conectado e invisível — publicando por trás da tela, ou falhando sem
ninguém ver. ⚠️ Sem tela de lixeira: recuperar é operação de suporte.

**DEC-77 — mover canal entre grupos existe; o histórico NÃO vai junto.** Sem mover, o primeiro erro
de cadastro vira permanente e a pessoa cria canal duplicado. O que já foi publicado fica onde saiu —
é o que sustenta a DEC-75.

**DEC-78 — criar grupo não troca de modo sozinho.** Trocar sozinho esvazia o painel inteiro sem
explicar, e a pessoa conclui que o sistema apagou o trabalho dela — ainda mais porque a tela de
vazio existente diz literalmente que ela nunca publicou nada. O diálogo pergunta, e avisa que canais
e posts continuam onde estavam.

**DEC-79 — publicar leva o modo junto.** Depois de enviar, o grupo corrente passa a ser o grupo das
contas, e o aviso o nomeia: *"Enviamos para 2 contas de Notícias."* Sem isso, publicar de uma aba em
outro grupo cai numa lista vazia com um aviso verde por cima — a tela contradiz a mensagem, e o
reflexo da pessoa é publicar de novo.

**DEC-80 — aviso de SAÚDE ignora o filtro de grupo.** Autorização vencendo e conta parada aparecem
sempre, nomeando onde estão (*"«X», em Novelas, parou de publicar"*). Aviso de **volume** segue o
grupo corrente. Conta da outra ponta não pode morrer calada só porque a pessoa está olhando outro
grupo.

⛔ **Fora de escopo, de propósito:** cobrança/limite por grupo (é coluna, não estrutura) e o
dashboard de métricas por grupo — este bloqueado por fora, porque enquanto o aplicativo do YouTube
estiver em modo de Testes todo vídeo sobe privado, e vídeo privado não tem métrica pública.

**DEC-81 — o seletor TROCA; quem administra é Minha conta › Grupos.** Criar, renomear e arquivar
saíram do menu suspenso e viraram uma aba de configuração, ao lado de Perfil, Senha e Aparência.

⭐ **Trocar de grupo é o gesto de todo dia; administrar é o de uma vez por mês.** Juntos, o raro
atrapalha o frequente: a lista de grupos — a única coisa que a pessoa abriu o seletor para ver —
ficava espremida no topo de três botões que ela quase nunca usa.

⚠️ A aba **só existe para o cliente**: o admin não publica, e para ele seria uma tela vazia com um
botão que não leva a nada.

**DEC-82 — gerenciar grupo é janela, não tela.** Criar e renomear são gestos de um campo só, e
arquivar é uma confirmação. Uma página inteira obrigava a sair de onde se está e voltar depois,
para uma tarefa de quinze segundos. A janela abre do próprio seletor, que vive na barra do topo de
toda tela — enterrar em Minha conta faria a pessoa procurar em configurações uma coisa que ela tem
na frente dos olhos o tempo todo.

⚠️ As contagens de canais e publicações passaram para as props compartilhadas, num `withCount` de
uma consulta só: elas são o **motivo** de o botão de arquivar estar apagado, e a janela abre sem
pedir nada ao servidor.

**DEC-83 — o painel é quadrado com a quina quebrada, e o canto sai de um lugar só.** Estava tudo
arredondado demais, e por um motivo concreto: metade dos componentes usava `rounded-xl`/`rounded-2xl`,
que **ignoravam** o token de raio e ficavam na escala embutida do Tailwind. Eram dois sistemas de
canto na mesma tela, sem ninguém ter decidido.

Agora `--radius` (4px) governa de `sm` a `2xl`. Mudar aquela linha muda o painel inteiro.

⛔ **`rounded-full` só em círculo de verdade** — avatar, ponto do semáforo, barra de progresso.
Etiqueta de status, chip de conta e selo sobre miniatura viraram retângulos de quina quebrada.

⚠️ Saíram junto **dez primitivos de UI sem nenhum importador** (`badge`, `avatar`, `select`,
`sheet`, `collapsible`, `tooltip`, `skeleton`, `alert`, `card`, `separator`). Cada um carregava um
raio próprio, e código morto com estilo próprio é o caminho mais curto para o drift voltar.

**DEC-84 — a tela diz EXCLUIR, e o *soft delete* é assunto do banco.** A linha sobrevive para
auditoria, e só para isso. ⛔ **A interface nunca promete que dá para recuperar:** prometer criaria
uma expectativa que tela nenhuma cumpre, e *"excluí sem querer, dá para voltar?"* vira chamado de
suporte para uma coisa que só existe no banco.

⚠️ Revoga o "arquivar" da DEC-76 como **palavra** — o mecanismo continua o mesmo.

**DEC-85 — só rede CONECTADA segura a exclusão de um grupo.** Rede desconectada não segura mais.

⭐ **O motivo é o que a pessoa vê:** conta desconectada não aparece na grade de redes. Um grupo
onde só restaram contas desconectadas parece **vazio** para ela — e ficava impossível de excluir,
sem nada em tela explicando o quê. Ela ficava presa num grupo que não enxerga.

⚠️ A linha da conta continua sobrevivendo: o histórico aponta para ela.

**DEC-86 — a lista de "primeiros passos" sai da Visão geral.** Ela marcava dois passos: conectar
uma rede e publicar o primeiro vídeo.

⛔ **Ensinava o que a tela já mostrava.** A grade de redes está logo abaixo dela, vazia, dizendo a
mesma coisa; e o número "0 no ar" já responde o segundo passo. Ocupava a porta de entrada de quem
abre o painel todo dia para resolver uma coisa que acontece uma vez.

⚠️ **Isso não fecha a porta para receber quem chega** — só reconhece que uma lista de tarefas na
tela principal não era a resposta. Quando existir uma primeira-vez de verdade, ela nasce como
primeira-vez, e não como bloco permanente.

⭐ **Saneamento radical:** saiu a seção, a prop, o método do controller, a interface do front e o
teste. Meia remoção deixaria um payload calculado a cada requisição para ninguém.

**DEC-87 — conectar rede é configuração DO GRUPO, e o modo segue a intenção.** A janela de
gerenciar passou a mostrar, em cada grupo, as marcas das redes que ele tem — e a oferecer o
conectar ali.

⭐ **Coerente com a DEC-69:** o grupo *é* seus canais, então dizer quais canais são dele é
exatamente o que se configura. Antes isso estava partido: renomear num lugar, escolher os canais
em outro.

⚠️ **A armadilha, e como ela foi fechada.** Conectar para um grupo em que a pessoa não está faria
a conta nascer onde ela não está olhando — o mesmo acidente que o grupo existe para evitar, só que
na hora de conectar em vez de na hora de publicar. Por isso **conectar dali troca o grupo em foco
antes de abrir o catálogo**, pela mesma regra do publicar (DEC-79): o modo segue a intenção.

A alternativa seria carregar o grupo de destino pela ida e volta do OAuth — mais código, mais
estado atravessando um site de terceiro, e um jeito a mais de errar.

⛔ **A grade de "Suas redes" na Visão geral fica onde está.** Ela não é configuração: é o semáforo
diário, o que avisa que uma conexão vai quebrar **antes** de quebrar (DEC-32). Dentro de um modal
de configuração, o aviso viraria algo que só se vê quando se vai procurar.

⚠️ **Complemento da DEC-87:** o quadro de "conectar uma rede" saiu da grade da Visão geral. Com o
conectar morando na configuração do grupo, deixá-lo ali seria uma **segunda porta para a mesma
coisa** — e duas portas para a mesma coisa é exatamente como nasce o *"conectei e não apareceu"*:
uma delas conecta no grupo em foco, a outra na intenção da pessoa, e elas divergem.

Grupo sem rede passa a dizer onde se resolve isso, em vez de mostrar um retângulo tracejado vazio.

---

### 2026-08-05 — A Visão geral passa a ver tudo (DEC-88 a DEC-92)

Plano completo em [`16-plano-visao-geral.md`](16-plano-visao-geral.md).

⚠️ O desenho saiu de **três propostas independentes julgadas por três críticos** — pela prova, pelo
ritmo e pela comparação entre grupos. Duas foram derrubadas por defeito estrutural, não por gosto:
a fita de semanas escalava cada grupo por si (10 por semana e 1 por semana desenhavam a mesma
altura), e a faixa clicável punha um alvo de 10px de altura para trocar o grupo em foco.

**DEC-88 — a Visão geral soma TODOS os grupos, e por isso o total deixa de ser link.** Com mais de
um grupo, *"ver publicações"* abriria uma lista de um grupo só que não bate com o número mostrado.
⭐ Isto também resolve a tensão da DEC-80: o aviso de saúde furava o filtro porque a tela não via
tudo. Agora ela vê.

**DEC-89 — aviso sobre conta de OUTRO grupo entra no grupo, em vez de abrir janela vazia.**
⛔ **Defeito que existia em produção:** os avisos carregam contas de todos os grupos, mas a grade de
redes é filtrada pelo grupo em foco. Clicar *"Resolver"* abria a janela daquela rede com **zero
contas dentro**. A ação virou *"Entrar no grupo"*; depois de entrar, o mesmo aviso reaparece com
"Resolver" e a conta está lá.

**DEC-90 — na Visão geral a unidade é o POST, não a publicação.** Publicação é o vídeo enviado; ela
vira **um post por canal**. ⛔ **Segundo defeito de produção:** o aviso contava destinos e escrevia
*"3 publicações não subiram"* — número diferente do que a aba de Publicações mostra, para o mesmo
fato.

**DEC-91 — o grupo em foco só muda por gesto rotulado com verbo.** Gráfico não troca modo, e
segmento de barra não é botão: 10px de altura é um vinte e quatro avos do alvo mínimo de toque, e
no celular não existe `hover` para avisar antes do clique.

**DEC-92 — o gráfico mora atrás de um contrato que não sabe o que é grupo.** Recebe número
absoluto, rótulo, cor já resolvida e a medida compartilhada. Não formata texto, não navega, não
guarda estado, não escolhe cor, não anima. ⭐ É isso que permite trocar CSS puro por **ApacheECharts**
mexendo em um arquivo só — e é isso que impede o gráfico de virar dono da regra de negócio.

⚠️ **A medida compartilhada é o argumento inteiro da comparação:** todas as barras medidas pelo
total do maior grupo. Sem ela, cada barra se escala por si e o grupo de 5 posts desenha do mesmo
tamanho do de 40 — um gráfico que mente por construção.

⭐ **A regra que governa o desenho:** esforço se mede por comprimento; **problema nunca**. A barra
carrega o volume; o ponto de saúde tem o mesmo tamanho no grupo de 40 e no de 5. E quando nenhum
post subiu de N, a coluna vira texto vermelho de tamanho fixo — é o caso em que a medida
compartilhada mais engana, porque 5 falhas em 5 desenham menos vermelho que 3 em 44.

⛔ **Fora, de propósito:** porcentagem (com 3 posts, "100%" vira "67%" e parece piora), ranking,
meta, escala logarítmica, e métricas de rede — bloqueadas enquanto o aplicativo do YouTube estiver
em Testes, porque todo vídeo sobe privado e a tela mostraria zero em tudo.

---

### 2026-08-08 — Threads: a publicação ganhou guardiões, e dois defeitos apareceram no caminho

⭐ **A Fase 4 do plano 21 fechou com 17 guardiões verdes.** O publicador do Threads já existia; o que
faltava era o que trava o comportamento sutil dele — e escrever teste foi o que fez os dois defeitos
abaixo aparecerem. Nenhum deles daria erro em desenvolvimento: os dois só apareceriam com um post
real na mão de alguém.

⛔ **A lista de erros da rede tem erro de digitação — e ela muda entre leituras.** A documentação
oficial escreve `INVALID_ASPEC_RATIO`, **sem o `T`**. Numa segunda leitura da mesma página, no mesmo
dia, `INVALID_FRAME_RATE` apareceu como `FAILED_FRAME_RATE`. O código casava a palavra inteira, então
a recusa **mais comum de todas** — a proporção do vídeo — cairia no genérico *"o Threads recusou este
post"*. A pessoa ficaria sem saber o que arrumar num vídeo que só precisava ser reenquadrado. O
casamento passou a ser por **pedaço estável** (`ASPEC`, `FRAME_RATE`, `BIT_RATE`…), que funciona com
a grafia errada de hoje e com a corrigida de amanhã; os dois guardiões cobrem as duas grafias.

⛔ **Cota estourada virava falha permanente.** O Threads não devolve código de erro próprio para
"acabaram as 250 do dia" — quem sabe é o endpoint `GET /{id}/threads_publishing_limit`, que existe e
está documentado (na primeira leitura a página devolvia 404, e a cópia local registrava o contrário).
Sem consultá-lo, a publicação de número 251 seria marcada como falha e queimaria as três tentativas
contra um limite que só volta amanhã. ⚠️ **Limite diário é espera, não falha (DEC-24).** O endpoint é
consultado **só depois** de uma recusa acontecer: uma chamada a mais no caminho do erro, nenhuma no
caminho normal. E na dúvida a resposta é "não estourou" — se a consulta da cota falhar, o motivo que
a pessoa vê continua sendo o que a rede deu; inventar "limite do dia" a partir de uma chamada que nem
respondeu esconderia o erro real.

⚠️ **O que ainda não aconteceu:** nenhum post saiu no Threads de verdade. A conta conecta e a
publicação está escrita e travada por teste — falta a prova de campo.

---

### 2026-08-09 — LinkedIn: a rede que não deixa provar

⭐ **Quinta rede conectável, com 32 guardiões verdes** — e a primeira em que a documentação oficial
obrigou a mudar uma promessa do produto, não só o código.

⛔ **A tese do produto não cabe inteira aqui.** A DEC-31 diz que o painel só afirma que subiu depois
de **reler** o post na rede. No LinkedIn, reler exige `r_member_social`, que é *restricted — available
to approved users only*. As únicas permissões abertas a qualquer desenvolvedor são `profile`, `email`
e `w_member_social`: **escrever, sim; ler, não.**

⭐ **O que sobra ainda é bastante, e vale medir com precisão:** dá para conferir que o vídeo chegou,
que foi aceito e que terminou de processar (`GET /rest/videos/{urn}` responde com a permissão de
escrita), e dá para ter o identificador do post. ⚠️ **A falha assíncrona clássica — a que o produto
existe para pegar, o vídeo que a rede aceita e depois recusa — é detectável.** O que fica de fora é a
remoção por moderação **depois** de publicado.

**DEC-106 — o LinkedIn tem um grau de certeza próprio, e a tela diz qual é.** Não existe "conferido"
para esta rede. O link aparece com uma ressalva escrita por extenso, e ela chega também a quem usa
leitor de tela. ⛔ Reusar a frase das outras redes seria afirmar uma conferência que não aconteceu —
o defeito exato que o produto critica nos concorrentes. E raspar a página pública do post, que
tecnicamente funcionaria, está fora: é violação dos termos de uso da API.

**DEC-112 — a renovação do token é aviso, não serviço.** *"Programmatic refresh tokens are available
for a limited set of partners."* O token vive 60 dias e a renovação passa pelo navegador da pessoa.
⛔ **Não existe `RenovarTokensDoLinkedin`** — um comando que "renova" e não renova seria pior que não
ter: a conexão morreria em silêncio com um serviço verde dizendo que está tudo bem. Tem guardião
travando a ausência dele.

⛔ **Três armadilhas que a documentação enterra, e que custariam caro em produção:**

**O identificador do post vem no CABEÇALHO `x-restli-id`, e o corpo do `201` vem vazio.** Procurá-lo
no JSON acharia `null`, o motor concluiria que falhou — com o post já publicado — e na passada
seguinte publicaria de novo. Publicação não tem desfazer.

**O exemplo de dividir o arquivo está errado.** A documentação manda `split -b 4194303` e devolve o
intervalo `firstByte: 0, lastByte: 4194303`, que **inclusive** dá 4.194.304 bytes. Seguir o exemplo
deixaria cada pedaço um byte curto, e o erro só apareceria em arquivo grande — com o vídeo montado
errado no fim, depois de tudo responder sucesso. O código lê o intervalo que a API manda.

**A mesma página diz 500 MB numa seção e 5 GB na outra.** Vale o menor: recusar em 500 MB é seguro
nas duas leituras; aceitar 5 GB pode estourar no meio do envio, com o arquivo já subindo.

⚠️ **E o limite é contado em REQUISIÇÕES, não em posts:** 150 por pessoa por dia, e uma publicação
gasta 1 inicializar + N pedaços + 1 finalizar + 1 conferir + 1 postar. Um vídeo de 40 MB são 14. O
teto real fica perto de **10 publicações por dia**, não 150 — e por isso recomeçar um envio pergunta
antes se o vídeo já subiu, em vez de reenviar às cegas.

---

### 2026-08-09 — TikTok: a rede que já pensa como o produto

⭐ **Sexta rede conectável, com 43 guardiões verdes.** E, ao contrário do LinkedIn, aqui a
documentação oficial trouxe boa notícia: **o TikTok implementa a tese do produto por conta
própria.**

O campo chama `publicaly_available_post_id`, e a documentação diz que ele *"returns post_id only for
public posts approved by moderation"*. Ou seja, a rede separa dois estados que todo mundo trata como
um só:

- `PUBLISH_COMPLETE` **sem** o identificador → subiu, ainda não liberado;
- `PUBLISH_COMPLETE` **com** o identificador → subiu **e a moderação aprovou**.

**DEC-115 — a prova é o identificador, não o `PUBLISH_COMPLETE`.** Parar no status seria o erro que o
produto critica: a rede aceitou, e o post pode não estar visível para ninguém. Enquanto o
identificador não chega, o destino continua **processando** — que é a verdade.

⛔ **E nada disso existe enquanto o aplicativo não for auditado** (DEC-116). Sem auditoria a rede
recusa qualquer privacidade que não seja `SELF_ONLY`, e post privado nunca recebe o identificador —
logo, não há link de prova. Mesma situação do YouTube antes da auditoria do Google, e a mesma
resposta: publicar funciona, o painel diz que é privado, e diz por quê já na hora de conectar.

⛔ **Cinco armadilhas que a documentação enterra:**

**O `total_chunk_count` arredonda para BAIXO.** *"video_size ÷ chunk_size, rounded down."* Um vídeo
de 12 MB com pedaço de 5 MB dá **dois** pedaços, e o último carrega 7 MB. Todo mundo escreveria
`ceil()` aqui — é o que faz sentido em qualquer outro protocolo de envio em partes — e o número que
não bate faz o envio falhar **depois** de o arquivo inteiro ter subido. A aritmética virou classe
própria, com sete guardiões que não tocam em rede nenhuma: aritmética se prova com números na mão.

**O token vive 24 horas.** O prazo mais curto do painel, por larga margem. Um comando de madrugada
não dá conta: vídeo agendado, fila parada ou worker que dormiu encontrariam token morto no meio do
dia. Por isso a renovação acontece **na hora de publicar** (DEC-118), e o comando diário ficou como
rede de segurança para as contas que passam dias sem publicar.

**O `refresh_token` gira.** *"The returned refresh_token may be different than the one passed in the
payload."* Guardar o antigo dá uma conexão que funciona hoje, funciona amanhã e um dia para sem
ninguém ter mexido em nada — o pior tipo de defeito, porque não tem evento para investigar.

**Erro dentro de um HTTP 200.** O `status/fetch` responde 200 e põe o erro em `error.code`. Confiar
no código HTTP trataria `invalid_publish_id` como sucesso, e o destino ficaria esperando para sempre
por um post que não existe.

**Perguntar ao criador antes de publicar é obrigatório** (DEC-117) — e não é etiqueta: privacidade
fora da lista devolve `privacy_level_option_mismatch`. ⭐ E a resposta trouxe algo que não existia em
nenhuma outra rede: **`max_video_post_duration_sec` é por CONTA, não por plataforma.** Contas novas
têm teto menor. O limite fixo do `EspecificacaoDaRede` passou a ser o máximo possível; o real se
pergunta, e a recusa acontece antes de subir um byte.

⚠️ **Duas frases que não podem culpar quem publicou:** `auth_removed` é a pessoa tendo tirado a
autorização no aplicativo do TikTok — dizer "falhou" mandaria ela procurar defeito no arquivo; e
`reached_active_user_cap` é o **nosso** aplicativo que estourou a cota do dia, não a conta dela.

⚠️ **O que ainda não aconteceu:** nenhum vídeo saiu no TikTok, e o aplicativo no portal ainda não
existe.

---

### 2026-08-09 — Revisão do que foi escrito hoje: quatro defeitos, e nenhum deles quebrava teste

⚠️ **Suíte verde não é ausência de bug.** Os 519 testes passavam, e mesmo assim os quatro achados
abaixo estavam lá — três deles publicariam coisa errada na conta de alguém.

⛔ **1. O TikTok podia publicar o mesmo vídeo DUAS VEZES.** O caminho: os pedaços sobem todos, e a
resposta do último se perde — tempo esgotado, processo morto, worker reiniciado. O destino volta para
a fila e o publicador começava do zero: novo `publish_id`, arquivo inteiro de novo, **dois vídeos no
ar**. O YouTube já tapava isso com `quantoJaSubiu` e o LinkedIn com `jaSubiu`; aqui faltava. Agora o
envio pergunta antes se já aconteceu — e só `FAILED` autoriza refazer.

⛔ **2. O LinkedIn podia publicar o mesmo post até vinte vezes** (DEC-125). Criar post não é
idempotente e a rede não aceita chave de repetição. Um tempo esgotado *depois* de ela ter recebido o
pedido significa post publicado e resposta perdida — e a conciliação roda vinte vezes. E não dá para
conferir antes de criar: reler post exige permissão restrita (DEC-106). ⭐ Entre repetir e duplicar,
ou parar e avisar, agora o produto **para e avisa**, com a frase dizendo que o post pode ter subido.

⛔ **3. As hashtags nunca chegavam ao Threads nem ao TikTok.** A pessoa escrevia, a tela contava, e
nada saía. Bluesky, Facebook e Instagram sempre usaram `Destino::textoFinal()` — o helper que junta
legenda e hashtags e respeita o texto próprio de cada destino. Esses dois publicadores montavam o
texto à mão e jogavam fora as hashtags **e** o `legenda_override`. ⚠️ No TikTok isso é grave de um
jeito próprio: hashtag é o mecanismo de descoberta da plataforma, e um post sem hashtag é um post que
ninguém acha.

⛔ **4. O título estourava o limite de texto em silêncio.** Threads e TikTok não têm campo de título:
ele sobe **colado na legenda**, e os dois dividem um orçamento só. A conferência media os dois
separados. No Threads, com 500 bytes, um título de 200 e uma legenda de 400 passavam nas duas
conferências e estouravam ao chegar lá — a recusa acontecendo depois de o vídeo inteiro ter subido.
Agora a régua mede **exatamente o que sobe**: título, legenda e hashtags juntos. ⚠️ E o contador da
tela mede a mesma coisa — front e servidor precisam contar igual, senão são duas verdades para o
mesmo texto.

⚠️ **E uma decisão que estava errada no papel: a DEC-116.** Ela dizia que o TikTok sem auditoria
seria *"mesma resposta do YouTube: publica privado e a tela diz por quê"*. **No YouTube o vídeo
privado tem endereço**; no TikTok o identificador só vem para post público aprovado, então um post
privado nunca ganha link — e `marcarPublicado()` recusa destino sem link, de propósito (DEC-31).
Publicar ali só poderia terminar em "falhou" depois de o vídeo ter subido de verdade, com o painel
oferecendo republicar e duplicando. ⭐ **DEC-124: sem auditoria, o TikTok não publica — recusa antes
de subir, e diz por quê.** Mesmo desenho do Threads sem endereço público (DEC-101).

**530 testes verdes** ao fim, com 11 guardiões novos — um para cada defeito acima, mais os das
frases que não podem culpar quem publicou.

---

### 2026-08-09 — X: a rede em que o TEXTO muda o preço

⭐ **Sétima rede conectável, com 36 guardiões verdes.** E a primeira em que o achado principal não é
técnico: **aqui publicar custa dinheiro, e uma escolha de texto muda o custo em treze vezes.**

| Operação | Preço |
|---|---|
| Post: criar | US$ 0,015 |
| ⛔ **Post: criar (com URL)** | **US$ 0,200** |
| ⭐ Post: ler o que é seu | US$ 0,001 |

Em 500 posts por mês: **US$ 7,50 sem link, US$ 100,00 com link em todos.** Não existe faixa gratuita
— os créditos são comprados antes, no console deles.

⚠️ **A pesquisa antiga (doc 10) estava desatualizada num ponto importante:** ela falava em assinatura
e níveis de acesso. Hoje é **pagamento por uso, com crédito comprado antes**. Reler a fonte oficial
antes de escrever código pegou isso.

**DEC-126 — o painel avisa o custo do link ANTES de publicar.** Quando a legenda tem link e o X está
entre as redes escolhidas, a tela diz, com o número na frente. ⛔ **Aviso, nunca bloqueio:** pode ser
exatamente o que a pessoa quer, e quem decide gastar é ela — o que não pode é ela descobrir na
fatura. ⚠️ E o aviso é da tela, não do publicador: quando o publicador roda, o gasto já aconteceu.

⭐ **E a prova tem preço baixo:** reler o próprio post é *owned read*, US$ 0,001. A tese do produto
(DEC-31) custa um décimo de centavo por conferência aqui — mas é a primeira rede em que **insistir
gasta crédito de alguém**, e não só limite de uso (DEC-127).

⛔ **Quatro armadilhas próprias desta rede:**

**O código de autorização vive 30 SEGUNDOS** (DEC-128) — uma ordem de grandeza abaixo de qualquer
outra; o LinkedIn dá 30 minutos. Qualquer coisa feita antes da troca pode consumir a janela e queimar
a autorização, e o erro que aparece é o genérico *"a autorização não pôde ser confirmada"*, que manda
a pessoa procurar no lugar errado. A troca passou a ser a **primeira** coisa da volta.

**PKCE obrigatório** (DEC-129) — primeira rede do painel a exigir. O segredo nasce na ida e é exigido
na volta: vai para a sessão junto com o `state`, ou a troca falha **sem recuperação possível**.

**O token vive 2 HORAS**, mais curto que o do TikTok (DEC-130). E **sem `offline.access` não existe
token de renovação nenhum**: a conexão funciona por duas horas e morre, sem nada ter mudado.

**`media.write` é escopo separado** (DEC-131), e o sintoma de esquecer engana: a conta conecta, o
texto subiria, **e o vídeo não**.

⚠️ **E o que a documentação NÃO diz** (DEC-132): nenhuma página consultada declara tamanho máximo,
duração, proporção, codecs, quantidade de mídias por post nem limite de caracteres do texto. Nada
disso foi inventado — os números que o painel aplica têm procedência escrita no próprio código: o
perfil canônico do produto, ou a doc 10, identificada como fonte de terceiro.

⭐ **Uma observação que só apareceu com quatro redes de envio em pedaços prontas:** cada uma ordena
os pedaços de um jeito diferente — YouTube e TikTok por faixa de bytes, LinkedIn pela ordem dos
recibos, X por um número. Quatro convenções. Tentar generalizar isso numa abstração só erraria nas
quatro.

**566 testes verdes** ao fim.

---

### 2026-08-09 — Revisão do X: e o mesmo defeito reaparecendo em rede nova

⚠️ **A suíte estava verde com 566 testes, e mesmo assim os cinco achados abaixo estavam lá.** Dois
deles são o **mesmo defeito que a revisão anterior já tinha corrigido em outras redes** — o que diz
que corrigir caso a caso não bastava: faltava um guardião que pegasse a *próxima* rede.

⛔ **1. O título sumia em CINCO redes, não em duas.** A revisão anterior tinha achado isso no Threads
e no TikTok. Faltou perguntar onde mais. **Bluesky, Instagram e X** também jogavam fora o título que
a pessoa escreveu — nenhuma das três tem campo próprio para ele, e nenhuma o colava no texto. A
pessoa escrevia, a tela contava, e a palavra não chegava.

⭐ **A correção agora é uma regra, não cinco remendos:** rede sem campo de título soma o título na
legenda, e o guardião **enumera num lugar só quem tem campo próprio** (YouTube, Facebook, LinkedIn).
Ligar uma rede nova **quebra o teste de propósito** — é para alguém decidir na hora onde o título dela
vai parar, em vez de descobrir depois que ele sumiu.

⛔ **2. O laudo dava VERDE em imagem que o publicador ia recusar — em quatro redes.**
`aceitaImagem` não quer dizer "a plataforma suporta imagem": a própria frase do laudo diz *"não
publica imagem **por aqui**"* — é sobre o painel. LinkedIn, Instagram, Facebook e X declaravam que
aceitavam, e os quatro publicadores recusavam na primeira linha. A pessoa via "formato aceito" e
recebia "o X recebe vídeo por aqui" depois. ⭐ Mesmo remédio: um guardião que percorre **todas** as
redes e exige que o laudo concorde com o publicador.

⛔ **3. Crédito acabado no envio da mídia caía numa frase genérica.** O `402` estava tratado só na
criação do post. No envio, virava *"o X recusou o envio do vídeo"* — mandando a pessoa reexportar um
arquivo perfeito quando o que faltava era **dinheiro no console do X**.

⛔ **4. Os preços do X existiam escritos em dois idiomas** (DEC-133). US$ 0,20 e US$ 0,015 estavam em
PHP e em TypeScript. No dia em que o X mudar a tabela, uma das cópias fica errada — e é a errada que
a pessoa lê. Agora a frase vem pronta do servidor, e a tela só decide quando mostrar. O guardião
passa pela **requisição de verdade**, porque o defeito que ele pega é a frase existir no servidor e
não chegar no React.

⚠️ **5. Um item do plano estava marcado como feito sem estar.** O X manda um campo dizendo quando
voltar a conferir (`check_after_secs`), e o código não o lê — a conciliação usa a espera própria
dela, que é sempre **maior** que a pedida. Nunca perguntamos cedo demais; perguntamos tarde. O plano
foi corrigido para dizer isso em vez de afirmar o que não acontece.

**573 testes verdes** ao fim, com 7 guardiões novos — e dois deles são do tipo que quebra quando
alguém ligar a próxima rede sem decidir.

---

### 2026-08-09 — As redes que faltavam: três entraram, três ficaram de fora com motivo

⭐ **Onze redes com código agora, e duas delas testáveis hoje** — sem esperar aprovação de ninguém.

**Pinterest.** ⭐ A documentação deles é aplicação JavaScript e não entrega nada para leitura
automática; a **spec OpenAPI oficial** entregou tudo, com os limites exatos. Foi a regra do projeto —
*preferir spec legível por máquina* — pagando sozinha. Duas coisas só existem aqui: **o Pin mora num
quadro** (e por isso conectar traz um canal por quadro, como a Meta traz um por Página), e **o
arquivo sobe para a AWS**, num formulário assinado onde o campo do arquivo tem que ir por último —
o S3 ignora o que vier depois dele, e recusa com um erro de XML que não menciona ordem nenhuma.

**Mastodon.** ⛔ Não é um serviço: é um protocolo, com milhares de servidores independentes. Isso
obrigou uma **terceira forma de conectar** (a pessoa diz onde a conta mora antes de autorizar) e uma
**coluna nova** (`contas_sociais.servidor`) — derivar o endereço do nome de exibição montaria URL de
API a partir de texto de tela. ⭐ E ele é a rede de **barreira zero de verdade**: o protocolo deixa
registrar o aplicativo por API, sem autenticação, então não há portal nenhum para cadastrar nada.

⭐ **E é a primeira rede do painel que aceita chave de idempotência.** Isso **inverte** a regra do
LinkedIn, do X e do Pinterest: lá, um tempo esgotado depois de a rede receber o pedido obriga a parar
e avisar, porque repetir criaria um segundo post. Aqui repetir é seguro, e a chave é o `ulid` do
destino.

**Discord.** ⭐ A conexão mais simples do painel: a pessoa cria um webhook no canal e cola o
endereço. ⛔ O endereço **é** a credencial, e por isso é partido na hora de guardar — identificador na
conta, segredo na credencial cifrada. ⛔ E `wait=true` é obrigatório: sem ele o Discord responde 204 e
*"unconfirmed messages don't generate errors"* — a publicação poderia falhar em silêncio com o painel
dizendo que deu certo. ⚠️ É também a primeira rede que **não aceita o vídeo do perfil canônico**: 10 MB
é o piso do servidor sem impulsionamento.

⛔ **E três ficaram de fora, com motivo escrito** (doc 28): o **Snapchat** não tem API de publicação
orgânica — nem é fila de aprovação, é ausência de endpoint; o **Google Meu Negócio** publica ficha de
estabelecimento, não vitrine de vídeo vertical; e o **LinkedIn Página** é o único dos três que já
está tecnicamente pronto — falta só a aprovação da LinkedIn, e é justamente ela que devolveria a
prova que falta naquela rede.

---

### 2026-08-09 — Revisão das três: o mesmo defeito, pela terceira vez

⚠️ **A suíte estava verde com 608 testes.** Os quatro achados abaixo estavam lá, e três deles são a
**mesma família** que as duas revisões anteriores já tinham corrigido em outras redes.

⛔ **1. As hashtags não contavam no limite — em toda rede com campo de título próprio.** As duas
revisões anteriores trataram o caso "rede sem campo de título". Faltou perguntar o que acontecia nas
outras: nelas, a legenda era medida sozinha e as hashtags entravam **de graça**. No Pinterest, com 800
de descrição, quinze hashtags passavam na conferência e eram recusadas na rede — depois do vídeo
inteiro ter subido.

⭐ **A correção virou regra geral, não um quarto remendo:** a legenda medida é **a que sobe**,
hashtags sempre incluídas, título junto só onde não há campo próprio. Nenhuma rede do painel tem
campo separado de hashtag — elas viajam dentro do texto, e agora a régua sabe disso.

⛔ **2. O LinkedIn também jogava as hashtags fora.** Ele montava a legenda à mão, como o Threads e o
TikTok faziam. Passou despercebido nas revisões anteriores por um motivo específico: o título **tem**
campo próprio nesta rede, e a atenção ficou nele.

⛔ **3. O link de prova do Discord ia para o lugar errado.** O endereço de uma mensagem tem três
partes — servidor, canal e mensagem — e só duas estavam ali. O link caía em `channels/@me`, que é
conversa privada: **um link de prova que não prova nada.** O servidor só existe na resposta do
webhook, não na da mensagem, então ele passou a ser guardado na conexão — na mesma coluna que a rede
federada usa.

⛔ **4. No Mastodon, erro definitivo virava três horas de espera.** Qualquer resposta que não fosse
200 devolvia "ainda processando", e a conciliação insistia vinte vezes contra um `404` ou um token
revogado — para terminar com a frase genérica *"a rede aceitou mas não confirmou"*, que não diz nada
sobre a causa.

**614 testes verdes** ao fim.

⭐ **E dois guardiões novos são do tipo que quebra sozinho:** ligar uma rede nova sem decidir onde o
título dela vai parar, ou sem alinhar o laudo de imagem com o publicador, **derruba a suíte de
propósito**. Foi assim que o Pinterest, o Mastodon e o Discord tiveram essas três decisões tomadas na
hora de entrar, em vez de descobertas depois.

---

### 2026-08-10 — O painel passa a responder "funcionou?" e "continua no ar?"

⭐ Três frentes do [plano 32](32-plano-metricas-e-prova.md), e a segunda delas conserta a promessa
central do produto.

**As quatro redes do escopo passaram a responder.** Instagram, Facebook e TikTok ganharam leitor de
métrica — antes só o YouTube respondia, e comparar redes era um gráfico de uma rede só.

⛔ **E métrica custou permissão nova** (DEC-143): `instagram_manage_insights`, `read_insights` e
`video.list`. ⚠️ Isso parece contrariar o escopo mínimo (DEC-41) e não contraria: o mínimo é o mínimo
**para o que o produto faz**, e responder "funcionou?" passou a ser parte disso. Continuamos sem pedir
permissão de apagar nem de alterar.

⭐ **E recusar não apaga a tela.** Curtida e comentário vêm do objeto da mídia e **não custam
permissão**; só visualização e compartilhamento exigem a permissão nova. Por isso são **duas
chamadas** de propósito: quem recusar continua vendo dois números, em vez de a tela inteira ficar
vazia por causa de uma permissão opcional.

**O número passou a ter ontem** (DEC-144). Até aqui o comando diário **sobrescrevia**: sabíamos
quanto tem, nunca quanto tinha. Agora grava uma linha por destino **por dia** — reexecutar no mesmo
dia atualiza a linha, não cria um segundo ponto. ⚠️ Ela nasceu **antes da tela** de propósito: só
começa a valer depois de coletar, então a curva de vida do post existe daqui a uma semana sem
ninguém fazer nada.

⭐ **A prova deixou de expirar em três horas e meia** (DEC-145). A conciliação perguntava 20 vezes e
parava para sempre — e moderação de rede não trabalha nesse relógio. Um vídeo derrubado no dia
seguinte continuava marcado "No ar", **com a mesma confiança de sempre**: a crítica que fazemos aos
concorrentes passava a valer para nós a partir da quarta hora. Agora um comando diário relê o que
está publicado, guarda **quando** conferiu, e rebaixa o que sumiu.

⛔ **E a máquina de estados brigou com isso na primeira execução — com razão de existir, e errada de
fato.** Ela dizia `Publicado => []`: uma vez no ar, para sempre no ar.

⭐ **DEC-148 — entra o `Removido`, "Saiu do ar".** As duas alternativas mentiam: deixar como
publicado afirma que continua no ar o que a rede tirou; marcar como falhou afirma que **nunca subiu**
o que subiu. Ele é a única saída de `Publicado`, é terminal, e não volta — republicar é outra
publicação, com outra data e outra prova.

⚠️ **E o estado novo quebrou duas suposições da tela**, as duas encontradas na revisão: a lista
considerava "em andamento" tudo que não fosse publicado ou falhado — então um post removido ficaria
**atualizando para sempre** —, e o alerta do cartão só olhava para `falhou`. As duas foram corrigidas
com a lista de terminais explícita.

**628 testes verdes**, com 13 guardiões novos.

⚠️ **O que falta:** a tela. O total com as três ressalvas (DEC-146) e a comparação do mesmo vídeo
entre redes (DEC-147) ainda não existem — mas o dado que elas desenham **já está sendo coletado**.

---

### 2026-08-10 — Revisão do que acabou de entrar: o estado novo vazou

⚠️ **628 testes verdes, e dois defeitos.** Os dois vieram da mesma causa: o estado `Removido`
(DEC-148) apareceu em lugares que ninguém tocou.

⛔ **1. Uma publicação com destino removido ficava em "Publicando…" para sempre.** `deduzirStatus`
somava só `Publicado` e `Falhou` para decidir se a publicação terminou. Um destino `Removido` não era
nem um nem outro, a conta nunca fechava, e a publicação inteira voltava ao estado de espera — para
algo que já tinha acabado.

⚠️ E o defeito ficava **invisível na tela do jeito pior**: o cartão mostrava o giro de "publicando"
num vídeo que subiu, saiu do ar, e não ia mudar mais.

⛔ **2. A segurança da reconferência era ACIDENTAL.** Em sete dos onze publicadores, `conciliar()` é
onde o post **nasce** — e o comando novo chama exatamente esse método, agora em destinos já
publicados. Nenhum republica hoje; mas por motivos **diferentes**: uns têm guarda explícita no
identificador, outros são leitura de ponta a ponta. Isso é o tipo de garantia que some numa
refatoração, sem ninguém perceber, publicando duas vezes.

⭐ Agora existe um guardião que percorre **as oito redes** e exige que nenhuma chamada de **criação**
saia da reconferência. ⚠️ E ele olha **caminho e método**: ler um post do X é `GET /2/tweets/{id}` e
criar é `POST /2/tweets` — o caminho sozinho não distingue, e a primeira versão do teste reprovava a
própria leitura.

⚠️ **Duas dívidas anotadas, não consertadas:**

- `reconferido_em` é gravado e **não aparece na tela**. A frase *"no ar · conferido hoje"* é metade
  do valor da DEC-145, e ela entra com a Fase 4.
- A tela nova (total com ressalvas e comparação por vídeo) segue pendente — mas o dado que ela
  desenha já está sendo coletado desde hoje.

**631 testes verdes.**

---

### 2026-08-10 — A tela das métricas, e o vazamento que quase passou

⭐ **Fase 4 do [plano 32](32-plano-metricas-e-prova.md) entregue — as quatro fases fechadas, 641
testes verdes.**

**O total apareceu, com as três ressalvas juntas do número** (DEC-146): ele é dito como **soma
bruta**, com a frase de que cada rede conta do seu jeito, e avisa **de quantas redes veio** quando
falta alguém. ⚠️ Sem esse último aviso, uma rede que não respondeu hoje viraria queda de desempenho
que não aconteceu. E ele **só aparece quando existe leitura** — um zero ali diria que ninguém viu,
quando o certo é que ninguém leu.

⭐ **DEC-149 — e aqui uma decisão anterior teve que ser refinada.** A DEC-147 mandava comparar o mesmo
vídeo entre redes; a DEC-146 proibia comparar redes pela soma. As duas estavam certas e **juntas não
fechavam**: 900 do YouTube ao lado de 900 do TikTok continua sendo régua diferente, mesmo com o vídeo
igual. O que fecha as duas é comparar cada post com **a média dos posts daquela rede** — mesma régua
dos dois lados. ⚠️ E só com base: menos de três posts e a frase cala.

⛔ **E o achado do dia: `Destino` não tem escopo de dono.** Quem tem são `ContaSocial` e `Publicacao`.
A primeira versão da soma consultava `Destino::query()` direto — varrendo o banco **inteiro, de todos
os clientes**.

⚠️ **Quem barrou foi o escopo da conta, e barrou quebrando**: a relação vinha nula e o código
estourava com "property on null". O teste que pegou não era um teste de soma — era o
*"não mostra post de outro dono"*, que já existia. **Barulho é melhor que silêncio, mas depender
disso é sorte**, e agora há guardião próprio somando dois donos e exigindo o número certo.

⭐ **A dívida tipográfica foi paga inteira.** Quinze lugares escreviam abaixo do piso de `0.8125rem`
— e num telefone, com a fonte base em 13px, `0.6rem` vira **7,8 pixels**. O que estava nesse tamanho
não era enfeite: era o número de visualizações, o "entregue em baixa", o nome do grupo. O produto
gastava atenção para dizer a verdade e depois a escrevia pequena demais para ser lida.

⚠️ E o guardião novo pegou um caso que o meu próprio `grep` tinha deixado passar (`0.6rem`) — porque
ele varre por **expressão numérica**, não por lista de tamanhos conhecidos. Escrever menor de novo
agora derruba a suíte.

**E a data da reconferência chegou à tela:** *"no ar · conferido há 2 horas"*. Era metade do valor da
DEC-145 e estava sendo gravada sem ninguém ver.
---

### 2026-08-10 — O passo que a Meta pede e ninguém avisava (DEC-150)

⛔ **Conectar o Facebook falhava com uma mensagem que podia estar mentindo.** A Meta responde a lista
de Páginas vazia em **duas situações diferentes**: quem não tem Página nenhuma, e quem tem mas
**passou pelo passo em que ela pergunta quais Páginas liberar sem marcar nenhuma**. O painel dizia
*"nenhuma Página foi encontrada nesta conta"* nos dois casos — mandando quem tem três Páginas criar a
quarta, em vez de refazer a autorização, que é o que resolve.

⭐ **E o pré-requisito não estava escrito em lugar nenhum.** O modal de conexão tinha o aviso do
YouTube (vídeo privado até a auditoria) e nada da Meta. Quem chegava ali não sabia que:

- Facebook e Instagram **só publicam em Página**, nunca em perfil pessoal — regra da Meta;
- existe um passo, **no site da Meta**, onde é preciso **marcar** a Página;
- **com mais de uma Página**, todas as marcadas viram **contas separadas** aqui — e escolher onde
  publicar continua sendo decisão de quem publica, post a post;
- o **Instagram vem junto**, sem conexão própria, desde que seja profissional e vinculado à Página.

⚠️ **O terceiro item é o que mais assusta**, e por isso está em negrito na tela: sem ele, quem marca
duas Páginas conclui que passou a publicar nas duas de uma vez.

⭐ A régua aqui é a mesma da DEC-41 e da DEC-46: **o que a rede exige se diz ANTES de autorizar**, na
tela em que a pessoa decide — não depois, num erro que ela vai ler como defeito do painel.

---

### 2026-08-10 — O vídeo que subia e sumia (DEC-151)

⛔ **Achado em campo, e era o pior tipo de defeito: o que parece perda de dado.** O envio chegava a
100%, a barra sumia e a tela voltava a pedir um arquivo — como se o vídeo tivesse evaporado. Ele
estava **salvo no banco o tempo todo**, com laudo lido.

⚠️ **A causa era de FORMA, não de lógica.** O servidor manda `midiaEnviada` como **objeto**
(`{ulid, nome, miniatura, laudo…}`) e a tela lia como se fosse o ULID em texto:

```js
const enviada = (pagina.props as { midiaEnviada?: string }).midiaEnviada;
if (enviada) aoEnviar?.(enviada);   // ← o objeto inteiro ia parar onde só cabe o ULID
```

O objeto era **verdadeiro**, então o `if` passava; o compositor guardava o objeto em `data.midia`; e
a comparação `midiaEnviada?.ulid === data.midia` nunca batia. Resultado: arquivo salvo e invisível
— e publicar ficava impossível.

⛔ **E o `as` era o cúmplice.** Afirmar um tipo ao TypeScript é **desligar a conferência** dele
naquele ponto: o compilador tinha como saber, e foi mandado calar. Agora só se afirma o campo que se
usa (`{ ulid: string }`).

⭐ **Segundo defeito, no mesmo arquivo, achado junto:** o tratamento de recusa lia só `arquivo` e
`tipo`. Qualquer outra recusa virava `undefined` — a barra sumia **sem escrever nada**. Recusa muda,
mas ficar em silêncio é sempre defeito, então agora existe fallback para a primeira mensagem que
vier e, na falta de todas, uma frase.

⚠️ **Guardião novo mira a FORMA do que sai do servidor** (`MidiaVoltaEscolhidaTest`), porque
`has('midiaEnviada')` passaria com o ULID solto — que é exatamente o estado quebrado. Três casos: o
objeto com `ulid`, o nulo de quem não enviou nada (DEC-60, o compositor não tem acervo) e a recusa
com mensagem no campo `arquivo`.

**644 testes verdes.**

**Correção do registro acima, no mesmo dia.** A primeira leitura do defeito estava errada e a
primeira correção não resolveu: o problema **não era objeto lido como texto**. Era **caminho**. O
campo de envio buscava `props.midiaEnviada`, e o servidor entrega em `props.compositor.midiaEnviada`
— um nível abaixo. O valor vinha `undefined`, o `if` nunca passava, e nada acontecia.

⛔ **A lição é a mesma, mais funda:** caminho escrito em texto dentro de um `as` é **duas** conferências
desligadas de uma vez — o tipo e o endereço. O TypeScript tinha como saber e foi mandado calar duas
vezes.

⭐ **Por isso a correção mudou de lugar, em vez de mudar de letra.** Escolher o arquivo virou
responsabilidade do **compositor**, que já recebe `midiaEnviada` como propriedade tipada e não
precisa cavar nada. O campo de envio agora só envia — e o `as` sumiu do projeto.

---

### 2026-08-10 — Duas ideias de campo, entregues (DEC-152 e DEC-153)

⭐ **[DEC-152](15-plano-grupo.md) — hashtags que já vêm escritas, guardadas no grupo.** Campo novo
na janela do grupo; o compositor começa com elas em post novo. **Ponto de partida, nunca carimbo:**
o texto continua editável e o que sobe é o que estiver escrito na hora de publicar. Ao republicar
valem as do post anterior (DEC-61).

⭐ **[DEC-153](12-plano-compositor.md) — post novo nasce com todas as contas marcadas.** Desmarcar
uma é mais rápido que marcar cinco, e o caso normal é publicar em tudo. ⛔ Republicar continua vindo
vazio — ali existe post que já subiu, e marcar sozinho publicaria de novo.

⚠️ **E uma recusa antiga caiu no caminho.** Publicar negava `#corte` — mas o campo da tela já separa
por `#`, então só chegava lá quem **colou** uma lista pronta. Recusar por um caractere que a pessoa
não escolheu escrever é recusa que ela não tem como entender. Agora o `#` é limpo antes da validação,
em `HashtagsLimpas`, **pelas duas portas** — publicar e o grupo. Espaço continua recusado, porque
`corte shorts` seria uma hashtag só e ela quis duas.

⛔ **O teste que afirmava a recusa foi reescrito, não removido:** ele agora afirma a regra nova e
guarda o caso do espaço, que continua valendo.

**652 testes verdes.**

**E o campo de hashtags não deixava digitar espaço — nos DOIS lugares.** Ele mostrava
`lista.join(' ')`: cada tecla virava lista, a lista voltava a virar texto **sem o espaço do fim**, e
o cursor ficava preso na primeira palavra. Dava para escrever uma hashtag e nunca a segunda.

⛔ **O defeito era anterior ao campo do grupo** — o compositor tinha o mesmo, e a cópia só o revelou.
Por isso a correção virou **um componente só** (`CampoDeHashtags`), usado pelos dois: dois campos com
a mesma regra são duas chances de a regra divergir.

⭐ Ele guarda o **texto cru** e deriva a lista dele. E colapsa espaço repetido para **um só** — mas
com `{2,}`, nunca com `trim`: o espaço do fim precisa sobreviver enquanto a palavra não terminou.

⚠️ **O `#` continua aparecendo enquanto se digita** e some só da lista. Caractere que desaparece
embaixo do dedo é pior que caractere indesejado: a pessoa acha que o teclado falhou.

⭐ **E a janela do grupo deixou de ser um beco** ([DEC-154](15-plano-grupo.md)). Ela listava as redes
do grupo sem nenhuma ação possível. ⛔ Pôr "desconectar" ali seria a **segunda porta** para uma ação
sem volta — então o quadrado virou botão e **leva** à janela da rede, onde desconectar e mover já
moram. Mesmo mecanismo do "conectar neste grupo": recado de uma requisição só.

**655 testes verdes.**

⛔ **E "Precisa de você" cobrava conserto de um gesto deliberado** (DEC-155). Desconectar uma conta
na mão a deixava sem publicar — e o painel a listava em vermelho, com um "Resolver" ao lado.

⚠️ A raiz é que `podePublicar()` responde **"não"** para dois casos **opostos**: a conta expirada
parou **sozinha** e precisa de conserto; a desconectada parou **porque a pessoa mandou**. Tratar as
duas igual é o painel discutindo a decisão de quem usa — e é assim que alguém aprende a ignorar o
bloco de avisos inteiro, inclusive no dia do problema de verdade.

⭐ O guardião veio em par: um exige o silêncio para a desconectada, o outro exige que a expirada
**continue avisando**. Sem o segundo, a correção poderia ter calado as duas.

---

### 2026-08-11 — Auditoria do módulo Meta contra a documentação (33)

⭐ **[Laudo completo em 33-auditoria-meta.md](33-auditoria-meta.md).** Três defeitos **silenciosos** —
nenhum aparecia como erro em lugar nenhum — e um bloqueio ainda sem causa provada.

⛔ **DEC-157 — a métrica do reel não é a métrica do vídeo.** Pedíamos `total_video_views`, que a
referência lista em *Video metrics*; reel usa *Reels metrics* (`blue_reels_play_count`). Como só
publicamos por `/video_reels`, **toda** leitura de visualização do Facebook pedia um campo que não
existe para o objeto lido. ⚠️ E a chamada não quebra: responde `200` vazio, e a tela dizia "sem
leitura" **para sempre** — indistinguível de rede que não respondeu.

⭐ **DEC-158 — a retomada do Instagram estava desligada por engano.** O código afirmava que ele não
documentava retomada. Documenta, no guia de *resumable uploads*, no campo `video_status` (o Facebook
usa `status`). Cada tropeço de rede reenviava o vídeo inteiro — o oposto do que a classe existe para
fazer. ⚠️ E há armadilha de grafia: `bytes_transfered` num exemplo, `bytes_transferred` no outro. As
quatro combinações agora são lidas.

⛔ **DEC-159 — o semáforo estava morto.** O diferencial declarado do produto (DEC-32) era acendido por
**1 dos 11** publicadores, e `marcarComProblema()` — escrito para isso — **não era chamado por
ninguém**. Na Meta passou despercebido porque o token de Página **não vence por tempo**; sem data,
parecia não haver o que vigiar. Ele não vence, ele **morre** (senha trocada, app removido, papel
perdido). A conta ficava **verde e "Conectada"** recusando toda publicação — e o `is_transient` da
Meta ainda fazia o motor queimar as três tentativas.

⚠️ **Corrigido só na Meta, de propósito.** As outras 9 redes ficaram como pendência escrita: cada uma
sinaliza credencial morta do seu jeito, e chutar o sinal de nove APIs de uma vez trocaria um silêncio
por nove alarmes falsos.

⭐ **Cada correção veio com o contraponto guardado** — erro comum de formato **não** derruba a conta.
Sem esse par, a correção do semáforo viraria "reconecte sua conta" a cada tropeço, que é o alarme
falso mais caro deste produto.

**664 testes verdes.**

⛔ **DEC-160 — e a causa do bloqueio era nossa, não da Meta.** O registro fechou o caso: sete
permissões concedidas, `pages_show_list` inclusive, e `{"data":[]}`. O código pedia
`instagram_business_account{...}` **aninhado** no `fields` do `/me/accounts` — uma viagem economizada
que a documentação não descreve (ela manda fazer **duas** chamadas, a segunda no nó da Página, com o
token dela).

⚠️ **Por isso funcionou antes e parou depois:** sem Instagram ligado, o campo aninhado não tinha nada
para resolver. No dia em que a Página ganhou um, a Meta parou de devolver **a Página inteira** — sem
erro, sem campo nulo. Lista vazia.

⛔ **E a suíte provava o caminho errado com convicção:** o `Http::fake` devolvia o Instagram aninhado,
reproduzindo uma forma de API que a Meta não documenta. **Teste verde sobre um `fake` inventado é
pior que teste ausente — ele afirma.**

⭐ **A hipótese mais convincente estava errada**, e vale registrar: a Página tinha sido restringida
por suspeita de personificação **no mesmo dia**. Perseguir isso levaria a mandar documento e esperar
48 horas por um problema que era nosso. Quem decidiu foi o registro, não o raciocínio.

**666 testes verdes.**

---

### 2026-08-13 — A configuração do Login para Empresas (DEC-162 e DEC-163)

⛔ **DEC-162 — o endereço de autorização ia com `scope`, e o app é Login para Empresas.** Esse tipo de
login se invoca por **`config_id`**; `scope` é do login clássico. Com o parâmetro errado, a Meta
aceita a autorização, concede **todas as permissões** e **não anexa ativo nenhum** — nem Página, nem
Instagram.

⚠️ **E falha em silêncio de um jeito cruel:** a integração aparece "Ativa" no Facebook com todos os
interruptores azuis, `/me/permissions` responde `granted` em tudo, e `/me/accounts` volta
`{"data":[]}`. **Três telas dizendo "deu certo" e nenhuma Página.** Quem soube distinguir *"autorizou
o aplicativo"* de *"autorizou o aplicativo NAQUELA Página"* foi o `debug_token`, no `granular_scopes`
**sem `target_ids`**.

⛔ **Isso derrubou três hipóteses minhas, todas plausíveis e todas erradas:** a Página restringida por
personificação (coincidência do mesmo dia), o campo aninhado no `/me/accounts` (desalinhamento real
com a documentação, mas não a causa) e a "configuração anterior" reaproveitada. O padrão dos três foi
o mesmo — **conclusão antes de evidência**. Quem decidiu foi a sonda, não o raciocínio.

⭐ **DEC-163 — permissões amplas agora, revisadas antes de pedir análise.** ⛔ **Regra da plataforma
inteira, não só da Meta:** toda rede pede o conjunto amplo enquanto o aplicativo é privado, e a
revisão permissão-por-permissão vira **passo obrigatório do checklist de publicação** de cada uma.

Decisão do dono, com a ressalva registrada em [21-plano-meta.md](21-plano-meta.md): enquanto o app
está **não publicado**, permissão a mais não custa nada. ⚠️ Permissão sem tela que a use segura a
aprovação do app **inteiro**, não só dela. A tabela de "o que usamos" × "o que foi pedido para o
futuro" ficou escrita — a revisão não pode depender de memória, senão vira esquecimento, e o
esquecimento aparece como reprovação semanas depois, com lançamento marcado.

⭐ **Configuração criada e ligada:** `META_CONFIG_ID` no `.env`, endereço de autorização agora saindo
com `config_id` e **sem** `scope`.

**668 testes verdes.**

⛔ **DEC-164 — e a causa era `business_management`, achada no fórum da Meta.** Duas confirmações
independentes: *"obrigatória para todas as versões da API"* desde a v19 (jan/2024), e *"o problema
acontece especificamente quando os usuários usam o Meta Business Suite — as Páginas deles não
aparecem em `/me/accounts` sem essa permissão"*.

⭐ **A linha do tempo do banco fecha com precisão de dia:** conexão funcionou **10/08 13:22**;
Instagram vinculado pelo Business Suite em **11/08**, o que tornou a Página um ativo de negócio; nada
mais funcionou desde então.

⚠️ **Ironia registrada:** `business_management` **estava** nas dez permissões da primeira
configuração — e o diálogo quebrava porque ela não constava dos **casos de uso** do app. Ao reduzir
para as sete "necessárias", o diálogo abriu e **saiu justamente a permissão que resolvia**.

⛔ **Risco de lançamento:** essa permissão é difícil de aprovar na análise. Sem ela, **cliente com
Página de negócio não conecta — e hoje isso é a maioria**. Está no laudo como item de lançamento.

**670 testes verdes.**

✅ **2026-08-14 — conectou.** Com `business_management` no caso de uso **e** na configuração, a
autorização passou: **Página `Teste` e `@gabrielmoraes1997` na mesma conexão**, três contas no painel.
A tese da carona (DEC-30) confirmada em campo: uma autorização, duas redes.

⭐ **A mesma sessão provou a regra da ordem, pelo avesso:** `pages_manage_engagement` marcada na
configuração **sem** estar no caso de uso derruba o diálogo; desmarcada, passa. **Primeiro no caso de
uso, depois na configuração.**

⭐ **O estado que funciona ficou registrado inteiro** em [21-plano-meta.md](21-plano-meta.md): as 9
permissões marcadas, a que fica **desmarcada de propósito** (`pages_manage_engagement`, ausente dos
casos de uso), o tipo de token, os casos de uso que precisaram existir e o endereço de autorização
que passa. **Registrado porque foi caro** — se um dia parar, é contra isto que se compara.

⛔ **DEC-165 — "saiu do ar" não é "não foi".** Achado de campo, minutos depois da reconferência
funcionar: o quadrado da rede mostrava **"não foi"** em vermelho para post que a pessoa **apagou com
a própria mão**.

⚠️ A decisão antiga estava escrita no código: *"entra em `naoSubiram` porque é o balde do que precisa
de atenção, e a frase da tela diz qual dos dois casos é"*. **No quadrado da rede não existe frase** —
existe a palavra, e a palavra era falsa.

⛔ **E o estrago é maior que um rótulo errado:** juntar os dois desfaz exatamente a distinção que a
reconferência (DEC-145) e o estado `Removido` (DEC-148) existem para criar, e faz o painel **acusar
falha onde houve uma decisão de quem publica**.

⭐ Balde próprio (`saiuDoAr`), número próprio no quadrado, e **cor neutra — não vermelha**: apagar um
post é escolha de quem publica, e o painel não discute escolha de ninguém.

**671 testes verdes.**

---

### 2026-08-14 — "Não preciso contar as falhas, preciso impedir elas" (DEC-166 e DEC-167)

⭐ **Frase do dono, e ela reorganiza a tela.** O quadrado da rede mostrava três números — no ar, não
foi, saiu do ar — e virou **placar**. Placar de falha não ajuda ninguém a agir.

⛔ **DEC-166 — a falha do YouTube era EVITÁVEL.** Publicar sem título subia na fila, o publicador
recusava lá na frente, o destino virava `Falhou` e o quadrado ficava vermelho. **O painel sabia que a
conta era do YouTube e sabia que o YouTube exige título** — e deixou enviar assim mesmo.

Agora `tituloObrigatorio` é propriedade da rede, conferida **antes do envio**, no mesmo lugar onde já
se recusa formato e texto longo demais. E o botão diz o que falta antes do clique: *"Escreva um
título para o YouTube"*. ⚠️ Recusar antes significa **não enfileirar** — nenhum destino nasce
condenado.

⭐ **DEC-167 — um número no quadrado, e o ponto no canto para o resto.** Fica só "no ar" (e "indo",
que responde *"cadê meu vídeo?"*). O que **precisa de você** — conta quebrada ou post que não subiu —
acende o ponto vermelho no canto, que já existia.

⛔ **E nada foi escondido:** a falha continua na janela da rede, em Publicações e no bloco "Precisa de
você". Ela some do **placar**, não do produto — a decisão antiga ("a falha aparece do lado do acerto,
no mesmo tamanho") valia para a tela grande, não para um quadrado de 6,5rem.

⚠️ "Saiu do ar" não acende ponto nenhum: apagar um post é decisão de quem publica, e o painel não
cobra providência de uma escolha.

**673 testes verdes.**

---

### 2026-08-14 — Auditoria do TikTok contra a documentação (23)

⭐ **Mesmo método da auditoria da Meta**, e ele achou dois defeitos silenciosos no código que já
estava escrito. [Laudo em 23-plano-tiktok.md](23-plano-tiktok.md).

⛔ **DEC-168 — `follower_count` estava no escopo errado.** A referência divide os campos do
`/v2/user/info/` em três escopos, e o nome do primeiro engana: `user.info.basic` dá **só identidade**.
Seguidor mora em **`user.info.stats`**, que não estava sendo pedido — então a leitura voltava `null`
para sempre e a tela dizia "sem leitura".

⚠️ **É o mesmo defeito que a Meta tinha** (DEC-157: `total_video_views`). Dois casos iguais em redes
diferentes tornam o padrão claro o bastante para virar conferência de rotina: **todo campo lido tem
um escopo, e o escopo precisa estar na lista.**

⭐ **DEC-169 — a declaração de IA sumia no TikTok.** A caixinha do compositor chegava ao Instagram
(`is_ai_generated`) e não ao TikTok (`is_aigc`). A mesma marcação valia numa rede e sumia na outra —
e isso não é preferência de interface, é transparência com quem assiste. ⚠️ Declarado só quando a
pessoa marca: `false` seria o painel afirmando "não é IA" por ela.

✅ **E o que estava certo ficou registrado** para a próxima auditoria não refazer o caminho: as regras
de pedaço (incluindo o arredondamento para baixo), o envio em sequência, o `creator_info` obrigatório,
o erro dentro do HTTP 200, os campos de métrica e a recusa antes de publicar sem auditoria.

**676 testes verdes.**

⭐ **DEC-171 — os documentos públicos, que são PORTA DE ENTRADA.** O cadastro do TikTok travou em
*Terms of Service URL* e *Privacy Policy URL* — os mesmos dois campos que estão pendentes na Meta.
Sem eles **não existe integração**, nem em modo de teste.

⛔ Então eles foram construídos: `/termos` e `/privacidade`, servidos pelo próprio painel, **fora de
qualquer grupo autenticado**. O robô da plataforma abre sem sessão — página que mandasse entrar
reprovaria a análise sem dizer por quê.

⚠️ **E eles descrevem o que o código FAZ, não o que seria bonito prometer.** Cada frase corresponde a
um comportamento que existe e tem guardião: o vídeo sai quando a publicação termina (DEC-59), a
credencial é cifrada e nunca aparece em tela, desconectar apaga o dado do titular na hora (exigência
do YouTube). Prometer o que o código não faz vira **declaração falsa numa análise** — e derruba o
aplicativo inteiro, não só aquele item.

⭐ Os termos dizem também **onde o produto NÃO promete**: que a rede aceite o vídeo, que a moderação
aprove, que o post fique no ar. É a mesma honestidade da tela, no papel.

⚠️ **Pendência registrada: o texto ainda não passou por revisão jurídica.** Serve para destravar
cadastro e teste; antes de cliente pagante, precisa de leitura de advogado.
