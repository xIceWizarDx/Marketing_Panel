# TikTok — erros e limites

_Cópia local do que a documentação oficial dizia em **2026-08-09**._

---

## Erros de iniciar a publicação

| HTTP | Código | O que é |
|---|---|---|
| 400 | `invalid_param` | o motivo vem na mensagem |
| 401 | `access_token_invalid` | token vencido ou inválido |
| 401 | `scope_not_authorized` | falta `video.publish` |
| 403 | `spam_risk_too_many_posts` | **teto de posts do dia** |
| 403 | `spam_risk_user_banned_from_posting` | a conta está proibida de publicar |
| 403 | `reached_active_user_cap` | **cota diária de usuários do nosso aplicativo** |
| 403 | `unaudited_client_can_only_post_to_private_accounts` | ⛔ aplicativo sem auditoria |
| 403 | `url_ownership_unverified` | domínio não verificado (PULL_FROM_URL) |
| 403 | `privacy_level_option_mismatch` | privacidade fora do que a conta permite |
| 429 | `rate_limit_exceeded` | passou do limite de uso |
| 5xx | | erro do servidor — repetir |

⚠️ `reached_active_user_cap` **não é problema da pessoa**: é o nosso aplicativo que estourou a cota
de usuários do dia. A frase para ela não pode culpar a conta dela.

---

## ⛔ Enquanto o aplicativo não for auditado, tudo é privado

> *"All content posted by unaudited clients will be restricted to private viewing mode."*

⚠️ É a mesma situação do YouTube antes da auditoria do Google. E aqui ela tem consequência dupla:

1. `privacy_level` **precisa** ser `SELF_ONLY`, ou o início devolve
   `unaudited_client_can_only_post_to_private_accounts`;
2. **`publicaly_available_post_id` nunca chega** — ele só vem para post público aprovado pela
   moderação. Ou seja, **não existe link de prova enquanto a auditoria não sair**.

---

## Motivos de falha (`fail_reason`)

| Motivo | O que é |
|---|---|
| `file_format_check_failed` | formato não aceito |
| `duration_check_failed` | duração fora do permitido |
| `frame_rate_check_failed` | taxa de quadros não aceita |
| `picture_size_check_failed` | dimensões não aceitas |
| `internal` | **indisponibilidade do servidor — passa** |
| `video_pull_failed` | falhou ao baixar o vídeo (PULL_FROM_URL) |
| `photo_pull_failed` | falhou ao baixar a foto |
| `publish_cancelled` | o desenvolvedor cancelou |
| `auth_removed` | **o criador revogou o acesso** |
| `spam_risk_too_many_posts` | passou do limite de 24 h |
| `spam_risk_user_banned_from_posting` | a conta está proibida de publicar |
| `spam_risk_text` | a legenda foi marcada como arriscada |
| `spam_risk` | o pedido foi marcado como arriscado |

⭐ **`internal` é o único passageiro da lista** — a própria documentação o marca como *retryable*.
Todos os outros são recusa de conteúdo, de conta ou de formato: repetir dá o mesmo resultado.

⚠️ `auth_removed` merece frase própria: não é falha de vídeo nem de rede, é a pessoa tendo tirado a
autorização no aplicativo do TikTok. A saída é reconectar, e dizer "falhou" mandaria ela procurar
defeito no arquivo.

---

## Erros de conferir a situação

⚠️ Estes vêm com **HTTP 200** e o código dentro do corpo.

| Código | O que é |
|---|---|
| `ok` | deu certo |
| `invalid_publish_id` | o identificador não existe |
| `token_not_authorized_for_specified_publish_id` | o token não é dono deste envio |
| `access_token_invalid` | token vencido |
| `scope_not_authorized` | falta o escopo |
| `rate_limit_exceeded` | passou do limite |
| `internal_error` | erro do servidor |

⛔ **HTTP 200 com erro dentro é a armadilha desta API.** Um motor que confie no código HTTP trataria
`invalid_publish_id` como sucesso e ficaria esperando para sempre um post que não existe.

---

## Limites de uso

| Chamada | Por minuto, por token |
|---|---|
| `creator_info/query` | 20 |
| `video/init` | **6** |
| `status/fetch` | 30 |

⚠️ Seis por minuto no início da publicação é o gargalo: publicar em várias contas ao mesmo tempo
esbarra nele antes de qualquer outro limite.
