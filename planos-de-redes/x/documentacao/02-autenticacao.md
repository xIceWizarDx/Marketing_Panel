# X — autorização (OAuth 2.0 com PKCE)

_Cópia local do que a documentação oficial dizia em **2026-08-09**._
_Fonte: `docs.x.com/resources/fundamentals/authentication/oauth-2-0/authorization-code`._

---

## Passo 1 — mandar a pessoa autorizar

```
GET https://x.com/i/oauth2/authorize
    ?response_type=code
    &client_id={client_id}
    &redirect_uri={callback}
    &scope=tweet.read%20tweet.write%20users.read%20media.write%20offline.access
    &state={aleatório, até 500 caracteres}
    &code_challenge={PKCE}
    &code_challenge_method=S256
```

⚠️ Os escopos vão separados por **espaço** (`%20`).

⚠️ O `redirect_uri` exige **correspondência exata** com o cadastrado.

### ⛔ PKCE é obrigatório

`code_challenge` + `code_challenge_method` (`S256` ou `plain`). O par nasce a cada autorização: um
segredo aleatório (`code_verifier`), e o desafio é o SHA-256 dele.

⛔ **O `code_verifier` precisa sobreviver até a volta**, junto com o `state` — os dois na sessão. Sem
ele, a troca do código falha e não há como recuperar.

---

## ⛔ Passo 2 — trocar o código, e o código vive 30 SEGUNDOS

```http
POST https://api.x.com/2/oauth2/token
Content-Type: application/x-www-form-urlencoded

code=…&code_verifier=…&grant_type=authorization_code&client_id=…&redirect_uri=…
```

> *"code: Authorization code (expires in 30 seconds)"*

⛔ **Trinta segundos é o prazo mais curto de qualquer rede do painel, por uma ordem de grandeza.**
Qualquer coisa feita antes da troca — ler perfil, conferir grupo, gravar no banco — pode consumir a
janela inteira e queimar a autorização. A troca tem que ser a **primeira** coisa que acontece na
volta.

**Autenticação do cliente:** aplicativo confidencial usa o cabeçalho `Authorization` (Basic);
aplicativo público manda `client_id` no corpo.

---

## ⛔ O token vive 2 HORAS

> *"Access tokens remain valid for 2 hours by default, unless the `offline.access` scope is
> included."*

⚠️ **Mais curto que o do TikTok (24 h) e muito mais curto que os 60 dias do LinkedIn.** Renovar não é
manutenção: é parte de publicar.

## ⛔ Sem `offline.access` não existe renovação

> *"Refresh Token Requirement: Only issued when the `offline.access` scope is requested."*

⚠️ Esquecer esse escopo dá uma conexão que funciona por duas horas e morre — e o erro só aparece
depois, sem nada ter mudado.

```http
POST https://api.x.com/2/oauth2/token

grant_type=refresh_token&refresh_token=…&client_id=…
```

---

## Os escopos

| Escopo | O que dá |
|---|---|
| `tweet.write` | criar e apagar post |
| `tweet.read` | ler post — **é a prova** |
| `users.read` | quem é a conta |
| `media.write` | *"Upload media, such as photos and videos, on your behalf."* |
| `offline.access` | ⛔ sem ele **não há token de renovação** |

⚠️ **`media.write` é escopo separado**, e é fácil esquecer: sem ele a conta conecta, o texto sobe, e
o vídeo não.
