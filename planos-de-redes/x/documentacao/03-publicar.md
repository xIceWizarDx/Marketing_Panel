# X — subir o vídeo e criar o post

_Cópia local do que a documentação oficial dizia em **2026-08-09**._
_Fontes: `docs.x.com/x-api/media/quickstart/media-upload-chunked`, `.../x-api/media/upload-media` e
`.../x-api/posts/creation-of-a-post`._

---

## Os quatro passos

```
1. INIT      POST /2/media/upload   command=INIT      → media_id
2. APPEND    POST /2/media/upload   command=APPEND    → um por pedaço, em ordem
3. FINALIZE  POST /2/media/upload   command=FINALIZE  → processing_info
4. STATUS    GET  /2/media/upload   command=STATUS    → até `succeeded`
5. postar    POST /2/tweets         { text, media }   → o id do post
```

Servidor: `https://api.x.com`.

---

## 1. INIT

```
command=INIT
media_type=video/mp4
total_bytes={tamanho}
media_category=tweet_video
```

⚠️ **`media_category` é obrigatório**, e para vídeo de post o valor é `tweet_video` — não
`tweet_gif`, não `dm_video`.

Escopo: **`media.write`**.

### Resposta

```json
{ "data": { "id": "…", "media_key": "…", "expires_after_secs": 86400 } }
```

⚠️ O `media_id` vale ~24 horas (`expires_after_secs`). É o identificador que amarra os pedaços.

---

## 2. APPEND — um por pedaço, EM ORDEM

```
command=APPEND
media_id={id}
segment_index={0, 1, 2, …}
media={bytes do pedaço}
```

⚠️ Os exemplos oficiais usam pedaços de **1 MB**. A documentação **não declara um teto explícito** —
e por isso o código segue o exemplo em vez de inventar um número maior.

⭐ O `segment_index` é quem define a ordem — diferente do TikTok, que usa faixa de bytes, e do
LinkedIn, que usa a ordem dos recibos.

---

## 3. FINALIZE

```
command=FINALIZE
media_id={id}
```

```json
{ "data": { "id": "…", "size": …, "expires_after_secs": 86400,
            "processing_info": { "state": "pending", "check_after_secs": 5 } } }
```

⭐ **`check_after_secs` é a rede dizendo quando voltar.** Perguntar antes disso é gastar requisição —
e aqui requisição custa dinheiro.

---

## 4. STATUS

```
GET /2/media/upload?command=STATUS&media_id={id}
```

| `processing_info.state` | O que é |
|---|---|
| `pending` | na fila |
| `in_progress` | processando |
| `succeeded` | **pronto para virar post** |
| `failed` | falhou |

⛔ **Criar o post antes de `succeeded` é o erro clássico** — o mesmo do Instagram, do Threads e do
LinkedIn.

---

## 5. Criar o post

```http
POST https://api.x.com/2/tweets
Content-Type: application/json
```

```json
{ "text": "a legenda", "media": { "media_ids": ["1234567890"] } }
```

```json
{ "data": { "id": "1234567890", "text": "…", "edit_history_post_ids": ["1234567890"] } }
```

⭐ Aqui o identificador vem **no corpo** (`data.id`) — ao contrário do LinkedIn, que devolve no
cabeçalho.

O endereço do post se monta com o nome de usuário:
`https://x.com/{username}/status/{id}`.

Escopos: `tweet.write` (criar), `tweet.read` (ler), `users.read` (conta).

---

## ⚠️ O que a documentação NÃO diz

Nenhuma das páginas consultadas declara, para vídeo de post:

- tamanho máximo de arquivo;
- duração mínima e máxima;
- proporção, taxa de quadros ou codecs aceitos;
- quantos itens de mídia cabem num post;
- limite de caracteres do `text`.

⛔ **Nada disso foi inventado no código.** O limite que o painel aplica é o do perfil canônico do
produto (doc 07 §6), não um número da plataforma que ninguém verificou — e a recusa da rede, quando
vier, tem frase própria.
