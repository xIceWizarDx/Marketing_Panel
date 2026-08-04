# Como configurar o YouTube para testar

> O código está pronto. O que falta é a **credencial do Google Cloud** — e ela só pode ser
> criada por você, porque é a sua conta Google.
>
> Leva uns 10 minutos. **Não precisa de auditoria nem de aprovação para testar.**

---

## ⚠️ Leia isto antes: a conexão vai cair sozinha a cada 7 dias

Não é defeito. Enquanto a tela de permissão do Google estiver em **"Testes"**, o Google
**encerra a autorização a cada 7 dias** — a conexão simplesmente para, sem nada ter mudado
aqui. É regra dele, está escrito na documentação oficial.

**Basta reconectar o canal na tela de Conexões.** O sistema já explica isso com essas palavras
quando acontece, para você não perder tempo procurando erro onde não tem.

Isso acaba quando a tela de permissão sair de "Testes" — o que só vale a pena depois da
auditoria.

---

## ⚠️ O endereço de retorno — **já está resolvido**

O Google exige que o endereço de retorno **bata caractere por caractere** com o cadastrado. Um
`/` a mais, `localhost` no lugar de `127.0.0.1`, porta diferente — e ele recusa com
`redirect_uri_mismatch`, uma mensagem que não ajuda em nada.

O `.env` já foi alinhado, e existe um teste que quebra se isso voltar a divergir. O endereço é:

```
http://localhost:8000/conexoes/youtube/retorno
```

**Só suba o servidor neste mesmo endereço:**

```bash
php artisan serve --host=localhost --port=8000
```

---

## Os passos no Google Cloud

**1. Criar o projeto** — <https://console.cloud.google.com/projectcreate>
Nome livre; é interno, ninguém vê.

**2. Ligar a API** — em *APIs e serviços → Biblioteca*, procurar **YouTube Data API v3** e
clicar em **Ativar**.

**3. Tela de consentimento** — em *APIs e serviços → Tela de permissão OAuth*:
- Tipo: **Externo**
- Preencher nome do app, seu e-mail de suporte e o e-mail do desenvolvedor
- **Deixar em "Testes"** — não clique em publicar. Em modo de teste, funciona **sem
  auditoria** para até 100 contas.
- Em **Usuários de teste**, adicione **a sua conta Google** *(sem isso, o Google recusa o login
  com "app não verificado")*

**4. Credencial** — em *APIs e serviços → Credenciais → Criar credenciais → ID do cliente
OAuth*:
- Tipo: **Aplicativo da Web**
- Em **URIs de redirecionamento autorizados**, colar **exatamente**:
  ```
  http://localhost:8000/conexoes/youtube/retorno
  ```
- Copiar o **ID do cliente** e a **Chave secreta**

**5. Colar no `.env`:**

```dotenv
GOOGLE_CLIENT_ID=...apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-...
```

> `GOOGLE_EM_TESTES=true` já está no `.env`. É ele que faz o sistema explicar a queda de 7 dias
> em vez de dizer só "reconecte". Mude para `false` quando a tela de permissão sair de "Testes".

**⚠️ Na hora de autorizar, mantenha as duas permissões marcadas.** O Google deixa desmarcar; sem
a de **enviar vídeos** não há como publicar, e o sistema recusa a conexão na hora em vez de
deixar você descobrir no primeiro vídeo.

**6. Limpar o cache e subir:**

```bash
php artisan config:clear
php artisan serve --host=localhost --port=8000
npm run dev          # 2º terminal
php artisan queue:work   # 3º terminal — o motor é assíncrono
```

Pronto: o cartão do YouTube deixa de dizer "Falta configurar" e passa a "Conectar".

---

## O que esperar no primeiro teste

**O vídeo vai subir privado.** É regra do YouTube para aplicativo ainda não auditado — vale
mesmo escolhendo "público", e a tela avisa isso antes de conectar.

**O que dá para conferir de verdade:**
- o vídeo aparece no seu canal, em *Conteúdo → seus vídeos*
- o destino chega a **"No ar"** com o link — passando por fila, envio em pedaços e conciliação
- se der problema, a mensagem vem **em português**, dizendo o que fazer

**Rode isto antes**, para confirmar que o servidor consegue analisar o vídeo:

```bash
php artisan midia:verificar
```

---

## Se der errado

| Mensagem | O que é |
|---|---|
| `redirect_uri_mismatch` | o servidor não subiu em `localhost:8000` |
| "app não verificado" / "acesso bloqueado" | falta adicionar sua conta em **Usuários de teste** |
| "Essa conta do Google não tem um canal" | a conta Google não tem canal criado — crie no YouTube |
| "O Google não devolveu a autorização de longo prazo" | remova o acesso em <https://myaccount.google.com/permissions> e conecte de novo |
| "A permissão de enviar vídeos não foi concedida" | ficou desmarcada na tela do Google — conecte de novo |
| "Confira se a YouTube Data API v3 está ativada" | faltou o **passo 2**, ou a cota do dia acabou |
| "A autorização do Google venceu… a cada 7 dias" | o modo de Testes expirando — só reconectar |
| Publicação parada em "Na fila" | o `queue:work` não está rodando |
| "Este servidor não consegue validar certificados" | falta o `cacert.pem` no `php.ini` — veja abaixo |

---

## ⚠️ Se NENHUMA rede responder: o pacote de certificados

Aconteceu no primeiro teste real e vale para qualquer máquina nova, inclusive o servidor.

O PHP no Windows vem **sem pacote de certificados**. Sem ele nenhuma chamada HTTPS funciona —
nem Google, nem Meta, nem Bluesky — e o erro que aparece é
`SSL certificate problem: unable to get local issuer certificate`.

Como resolver, uma vez só:

1. Baixe <https://curl.se/ca/cacert.pem>
2. Salve em `C:/tools/php82/cacert.pem`
3. No `php.ini`, tire o `;` do começo destas duas linhas e aponte para o arquivo:

```ini
curl.cainfo = "C:/tools/php82/cacert.pem"
openssl.cafile = "C:/tools/php82/cacert.pem"
```

4. Reinicie o `php artisan serve` **e** o `php artisan queue:work`

⚠️ Use **barras normais** (`/`). Com barra invertida, a sequência `\t` de `C:\tools` é lida como
tabulação e o PHP não acha o arquivo — o erro vira `error setting certificate file`.

---

## Depois do teste, para valer

Publicar **público** exige duas aprovações separadas, e **as duas pedem a integração
funcionando** (com vídeo de demonstração):

1. **Verificação do OAuth** — porque usamos escopo sensível
2. **Auditoria de compliance do projeto** — é ela que destrava o vídeo público

Por isso testar agora não é perda de tempo: **é como se produz a evidência que a auditoria vai
cobrar.**

_2026-07-31_
