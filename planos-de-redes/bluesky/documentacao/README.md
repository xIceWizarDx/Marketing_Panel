# Documentação oficial do Bluesky — cópia local

## Os arquivos

| Arquivo | O que é | Confiança |
|---|---|---|
| **`lexicons/*.json`** | **Os lexicons oficiais**, baixados do repositório do AT Protocol. É onde os limites são declarados de verdade. | ⭐ **fonte de verdade** |
| `01-post-e-limites.md` | Extrato das páginas de prosa | extrato |

## ⚠️ A prosa estava desatualizada; o lexicon não

Foi por isso que baixar o lexicon valeu a pena — duas divergências apareceram:

| | Página de prosa | Lexicon | Quem vale |
|---|---|---|---|
| Tamanho da imagem | 1.000.000 bytes | **2.000.000 bytes** | lexicon |
| Tamanho do vídeo | "100 MB ou mais" | **100.000.000 bytes** (exato) | lexicon |

## 🔴 O que o lexicon revelou e a prosa não dizia

**`app.bsky.embed.video` aceita SOMENTE `video/mp4`.**

A prosa não menciona isso. Nossa implementação manda o MIME do arquivo — e a biblioteca aceita
`.mov` do iPhone. Um `.mov` seria **recusado pelo Bluesky**, e a mensagem viria em inglês e sem
explicação.

## Números confirmados

| | |
|---|---|
| `text.maxGraphemes` | **300** ⚠️ grafemas, não caracteres |
| `text.maxLength` | 3000 bytes |
| obrigatórios no post | `text`, `createdAt` |
| vídeo | **100.000.000 bytes** · só `video/mp4` |
| imagem | **2.000.000 bytes** · `image/*` · **até 4 por post** |
| embeds possíveis | `images` · `video` · `gallery` · `external` · `record` · `recordWithMedia` |

## Como consultar e atualizar

```bash
# limites de um lexicon
node -e "console.log(require('./lexicons/app.bsky.embed.video.json').defs.main.properties.video)"

# atualizar
curl -sL -o lexicons/app.bsky.feed.post.json \
  "https://raw.githubusercontent.com/bluesky-social/atproto/main/lexicons/app/bsky/feed/post.json"
```

Baixados em **31/07/2026**.
