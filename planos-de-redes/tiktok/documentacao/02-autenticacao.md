# TikTok — autorização e token

_Cópia local do que a documentação oficial dizia em **2026-08-09**._
_Fontes: `developers.tiktok.com/doc/login-kit-web` e `.../oauth-user-access-token-management`._

---

## Passo 1 — mandar a pessoa autorizar

```
GET https://www.tiktok.com/v2/auth/authorize/
    ?client_key={client_key}
    &response_type=code
    &scope=user.info.basic,video.publish
    &redirect_uri={callback}
    &state={valor único e difícil de adivinhar}
```

⚠️ **É `client_key`, não `client_id`.** O nome é diferente de toda outra rede, e mandar `client_id`
devolve erro que não diz qual parâmetro faltou.

⚠️ Os escopos vão separados por **vírgula**.

### O endereço de retorno

- no máximo **10 por aplicativo**, de 512 caracteres cada;
- absoluto e começando com **`https`**;
- **estático** — nada de parâmetro dinâmico;
- **sem `#`**;
- registrado no portal.

### A volta

| Sucesso | |
|---|---|
| `code` | o código de autorização |
| `scopes` | ⭐ os escopos **aprovados**, separados por vírgula |
| `state` | o mesmo que foi mandado |

⭐ Repare no plural: **`scopes`**, não `scope`. Ler o campo errado devolveria vazio e recusaria toda
conexão válida.

| Erro | |
|---|---|
| `error` | a pessoa não pode usar login de terceiro |
| `error_description` | texto legível |

---

## Passo 2 — trocar o código pelo token

```http
POST https://open.tiktokapis.com/v2/oauth/token/
Content-Type: application/x-www-form-urlencoded

client_key=…&client_secret=…&code=…&grant_type=authorization_code&redirect_uri=…
```

⚠️ O `code` precisa ser **decodificado de URL** antes de ser mandado.

```json
{
    "access_token": "act.example…",
    "expires_in": 86400,
    "open_id": "afd97af1-b87b-48b9-ac98-410aghda5344",
    "refresh_expires_in": 31536000,
    "refresh_token": "rft.example…",
    "scope": "user.info.basic,video.list",
    "token_type": "Bearer"
}
```

---

## ⛔ O token vive 24 HORAS

*"It is valid for 24 hours after initial issuance."*

⚠️ **É o prazo mais curto de todas as redes do painel, por larga margem.** Um vídeo agendado para o
dia seguinte encontraria um token morto. Renovar não é rotina de manutenção aqui — é parte do
caminho de publicar.

O `refresh_token` vale **365 dias**.

## ⛔ E o `refresh_token` GIRA

```http
POST https://open.tiktokapis.com/v2/oauth/token/

client_key=…&client_secret=…&grant_type=refresh_token&refresh_token=…
```

> *"The returned `refresh_token` may be different than the one passed in the payload."*

⛔ **Guardar o novo é obrigatório.** Renovar e continuar guardando o antigo é a receita de uma conexão
que funciona hoje, funciona amanhã e um dia para de funcionar sem ninguém ter mexido em nada.

---

## Revogar

```http
POST https://open.tiktokapis.com/v2/oauth/revoke/
client_key=…&client_secret=…&token={access_token}
```

Resposta vazia quando dá certo.

---

## O identificador da conta

`open_id` é o que identifica a conta para este aplicativo.

⚠️ Ele é **por aplicativo**: o mesmo criador tem `open_id` diferente em outro app. Não é um
identificador público do TikTok, e não serve para montar endereço de perfil.

---

## Erro

```json
{
    "error": "invalid_request",
    "error_description": "Redirect_uri is not matched with the uri when requesting code.",
    "log_id": "20220622185437…"
}
```
