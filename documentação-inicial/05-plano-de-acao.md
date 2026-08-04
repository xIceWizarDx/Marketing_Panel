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
