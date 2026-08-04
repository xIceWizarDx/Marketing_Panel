# Upload retomável — YouTube Data API v3

> **Cópia local da consulta à documentação oficial.**
> Fonte: <https://developers.google.com/youtube/v3/guides/using_resumable_upload_protocol>
> Consultado em **31/07/2026**.
>
> ⚠️ Isto é um **extrato**, não a página inteira: registra as respostas às perguntas que a
> implementação precisava. Para detalhe fora do que está aqui, voltar à fonte.

---

## 1. Abrir a sessão

```
POST https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=PARTES
```

Cabeçalhos obrigatórios:

| Cabeçalho | Valor |
|---|---|
| `Authorization` | token |
| `Content-Type` | `application/json; charset=UTF-8` |
| `Content-Length` | tamanho do corpo JSON |
| `X-Upload-Content-Length` | tamanho **total do arquivo** em bytes |
| `X-Upload-Content-Type` | MIME do vídeo (`video/*` ou `application/octet-stream`) |

**Resposta `200 OK`** traz o cabeçalho **`Location`** com o endereço da sessão.

---

## 2. Enviar o arquivo

`PUT` para o endereço da sessão, com `Authorization`, `Content-Length` e `Content-Type`.
Corpo = bytes do vídeo.

| Status | Significado |
|---|---|
| **`201 Created`** | terminou; a resposta traz o recurso `video` criado |
| **`308 Resume Incomplete`** | recebeu o pedaço; o cabeçalho `Range` diz até onde |

---

## 3. Perguntar quanto já subiu

```
PUT ENDERECO_DA_SESSAO
Content-Length: 0
Content-Range: bytes */TAMANHO_TOTAL
```

Responde **`308`** com **`Range: bytes=0-999999`** — indexado a partir de zero.
Sem cabeçalho `Range`, o servidor ainda não recebeu byte nenhum.

---

## 4. Validade e erros

- A sessão tem **duração finita** e expira. **`404` = sessão expirada** → recomeçar.
- **`500`, `502`, `503`, `504`** → **espera progressiva e retomada** pelo passo 3.
  ⚠️ Não confundir com expiração: em 5xx o endereço da sessão **continua valendo**.
