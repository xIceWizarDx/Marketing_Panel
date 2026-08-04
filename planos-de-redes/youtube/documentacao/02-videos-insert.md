# `videos.insert` — YouTube Data API v3

> **Cópia local da consulta à documentação oficial.**
> Fonte: <https://developers.google.com/youtube/v3/docs/videos/insert>
> Consultado em **31/07/2026**.
>
> ⚠️ Extrato das respostas às perguntas da implementação, não a página inteira.

---

## Escopos aceitos

```
https://www.googleapis.com/auth/youtube.upload      ← o que usamos
https://www.googleapis.com/auth/youtube
https://www.googleapis.com/auth/youtubepartner
https://www.googleapis.com/auth/youtube.force-ssl   ← NÃO pedimos (permite apagar vídeo)
```

⭐ **DEC-41:** pedimos `youtube.upload` (envia) + `youtube.readonly` (confere se subiu).
`force-ssl` daria poder de apagar vídeos do canal — é o medo nº 1 nas entrevistas do doc 20, e
não precisamos dele para nada.

---

## Cota

> "A call to this method has a quota cost of **1 unit** in the **Video Uploads quota bucket**"
> — limite de **100 calls per day**.

⚠️ O teto é **do projeto**, somando todos os clientes (DEC-24). Estourou → o destino vai para
`aguardando_janela`, **não** para `falhou`.

---

## Limites do arquivo

| | |
|---|---|
| Tamanho máximo | **256 GB** |
| MIME aceitos | `video/*` · `application/octet-stream` |

---

## Campos

| Campo | Regra |
|---|---|
| `part` | **obrigatório**; define o que se está enviando e o que volta na resposta |
| `status.privacyStatus` | `public` · `private` · `unlisted` |
| `status.selfDeclaredMadeForKids` | **opcional** |
| `snippet.categoryId` | a doc diz "obrigatório ao chamar `videos.update`" — **não afirma nada sobre o `insert`**. ⚠️ Enviamos por precaução; **não verificado**. |

---

## Erros documentados nesta página

`uploadLimitExceeded` · `forbidden` · `forbiddenPrivacySetting` · `forbiddenLicenseSetting` ·
`invalidVideoMetadata`

⚠️ **`quotaExceeded` não aparece nesta página** — tratamos os dois assim mesmo, porque
`uploadLimitExceeded` é o documentado para o teto de envios.
