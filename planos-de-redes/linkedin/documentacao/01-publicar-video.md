# LinkedIn — subir o vídeo e criar o post

_Cópia local do que a documentação oficial dizia em **2026-08-08**._
_Fontes: `learn.microsoft.com/linkedin/marketing/community-management/shares/videos-api` e `.../posts-api`._

---

## São DUAS APIs e QUATRO chamadas

O LinkedIn não recebe o arquivo junto com o post. Primeiro o vídeo vira um recurso próprio, com URN
próprio; só depois esse URN entra num post.

```
1. inicializar   POST /rest/videos?action=initializeUpload   → URN do vídeo + URLs das partes
2. subir         PUT  {uploadUrl de cada parte}              → ETag de cada parte no cabeçalho
3. finalizar     POST /rest/videos?action=finalizeUpload     → junta as partes
4. postar        POST /rest/posts                            → 201 + URN do post no cabeçalho
```

⚠️ **Todas as chamadas exigem dois cabeçalhos**, e sem eles a API responde erro:

```
LinkedIn-Version: AAAAMM          (ex.: 202607 — versão datada, muda com o tempo)
X-Restli-Protocol-Version: 2.0.0
```

---

## 1. Inicializar

```http
POST https://api.linkedin.com/rest/videos?action=initializeUpload
Content-Type: application/json
```

```json
{ "initializeUploadRequest": {
     "owner": "urn:li:person:ABC123",
     "fileSizeBytes": 1055736,
     "uploadCaptions": false,
     "uploadThumbnail": false
} }
```

`owner` aceita `urn:li:person:{id}` **ou** `urn:li:organization:{id}`.

### Resposta

```json
{ "value": {
    "uploadUrlsExpireAt": 1633234498985,
    "video": "urn:li:video:C5505AQH-oV1qvnFtKA",
    "uploadInstructions": [
      { "uploadUrl": "https://www.linkedin.com/dms-uploads/…", "firstByte": 0, "lastByte": 4194303 }
    ],
    "uploadToken": ""
} }
```

⭐ **`video` é o URN, e ele existe antes de um único byte subir.** É o equivalente ao endereço de
retomada do YouTube: guardado antes do efeito, um reenvio encontra o recurso que já existe em vez de
criar outro.

⚠️ `uploadToken` vem **string vazia** no exemplo oficial — e mesmo assim é obrigatório repetir o
valor no passo de finalizar.

⚠️ As URLs de envio expiram (tipicamente 30 dias). Subir numa URL vencida devolve **401**.

---

## 2. Subir as partes

```bash
curl -H "Content-Type:application/octet-stream" --upload-file parte.mp4 "{uploadUrl}"
```

A resposta é `200 OK` **sem corpo**, e o que importa está no cabeçalho:

```
etag: /ambry-videoei/signedId/AQHX97-zKFZrew….bin
```

⭐ **O `ETag` de cada parte é o recibo, e a ORDEM importa:** eles vão para `uploadedPartIds` na mesma
ordem em que as partes aparecem em `uploadInstructions`. Trocar a ordem monta o vídeo embaralhado.

### ⛔ O tamanho da parte sai do `firstByte`/`lastByte`, NUNCA do exemplo

A documentação manda `split -b 4194303`, mas o intervalo que ela mesma devolve é `0` a `4194303`
**inclusive** — 4.194.304 bytes. Os dois números não fecham: seguir o `split` deixaria cada parte um
byte menor que o pedido, e o desencontro só apareceria num arquivo grande, com o vídeo montado
errado no fim.

⚠️ **A fonte da verdade é o par `firstByte`/`lastByte` que a API devolve em cada instrução.**

---

## 3. Finalizar

```http
POST https://api.linkedin.com/rest/videos?action=finalizeUpload
```

```json
{ "finalizeUploadRequest": {
     "video": "urn:li:video:C5505AQHErI8lGthkfA",
     "uploadToken": "",
     "uploadedPartIds": ["/ambry-video/signedId/….bin", "…"]
} }
```

Resposta `200 OK`.

---

## ⭐ Conferir o vídeo — o que dá para provar

```http
GET https://api.linkedin.com/rest/videos/urn%3Ali%3Avideo%3AC4E10AQGUkQY7trgh-Q
```

| `status` | O que é |
|---|---|
| `WAITING_UPLOAD` | esperando o arquivo ou o envio terminar |
| `PROCESSING` | processando |
| `AVAILABLE` | **pronto** — pode entrar num post |
| `PROCESSING_FAILED` | falhou; o motivo vem em `processingFailureReason` |

⚠️ **Criar o post antes de `AVAILABLE` é o erro clássico:** a API de posts devolve
`MEDIA_ASSET_WAITING_UPLOAD` ou `MEDIA_ASSET_PROCESSING_FAILED`.

---

## 4. Criar o post

```http
POST https://api.linkedin.com/rest/posts
```

```json
{
  "author": "urn:li:person:ABC123",
  "commentary": "Legenda do post",
  "visibility": "PUBLIC",
  "distribution": {
    "feedDistribution": "MAIN_FEED",
    "targetEntities": [],
    "thirdPartyDistributionChannels": []
  },
  "content": { "media": { "title": "título do vídeo", "id": "urn:li:video:C5F10AQGKQg_6y2a4sQ" } },
  "lifecycleState": "PUBLISHED",
  "isReshareDisabledByAuthor": false
}
```

### ⭐ O URN do post vem no CABEÇALHO, não no corpo

Resposta `201 Created`, e o identificador está em:

```
x-restli-id: urn:li:share:6844785523593134080
```

⛔ **O corpo vem vazio.** Um publicador que procure o id no JSON acha `null` e trata como falha — com
o post já publicado. É a receita para publicar duas vezes.

⚠️ Pode vir `urn:li:share:{id}` **ou** `urn:li:ugcPost:{id}`. Os dois são válidos, e o endereço se
monta igual:

```
https://www.linkedin.com/feed/update/{urn}/
```

### `lifecycleState`

`PUBLISHED` é o **único** valor aceito na criação. Nas leituras podem voltar `DRAFT`,
`PUBLISH_REQUESTED` (aceito, ainda processando) e `PUBLISH_FAILED` (aceito e **não** publicado —
exige edição para tentar de novo).

---

## Limites do arquivo

| | |
|---|---|
| Duração | 3 segundos a 30 minutos |
| Formato | MP4 |
| Tamanho | **entre 75 KB e 500 MB** |

⚠️ **A mesma página se contradiz:** a seção de especificação diz 500 MB, e a descrição do campo
`initializeUploadRequest.fileSizeBytes` diz *"Maximum allowed Videos size is 5GB"*. Fica valendo o
menor — recusar em 500 MB é seguro nas duas leituras; aceitar 5 GB pode falhar no meio do envio, com
o arquivo já subindo.
