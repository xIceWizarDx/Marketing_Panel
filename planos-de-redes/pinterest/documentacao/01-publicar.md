# Pinterest — publicar um vídeo

_Extraído da **spec OpenAPI oficial** (`github.com/pinterest/api-description`, `v5/openapi.yaml`),
lida em **2026-08-09**._

⭐ **Spec legível por máquina, não prosa.** A regra do projeto manda preferir isso — e aqui foi
decisivo: as páginas de documentação do Pinterest são aplicação JavaScript e não entregam conteúdo
para leitura automática. A spec entregou tudo, com os limites exatos.

---

## Os quatro passos

```
1. registrar  POST /v5/media          { media_type: "video" }  → media_id + upload_url + upload_parameters
2. enviar     POST {upload_url}       multipart, para a AWS    → sem corpo
3. conferir   GET  /v5/media/{id}     → status
4. fixar      POST /v5/pins           { board_id, media_source } → o Pin
```

Servidor: `https://api.pinterest.com/v5`.

---

## ⛔ 1. Registrar — e o envio NÃO é para o Pinterest

```json
POST /v5/media
{ "media_type": "video" }
```

Resposta:

```json
{
  "media_id": "12345",
  "media_type": "video",
  "upload_url": "https://pinterest-media-upload.s3-accelerate.amazonaws.com/",
  "upload_parameters": {
    "Content-Type": "multipart/form-data",
    "key": "uploads/11/aa/22/3:video:20301…",
    "policy": "eyJleHBpcmF0aW9uIjoiMj..==",
    "x-amz-algorithm": "AWS4-HMAC-SHA256",
    "x-amz-credential": "ASIA…/20220127/us-east-1/s3/aws4_request",
    "x-amz-date": "20220127T185143Z",
    "x-amz-security-token": "IQoJb3JpZ2luX2VjEJr...==",
    "x-amz-signature": "…"
  }
}
```

⚠️ **O `upload_url` é da Amazon, não do Pinterest.** É um formulário assinado do S3.

---

## ⛔ 2. Enviar — e a instrução da spec é literal

> *"To upload the media, make an HTTP POST request to `upload_url` using the `Content-Type` header
> value. Send the media file's contents as the request's `file` parameter and also include **all of
> the parameters** from `upload_parameters`."*

⛔ **Todos os parâmetros, e o arquivo por último.** Formulário assinado do S3 ignora o que vier
**depois** do campo `file` — mandar `key` ou `policy` no fim faz a Amazon recusar com um erro de XML
que não menciona ordem nenhuma.

⚠️ E não vai token nosso aqui: quem autoriza é a assinatura que veio dentro dos parâmetros.

---

## 3. Conferir

```
GET /v5/media/{media_id}
```

| `status` | O que é |
|---|---|
| `registered` | registrado, nada chegou ainda |
| `processing` | processando |
| `succeeded` | **pronto para virar Pin** |
| `failed` | falhou |

---

## ⛔ 4. Fixar — e todo Pin mora num QUADRO

```json
POST /v5/pins
{
  "board_id": "1234567890",
  "title": "…",
  "description": "…",
  "link": "…",
  "media_source": {
    "source_type": "video_id",
    "media_id": "12345",
    "cover_image_key_frame_time": 1
  }
}
```

⛔ **`board_id` é o "para onde" desta rede.** Nenhuma outra tem isso: no YouTube o vídeo vai para o
canal, no X para o perfil. Aqui a conta tem N quadros, e o Pin **precisa** escolher um.

### A capa

`media_source` aceita três formas de capa, e todas são **opcionais** na spec:

| Campo | O que é |
|---|---|
| `cover_image_key_frame_time` | ⭐ o segundo do próprio vídeo que vira capa |
| `cover_image_url` | endereço de uma imagem |
| `cover_image_data` + `cover_image_content_type` | imagem em Base64 |

⭐ O quadro do próprio vídeo é o único que **não** exige subir um segundo arquivo.

---

## Os limites de texto — estes a spec declara

| Campo | Máximo |
|---|---|
| `title` | **100** |
| `description` | **800** |
| `alt_text` | 500 |
| `link` | 2048 |

⭐ **O Pinterest TEM campo de título separado** — como YouTube e Facebook, e ao contrário de Threads,
TikTok, X, Bluesky e Instagram.

⚠️ **O que a spec NÃO declara:** tamanho máximo do arquivo, duração, proporção, taxa de quadros e
codecs. Nada disso foi inventado no código.

---

## Autorização

```
autorizar → https://www.pinterest.com/oauth/
token     → https://api.pinterest.com/v5/oauth/token
```

Escopos existentes na spec: `boards:read`, `boards:read_secret`, `boards:write`, `pins:read`,
`pins:read_secret`, `pins:write`, `user_accounts:read`, `user_accounts:write`.

⭐ Precisamos de quatro: `boards:read` (achar os quadros), `pins:write` (fixar), `pins:read`
(**reler o Pin — a prova**) e `user_accounts:read` (saber de quem é a conta).

---

## A prova

```
GET /v5/pins/{pin_id}
```

⭐ Existe, e é o que fecha a DEC-31 nesta rede.
