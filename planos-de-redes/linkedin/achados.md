# LinkedIn — achados da documentação oficial

> Lido antes de escrever qualquer linha, como manda a regra. E o primeiro achado é o mais caro de
> todos: **esta rede não deixa a gente cumprir a promessa central do produto.**

---

## ⛔ L-1 — No nível aberto, o LinkedIn é uma rede de ESCRITA APENAS

A página *Getting Access* é literal: *"Open Permissions are the only permissions that are available
to all developers without special approval."* A lista inteira tem **três** permissões:

| Produto | Permissão | O que dá |
|---|---|---|
| Sign in with LinkedIn using OpenID Connect | `profile` | nome, título, foto |
| Sign in with LinkedIn using OpenID Connect | `email` | e-mail |
| Share on LinkedIn | `w_member_social` | **publicar** |

Não há permissão de leitura de post na lista. `r_member_social` é *restricted — available to approved
users only*. As permissões de organização (`w_organization_social`, `r_organization_social`) são do
programa de Marketing e exigem aprovação.

### ⛔ O que isso quebra

A tese do produto é a DEC-31: **HTTP 200 não é publicado.** O painel só diz "no ar" depois de reler
o post na rede. No LinkedIn, sem aprovação, **não existe reler**.

### ⭐ O que ainda dá para provar — e é mais do que parece

O buraco é menor que "nenhuma prova", e vale medir com precisão:

| Etapa | Dá para conferir? | Como |
|---|---|---|
| O vídeo chegou e foi aceito | **Sim** | `GET /rest/videos/{urn}` → `status` |
| O vídeo falhou no processamento | **Sim** | `PROCESSING_FAILED` + `processingFailureReason` |
| O post foi criado | **Sim** | `201` + URN no cabeçalho `x-restli-id` |
| O post continua no ar depois | **Não** | precisa de `r_member_social` |

⚠️ **A falha assíncrona clássica — o vídeo que a rede recusa depois de aceitar o envio — é
detectável.** É o `status` do vídeo, e ele é lido com a permissão de escrita.

⛔ **O que fica fora é a remoção por moderação depois de publicado.** Nas outras redes o painel pega
isso relendo o post; aqui, não.

### A decisão que isso obriga

O painel **não pode dizer "conferido no ar"** para o LinkedIn com a mesma cara com que diz para o
YouTube. Ou o estado é outro, ou a frase é outra — mentir sobre o grau de certeza é exatamente o que
o produto existe para não fazer.

⛔ **Raspar a página pública do post está fora de questão** — é violação dos termos de uso da API, e
o produto não se sustenta em cima disso.

---

## ⛔ L-2 — Não existe renovação de token em segundo plano

*"Programmatic refresh tokens are available for a limited set of partners."*

O token vive **60 dias**. Renovar é mandar a pessoa pelo endereço de autorização de novo — silencioso
**se** ela ainda estiver logada no LinkedIn **e** o token ainda não tiver vencido; fora disso, é a
tela inteira.

⚠️ Todas as outras redes do painel renovam sozinhas de madrugada. **O LinkedIn não.** O comando que
serve aqui não é um renovador, é um **avisador**: ele precisa alertar com folga, e o semáforo de
conexão já existe para isso.

---

## ⛔ L-3 — O identificador do post vem no CABEÇALHO, e o corpo vem vazio

`POST /rest/posts` responde `201` com o corpo vazio e o URN em `x-restli-id`.

⚠️ Um publicador que procure o id no JSON acha `null`, conclui que falhou — e o post está publicado.
Na tentativa seguinte, publica de novo. **Publicação não tem desfazer.**

---

## ⛔ L-4 — A documentação se contradiz em dois números

**Tamanho do arquivo:** a seção de especificação diz **500 MB**; a descrição do campo
`fileSizeBytes`, na mesma página, diz **5 GB**. Vale o menor — recusar em 500 MB é seguro nas duas
leituras; aceitar 5 GB pode estourar no meio do envio, com o arquivo já subindo.

**Tamanho da parte:** a instrução manda `split -b 4194303`, e o intervalo devolvido pela própria API
é `firstByte: 0, lastByte: 4194303` — que **inclusive** dá 4.194.304 bytes. Seguir o exemplo deixa
cada parte um byte curta, e o erro só aparece em arquivo grande, com o vídeo montado errado no fim.
**A fonte da verdade é o par de bytes que a API devolve.**

---

## ⚠️ L-5 — 150 requisições por dia não são 150 posts

O limite por membro é **150 requisições por dia**, e uma publicação nossa gasta várias:
1 inicializar + N partes + 1 finalizar + 1 conferir + 1 postar. Um vídeo de 40 MB tem 10 partes —
**14 requisições**. O teto real fica perto de **10 publicações por dia por pessoa**.

⚠️ E cada conferência de vídeo também conta. Conciliar de minuto em minuto queimaria a cota da pessoa
sem publicar nada.

---

## ⭐ L-6 — Aqui a mídia sobe de verdade, em pedaços, com recibo

Depois de duas redes que vêm **buscar** o arquivo (Threads, Instagram), o LinkedIn volta ao envio
direto — e em partes de 4 MB, cada uma devolvendo um `ETag` que precisa ser guardado **na ordem**.

⭐ Isso torna o `handle_externo` obrigatório desde a primeira chamada: o URN do vídeo nasce **antes**
de qualquer byte subir. É o mesmo desenho da retomada do YouTube, e a peça `Retomada` já existe.

⚠️ Diferença importante: no YouTube o endereço de retomada retoma **o envio**. Aqui o URN identifica
**o recurso**, e as partes já enviadas continuam válidas. Reenviar tudo não duplica o vídeo — mas
gasta cota, que é escassa (L-5).
