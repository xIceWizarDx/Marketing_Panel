# Plano de ação — o módulo Meta, organizado

_Criado em 2026-08-06. Documentação oficial de Facebook e Instagram consultada em 2026-07-31;
a do Threads em 2026-08-06._

> **A ideia em uma frase:** Facebook e Instagram já têm publicador escrito e nunca foram ligados;
> o Threads não existe. Este plano fecha os três — e descobre, no caminho, que o Threads não é o
> que a DEC-30 dizia que era.
>
> Regra de conclusão: **suíte verde, tipos e lint limpos, grep-zero, conferido no navegador**.

---

## Onde as três estão hoje

| | Publicador | Credencial no `.env` | Conecta | Métrica | Plano | O que falta |
|---|---|---|---|---|---|---|
| **Facebook** | escrito | **não** | não | não | sim | credencial + análise |
| **Instagram** | escrito | **não** | não | não | sim | credencial + análise |
| **Threads** | **não** | **não** | não | não | **agora sim** | tudo |

⚠️ **O código das duas primeiras nunca rodou contra a rede de verdade.** `podeConectar()` devolve
`false` porque `services.meta.client_id` está vazio, então o botão nem aparece. Publicador escrito
e nunca executado é uma promessa, não uma entrega — e a revisão do Facebook já achou **sete
divergências** nele (ver `planos-de-redes/facebook/achados.md`), quatro delas graves.

---

## O achado que reordena tudo

**A carona do Threads é menor do que a DEC-30 supunha.** Ele é da Meta, mas:

| | Facebook / Instagram | Threads |
|---|---|---|
| Autorização | Login do Facebook | **`threads.net/oauth/authorize`** |
| Servidor | `graph.facebook.com` | **`graph.threads.net`** |
| Permissões | `instagram_*`, `pages_*` | **`threads_*`** |
| Mídia | arquivo direto (`rupload`) | **só URL pública** |
| Token | de Página, **não expira** | 60 dias, **morre se não renovar** |

Sobra de carona: o mesmo aplicativo Meta e a mesma conta de desenvolvedor. **Não sobra o código.**

---

## As decisões

**DEC-99 — o Threads é uma rede à parte, não uma variação do Instagram.** `PublicadorThreads` não
herda do publicador da Meta e não reusa o serviço de conexão. Compartilha o formato do erro do
Graph e mais nada. ⛔ Tentar unificar produziria um serviço com dois hosts, dois conjuntos de
escopo e dois modelos de token dentro do mesmo `if` — que é como um módulo vira intratável.

**DEC-100 — a URL pública temporária nasce agora, e ela é do ENVIO, não do arquivo.** O Threads é a
primeira rede que exige que a Meta **busque** a mídia. A URL é assinada, curta, imprevisível,
expira, e serve só o arquivo (0.M). ⛔ **Ela não é um endereço permanente da mídia do cliente:**
vive o tempo de um envio e morre com ele. Um link permanente para o arquivo de quem paga é
vazamento com data marcada.
⚠️ Isto **corrige o `CLAUDE.md`**, que descreve esse buraco como aberto *"p/ Instagram e TikTok"* —
o Instagram não usa, porque o Login do Facebook deu upload direto justamente para evitá-lo.

**DEC-101 — enquanto não houver servidor alcançável pela internet, o Threads fica desligado na
tela.** A Meta não enxerga `localhost`. ⛔ Botão que leva a um erro é pior que botão ausente:
`podeConectar()` responde `false` sem `APP_URL` pública, e a tela diz *"falta configurar"*, que é o
estado que ela já sabe mostrar.

**DEC-102 — a renovação do token do Threads é obrigatória e tem janela.** Entre 24 horas e 60 dias;
fora dela, a conta morre de vez. Entra no comando diário que já existe, com a mesma folga do
`youtube:reconferir`: mexer antes do prazo, nunca em cima dele.

**DEC-103 — o segundo passo do Threads não bloqueia worker.** A documentação pede ~30 s entre criar
o contêiner e publicar. O destino vai para `processando` e o passo dois acontece na passada
seguinte. ⛔ Dormir 30 segundos segurando um worker é como uma fila de 10 publicações vira 5
minutos de nada acontecendo.

**DEC-104 — o limite de texto do Threads é contado em BYTES.** *"Emojis são contados como o número
de bytes UTF-8."* Um emoji comum ocupa 4 bytes; com modificador de tom de pele passa de 8. Uma
legenda de 480 caracteres com dez emojis estoura os 500 sem parecer. ⚠️ O limite por rede já existe
em `EspecificacaoDaRede` — o que muda é a **unidade**, e ela precisa viajar junto do número, senão
o contador da tela mente para esta rede.

**DEC-105 — antes de escrever o Threads, as duas escritas são ligadas de verdade.** Facebook e
Instagram publicam em conta própria **sem análise nenhuma** (Acesso Padrão vale para quem tem papel
no app). ⭐ Ligar primeiro transforma sete divergências achadas na leitura em sete achados
verificados — ou em novos. Escrever a terceira rede sobre duas que nunca rodaram é empilhar suposição.

---

## ⚠️ O que falta do lado da Meta — e nada disso é código

_Estado em 2026-08-07. O aplicativo existe, com os três casos de uso e as credenciais já no `.env`._

### 🔴 Antes de qualquer coisa ir para produção

- [ ] **Redefinir a chave secreta do aplicativo.** Ela foi exposta numa captura de tela durante a
      configuração. ⛔ A chave é o que prova que o app é o app — quem a tiver pode se passar por
      ele. Não é urgente com o app despublicado e um único usuário, mas **não pode atravessar para
      produção**.
- [ ] **Trocar o nome provisório do app pelo nome comercial.** Ele aparece na tela de autorização
      que o usuário final lê, e **congela na prática ao submeter para análise** — mudar depois pode
      custar nova submissão. Hoje trocar é grátis e não mexe nas credenciais.

### 🟡 Dependem do domínio próprio (Fase I)

Todos estão vazios ou com sobra do formulário, e todos são exigidos na Análise do Aplicativo:

- [ ] **URL da Política de Privacidade** — vazia
- [ ] **URL dos Termos de Serviço** — está apontando para o site da própria Meta
- [ ] **URL de instruções de exclusão de dados** — idem. ⚠️ A Meta exige um endereço onde a pessoa
      peça a exclusão dos dados dela
- [ ] **Domínios do aplicativo** — vazio
- [ ] **Ícone e categoria do app** — vazios
- [ ] **Verificação da empresa** — o portfólio está como *não verificada*. Só é cobrada no Acesso
      Avançado, mas leva dias e não depende de código: dá para começar em paralelo

### 🟢 Para o Threads conectar

- [ ] **Colar a Redirect Callback URL** em Casos de uso → API do Threads → Configurações
- [ ] **Adicionar-se como testador do Threads** em Funções do app, e **aceitar o convite dentro do
      aplicativo do Threads** — sem aceitar, o Acesso Padrão não vale
- [ ] **Chave secreta do app do Threads** no `.env`

### ⚠️ O endereço público é temporário

O painel está atrás de um túnel, que dá um endereço `https://` novo a cada vez que sobe. **Se ele
cair, os dois campos de retorno na Meta precisam ser refeitos.** É suficiente para testar hoje e
insuficiente para qualquer outra coisa — a saída definitiva é o domínio da Fase I.

⛔ E o túnel expõe a máquina de desenvolvimento na internet aberta: derrubar quando não estiver
testando.

---

## Fase 1 — Ligar o que já está escrito

⚠️ Nada aqui é código novo: é a primeira execução real do que existe.

- [ ] **1.1** Aplicativo na Meta + credencial em `services.meta` — passo do Gabriel
- [ ] **1.2** Conectar uma Página e uma conta profissional de verdade
- [ ] **1.3** Publicar **em rascunho** primeiro (`video_state: DRAFT`) — 0.A
- [ ] **1.4** Conferir os sete achados da revisão do Facebook contra a rede viva, um a um
- [ ] **1.5** ⭐ Conferir os dois nomes de permissão de Página (`CREATE_CONTENT` e
      `PROFILE_PLUS_CREATE_CONTENT`) com uma Página recém-criada, que é onde o defeito aparecia
- [ ] **1.6** Registrar no LOG o que a rede respondeu diferente da documentação

**Pronto quando:** existe um post real, publicado pelo painel, com link conferido — em cada uma.

---

## Fase 2 — A URL temporária do envio (DEC-100) ✅

- [x] **2.1** Rota assinada e expirável que serve **um arquivo, de um envio, por um tempo**
- [x] **2.2** ⛔ Fora do grupo autenticado — a Meta não tem sessão — e por isso **a assinatura é a
      única trava**: sem sessão, um endereço adivinhável é o arquivo de qualquer cliente
- [x] **2.3** Expira em **15 minutos**; o relógio começa quando o endereço é gerado, no envio
- [x] **2.4** Serve **só** o arquivo: sem nome original, sem cache, fora de buscador
- [x] **2.5** Guardião: sem assinatura, com assinatura de outro arquivo, e assinatura vencida →
      todos recusados
- [x] **2.6** ⚠️ Guardião do que **parece** furo: o endereço serve mídia de qualquer dono, e tem
      que servir — não há sessão para o escopo usar, e com escopo a consulta **lançaria exceção**.
      Quem separa um dono do outro aqui é o endereço ser imprevisível e curto, não uma consulta. O
      teste existe para que ninguém "conserte" isso e quebre a integração
- [x] **2.7** Guardião: o endereço **não** é guardado em banco — URL assinada guardada é URL
      permanente com outro nome

**Pronto:** 9 guardiões verdes. Existe um endereço que a Meta alcança, que ninguém adivinha, e que
morre sozinho.

---

## Fase 3 — Conectar o Threads (DEC-99, DEC-101, DEC-102) ✅

- [x] **3.1** `ConexaoComThreads` — janela em `threads.net`, troca em `graph.threads.net`
- [x] **3.2** Escopos `threads_basic` e `threads_content_publish`; ⚠️ conferidos os **concedidos**,
      nunca os pedidos. ⛔ **E o campo é `permissions`, não `scope`:** o Threads não devolve a
      string do padrão OAuth, e ler o campo errado recusaria toda conexão válida
- [x] **3.3** ⭐ Trocado pelo token longo **na hora da conexão** — o curto vive 1 hora
- [x] **3.4** `state` assinado na sessão contra CSRF, igual ao YouTube
- [x] **3.5** ⛔ Sem endereço público, a conexão **nem começa** (DEC-101). Recusa no início, não no
      fim: deixar passar seria a pessoa autorizar na rede para descobrir depois que não publica
- [x] **3.6** `CanalDeUmGrupoSo` também aqui
- [x] **3.7** Comando `threads:renovar`, diário às 04:50, mexendo com **15 dias de folga**
- [x] **3.8** ⭐ Guardiões da renovação, que é a única coisa do produto que **mata a conta** se
      falhar calada: menos de 24 h não renova · perto do vencimento renova · vencido marca a conta
      e diz para reconectar · **rede fora do ar NÃO marca** · longe do prazo não mexe à toa
- [x] **3.9** ⛔ `refresh_token` fica **nulo**: aqui ele não existe — o Threads renova o próprio
      token longo apresentando ele mesmo, e `expira_em` passa a significar **o prazo de morte da
      conta**, não o de um token que se renova sozinho

**Pronto:** 15 guardiões verdes. A conta conecta, o token é longo, e o semáforo mostra um prazo
real. ⚠️ Falta a credencial do aplicativo para conectar de verdade.

---

## Fase 4 — Publicar no Threads (DEC-103, DEC-104)

- [x] **4.1** `PublicadorThreads`: contêiner → publicar, com a URL temporária da Fase 2
- [x] **4.2** ⭐ O `id` do contêiner é o `handle_externo` — é ele que impede publicar duas vezes
- [x] **4.3** O passo dois acontece na passada seguinte, sem dormir (DEC-103)
- [x] **4.4** `EspecificacaoDaRede`: vídeo até 5 min, 1 GB, MOV/MP4, 23-60 FPS, largura ≤ 1920;
      imagem JPEG **ou PNG** até 8 MB
- [x] **4.5** Texto: 500 **bytes UTF-8** (DEC-104) — a recusa acontece antes do envio
- [x] **4.6** 250 publicações por 24 h → `aguardando_janela`, não erro (DEC-24)
- [x] **4.7** Conciliação: reler o post e guardar o link — a prova (DEC-31)
- [x] **4.8** Guardião: legenda de 480 caracteres com emoji **é recusada** antes de subir

**Pronto:** 17 guardiões verdes. **Falta a prova de campo** — nenhum post saiu no Threads de verdade
ainda; a conta conecta, e a publicação está escrita e travada por teste.

### ⛔ Dois defeitos que só apareceram ao reler a documentação oficial

**A lista de erros da rede tem erro de digitação, e ela muda entre leituras.** A fonte escreve
`INVALID_ASPEC_RATIO`, **sem o `T`**, e numa segunda leitura da mesma página, no mesmo dia,
`INVALID_FRAME_RATE` virou `FAILED_FRAME_RATE`. O código casava a palavra inteira: a recusa **mais
comum de todas** — a proporção do vídeo — cairia no genérico *"o Threads recusou este post"*, que não
diz o que arrumar. Agora o casamento é por **pedaço estável** (`ASPEC`, `FRAME_RATE`, `BIT_RATE`…),
que funciona com a grafia errada de hoje e com a corrigida de amanhã.

**A cota não tem código de erro — tem endpoint.** `GET /{id}/threads_publishing_limit` existe e está
documentado (na primeira leitura a página devolvia 404, e este plano registrava o contrário). Como a
rede não devolve código próprio para "cota estourada", o publicador consulta esse endpoint **só
depois** de uma recusa acontecer: uma chamada a mais no caminho do erro, nenhuma no caminho normal.
Sem isso, a publicação de número 251 do dia seria falha permanente e queimaria as três tentativas
contra um limite que só volta amanhã. ⚠️ Na dúvida a resposta é "não estourou" — se a consulta da
cota falhar, o motivo que a pessoa vê continua sendo o que a rede deu.

---

## Fase 5 — Arrumar a casa

- [x] **5.1** `CLAUDE.md`: a URL temporária é do **Threads e do TikTok**, não do Instagram
- [x] **5.2** DEC-30 corrigida — a carona é de aplicativo, não de fluxo
- [ ] **5.3** `threads` sai de "Planejada" quando publicar de verdade
- [x] **5.4** Comando `redes:situacao` — a tabela do topo deste plano, gerada do código em vez de
      escrita à mão, para nunca mais envelhecer
- [x] **5.5** LOG atualizado

---

## ⭐ DEC-150 — a exigência da Página se diz ANTES de autorizar

Achado em campo, na primeira conexão real: a Meta devolve **lista de Páginas vazia** em duas
situações que não são a mesma coisa — quem **não tem** Página, e quem **tem mas não marcou** a Página
no passo em que ela pergunta quais liberar. Dizer *"nenhuma Página encontrada nesta conta"* nos dois
casos manda quem tem três Páginas criar a quarta.

**O que passou a valer:**

1. **A mensagem cobre as duas causas**, com a mais provável primeiro — refazer a autorização e marcar
   a Página; criar uma Página só se realmente não houver.
2. **O modal de conexão explica o pré-requisito antes**, como o do YouTube já fazia com o vídeo
   privado: só publica em Página; é preciso marcar a Página no site da Meta; **com mais de uma
   Página, todas as marcadas viram contas separadas** e o destino continua sendo escolhido post a
   post; o Instagram vem junto se for profissional e vinculado.

⚠️ O item das várias Páginas é o que mais assusta — sem ele, marcar duas parece autorizar publicação
simultânea nas duas.

⭐ Mesma régua da DEC-41 e da DEC-46: **o que a rede exige aparece na tela em que a pessoa decide**,
não num erro depois, que ela leria como defeito do painel.

---

## ✅ O ESTADO QUE FUNCIONA — configuração da Meta em 2026-08-14

⭐ **Registrado porque foi caro.** Este é o conjunto exato com o qual a conexão passou e trouxe
Página + Instagram na mesma autorização. Se um dia parar, é contra isto que se compara.

**Configuração do Login para Empresas** — `META_CONFIG_ID` no `.env`, valor `1359615295763791`.
⚠️ Não é segredo: ele viaja na URL de autorização, à vista de quem conecta. O segredo é o
`META_CLIENT_SECRET`, e esse nunca sai do `.env`.

| Passo do assistente | Valor |
|---|---|
| Token de acesso | **Token de acesso do usuário** |
| Ativos | *não selecionável* — é assim mesmo com token de usuário; quem escolhe é o cliente, na hora |

### Permissões marcadas (9 de 10)

| Permissão | Marcada | Para quê |
|---|:---:|---|
| `business_management` | ✅ | ⛔ **sem ela `/me/accounts` volta VAZIO** para Página de portfólio (DEC-164) |
| `pages_show_list` | ✅ | listar as Páginas |
| `pages_manage_posts` | ✅ | publicar o reel |
| `pages_read_engagement` | ✅ | ler curtidas e comentários |
| `read_insights` | ✅ | visualizações do reel |
| `instagram_basic` | ✅ | perfil e mídia |
| `instagram_content_publish` | ✅ | publicar o reel |
| `instagram_manage_insights` | ✅ | visualizações e compartilhamentos |
| `instagram_manage_comments` | ✅ | guardada para o futuro (DEC-163) |
| `pages_manage_engagement` | ⛔ **NÃO** | **não está habilitada no caso de uso** — marcar derruba o diálogo |

⛔ **A regra que a última é: primeiro no CASO DE USO do app, depois na configuração.** Permissão que
aparece na lista da configuração mas não está habilitada nos casos de uso derruba a tela inteira com
um *"Sorry, something went wrong"* — sem dizer qual permissão, sem dizer que é permissão.

⚠️ **Casos de uso do app que precisaram existir:** "Gerenciar tudo na sua Página", "Gerenciar
mensagens e conteúdo no Instagram", "Acessar a API do Threads" — e dentro dos dois primeiros,
`read_insights` e `instagram_manage_insights` tiveram que ser adicionadas à mão, além de
`business_management`.

### Endereço de autorização que funciona

```
https://www.facebook.com/v25.0/dialog/oauth
  ?client_id=<META_CLIENT_ID>
  &redirect_uri=<META_REDIRECT_URI>
  &response_type=code
  &config_id=<META_CONFIG_ID>
  &override_default_response_type=true
  &state=<estado>
```

⚠️ **`scope` NÃO entra** (DEC-162), e `override_default_response_type` é obrigatório junto do
`config_id` — sem ele o diálogo quebra com a mesma tela genérica.

---

## ⭐ DEC-163 — permissões amplas AGORA, revisadas antes de pedir análise

⛔ **Regra da PLATAFORMA INTEIRA, não só da Meta.** Vale para toda rede que o painel conectar: pedir
o conjunto amplo enquanto o aplicativo é privado, e **revisar permissão por permissão antes de
submeter à análise da plataforma**.

**Decisão do dono em 2026-08-13**, com a ressalva registrada. A configuração do Login para Empresas
(DEC-162) foi criada com **10 permissões** — as sete que o produto usa e mais três guardadas para o
futuro.

**Por que isso se sustenta hoje:** o app está **não publicado**. Nenhuma dessas permissões passa por
revisão, ninguém além do dono vê a tela de autorização, e permissão a mais não custa nada nessa fase.

⚠️ **O que muda com essa regra:** a revisão deixa de ser "se der problema a gente vê" e vira **passo
obrigatório do checklist de publicação de cada rede**. Sem isso, a decisão vira esquecimento — e o
esquecimento aparece como reprovação, semanas depois, com o lançamento marcado.

⛔ **O gatilho para enxugar é um só, e é obrigatório: antes de submeter o app para análise.** Ali a
Meta pergunta, permissão por permissão, **em qual tela do produto ela é usada** — e a que sobrar sem
resposta segura a aprovação **do app inteiro**, não só dela.

### O que o produto REALMENTE usa hoje

| Permissão | Para quê | Sem ela |
|---|---|---|
| `pages_show_list` | listar as Páginas na conexão | não conecta nada |
| `pages_manage_posts` | publicar o reel na Página | não publica |
| `pages_read_engagement` | ler curtidas e comentários da Página | contadores vazios |
| `read_insights` | visualizações do reel | "sem leitura" para sempre |
| `instagram_basic` | perfil e mídia da conta | não conecta o Instagram |
| `instagram_content_publish` | publicar o reel no Instagram | não publica |
| `instagram_manage_insights` | visualizações e compartilhamentos | número some |

### O que foi pedido "para o futuro" — e o que teria que existir para justificar

| Permissão | O que ela abre | Tela que precisaria existir |
|---|---|---|
| `pages_manage_engagement` | responder/apagar comentários na Página | caixa de entrada |
| `instagram_manage_comments` | ler o texto e responder comentários no Instagram | caixa de entrada |
| `business_management` | ler/escrever no Gerenciador de Negócios | nenhuma prevista |
| `ads_*` | anúncios | nenhuma prevista |

⚠️ **`pages_manage_engagement` é caso à parte**, e pode acabar sendo necessária **sem** caixa de
entrada: a referência do `video_insights` lista essa permissão junto de `read_insights`, enquanto o
guia de publicação de reels diz que `pages_read_engagement` basta. **As duas páginas da Meta se
contradizem** — só o teste em campo decide (pendência 2 da [auditoria](33-auditoria-meta.md)).

⭐ **Mexer nisso depois é barato:** volta-se na mesma configuração, marca ou desmarca, e salva. Quem
já conectou reconecta **uma vez** para conceder o que mudou.

---

## ⛔ O que fica de fora, de propósito

**Carrossel.** O produto publica **um** vídeo vertical por vez. Carrossel é outra forma de conteúdo,
e ela mudaria o compositor inteiro.

**Resposta e thread encadeada** (`threads_manage_replies`). É conversa, não publicação — e conversa
pede caixa de entrada, que é outro produto.

**Métricas do Threads.** O escopo existe (`threads_manage_insights`) e os campos não foram
consultados. Entra quando o contrato de métrica (plano 17) for estendido, não antes.

**Análise do Aplicativo.** Facebook, Instagram e Threads publicam em conta própria sem ela. A
análise só é necessária quando **outra pessoa** usar o produto — e ela depende do domínio, da
política de privacidade e da URL de exclusão, que são Fase I.

**`link_attachment` e `gif_attachment`.** Só existem em post de texto, e o produto publica mídia.
