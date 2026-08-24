# Plano — TikTok

> Escrito depois de ler a documentação oficial inteira (cópia local em
> [`../planos-de-redes/tiktok/documentacao/`](../planos-de-redes/tiktok/documentacao)) e de registrar
> os achados em [`../planos-de-redes/tiktok/achados.md`](../planos-de-redes/tiktok/achados.md).

---

## ⭐ Leia isto antes do resto

**Esta é a rede que mais se parece com o produto.** Depois do LinkedIn, que não deixa reler o post,
o TikTok faz o contrário: ele separa "subiu" de "a moderação aprovou" **na própria resposta**.

| O que a rede diz | O que significa |
|---|---|
| `PUBLISH_COMPLETE` **sem** `publicaly_available_post_id` | subiu, ainda não liberado |
| `PUBLISH_COMPLETE` **com** `publicaly_available_post_id` | subiu **e passou pela moderação** |

⭐ É a prova mais forte de todas as redes do painel.

⛔ **E ela não existe enquanto o aplicativo não for auditado**, porque post privado nunca recebe esse
identificador. Ver DEC-116.

---

## DEC-115 — A prova do TikTok é o `publicaly_available_post_id`, não o `PUBLISH_COMPLETE`

⛔ Parar em `PUBLISH_COMPLETE` seria exatamente o erro que o produto critica: a rede aceitou, e o
post pode não estar visível para ninguém.

O destino só vira **publicado com prova** quando o identificador chega. Enquanto ele não chega e o
status é `PUBLISH_COMPLETE`, o destino continua **processando** — que é a verdade: subiu, e a
moderação ainda não se pronunciou.

⚠️ Com um teto de tentativas, para não conciliar para sempre um post que a moderação nunca vai
liberar.

---

## DEC-116 — Sem auditoria, o post é privado — e a tela diz isso ANTES

*"All content posted by unaudited clients will be restricted to private viewing mode."*

1. `privacy_level` **precisa** ser `SELF_ONLY`, ou o início devolve
   `unaudited_client_can_only_post_to_private_accounts`;
2. post privado **nunca** recebe `publicaly_available_post_id`;
3. logo, **não existe link de prova** enquanto a auditoria não sair.

⛔ **A privacidade não é escolha da tela enquanto isso.** Oferecer "público" num aplicativo não
auditado seria oferecer um botão que sempre falha.

### ⚠️ Correção — esta decisão dizia "mesma resposta do YouTube", e estava errada

Dizia: *"publicar funciona, o painel diz que é privado"*. **No YouTube o vídeo privado tem
endereço**; aqui o identificador só vem para post público aprovado, então um post privado **nunca
ganha link**. As duas situações diferem exatamente no que importa. Ver DEC-124.

---

## DEC-124 — Sem auditoria, o TikTok não publica: recusa antes de subir

⛔ `marcarPublicado()` recusa destino sem link, de propósito — é o DEC-31 em forma de guarda. Um post
privado no TikTok nunca terá link, então publicar ali só poderia terminar de dois jeitos, e os dois
são mentira:

- **"falhou"** depois de o vídeo ter subido de verdade — o painel dizendo que não subiu o que subiu,
  e ainda oferecendo republicar, o que **duplicaria** o vídeo;
- **"publicado"** sem prova nenhuma — que é o que o produto existe para não fazer.

⭐ A saída honesta é a terceira: **não publicar, e dizer por quê**. Mesmo desenho do Threads sem
endereço público (DEC-101) — botão que leva a isso é pior que botão ausente.

⚠️ **O custo:** enquanto a auditoria não sair, o TikTok conecta e não publica. Se o TikTok exigir uma
demonstração de publicação para conceder a auditoria, é aqui que essa conversa acontece — a trava é
uma linha de configuração.

---

## DEC-117 — Perguntar ao criador antes de publicar, sempre

`creator_info/query` é **obrigatório** pela documentação — e é enforçado: privacidade fora de
`privacy_level_options` devolve `privacy_level_option_mismatch`.

⭐ **E ele traz `max_video_post_duration_sec`, que é por CONTA, não por rede.** Isso quebra uma
suposição que valia para todas as outras: o teto de duração do `EspecificacaoDaRede` é o **máximo
possível**, e o real só se sabe perguntando.

⚠️ A recusa por duração acontece **antes** de subir um byte. Descobrir isso depois do envio inteiro
seria gastar a cota da pessoa para nada.

---

## DEC-118 — O token vive 24 horas, então ele é renovado NA HORA DE USAR

Prazo mais curto de todas as redes do painel, por larga margem.

⛔ **Um comando diário não basta.** Vídeo agendado, fila parada, worker que dormiu — qualquer atraso
maior que um dia encontra token morto.

⭐ O publicador renova **antes de publicar** quando o token está perto de vencer. O comando diário
continua existindo, mas como rede de segurança, não como o mecanismo principal.

---

## DEC-119 — O `refresh_token` girado é GRAVADO, sempre

*"The returned `refresh_token` may be different than the one passed in the payload."*

⛔ Guardar o antigo dá uma conexão que funciona hoje, funciona amanhã e um dia para — sem evento
para investigar. É o pior tipo de defeito.

---

## DEC-120 — `total_chunk_count` arredonda para BAIXO, e o último pedaço absorve a sobra

*"video_size ÷ chunk_size, rounded down."*

12 MB com pedaço de 5 MB dá **2** pedaços, e o último carrega 7 MB.

⛔ Todo mundo escreveria `ceil()` aqui. Arredondar para cima manda um número que não bate com o que
sobe, e o envio falha **depois** de o arquivo inteiro ter subido.

Regras que acompanham: pedaço de 5 MB a 64 MB (o último até 128 MB), 1 a 1000 pedaços, vídeo até
4 GB, **em sequência** — paralelo é proibido. Vídeo menor que 5 MB sobe em um pedaço só.

---

## DEC-121 — HTTP 200 com erro dentro é lido como erro

O `status/fetch` responde **200** e põe o erro em `error.code`.

⛔ Confiar no código HTTP trataria `invalid_publish_id` como sucesso, e o destino ficaria esperando
para sempre por um post que não existe.

---

## DEC-122 — `FILE_UPLOAD`, e o `PULL_FROM_URL` fica de fora

O `PULL_FROM_URL` exige verificar a posse do domínio no portal, por DNS ou prefixo de URL.

⚠️ É a rota que a URL temporária (DEC-100) atenderia — mas a verificação é um passo manual por
servidor, e o painel ainda muda de endereço. O `FILE_UPLOAD` não precisa de nada disso, e o painel já
sobe arquivo em pedaços desde o YouTube.

---

## DEC-123 — Só `internal` volta para a fila

A documentação marca **um** motivo como *retryable*. Todo o resto é recusa de conteúdo, de conta ou
de formato.

⚠️ **`auth_removed` ganha frase própria:** não é falha de vídeo nem de rede — é a pessoa tendo tirado
a autorização no aplicativo do TikTok. Dizer "falhou" mandaria ela procurar defeito no arquivo.

⚠️ **`reached_active_user_cap` também:** é o **nosso** aplicativo que estourou a cota do dia, não a
conta dela. A frase não pode culpar quem publicou, e a saída é esperar.

---

## As fases

### Fase 1 — Conexão

- [x] **1.1** `ConexaoComTiktok`: `client_key` (não `client_id`), escopos por vírgula
- [x] **1.2** Escopos **concedidos** do campo `scopes` (plural) do retorno
- [x] **1.3** `open_id` vira `identificador_externo`
- [x] **1.4** Token de 24 h + `refresh_token` de 365 dias, os dois guardados
- [x] **1.5** `CanalDeUmGrupoSo` — a mesma trava das outras redes
- [x] **1.6** Guardiões da conexão

### Fase 2 — Renovação

- [x] **2.1** `TokenDoTiktok`: renova e **grava o `refresh_token` girado** (DEC-119)
- [x] **2.2** Renovação na hora de usar, quando falta pouco para vencer (DEC-118)
- [x] **2.3** Comando diário como rede de segurança
- [x] **2.4** Guardiões da renovação

### Fase 3 — Publicação

- [x] **3.1** `creator_info/query` antes de tudo (DEC-117)
- [x] **3.2** Duração conferida contra `max_video_post_duration_sec` da conta
- [x] **3.3** Sem auditoria, recusa antes de subir (DEC-124); e `SELF_ONLY` como segunda barreira
- [x] **3.4** Pedaços com arredondamento para baixo e envio em sequência (DEC-120)
- [x] **3.5** `publish_id` no `handle_externo`, antes do primeiro byte — e **relido antes de
      recomeçar**, para não publicar duas vezes
- [x] **3.6** `conciliar()`: a prova é o `publicaly_available_post_id` (DEC-115)
- [x] **3.7** Erro dentro do 200 (DEC-121) e a separação de passageiro (DEC-123)
- [x] **3.8** Guardiões da publicação

**Pronto:** 50 guardiões verdes — 16 da conexão e do token, 20 da publicação, 7 só da aritmética de
pedaços.
**Falta a prova de campo:** nenhum vídeo saiu no TikTok de verdade, e o aplicativo no portal ainda
não existe.

---

## O que falta do lado do TikTok

Nada disso é código — é cadastro no portal, e só Gabriel pode fazer.

1. **Criar o aplicativo** em `developers.tiktok.com`.
2. **Adicionar dois produtos:** *Login Kit* e *Content Posting API*, este último com **Direct Post**
   habilitado. ⚠️ Sem o Direct Post, só existe o fluxo de caixa de entrada — e ele não serve
   (o vídeo terminaria de ser postado dentro do aplicativo do TikTok, sem post nosso para reler).
3. **Cadastrar o endereço de retorno:** `{APP_URL}/conexoes/tiktok/retorno`.
   ⚠️ HTTPS, absoluto, **estático** (sem parâmetro), sem `#`, e registrado no portal.
4. **Preencher no `.env`:** `TIKTOK_CLIENT_KEY` e `TIKTOK_CLIENT_SECRET`.
   ⚠️ É `client_key`, não `client_id`.
5. **Pedir a auditoria** quando o produto estiver pronto para gente de fora. Só depois disso
   `TIKTOK_AUDITADO=true` — e só então existe post público e link de prova.

---

## ⛔ O que fica de fora, de propósito

**Foto e carrossel.** O produto publica um vídeo vertical por vez.

**`SEND_TO_USER_INBOX` / escopo `video.upload`.** É o outro fluxo: o vídeo vai para a caixa de
entrada do criador e ele termina de postar **dentro do aplicativo do TikTok**. ⚠️ Não serve ao
produto — sem publicação nossa não há post para reler, e a promessa cai.

**Dueto, stitch e comentários por publicação.** O `creator_info` diz se a conta os desligou, e
respeitar isso é obrigatório; oferecer o controle **por post** é outra tela.

**`brand_content_toggle` / `brand_organic_toggle`.** Declaração de conteúdo comercial. Marcar errado
tem consequência para o criador, e isso é decisão dele, não padrão nosso.

---

# 🔬 Auditoria contra a documentação — 2026-08-14

_Mesmo método que achou os cinco defeitos da Meta: ler o código inteiro, ler a documentação oficial,
cruzar campo a campo. Lidos: `ConexaoComTiktok`, `TokenDoTiktok`, `PublicadorTiktok`,
`PedacosDoEnvio`, `FichaDoCriador`._

## ⛔ Achado 1 — `follower_count` estava no escopo errado (DEC-168)

A referência do `/v2/user/info/` divide os campos em **três** escopos, e o nome do primeiro engana:

| Escopo | Campos |
|---|---|
| `user.info.basic` | `open_id`, `union_id`, `avatar_url`, `display_name` — **só identidade** |
| `user.info.profile` | `bio_description`, `username`, `is_verified` |
| **`user.info.stats`** | **`follower_count`**, `following_count`, `likes_count`, `video_count` |

⛔ Pedíamos só `user.info.basic` e líamos `follower_count`. Resultado: `metricasDaConta()` voltando
`null` **para sempre**, e a tela dizendo *"sem leitura"* — indistinguível de rede que não respondeu.

⚠️ **Mesma família do `total_video_views` na Meta** (DEC-157): campo pedido com a chave errada,
resposta vazia, nenhum erro em lugar nenhum. Dois achados iguais em duas redes diferentes — o padrão
é claro o bastante para virar conferência de rotina: **todo campo lido tem um escopo, e o escopo
precisa estar na lista.**

## ⭐ Achado 2 — a declaração de IA sumia aqui (DEC-169)

O compositor tem a caixinha *"feito com IA"*. O Instagram recebia (`is_ai_generated`); o TikTok tem
`is_aigc` e **não recebia nada**. A mesma marcação, feita uma vez, valia numa rede e sumia na outra.

⛔ Não é preferência de interface — é **transparência com quem assiste**, e sumia sem ninguém notar.

⚠️ E é declarado **só quando a pessoa marca**: `is_aigc: false` seria o painel afirmando *"isto não é
IA"* em nome de quem não disse nada.

## ✅ Conferido e alinhado

Vale registrar o que **não** estava errado, para a próxima auditoria não refazer o caminho:

- **Pedaços do envio** — mínimo 5 MB, máximo 64 MB, último até 128 MB, teto de 1000 pedaços, arquivo
  abaixo de 5 MB num pedaço só, `total_chunk_count` **arredondando para baixo**. Tudo confere, e o
  arredondamento para baixo é a armadilha que a `PedacosDoEnvio` existe para provar com números.
- **Envio em sequência** — *"File chunks must be uploaded sequentially"*; o gerador deixa isso
  explícito e não convida a paralelizar.
- **`creator_info/query` obrigatório antes** de publicar, e a duração conferida contra o teto **da
  conta**, não o da rede.
- **Erro dentro de HTTP 200** — a armadilha central desta API, tratada em todas as chamadas.
- **`video/query`** — `view_count`, `like_count`, `comment_count`, `share_count` são campos válidos,
  exigem `video.list`, e a rede só devolve vídeo da conta autorizada.
- **Título 2200** e sem campo próprio (o `title` da API **é** a legenda).
- **Token de 24 h com `refresh_token` que gira** — renovado antes de começar o envio.
- **App não auditado publica só privado** — e post privado nunca ganha
  `publicaly_available_post_id`, então não há como provar. Continuamos recusando antes de subir
  (DEC-124), em vez de terminar em "falhou" depois do vídeo ter subido de verdade.

## 📌 O que ficou de fora, com motivo

- **`video_cover_timestamp_ms`** — escolher o quadro da capa. Depende da tela de capa, que não
  existe (está na lista do que falta entregar).
- **`brand_content_toggle` / `brand_organic_toggle`** — declaração de publieditorial. É decisão de
  quem publica, e exige uma pergunta na tela que ninguém pediu ainda.
- **`PULL_FROM_URL`** — exige domínio verificado e expõe o arquivo do cliente na internet. O envio
  direto não tem esse custo.

---

## ⛔ Achado 3 — as regras de TELA do TikTok são exigência de auditoria (DEC-170)

_Achado ao ler a documentação de **portal**, não a de API — a lacuna que o dono apontou._

O TikTok tem **UX Guidelines obrigatórias** para o Content Posting API, e elas não são sugestão de
desenho: são condição de auditoria. As duas que nos alcançam:

> *"API Clients **must** retrieve the latest creator info when rendering the Post to TikTok page. The
> upload page **must display the creator's nickname**, so users are aware of which TikTok account the
> content will be uploaded to."*
>
> *"The users of API Clients **must have full awareness and control** of what is being posted... API
> Clients **should display a preview** of the to-be-posted content."*

⛔ **O compositor não faz isso hoje.** Ele consulta `creator_info` **no servidor, na hora de
publicar** — o que satisfaz a regra técnica (DEC-117) e **não** a regra de tela: quem publica nunca vê
o apelido da conta do TikTok, nem as opções de privacidade que aquela conta permite.

⚠️ **Isso é bloqueio de lançamento, não detalhe.** Sem a auditoria, todo post sai privado e sem link
— e sem link o produto não cumpre a própria promessa (DEC-124 recusa publicar nesse estado).

**O que falta construir, do lado da tela:**

1. Mostrar o **apelido da conta** do TikTok no compositor, vindo de `creator_info`.
2. Deixar a pessoa **escolher a privacidade** entre as opções que `privacy_level_options` devolve —
   hoje o publicador escolhe sozinho.
3. Mostrar **prévia** do que vai subir.
4. Refletir `comment_disabled` / `duet_disabled` / `stitch_disabled` da conta.

⚠️ **E há um custo escondido no item 1:** `creator_info` é uma chamada à rede **por conta**, feita ao
abrir o compositor. Com o limite de **6 requisições por minuto por token**, abrir o compositor várias
vezes seguidas pode esbarrar — precisa de cache curto.

## ⚠️ Limites de volume — anotados antes de esbarrar

| O quê | Limite |
|---|---|
| Requisições por token, no geral | **6 por minuto** |
| Conferência de status | **30 por minuto** |
| Estouro | HTTP 429, `rate_limit_exceeded`, janela deslizante de 1 minuto |

⭐ O publicador já trata `rate_limit_exceeded` como espera, não como falha. O que **não** existe ainda
é o cuidado do lado da tela, no item acima.
