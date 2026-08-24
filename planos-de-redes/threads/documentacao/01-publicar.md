# Threads — publicar

_Cópia local do que a documentação oficial dizia em **2026-08-06**._
_Fonte: `developers.facebook.com/docs/threads/posts`._

> ⚠️ Cópia exigida pelo contrato (`CLAUDE.md` → *Antes de integrar QUALQUER rede*).
> O que não estiver aqui **não foi verificado**.

---

## O fluxo

**Post simples — dois passos:**

```
1. POST /{threads-user-id}/threads          → cria o contêiner, devolve o id
2. POST /{threads-user-id}/threads_publish  → publica o contêiner
```

**Carrossel — três passos:** um contêiner por item (`is_carousel_item=true`), depois um contêiner
`CAROUSEL` com `children`, depois o `threads_publish`.

⚠️ **Recomendação oficial: esperar ~30 segundos** entre criar o contêiner e publicar, *"para dar ao
nosso servidor tempo suficiente de processar o upload"*.

---

## Parâmetros do contêiner

| Parâmetro | Valores | Quando |
|---|---|---|
| `media_type` | `TEXT` · `IMAGE` · `VIDEO` (simples) · `CAROUSEL` (contêiner do carrossel) | sempre |
| `is_carousel_item` | `true` / `false` | sempre |
| `image_url` | **URL pública** | `IMAGE` |
| `video_url` | **URL pública** | `VIDEO` |
| `text` | string | `TEXT` |
| `children` | ids separados por vírgula | `CAROUSEL` |
| `topic_tag` | 1 a 50 caracteres, sem ponto e sem `&` | opcional |
| `link_attachment` | URL pública | **só em post de texto** |
| `gif_attachment` | objeto com `gif_id` e `provider` (GIPHY) | só em post de texto |

## ⛔ O achado que decide a arquitetura: **só URL pública**

Não existe envio de arquivo direto. `video_url` e `image_url` recebem **um endereço que a Meta vai
buscar**. Não há `rupload`, não há envio em pedaços, não há retomada.

Isso é o oposto do caminho de Facebook e Instagram por Login do Facebook, onde o arquivo sobe
direto. Aqui o vídeo precisa estar **acessível pela internet** no momento da publicação.

---

## Limites

| | |
|---|---|
| Texto | **500 caracteres** — emoji conta como número de bytes UTF-8 |
| Publicações | **250 por perfil a cada 24 h** (carrossel conta como uma) |
| Links | só em post de texto · até **5 links únicos** por post |
| Carrossel | mínimo 2, máximo 20 itens |

## Vídeo

| | |
|---|---|
| Contêiner | **MOV ou MP4** (`moov atom` no início) |
| Vídeo | **HEVC ou H.264** |
| Áudio | **AAC**, até 48 kHz, 1 a 2 canais, 128 kbps |
| Quadros | **23 a 60 FPS** |
| Largura máxima | **1920 px** |
| Proporção | 0,01:1 a 10:1 — **9:16 recomendado** |
| Taxa | VBR, até 100 Mbps |
| Duração | **até 300 segundos (5 min)** |
| Tamanho | **1 GB** |

## Imagem

| | |
|---|---|
| Formato | **JPEG ou PNG** |
| Tamanho | **8 MB** |
| Largura | mínimo **320 px**, máximo **1440 px** |
| Proporção | limite 10:1 |
| Cor | sRGB |

⚠️ **Aceita PNG** — diferente do Instagram, que só aceita JPEG.
