# LinkedIn — autorização e permissões

_Cópia local do que a documentação oficial dizia em **2026-08-08**._
_Fontes: `learn.microsoft.com/linkedin/shared/authentication/authorization-code-flow`,
`.../getting-access` e `.../consumer/integrations/self-serve/share-on-linkedin`._

---

## ⛔ O que um aplicativo comum consegue — a lista COMPLETA

A página *Getting Access* é explícita: *"Open Permissions are the only permissions that are
available to all developers without special approval."* E a lista inteira é esta:

| Produto | Permissão | O que dá |
|---|---|---|
| Sign in with LinkedIn using OpenID Connect | `profile` | nome, título e foto |
| Sign in with LinkedIn using OpenID Connect | `email` | e-mail principal |
| Share on LinkedIn | `w_member_social` | **publicar** em nome da pessoa |

⛔ **Não existe permissão de LEITURA de post na lista.** `r_member_social` é *restricted — available
to approved users only*. `w_organization_social` e `r_organization_social` são do programa de
Marketing e exigem aprovação da LinkedIn.

⚠️ **Consequência direta:** no nível aberto o LinkedIn é uma rede de **escrita apenas**. Dá para
publicar; não dá para reler o post pela API.

---

## Fluxo de autorização (3-legged OAuth)

### Passo 1 — mandar a pessoa autorizar

```
GET https://www.linkedin.com/oauth/v2/authorization
    ?response_type=code
    &client_id={client_id}
    &redirect_uri={callback}
    &state={valor único e difícil de adivinhar}
    &scope=openid%20profile%20w_member_social
```

⚠️ O `redirect_uri` precisa ser **HTTPS e absoluto**, tem que bater exatamente com o cadastrado no
portal, **não pode ter `#`**, e os parâmetros de query são ignorados na comparação.

⚠️ Mudar o `scope` do aplicativo **obriga todo mundo a autorizar de novo**.

### Erros de recusa

| `error` | O que foi |
|---|---|
| `user_cancelled_login` | a pessoa não entrou na conta |
| `user_cancelled_authorize` | a pessoa recusou as permissões |

### Passo 2 — trocar o código pelo token

```http
POST https://www.linkedin.com/oauth/v2/accessToken
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&code=…&client_id=…&client_secret=…&redirect_uri=…
```

⚠️ O código de autorização **vive 30 minutos** e é de uso único.

```json
{ "access_token": "AQUvlL_DYEzvT2wz1QJiEPeLioeA", "expires_in": 5184000, "scope": "…" }
```

⚠️ O token pode passar de 500 caracteres, e a documentação pede para o sistema aguentar **1000**.

---

## ⛔ Renovar o token NÃO é coisa de servidor

`expires_in: 5184000` são **60 dias** — *"all access tokens are issued with a 60-day lifespan"*.

E a renovação é diferente de todas as outras redes que o painel atende:

> *"To refresh an access token, go through the authorization process again to fetch a new token."*
> *"Programmatic refresh tokens are available for a limited set of partners."*

⚠️ **Sem ser parceiro aprovado não existe renovação em segundo plano.** A pessoa precisa passar pelo
endereço de autorização de novo — o que é invisível para ela **se** ainda estiver logada no LinkedIn
e o token ainda não tiver vencido; fora dessas duas condições, é a tela de autorização inteira.

⛔ **Um serviço que renova sozinho de madrugada não resolve o LinkedIn.** O que resolve é avisar
antes de vencer, com folga, e o painel já sabe fazer isso: o semáforo de conexão existe para
exatamente este caso.

---

## O URN da pessoa

`author` e `owner` exigem `urn:li:person:{id}`. O `{id}` é o campo `sub` do
*Sign In with LinkedIn using OpenID Connect* — o `r_liteprofile` antigo saiu de cena.

---

## Limites de uso

| Tipo | Por dia (UTC) |
|---|---|
| Por membro | **150 requisições** |
| Por aplicativo | 100.000 requisições |

⚠️ **150 requisições ≠ 150 posts.** Uma publicação nossa gasta, no mínimo: 1 inicializar + N partes +
1 finalizar + 1 conferir vídeo + 1 criar post. Um vídeo de 40 MB são 10 partes — **14 requisições**.
O teto real fica perto de **10 publicações por dia por pessoa**, não 150.
