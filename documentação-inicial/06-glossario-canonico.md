# GLOSSÁRIO CANÔNICO & MODELAGEM

> **FONTE ÚNICA DE NOMES (DEC-18).** Nenhum nome de papel, enum, tabela, coluna, rota ou
> pasta nasce no código sem estar registrado aqui ANTES. Este documento é a lei; o código
> obedece. Rótulos de tela são camada à parte (seção 5) e mudam à vontade.

---

## 1. Checklist de nascimento de um nome (obrigatório)

Antes de criar QUALQUER nome novo no projeto:
1. É um **conceito estável**? (nomeia o que a coisa É, não o valor do momento)
2. Já existe aqui um nome pra esse conceito? (**proibido sinônimo**)
3. Identificador **ASCII, sem acento/ç** (`publicacoes`, nunca `publicações`); acento é livre só no **rótulo**.
4. **Registrar aqui** → depois escrever o código (mesmo commit inclui glossário + código).
5. Se for enum visível na tela → adicionar o **rótulo** em `lang/pt_BR/rotulos.php`.

---

## 2. Convenções de nomenclatura (a lei)

| Coisa | Convenção | Exemplo |
|---|---|---|
| Tabela | plural, snake_case, PT-BR sem acento | `publicacoes`, `contas_sociais` |
| Model | singular, PascalCase | `Publicacao`, `ContaSocial` |
| FK | `<singular>_id` | `usuario_id`, `conta_social_id` |
| Boolean | adjetivo/particípio direto (sem prefixo `flag_`/`is_`) | `ativo`, `sucesso` |
| Timestamp de domínio | `<particípio>_em` / `<verbo futuro>_em` | `publicado_em`, `expira_em` |
| ID em sistema externo | **sempre** `identificador_externo` | id do canal/post na rede |
| Enum (classe) | singular, PascalCase, em `app/Enums` | `Papel`, `StatusDestino` |
| Valor de enum | lowercase snake_case (chave canônica, **nunca muda**) | `concluida_com_falhas` |
| Rota (path) | kebab-case PT-BR sem acento | `/visao-geral` |
| Rota (nome) | `area.recurso.acao` com verbos PT | `admin.clientes.criar` |
| Ações de rota | `listar · criar · salvar · atualizar · remover` | `cliente.publicacoes.salvar` |
| Controller/Service/Job/Action | radical PT-BR + sufixo padrão do framework (DEC-15) | `PublicacaoService`, `PublicarDestinoJob` |
| Página React | kebab-case (convenção do starter) | `visao-geral.tsx` |
| Componente React | PascalCase PT-BR | `CartaoConexao.tsx` |

**🔒 Território do framework (INTOCÁVEL — nomes padrão em inglês):** colunas `password`,
`remember_token`, `email_verified_at`, `created_at`, `updated_at`, **`deleted_at`** (o
`SoftDeletes` do Laravel procura esse nome — renomear para `arquivado_em` desliga o recurso
inteiro **em silêncio**, e a linha arquivada volta a aparecer); tabelas
`password_reset_tokens`, `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`,
`notifications` (canal database do Laravel — o "sininho", DEC-19); verbos HTTP/métodos
resource. **Motivo:** renomear coluna de auth exige override frágil — classe do bug
`senha_hash` do EmpiresCloud (null silencioso no login). Domínio em PT-BR; andaime, não.

⚠️ **Publicação ≠ post.** *Publicação* é o vídeo que a pessoa mandou; ela vira **um post por
canal** escolhido — e post, no banco, é `destinos`. As abas da tela de Publicações contam
**publicações**; os números da Visão geral contam **posts** (DEC-90). Trocar um pelo outro produz
dois números para o mesmo fato, que foi exatamente o defeito do aviso *"3 publicações não subiram"*.

**Derivado nunca vira coluna.** O que se calcula a partir de outra coluna não é armazenado
(ex.: proporção = `largura/altura`). Uma fonte por fato = zero drift de dado.

---

## 3. Papéis (enum `Papel`)

| Chave canônica | Rótulo inicial | Lado | O que pode |
|---|---|---|---|
| `admin` | Administrador | operador | Painel admin: gerir clientes, impersonar, ver logs |
| `cliente` | Cliente | cliente | Painel cliente: conectar redes, subir mídia, publicar |

- Coluna `usuarios.papel`, default `cliente`. **Sem tabela de papéis, sem RBAC** (0.G).
- Impersonação **não é papel**: é estado de sessão (seção 8).

### 🧭 Papel ≠ escopo (a distinção que evita retrabalho)

> **Papel** = *de que lado você está.* · **Escopo** = *quanto você enxerga desse lado.*

`comercial` e `suporte` são **papéis de operador**: entram por `/admin`, não pelo painel do
cliente. Mas "o comercial só vê a carteira dele" **não é papel — é escopo**, e escopo é um
módulo próprio (Global Scope + coluna de dono), não um `if` no controller.

**Por que isso importa:** no EmpiresCloud, papéis novos entraram herdando o alcance do admin,
e `suporte`/`dev` passaram a enxergar o **MRR da plataforma inteira**. O vazamento só apareceu
depois, em produção. Aqui a arquitetura força a decisão na hora de criar o papel.

### Como nasce um papel novo

1. **Case no enum** `Papel`.
2. **Responder as duas perguntas obrigatórias** — `ehOperador()` e `rotaInicial()` são `match`
   **sem `default`**: papel sem resposta **explode na hora**, em vez de herdar o poder do admin
   em silêncio. O teste-guardião de rótulos cobra as duas.
3. **Rótulo** em `lang/pt_BR/rotulos.php` (o guardião também cobra).
4. **Se o papel enxerga menos que o admin, é escopo** → módulo à parte, com teste de
   isolamento próprio. Papel novo **nunca** entra "vendo tudo até a gente ajustar".

As rotas de `/admin` usam `papel:` + `Papel::listaDeOperadores()` — não têm `admin` escrito na
mão. Um papel de operador novo passa a valer nelas **sozinho**, sem editar arquivo de rota.

## 4. Enums e máquinas de estado

### `Plataforma` — youtube · facebook · instagram · tiktok
Rótulos: YouTube · Facebook · Instagram · TikTok. Cada case aponta seu `Publicador`.

### `TipoMidia` — video · imagem

### `StatusConta` (conta social conectada)
| Chave | Rótulo inicial | Significado |
|---|---|---|
| `ativa` | Conectada | credencial válida *(chave `ativa`, rótulo "Conectada" — DEC-18 em ação)* |
| `expirada` | Reconectar | token venceu e o refresh não resolveu |
| `erro` | Com problema | revogada/rejeitada pela rede (`status_detalhe` explica) |
| `desconectada` | Desconectada | o cliente desconectou (linha **preservada** pelo histórico) |

Transições: `ativa → expirada|erro` (falha de token) · `expirada|erro → ativa` (reconexão/
refresh OK) · `ativa|expirada|erro → desconectada` (ação do cliente) · `desconectada → ativa`
(reconectar). **Desconectar nunca apaga a linha** — revoga o token na rede e **apaga só a
`credencial`**, preservando `destinos` históricos (requisito de histórico do doc 01 §4.4).

### `StatusDestino` (publicação × conta — o coração do motor)
| Chave | Rótulo inicial | Significado |
|---|---|---|
| `pendente` | Na fila | aguardando o job (inclui espera de backoff entre tentativas) |
| `aguardando_janela` | Aguardando vaga | quota diária do YouTube esgotada — republica amanhã (DEC-24) |
| `enviando` | Enviando… | job em execução (upload em andamento) |
| `processando` | Processando na rede | ⭐ a rede **aceitou** o arquivo, mas ainda **não confirmou** que está no ar (TikTok/YouTube moderam depois) |
| `publicado` | **No ar** | ⭐ **verificado**: relemos o post na rede e ele existe — tem `url_publicada` (terminal) |
| `falhou` | Falhou | erro **final** — tem `erro_mensagem`; pode reprocessar |

⭐ **DEC-31 — status honesto (o diferencial do produto).** `processando` existe porque
**HTTP 200 não é publicação**: TikTok e YouTube aceitam o upload de forma assíncrona e moderam
depois. **É PROIBIDO marcar `publicado` sem ter relido o post na rede e obtido o link.**
Concorrente nenhum faz isso — é o que a gente vende. O rótulo de tela nunca diz "publicado"
antes da verificação.

Transições permitidas (**qualquer outra lança exceção**):
`pendente → enviando` · **`enviando → processando`** (rede aceitou) ·
**`processando → publicado | falhou`** (conciliação confirmou ou a rede rejeitou) ·
`enviando → falhou` (erro no envio) · **`enviando|processando → pendente`** (retry automático:
backoff/release, ou **watchdog** de destino órfão) · `falhou → pendente` (reprocessar manual) ·
`pendente ↔ aguardando_janela` (quota diária esgotada/liberada) ·
**`enviando → aguardando_janela`** (a quota só é descoberta ao chamar a API, e aí o destino já
saiu de `pendente`).
**Escritor único:** só `PublicacaoService` muda status (métodos nomeados: `marcarEnviando`,
`marcarPublicado`, `marcarFalha`, `devolverParaFila`, `reprocessar`). Controller/Job nunca
fazem `update` direto.

**⚠️ Regras de sobrevivência do motor** (o que impede os 3 modos de falha reais):
1. **Watchdog de órfão.** Comando agendado marca `enviando → pendente` (ou `falhou` se
   estourou `max_tentativas`) quando o destino está `enviando` sem tentativa ativa há > N
   min — cobre worker morto/deploy/kill no meio do job. Sem isso o destino trava pra sempre
   e a publicação fica "Publicando…" eternamente.
2. **Retry ≠ falha final.** Falha transitória (429, timeout, 5xx) → `devolverParaFila`
   (`enviando → pendente`) enquanto `tentativas < max_tentativas`; só a última vira `falhou`.
   **`PublicacaoFalhou` (notificação) e `recalcularStatus` só disparam em estado terminal** —
   nunca em tentativa intermediária (senão o status oscila na tela e o e-mail dispara à toa).
3. **Conciliação (DEC-31).** Job agendado consulta a rede de volta até estado terminal e **só
   então** marca `publicado`, gravando o **permalink como prova**. Sem isso o motor mente —
   é exatamente o defeito que vendemos como diferencial.
4. **Anti-double-post.** Persistir o **handle de retomada ANTES** do efeito irreversível
   (session URI do upload resumável do YouTube; `creation_id` do container do IG) em
   `destinos.handle_externo`; no retry, **retomar/verificar** em vez de recomeçar. Definir
   `$timeout` do job e `retry_after` da fila **maior** que o maior upload (300MB > 90s
   default → re-entrega dupla sem crash nenhum). O UNIQUE (`publicacao_id`,`conta_social_id`)
   impede **dois destinos pra mesma conta** — ele **não** impede o mesmo destino postar duas
   vezes; quem impede é o handle + verificação.

### `StatusPublicacao` (agregado dos destinos)
| Chave | Rótulo inicial | Regra |
|---|---|---|
| `rascunho` | Rascunho | nunca enviada |
| `processando` | Publicando… | enviada; existe destino `pendente`/`enviando` |
| `concluida` | Publicada | todos os destinos `publicado` |
| `concluida_com_falhas` | Publicada com falhas | mistura de `publicado` e `falhou` |
| `falhou` | Falhou | todos os destinos `falhou` |

**Escritor único:** `PublicacaoService::recalcularStatus()` — chamado sempre que um destino
chega a estado **terminal** (`publicado`/`falhou`), nunca em tentativa intermediária.
Ninguém mais escreve essa coluna. Reprocessar destino → volta a `processando`.
Recálculo roda **dentro de transação com lock do registro pai** (2 destinos terminando no
mesmo instante em SQLite = leitura suja do agregado).

---

## 5. Rótulos — mecânica (uma fonte, back e front)

- **Fonte única:** `lang/pt_BR/rotulos.php` — array aninhado `enum → chave → rótulo`
  (`'papel' => ['cliente' => 'Cliente', ...]`, `'status_destino' => [...]`, ...).
- **Back:** todo enum visível tem `->rotulo(): string` lendo de `__('rotulos.<enum>.<valor>')`.
- **Front:** `HandleInertiaRequests` compartilha `rotulos` como prop global; hook
  `useRotulos()` consome (`rotulo('status_destino', destino.status)`). **Proibido** texto de
  enum solto em página React.
- **Teste-guardião:** para cada enum visível, TODO case tem rótulo (seção 10).
- `APP_LOCALE=pt_BR`. Mensagens de validação/UI também em `lang/pt_BR`.

---

## 6. Tabelas — coluna a coluna

> 🔵 = dado global · 🟢 = dado do cliente (isolado). Isolamento: tabelas 🟢 com `usuario_id`
> levam o **Global Scope** (filtra pelo usuário efetivo — seção 8); **filhas** (`credenciais`,
> `destinos`, `tentativas`) são acessadas **sempre via relação do pai**, nunca por query solta.

### 🔵 `usuarios` (Model `Usuario`)
| Coluna | Tipo | Regra |
|---|---|---|
| `id` | PK | |
| `nome` | string | |
| `email` | string **unique** | |
| `email_verified_at` | timestamp null | framework — libera acesso só confirmado |
| `password` | string **null** | framework — null p/ conta Google sem senha |
| `remember_token` | | framework |
| `google_id` | string null **unique** | vínculo de login Google (Socialite) |
| `papel` | string (enum `Papel`) default `cliente` | |
| `ativo` | boolean default `true` | admin pode desativar cliente |
| timestamps | | |

Admin cria cliente → **sem digitar senha**: dispara link de definição de senha (mesma
mecânica do reset, tabela framework `password_reset_tokens`) — padrão herdado do EmpiresCloud.

### 🟢 `grupos` (Model `Grupo`)

A **rede de canais de uma linha de conteúdo** (ex.: "Notícias", "Novelas"). O grupo **é** seus
canais: sem canal, ele não tem o que ser (DEC-69).

| Coluna | Tipo | Regra |
|---|---|---|
| `id` / `ulid` / `usuario_id` | PK / público / FK index | o `id` nunca sai do servidor |
| `nome` | string | ⛔ **sem UNIQUE** — índice único + soft delete faria "já existe um grupo com esse nome" para um grupo que a pessoa não enxerga mais |
| `deleted_at` | timestamp null | arquivado. Só arquiva grupo **sem canal** e **nunca o último** (DEC-76) |
| timestamps | | índice (`usuario_id`,`deleted_at`) |

⛔ **Grupo NÃO tem Global Scope** (DEC-74). Ele usa `PertenceAoUsuario` como toda tabela de
cliente — isso é o escopo de **dono**. Filtrar por grupo é sempre **explícito**, na consulta da
tela: job, comando e conciliação não têm grupo corrente, e um scope que lançasse aí quebraria o
motor.

⚠️ **Proibido `Grupo::withoutGlobalScopes()`** — ele derruba o escopo de dono **e** o de arquivado
de uma vez. Onde precisar furar o de dono, usar `withoutGlobalScope(EscopoDoUsuario::class)` com o
`where('usuario_id', ...)` escrito na mão.

### 🟢 `contas_sociais` (Model `ContaSocial`)
| Coluna | Tipo | Regra |
|---|---|---|
| `id` / `usuario_id` | PK / FK index | |
| `grupo_id` | FK `grupos` restrict | a conta pertence a **um** grupo só (DEC-70). ⛔ **NÃO entra na UNIQUE abaixo**: dentro dela, o mesmo canal poderia ser conectado em dois grupos, que é exatamente o que a única existe para impedir |
| `plataforma` | string (enum `Plataforma`) | |
| `identificador_externo` | string | id do canal/página/conta na rede |
| `nome_exibicao` | string | nome do canal/página (vem da API) |
| `avatar_url` | string null | |
| `status` | string (enum `StatusConta`) default `ativa` | |
| `status_detalhe` | string null | motivo humano do erro |
| `seguidores` | unsignedBigInteger **null** | inscritos, no YouTube. ⛔ **`null` ≠ `0`** (DEC-95): ou a rede não publica o número, ou o dono escondeu, ou ainda não lemos. ⚠️ Fora do `fillable` de propósito — escrita só por máquina, via `forceFill` |
| `metricas_lidas_em` | timestamp null | quando o contador acima foi lido. Vira frase no servidor (`DataEmPalavras`), nunca na tela |
| timestamps | | **UNIQUE** (`usuario_id`,`plataforma`,`identificador_externo`) — DEC-10 · índice (`usuario_id`,`grupo_id`) |

### 🟢 `credenciais` (Model `Credencial`) — filha 1:1 de conta
| Coluna | Tipo | Regra |
|---|---|---|
| `id` / `conta_social_id` | PK / FK **unique** | 1:1 |
| `access_token` | text, **cast `encrypted`** | nunca em resposta/log/tela |
| `refresh_token` | text null, **cast `encrypted`** | |
| `expira_em` | timestamp null | |
| `escopos` | json null | scopes concedidos |
| timestamps | | refresh serializado com `Cache::lock` |

### 🔒 Isolamento — como toda tabela de cliente nasce

Toda tabela com dono usa a trait **`PertenceAoUsuario`**, que traz Global Scope + carimbo
automático de `usuario_id` + relação `usuario()`. Nunca escrever `where('usuario_id', ...)` no
controller: dá a impressão de que a proteção mora ali, e ela não mora.

| Situação | O que fazer |
|---|---|
| Requisição web | nada — o dono é o usuário da sessão (inclui impersonação) |
| **Job / comando** | `ContextoDoUsuario::definir($usuarioId)` **antes** de qualquer consulta |
| Admin / seeder / relatório | `ContextoDoUsuario::semEscopo(fn () => ...)` — explícito e curto |

⚠️ **Sem dono definido, a consulta LANÇA EXCEÇÃO** (`EscopoDeUsuarioIndefinido`). É proposital:
filtrar por `usuario_id IS NULL` devolveria lista vazia sem erro, e o bug do worker sem sessão
só apareceria em produção como "sumiu tudo".

### 🟢 `midias` (Model `Midia`)
| Coluna | Tipo | Regra |
|---|---|---|
| `id` / `ulid` / `usuario_id` | PK / público / FK index | o `id` nunca sai do servidor |
| `tipo` | string (enum `TipoMidia`) | |
| `nome_original` | string | |
| `caminho` | string | relativo ao storage local (DEC-07); pasta por dono (DEC-50) |
| `mime_type` | string | |
| `tamanho_bytes` | unsignedBigInteger | |
| `duracao_segundos` | unsignedInteger null | só vídeo |
| `largura` / `altura` | unsignedInteger null | proporção/9:16 = **derivado** (accessor, nunca coluna) |
| `laudo` | json null | o que cada rede fará com o arquivo (doc 09) |
| `miniatura` | string null | ~40 KB; **nunca é apagada** (DEC-56) |
| `assinatura` | string null | SHA-256 do conteúdo, por dono — reencontra o reenvio (DEC-58) |
| `arquivo_removido_em` | timestamp null | nulo = o arquivo está aqui; preenchido = só o registro ficou |
| timestamps | | |

Validação de upload segue o **perfil canônico (doc 07 §6)**: vídeo MP4 H.264+AAC 9:16,
3–180s, ≤300MB; imagem JPEG. Regras **por conta** (ex.: teto dinâmico do TikTok via
`creator_info`) são validadas pelo `Publicador` na hora de publicar, não no upload.

⭐ **O arquivo vive enquanto tem função (DEC-59).** O produto é um caminho de publicação com
prova, não um lugar onde se guardam arquivos: assim que o último destino termina, o vídeo sai —
na hora, sem carência. Fica o registro inteiro (miniatura, laudo, links e prova). Quem responde
"quando pode sair?" é `App\Support\Midia\LiberacaoDeArquivo`, **fonte única** dessa conta.

### 🟢 `publicacoes` (Model `Publicacao`)
| Coluna | Tipo | Regra |
|---|---|---|
| `id` / `usuario_id` / `midia_id` | PK / FK / FK | |
| `grupo_id` | FK `grupos` restrict | ⭐ **gravado, não deduzido** — e é exceção consciente à regra "derivado nunca vira coluna". Deduzir pelo canal faria o número histórico de um grupo **mudar sozinho** quando alguém reorganizasse os canais, e número que muda retroativamente não serve para decidir nada (DEC-75). Vem das **contas escolhidas** no envio (DEC-73). Índice (`usuario_id`,`grupo_id`,`created_at`) |
| `titulo` | string null | exigido pelo YouTube |
| `legenda` | text null | base para todas as redes |
| `hashtags` | json null | array de strings **sem** `#` |
| `status` | string (enum `StatusPublicacao`) default `rascunho` | escritor único (seção 4) |
| `enviada_em` | timestamp null | quando disparou os jobs |
| timestamps | | |

### 🟢 `destinos` (Model `Destino`) — filha de publicação
| Coluna | Tipo | Regra |
|---|---|---|
| `id` / `publicacao_id` / `conta_social_id` | PK / FK cascade / FK restrict | |
| `status` | string (enum `StatusDestino`) default `pendente` | |
| `titulo_override` / `legenda_override` / `hashtags_override` | null | DEC-11 (null = usa o da publicação) |
| `opcoes` | json null | escolhas **por post** exigidas pela rede: TikTok `privacidade` (sem default), `disclosure_comercial`, `marca_propria`/`conteudo_patrocinado`, `musica_confirmada`; YouTube `visibilidade` |
| `identificador_externo` | string null | id do post/vídeo na rede |
| `handle_externo` | string null | session URI (YT) / `creation_id` (IG) — **anti-double-post** |
| `url_publicada` | string null | link final |
| `erro_mensagem` | text null | |
| `tentativas_feitas` | unsignedTinyInteger default 0 | controla retry vs falha final |
| `publicado_em` | timestamp null | |
| `visualizacoes` / `curtidas` / `comentarios` / `compartilhamentos` | unsignedBigInteger **null** | os contadores que **aquela** rede publica. ⛔ **`null` nunca vira `0` na tela** (DEC-95): no Bluesky visualização não existe no protocolo, e um zero ali diria "ninguém viu" quando o certo é "ninguém conta" |
| `metricas_lidas_em` | timestamp null | quando os quatro acima foram lidos |

### 🟢 `tentativas` (Model `Tentativa`) — filha de destino
| Coluna | Tipo | Regra |
|---|---|---|
| `id` / `destino_id` | PK / FK | |
| `numero` | unsignedTinyInteger | **UNIQUE** (`destino_id`,`numero`) |
| `sucesso` | boolean | |
| `erro_mensagem` | text null | |
| `iniciada_em` / `finalizada_em` | timestamp / null | |
| timestamps | | |

### 🟢 `configuracoes_rede` (Model `ConfiguracaoRede`) — DEC-17, padrões por módulo
| Coluna | Tipo | Regra |
|---|---|---|
| `id` / `usuario_id` | PK / FK | |
| `plataforma` | string (enum `Plataforma`) | |
| `conta_social_padrao_id` | FK null | conta pré-selecionada ao publicar |
| `legenda_padrao` | text null | |
| `hashtags_padrao` | json null | |
| `visibilidade_padrao` | string null | YouTube: `privado`·`nao_listado`·`publico` |
| timestamps | | **UNIQUE** (`usuario_id`,`plataforma`) |

### 🔵 Tabelas de conformidade (obrigatórias por lei/política — doc 08 §5)
| Tabela | Model | Papel | Retenção |
|---|---|---|---|
| `logs_acesso` | `LogAcesso` | data/hora, IP, conta — **sobrevive à exclusão da conta** | **6 meses** (Marco Civil art. 15) |
| `incidentes_seguranca` | `IncidenteSeguranca` | todo incidente, **inclusive os não comunicados** | **5 anos** (a falta do registro é infração autônoma) |
| `pedidos_titular` | `PedidoTitular` | tipo, entrada, prazo-alvo, resposta | prova de cumprimento (LGPD art. 18) |

### 🔵 `logs_impersonacao` (Model `LogImpersonacao`)
| Coluna | Tipo | Regra |
|---|---|---|
| `id` | PK | |
| `admin_id` / `usuario_id` | FK usuarios **null** · `nullOnDelete` | conta apagada não pode travar o log — nem o log travar o apagamento |
| `admin_ulid` / `usuario_ulid` | ulid (cópia) | **apelido que sobrevive à exclusão**: o evento continua rastreável sem guardar dado pessoal |
| `iniciada_em` / `finalizada_em` | timestamp / null | |
| `ip` / `agente_usuario` | string null / text null | de onde partiu o acesso |
| timestamps | | DEC-04: **toda** sessão registrada |

### Relações (resumo)
`usuario 1—N` grupos · contas_sociais · midias · publicacoes · configuracoes_rede
`grupo 1—N` contas_sociais · publicacoes
⛔ **mídia não pertence a grupo:** não existe acervo (DEC-59), o arquivo só vive dentro da
composição. Dar grupo a ela criaria uma biblioteca por grupo que o produto decidiu não ter.
⛔ **destino não tem `grupo_id`:** o grupo dele se lê pela publicação. Gravar nos dois criaria duas
fontes para o mesmo fato, e bastaria mover um canal para elas discordarem.
`conta_social 1—1` credencial · `1—N` destinos
`publicacao N—1` midia · `1—N` destinos · `destino 1—N` tentativas

### Regras de deleção (obrigatórias — o histórico é sagrado)
| FK | Regra | Por quê |
|---|---|---|
| `credenciais.conta_social_id` | **cascade** | token não sobrevive à conta |
| `destinos.conta_social_id` | **restrict** | histórico não some; desconectar usa o status `desconectada`, não DELETE |
| `destinos.publicacao_id` | **cascade** | destino não existe sem a publicação |
| `tentativas.destino_id` | **cascade** | log da tentativa pertence ao destino |
| `publicacoes.midia_id` | **restrict** | o registro da mídia é o histórico da publicação; some o arquivo pesado (`arquivo_removido_em`), nunca a linha |
| `contas_sociais.grupo_id` | **restrict** | grupo com canal não se apaga — canal invisível continuaria publicando por trás da tela, ou falhando sem ninguém ver |
| `publicacoes.grupo_id` | **restrict** | apagar um grupo levaria junto a prova de onde o post saiu |
| `grupos.usuario_id` | **restrict** | mesmo padrão das outras tabelas de cliente |
| `configuracoes_rede.conta_social_padrao_id` | **nullOnDelete** | perder a conta padrão não pode quebrar a config |
| `*.usuario_id` (dados do cliente) | **restrict** | apagar cliente com dados exige decisão explícita (nunca cascata silenciosa) — quem apaga é o serviço de exclusão de conta, na ordem certa |
| `logs_impersonacao.admin_id`/`usuario_id` | **nullOnDelete** + cópia do ULID | ⚠️ **corrigido**: com `restrict`, quem já tinha recebido suporte **nunca mais** conseguia apagar a conta — um log bloqueava o direito de eliminação (LGPD art. 18). Agora a **pessoa some e o evento fica** |

> **A regra que resolve o conflito:** *dado pessoal se apaga; registro de acesso sobrevive
> anonimizado.* LGPD art. 18 (eliminação) e Marco Civil art. 15 (guarda do log) não brigam —
> o que a lei manda guardar é **que houve o acesso**, não **quem era a pessoa**.
> Toda tabela de log nasce com FK `nullOnDelete` **+ cópia do ULID**; sem o ULID sobra uma
> linha de nulos, que não responde "quantos acessos houve naquela conta?" numa auditoria.

⛔ **Não existe "remover da mídia" nem `arquivada_em`.** A saída do arquivo é automática e vem do
fim da publicação, não de um botão — não há o que gerenciar.

---

## 7. Rotas canônicas

### Área do cliente (middleware `papel:cliente`)
| Path | Nome | O quê |
|---|---|---|
| `/painel` | `painel` | landing pós-login |
| `/publicacoes` | `publicacoes` | o histórico com a prova — **a tela principal** |
| `/publicar` | `publicar` | ⭐ o **compositor**: modal por cima de `/publicacoes`, mas rota de verdade (recarregar não perde o que foi escrito) |
| `/publicar/{publicacao}` | `publicar.de-novo` | o mesmo compositor com o **texto** da publicação anterior (DEC-61) |
| `POST /publicar` | `publicar.enviar` | cria publicação + destinos + dispara |
| `POST /publicacoes/destinos/{ulid}/tentar-de-novo` | `publicacoes.reprocessar` | ULID resolvido **dentro do escopo do dono**, nunca por binding solto |
| `POST /midias` | `midias.salvar` | envio — vive **dentro** do compositor, não numa tela própria |
| `GET /midias/{ulid}/arquivo` | `midias.arquivo` | único caminho até o vídeo; o disco fica fora da raiz pública |
| `GET /midias/{ulid}/miniatura` | `midias.miniatura` | separada do arquivo: a lista pede muitas de uma vez |
| `POST /conexoes/bluesky` · `DELETE /conexoes/{ulid}` | `conexoes.bluesky` · `conexoes.desconectar` | conectar e desconectar |
| `/conexoes/{rede}` → `/retorno` | `conexoes.{rede}[.retorno]` | OAuth (`youtube`, `meta`); o retorno cai em `/painel` |
| `POST /grupos` · `PATCH /grupos/{ulid}` · `DELETE /grupos/{ulid}` | `grupos.criar` · `grupos.renomear` · `grupos.arquivar` | o grupo não tem tela: nasce, muda de nome e arquiva por diálogo |
| `POST /grupos/{ulid}/usar` | `grupos.usar` | troca o grupo corrente. ⛔ **POST, nunca GET** — com GET, o prefetch do navegador trocaria o modo sozinho |
| `PATCH /conexoes/{ulid}/grupo` | `conexoes.grupo` | move um canal de grupo (DEC-77) |
| `/minha-conta/...` | `minha-conta.*` | perfil · senha · aparência (starter em PT-BR) |

⛔ **Não existe `/midias` como tela.** Ela existia quando o produto era um drive; enviar e publicar
viraram o mesmo gesto (DEC-60), e o que restou de `midias` são rotas de serviço.

⛔ **Não existe `/conexoes` como tela** (DEC-63). O estado das redes mora na Visão geral, que é a
primeira coisa que a pessoa abre; `conexoes` sobrou como rotas de **ação**. O resumo é montado num
lugar só: `App\Support\Conexao\ResumoDasRedes` (DEC-65).

⭐ **O menu do cliente tem dois itens:** Visão geral e Publicações. Publicar e conectar são ações,
e ação não é lugar para onde ir.

⛔ **O grupo também não é item de menu** (DEC-71). Ele é **modo**, e modo se mostra onde a pessoa
está — o seletor vive na barra superior, visível em toda tela. O grupo corrente mora na **sessão**
e nunca na URL (DEC-72): é preferência de visualização, não recorte compartilhável.

### Área do admin (prefixo `/admin`, middleware `papel:admin`)
| Path | Nome | O quê |
|---|---|---|
| `/admin` | `admin.visao-geral` | esqueleto agora, encorpa depois (DEC-16) |
| `/admin/clientes` (+ POST) | `admin.clientes.listar/criar/salvar` | |
| `POST /admin/clientes/{usuario}/impersonar` | `admin.clientes.impersonar` | |
| `POST /impersonacao/sair` | `impersonacao.sair` | disponível durante a impersonação |
| `/admin/logs-impersonacao` | `admin.logs-impersonacao.listar` | |

`{plataforma}` resolve pelo enum (binding); valor inválido = 404. **Regra de ouro:** todo
`route('X')` do front existe no back (`Route::has` — lição EmpiresCloud); rota nova nasce aqui.

---

## 8. Impersonação — mecânica canônica

- Sessão guarda `impersonacao.admin_id` + `impersonacao.log_id`; o auth troca para o cliente.
- **`UsuarioAtual::efetivo()`** (helper único) resolve quem é o usuário para o Global Scope —
  **nenhum outro lugar** decide isso. Banner fixo "Modo impersonação" + `impersonacao.sair`.
- Entrar grava `logs_impersonacao.iniciada_em`; sair grava `finalizada_em` (DEC-04).
- Token de rede **nunca** exibido — nem impersonando (regra crítica).

## 9. Estrutura de pastas

```text
app/
  Enums/            Papel · Plataforma · TipoMidia · StatusConta · StatusDestino · StatusPublicacao
  Models/           Usuario · ContaSocial · Credencial · Midia · Publicacao · Destino ·
                    Tentativa · ConfiguracaoRede · LogImpersonacao
  Services/         PublicacaoService · ContaSocialService · MidiaService · ImpersonacaoService
  Actions/          uma operação por classe (ex.: CriarClienteAction)
  Jobs/             PublicarDestinoJob (1 job por destino; fila por plataforma)
  Publicadores/     Publicador (contrato) · ResultadoPublicacao (DTO) ·
                    PublicadorYoutube · PublicadorFacebook · PublicadorInstagram · PublicadorTiktok
  Http/Controllers/ Cliente/ · Admin/ · MinhaConta/
  Http/Middleware/  VerificarPapel
lang/pt_BR/         rotulos.php · validation.php · ...
resources/js/
  pages/            auth/ · cliente/ (visao-geral · publicacoes) ·
                    minha-conta/ · admin/ (visao-geral · clientes · impersonacoes)
  components/publicacao/ Compositor  ← ⭐ publicar é um MODAL por cima de publicacoes
  components/midia/      EnviarMidia · Miniatura · Previa · PainelLaudo · SeloLaudo
  components/conexao/    PainelDeRedes ← ⭐ as redes vivem DENTRO da visao geral ·
                         MarcaDaRede · TermosDaRede
  components/            Quadro ← ⭐ o quadrado de lado FIXO; a forma padrao do painel ·
                         TituloDeSecao · CabecalhoDePagina · Avisos · ...
  hooks/            useAvisos · useAtualizacaoViva
```

## 10. Testes-guardiões (anti-drift automatizado)

| Teste | O que trava |
|---|---|
| **Isolamento** | cliente A nunca lê/edita dado do B — **inclusive nas rotas de filha** (reprocessar destino alheio = 404) |
| **Rótulos completos** | todo case de enum visível tem rótulo em `rotulos.php` — impossível esquecer |
| **Transições de status** | transição fora da máquina (seção 4) lança exceção |
| **Órfão** | destino `enviando` sem tentativa ativa há > N min volta pra `pendente`/`falhou` (watchdog) |
| **Retry ≠ falha** | falha transitória devolve à fila sem disparar notificação nem mudar o agregado |
| **Idempotência** | job re-executado sobre destino `publicado` é no-op **e** re-execução com `handle_externo` gravado **retoma/verifica**, não republica |
| **Papel/middleware** | cliente não acessa `/admin` e vice-versa; impersonação registra log |
| **Rotas existem** | todo `route()` referenciado resolve (`Route::has`) |

Rodam na suíte normal (Pest). **Definição de pronto** de toda fase inclui os guardiões verdes.

## 11. LOG do glossário (append)
- 2026-07-27 — Glossário criado: convenções, papéis, 6 enums + máquinas de estado (escritor
  único), 9 tabelas coluna a coluna, mecânica de rótulos (fonte única back→front), rotas
  canônicas, impersonação, pastas, testes-guardiões. Correção vs plano: colunas de framework
  mantêm nome padrão (`email_verified_at`, `password`) — lição do bug `senha_hash`; vínculo
  Google = `google_id` (1 coluna, sem par provedor/id especulativo).
- 2026-07-27 — Sincronizado com a pesquisa verificada (doc 07): tabela `notifications` do
  framework adicionada ao território intocável (DEC-19); nota do perfil canônico de mídia em
  `midias` (validação por conta fica no `Publicador`).
- 2026-08-04 — Sincronizado com o produto que existe (plano 13). `midias` ganhou as colunas que
  faltavam no glossário (`ulid`, `laudo`, `miniatura`, `assinatura`, `arquivo_removido_em`) e
  perdeu o `arquivada_em` que nunca chegou a existir. As rotas do cliente passaram a ser as
  reais: `/publicar` é o **compositor** (modal por rota), `/publicacoes` é a tela principal e
  `midias` sobrou só como rotas de serviço — **não há tela de mídias**. Pastas do front
  reescritas: existem 3 páginas de cliente, não 6.
- 2026-08-04 — Conexões deixou de ser tela (plano 14). A rota `conexoes` saiu; sobraram as de
  ação. O resumo das redes ganhou fonte única (`ResumoDasRedes`) e o menu do cliente ficou com
  dois itens. Restam **2 páginas de cliente**.
- 2026-08-04 — Nasce o conceito **grupo** (plano 15): tabela `grupos`, `grupo_id` em
  `contas_sociais` e `publicacoes`, rotas de criar/renomear/arquivar/usar e mover canal.
  `deleted_at` entrou no território do framework. ⚠️ O nome foi escolhido por eliminação: "rede",
  "perfil", "marca" e "projeto" já significam outra coisa aqui.
