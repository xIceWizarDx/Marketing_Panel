# Recurso `video` — os campos que dizem se o vídeo está no ar

> **Cópia local da consulta à documentação oficial.**
> Fonte: <https://developers.google.com/youtube/v3/docs/videos>
> Consultado em **31/07/2026**.
>
> ⚠️ Extrato das respostas às perguntas da implementação, não a página inteira.

---

## ⭐ `status.uploadStatus` — é daqui que sai a prova de entrega

| Valor | Significado | O que o motor faz |
|---|---|---|
| `uploaded` | o arquivo chegou | **espera** — chegou ≠ está no ar |
| `processed` | o YouTube terminou de processar | **publicado**, com o link |
| `rejected` | o YouTube recusou o vídeo | **falhou**, com o motivo |
| `failed` | o envio deu errado | **falhou**, com o motivo |
| `deleted` | o vídeo foi removido | **falhou** |

⭐ **DEC-31 mora aqui.** Um vídeo pode ser aceito e **rejeitado depois**. Marcar "publicado" no
`uploaded` seria exatamente a mentira que o produto existe para não contar.

---

## `status.rejectionReason` — 10 valores

| Valor | Significado |
|---|---|
| `claim` | reivindicação de direitos por terceiro |
| `copyright` | violação de direito autoral |
| `duplicate` | conteúdo idêntico já existe |
| `inappropriate` | viola as políticas |
| `legal` | questão jurídica |
| `length` | passa da duração permitida |
| `termsOfUse` | viola os termos de uso |
| `trademark` | violação de marca registrada |
| **`uploaderAccountClosed`** | ⚠️ **a conta não existe mais** |
| **`uploaderAccountSuspended`** | ⚠️ **a conta está suspensa** |

⚠️ Os dois últimos **não são sobre o vídeo, são sobre a CONTA**. Tratar como "vídeo recusado"
faria o motor seguir tentando publicar numa conta morta.

---

## `status.failureReason` — 6 valores

| Valor | Significado |
|---|---|
| `codec` | codec de áudio ou vídeo não suportado |
| `conversion` | erro ao converter |
| `emptyFile` | arquivo sem conteúdo |
| `invalidFile` | arquivo corrompido |
| `tooSmall` | arquivo abaixo do mínimo |
| `uploadAborted` | envio cancelado |

---

## `status.privacyStatus`

`private` (só o dono vê) · `public` · `unlisted` (só quem tem o link)

⚠️ **Antes da auditoria de compliance, o YouTube ignora o que a gente pedir e trava tudo em
`private`.** Confirmado na nossa pesquisa (doc 07 §3). São dois portões separados: a
verificação do OAuth e a auditoria do projeto.

---

## `processingDetails.processingStatus`

`processing` · `succeeded` · `failed` · `terminated` (sem dados)

---

## Limites de texto

| Campo | Limite |
|---|---|
| `snippet.title` | **100 caracteres** |
| `snippet.description` | **5000 bytes** ⚠️ — bytes, não caracteres: acento ocupa 2 |
| `snippet.tags` | **500 caracteres no total**, contando vírgulas; tag com espaço vai entre aspas e elas contam (`"Foo Baz"` = 9, não 7) |
