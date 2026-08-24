# Bluesky — os contadores que o protocolo publica

_Cópia local do que os lexicons oficiais diziam em **2026-08-05**._
_Fonte: `github.com/bluesky-social/atproto` → `lexicons/app/bsky/feed/defs.json` e
`lexicons/app/bsky/actor/defs.json`._

> ⚠️ Cópia exigida pelo contrato (`CLAUDE.md` → *Antes de integrar QUALQUER rede*).
> O que não estiver aqui **não foi verificado**.

---

## ⛔ O achado que decide o desenho: **visualização não existe**

O lexicon `app.bsky.feed.defs` **não define nenhum contador de visualização ou impressão**. Não é
"não temos permissão" nem "está atrás de um plano pago": o número **não existe no protocolo**.

Consequência direta: `visualizacoes` é **sempre `null`** no Bluesky, e a tela escreve a frase em vez
de mostrar zero (DEC-94/95). Não dá para calcular, estimar nem inferir.

---

## `app.bsky.feed.getPosts` → `postView`

| Campo | Tipo | Obrigatório? |
|---|---|---|
| `uri`, `cid`, `author`, `record`, `indexedAt` | — | **sim** |
| `likeCount` | integer | **não** |
| `repostCount` | integer | **não** |
| `replyCount` | integer | **não** |
| `quoteCount` | integer | **não** |
| `bookmarkCount` | integer | **não** |

⭐ **Os cinco contadores são OPCIONAIS.** Campo ausente é `null`, nunca `0` — e num protocolo
federado, servidor que não indexou ainda simplesmente não manda o campo.

Mapeamento usado no produto: `likeCount` → curtidas · `repostCount` → compartilhamentos ·
`replyCount` → comentários. `quoteCount` e `bookmarkCount` ficaram de fora: seriam colunas que
existem em uma rede só.

---

## `app.bsky.actor.getProfile` → `profileViewDetailed`

| Campo | Tipo | Obrigatório? |
|---|---|---|
| `did`, `handle` | string | **sim** |
| `followersCount` | integer | **não** |
| `followsCount` | integer | **não** |
| `postsCount` | integer | **não** |

Só `followersCount` entra (→ `seguidores`). Os outros dois não respondem nenhuma pergunta que este
produto faça.

---

## ⚠️ Por que a conciliação NÃO muda

A prova de publicação usa `com.atproto.repo.getRecord`, que lê o **repositório do autor direto**.
Os contadores vivem no **AppView** (o índice), e chegam por `getPosts`.

⛔ **Trocar o endereço da conciliação para ganhar o contador de brinde enfraqueceria a prova:** o
AppView pode não ter indexado o post ainda, e "não indexado" viraria "não publicado". O repositório
não tem esse atraso. O contador custa uma chamada a mais, e ela vale o preço.

---

## Sem autorização

O perfil público sai do AppView público (`public.api.bsky.app`) **sem token**. Dentro do produto a
leitura usa a sessão que já existe, para não depender de um segundo caminho de rede.
