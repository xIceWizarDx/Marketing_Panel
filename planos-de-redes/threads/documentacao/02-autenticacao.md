# Threads — autenticação

_Cópia local do que a documentação oficial dizia em **2026-08-06**._
_Fontes: `developers.facebook.com/docs/threads/get-started`,
`.../get-started/get-access-tokens-and-permissions`, `.../get-started/long-lived-tokens`._

---

## ⛔ O achado que corrige a DEC-30: **o login é OUTRO**

O Threads **não usa o Login do Facebook** e **não fala com `graph.facebook.com`**. Ele tem janela
de autorização, servidor e permissões próprios:

| | Facebook / Instagram | **Threads** |
|---|---|---|
| Janela de autorização | `www.facebook.com/.../dialog/oauth` | **`https://threads.net/oauth/authorize`** |
| Servidor da API | `graph.facebook.com` | **`graph.threads.net`** |
| Permissões | `instagram_*`, `pages_*` | **`threads_*`** |
| Envio de mídia | arquivo direto (`rupload`) | **só URL pública** |

O que continua sendo carona: **o mesmo aplicativo Meta** (acrescentando o caso de uso do Threads) e,
com isso, a mesma conta de desenvolvedor e provavelmente a mesma submissão de análise.

⚠️ Consequência: **é uma conexão separada, com token separado.** Conectar o Instagram não acende o
Threads.

---

## Permissões

| Escopo | Para quê |
|---|---|
| `threads_basic` | **obrigatório em todo endpoint** |
| `threads_content_publish` | publicar |
| `threads_manage_replies` | responder (`POST` em respostas) |
| `threads_read_replies` | ler respostas (`GET`) |
| `threads_manage_insights` | métricas |

---

## Os três passos do token

**1. Janela de autorização**

```
GET https://threads.net/oauth/authorize
    client_id · redirect_uri · response_type=code · scope · state
```

O código volta e vale **1 hora, uma vez só**.

**2. Código → token curto** — vale **1 hora**

```
POST https://graph.threads.net/oauth/access_token
     client_id · client_secret · code · grant_type=authorization_code · redirect_uri
```

**3. Token curto → token longo** — vale **60 dias**

```
GET https://graph.threads.net/access_token
    grant_type=th_exchange_token · client_secret · access_token
```

**Renovar o token longo** — volta a valer 60 dias:

```
GET https://graph.threads.net/refresh_access_token
    grant_type=th_refresh_token · access_token
```

⚠️ **Condições da renovação, e elas mordem:**
- o token precisa ter **pelo menos 24 horas de idade** e não estar vencido;
- exige `threads_basic` concedido;
- *"tokens que não forem renovados em 60 dias expiram e não podem mais ser renovados"*.

⛔ **Não existe token que não expira**, como o de Página do Facebook. Aqui a renovação é
obrigatória e tem janela: entre 24 horas e 60 dias. Fora dela, só reconectando.

---

## Acesso sem análise

A análise do aplicativo só é necessária para quem **não** tem papel no app. Quem é *"testador do
Threads"* concede as permissões na hora — mesmo desenho do Acesso Padrão da Meta e do modo de
Testes do YouTube.

---

## Quem sou eu — o perfil da conta conectada

```
GET https://graph.threads.net/v1.0/me
    ?fields=id,username,name,threads_profile_picture_url,threads_biography,is_verified
    &access_token=<TOKEN>
```

`me` é aceito no lugar do id. O `id` devolvido é o **threads-user-id**, que é o mesmo usado no
endereço de publicação (`POST /{threads-user-id}/threads`) — ou seja, é ele que vira o nosso
`identificador_externo`.

_Fonte: `developers.facebook.com/docs/threads/threads-profiles`, consultada em 2026-08-06._
