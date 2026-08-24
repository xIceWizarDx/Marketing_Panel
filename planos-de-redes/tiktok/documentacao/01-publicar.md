# TikTok — publicar um vídeo (Direct Post)

_Cópia local do que a documentação oficial dizia em **2026-08-09**._
_Fonte: `developers.tiktok.com/doc/content-posting-api-reference-direct-post` e o guia de
transferência de mídia._

---

## Os três passos

```
0. perguntar   POST /v2/post/publish/creator_info/query/   → OBRIGATÓRIO antes de publicar
1. iniciar     POST /v2/post/publish/video/init/           → publish_id + upload_url
2. subir       PUT  {upload_url}                           → em pedaços, com Content-Range
3. conferir    POST /v2/post/publish/status/fetch/         → status e a PROVA
```

Servidor: `https://open.tiktokapis.com`.

Cabeçalhos de toda chamada da API:

```
Authorization: Bearer {token}
Content-Type: application/json; charset=UTF-8
```

---

## ⛔ 0. Perguntar ao criador — é OBRIGATÓRIO

```http
POST /v2/post/publish/creator_info/query/
```

Sem corpo. Escopo: `video.publish`.

| Campo da resposta | O que é |
|---|---|
| `creator_username` | identificador único |
| `creator_nickname` | nome de exibição |
| `creator_avatar_url` | ⚠️ vive **2 horas** |
| `privacy_level_options` | **quais privacidades esta conta pode usar** |
| `comment_disabled` | comentários desligados na conta |
| `duet_disabled` | dueto desligado (vídeo) |
| `stitch_disabled` | *stitch* desligado (vídeo) |
| `max_video_post_duration_sec` | **duração máxima DESTA conta** |

A documentação é literal: *"When rendering the Export to TikTok page, your app **must** invoke the
API and use the latest creator information returned to display the account's available privacy level
options and video/photo interaction settings."*

⚠️ **E não é só regra de etiqueta:** mandar uma privacidade fora de `privacy_level_options` devolve
`privacy_level_option_mismatch` e a publicação não acontece.

⭐ **`max_video_post_duration_sec` varia por conta.** Contas novas têm teto menor que contas
estabelecidas. Um limite fixo no nosso código diria "cabe" para um vídeo que aquela conta não aceita.

Limite de uso: **20 requisições por minuto** por token.

---

## 1. Iniciar

```http
POST /v2/post/publish/video/init/
```

```json
{
  "post_info": {
    "title": "legenda, até 2200 runas UTF-16",
    "privacy_level": "PUBLIC_TO_EVERYONE",
    "disable_duet": false,
    "disable_stitch": false,
    "disable_comment": false,
    "video_cover_timestamp_ms": 1000,
    "brand_content_toggle": false,
    "brand_organic_toggle": false,
    "is_aigc": false
  },
  "source_info": {
    "source": "FILE_UPLOAD",
    "video_size": 50000123,
    "chunk_size": 10000000,
    "total_chunk_count": 5
  }
}
```

| `privacy_level` | Quem vê |
|---|---|
| `PUBLIC_TO_EVERYONE` | todo mundo |
| `MUTUAL_FOLLOW_FRIENDS` | quem se segue de volta |
| `FOLLOWER_OF_CREATOR` | seguidores |
| `SELF_ONLY` | **só o dono** |

### Resposta

```json
{ "data": { "publish_id": "…", "upload_url": "…" },
  "error": { "code": "ok", "message": "", "log_id": "…" } }
```

⚠️ **O `upload_url` vale UMA HORA.** Passou, o envio precisa recomeçar do início.

Limite de uso: **6 requisições por minuto** por token.

---

## ⛔ 2. Subir — as regras de pedaço que enganam

| | |
|---|---|
| Pedaço mínimo | **5 MB** |
| Pedaço máximo | **64 MB** (menos o último) |
| Último pedaço | pode chegar a **128 MB**, para absorver a sobra |
| Pedaços | de **1 a 1000** |
| Vídeo | até **4 GB** |

⛔ **`total_chunk_count` = `video_size ÷ chunk_size`, ARREDONDADO PARA BAIXO.** Não é para cima.

Um vídeo de 12 MB com pedaço de 5 MB dá **2** pedaços, não 3 — e o segundo carrega 7 MB. Arredondar
para cima manda um número que não bate com o que sobe, e o envio falha.

⚠️ **Vídeo menor que 5 MB sobe em UM pedaço só**, com `chunk_size` igual ao arquivo inteiro.

⚠️ **Os pedaços sobem em SEQUÊNCIA.** *"File chunks must be uploaded sequentially"* — em paralelo não.

```http
PUT {upload_url}
Content-Type: video/mp4
Content-Length: {bytes deste pedaço}
Content-Range: bytes {PRIMEIRO}-{ULTIMO}/{TOTAL}
```

Tipos aceitos: `video/mp4`, `video/quicktime`, `video/webm`.

---

## ⭐ 3. Conferir — e aqui mora a prova

```http
POST /v2/post/publish/status/fetch/
{ "publish_id": "…" }
```

| `status` | O que é |
|---|---|
| `PROCESSING_UPLOAD` | subindo (FILE_UPLOAD) |
| `PROCESSING_DOWNLOAD` | baixando (PULL_FROM_URL) |
| `SEND_TO_USER_INBOX` | foi para a caixa do criador (fluxo de *upload*, não de publicação) |
| `PUBLISH_COMPLETE` | **publicado** |
| `FAILED` | falhou — o motivo vem em `fail_reason` |

### ⭐ `publicaly_available_post_id`

> *"Returns `post_id` only for public posts approved by moderation."*

⭐ **É a tese do produto implementada pela própria rede:** o identificador do post só aparece depois
que a **moderação aprovou**. `PUBLISH_COMPLETE` sem este campo quer dizer publicado e ainda não
liberado.

⚠️ Também vem `uploaded_bytes` (quantos bytes chegaram) e `downloaded_bytes`.

Limite de uso: **30 requisições por minuto** por token.

---

## PULL_FROM_URL — e por que não usamos

```json
{ "source_info": { "source": "PULL_FROM_URL", "video_url": "https://…" } }
```

⛔ Exige **verificar a posse do domínio** no portal do TikTok, por DNS ou prefixo de URL. A URL
precisa ser HTTPS, **não pode redirecionar** e tem que ficar de pé por uma hora.

⚠️ Verificação de domínio é um passo manual por servidor — e o painel muda de endereço. O
`FILE_UPLOAD` não precisa de nada disso.
