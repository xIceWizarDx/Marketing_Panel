# Documentação oficial do YouTube — cópia local

## Os arquivos

| Arquivo | O que é | Confiança |
|---|---|---|
| **`00-especificacao-oficial.json`** | **A especificação da API, publicada pelo Google.** Legível por máquina, versionada. Revisão **20260729**, baixada em 31/07/2026. | ⭐ **fonte de verdade** |
| `01-upload-retomavel.md` | Extrato da página do protocolo de upload retomável | extrato |
| `02-videos-insert.md` | Extrato da página do `videos.insert` | extrato |
| `03-recurso-video.md` | Extrato da página do recurso `video` | extrato |

⚠️ Os `.md` são **extratos** — respostas às perguntas que a implementação precisava, não as
páginas inteiras. O `.json` é a coisa completa.

**Por que os dois?** O JSON tem todos os campos, tipos e valores possíveis, mas **não explica
comportamento**: o protocolo de upload retomável (códigos 308, cabeçalho `Range`, o que fazer em
5xx) só existe na prosa. Um não substitui o outro.

## Como consultar o JSON

São 503 KB — **não abrir inteiro**. Consultar o pedaço que interessa:

```bash
# valores possíveis de um campo
node -e "console.log(require('./00-especificacao-oficial.json').schemas.VideoStatus.properties.uploadStatus.enum)"

# limites de upload do videos.insert
node -e "console.log(require('./00-especificacao-oficial.json').resources.videos.methods.insert.mediaUpload)"

# escopos de um método
node -e "console.log(require('./00-especificacao-oficial.json').resources.videos.methods.insert.scopes)"
```

## O que a especificação confirmou

Todos os valores extraídos das páginas de prosa **batem** com a spec:

- `uploadStatus`: `uploaded` · `processed` · `failed` · `rejected` · `deleted`
- `rejectionReason`: 10 valores, incluindo `uploaderAccountClosed` e `uploaderAccountSuspended`
- `failureReason`: `conversion` · `invalidFile` · `emptyFile` · `tooSmall` · `codec` · `uploadAborted`
- `privacyStatus`: `public` · `unlisted` · `private`
- upload: até **274.877.906.944 bytes** (256 GB), aceita `video/*` e `application/octet-stream`

## Como atualizar

```bash
curl -sL -o 00-especificacao-oficial.json \
  "https://www.googleapis.com/discovery/v1/apis/youtube/v3/rest"
```

Vale refazer quando algo quebrar sem motivo aparente: comparar a revisão mostra o que mudou.
