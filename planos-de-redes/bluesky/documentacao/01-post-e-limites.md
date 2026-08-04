# `app.bsky.feed.post` — limites e anexos

> **Cópia local da consulta à documentação oficial.**
> Fontes:
> - <https://docs.bsky.app/docs/advanced-guides/posts>
> - <https://github.com/bluesky-social/atproto/blob/main/lexicons/app/bsky/feed/post.json> (o lexicon é a fonte de verdade)
>
> Consultado em **31/07/2026**. ⚠️ Extrato, não a página inteira.

---

## ⚠️ O texto é medido em GRAFEMAS, não em caracteres

O lexicon define **dois** limites no campo `text`:

| Regra | Valor |
|---|---|
| `maxGraphemes` | **300** |
| `maxLength` | **3000** (bytes) |

**Grafema ≠ caractere ≠ byte.** Um emoji de família (👨‍👩‍👧‍👦) é **1 grafema**, mas vários pontos
de código e muitos bytes. Contar com `mb_strlen` (pontos de código) **recusa texto que o
Bluesky aceitaria**.

Em PHP a contagem certa é `grapheme_strlen()` (extensão `intl`).

> O limite de 300 é posição declarada do projeto, não detalhe de implementação: *"qualquer
> outro tamanho seria uma modalidade diferente de aplicativo"*.

---

## Anexos

| | |
|---|---|
| Imagens | até **4 por post**, cada uma com texto alternativo e proporção próprios |
| Tamanho da imagem | **1.000.000 bytes** na documentação de posts; a discussão do repositório fala em **2 MB** no lexicon — ⚠️ **divergência entre as fontes, não resolvida** |
| Vídeo | `app.bsky.embed.video`. Tamanho **"100 MB ou mais"**, sem número exato publicado — ⚠️ **não verificado** |

---

## Campos do registro

| Campo | Regra |
|---|---|
| `text` | obrigatório |
| `createdAt` | obrigatório; ISO 8601 — **o `Z` no fim é preferido** a `+00:00` |
| `langs` | lista de idiomas (ex.: `["pt-BR"]`) |
| `embed` | opcional; `app.bsky.embed.images` · `app.bsky.embed.video` · `app.bsky.embed.external` |

---

## 🔴 O que isto corrige na nossa implementação

| Escrito | Documentado | Impacto |
|---|---|---|
| limite conferido com `mb_strlen` | são **grafemas** | 🔴 recusa texto válido com emoji |
| vídeo limitado a 50 MB | "100 MB ou mais", sem número oficial | 🟡 limite inventado, mais restritivo que o real |
| imagem sem limite próprio | 1 MB (ou 2 MB) | 🟡 falta a conferência |
