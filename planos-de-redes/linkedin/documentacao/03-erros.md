# LinkedIn — erros das duas APIs

_Cópia local do que a documentação oficial dizia em **2026-08-08**._

---

## API de vídeos

| HTTP | Código | O que é |
|---|---|---|
| 400 | `INVALID_VIDEO_ID` | o URN do vídeo não vale |
| 400 | `INVALID_URN_TYPE` | o campo recebeu um URN de outro tipo |
| 400 | `INVALID_URN_ID` | o id dentro do URN não vale |
| 400 | `EXPIRED_UPLOAD_URL` | **a URL de envio venceu** |
| 400 | `MEDIA_ASSET_PROCESSING_FAILED` | o vídeo falhou no processamento |
| 400 | `MEDIA_ASSET_WAITING_UPLOAD` | **o vídeo ainda não terminou de subir** |
| 400 | `UPDATING_ASSET_FAILED` | *"Please recreate the asset and try again"* |
| 403 | — | `{"message": "Accessing this video resource is forbidden…", "status": 403}` |

⚠️ `MEDIA_ASSET_WAITING_UPLOAD` **não é falha** — é pressa nossa. Aparece quando o post é criado antes
de o vídeo ficar `AVAILABLE`. Marcar como erro mandaria a pessoa reenviar um vídeo que estava
subindo bem.

---

## API de posts

| HTTP | Código | O que é |
|---|---|---|
| 400 | `INVALID_URN_TYPE` | tipo de URN errado em `author` ou `content.media.id` |
| 400 | `INVALID_URN_ID` | id do URN inválido |
| 400 | `MISSING_FIELD` | falta `author`, `visibility`, `distribution` ou `lifecycleState` |
| 400 | `INVALID_VALUE_FOR_FIELD` | valor fora do enum |
| 400 | `FIELD_LENGTH_TOO_LONG` | **texto passou do limite** |
| 400 | `INVALID_VALUE_BLANK_FIELD` | campo obrigatório em branco |
| 401 | `EMPTY_ACCESS_TOKEN` | token ausente ou vazio |
| 403 | `ACCESS_DENIED` | falta a permissão, ou o papel na página |
| 404 | `NOT_FOUND` | post não encontrado |
| 409 | `CONFLICT` | conflito de escrita — **repetir** |
| 422 | `UNPROCESSABLE_ENTITY` | pedido bem formado, sem sentido semântico |
| 429 | `TOO_MANY_REQUESTS` | **passou do limite de uso** |
| 500 | `INTERNAL_SERVER_ERROR` | erro da LinkedIn — repetir |
| 503 | `SERVICE_UNAVAILABLE` | serviço fora — repetir |

---

## ⭐ Quais passam sozinhos e quais não

Diferente da Meta, **o LinkedIn não devolve nenhum campo dizendo se o erro é passageiro**. A
separação sai do código, e ela é clara o bastante para não virar adivinhação:

**Passa (voltar para a fila):** `409 CONFLICT`, `429 TOO_MANY_REQUESTS`, `500`, `503`,
`MEDIA_ASSET_WAITING_UPLOAD`.

**Não passa (falha de verdade):** todo o resto dos `400`, o `403 ACCESS_DENIED` e o `404`.

⚠️ `EXPIRED_UPLOAD_URL` é o caso que engana: parece permanente, mas some se o envio recomeçar do
zero. Só que recomeçar é **um envio novo**, não uma repetição — e por isso ele é tratado como falha,
com frase dizendo para enviar de novo.

⛔ **`401` não é para repetir.** No LinkedIn ele quer dizer token vencido, e como não existe renovação
em segundo plano (ver `02-autenticacao.md`), repetir só queima tentativa. O certo é marcar a conexão
como vencida e pedir para reconectar.
