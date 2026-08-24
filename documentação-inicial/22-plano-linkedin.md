# Plano — LinkedIn

> Escrito depois de ler a documentação oficial inteira (cópia local em
> [`../planos-de-redes/linkedin/documentacao/`](../planos-de-redes/linkedin/documentacao)) e de
> registrar os achados em [`../planos-de-redes/linkedin/achados.md`](../planos-de-redes/linkedin/achados.md).

---

## ⛔ Leia isto antes do resto

**O LinkedIn não deixa o painel cumprir a promessa central do produto, e isso não é contornável com
código.**

A tese é a DEC-31: *HTTP 200 não é publicado* — o painel só diz "no ar" depois de **reler** o post na
rede. No LinkedIn, reler um post exige `r_member_social`, que é **restrita, só para aprovados**. As
únicas permissões abertas a qualquer desenvolvedor são `profile`, `email` e `w_member_social`.

O buraco, medido com precisão:

| Etapa | Dá para conferir? |
|---|---|
| O vídeo chegou e foi aceito | **Sim** — `GET /rest/videos/{urn}` → `status` |
| O vídeo falhou no processamento | **Sim** — `PROCESSING_FAILED` + motivo |
| O post foi criado | **Sim** — `201` + URN em `x-restli-id` |
| O post continua no ar depois | **Não** |

⭐ **A falha assíncrona clássica é detectável** — a que o produto existe para pegar, o vídeo que a rede
aceita e depois recusa, aparece no `status` do vídeo, lido com a permissão de escrita.

⛔ **O que fica de fora é a remoção por moderação depois de publicado.**

---

## DEC-106 — O LinkedIn tem um grau de certeza PRÓPRIO, e o painel diz qual é

⛔ **Não vai existir "conferido no ar" para o LinkedIn.** Reusar a mesma frase do YouTube seria dizer
que houve uma conferência que não houve — e mentir sobre o grau de certeza é exatamente o defeito que
o produto critica nos concorrentes.

O destino chega em **publicado**, com link — e a frase diz o que foi feito: o vídeo foi aceito pela
rede, o post foi criado e este é o endereço dele. Sem "conferido", sem "confirmado", sem "no ar".

⚠️ E o motivo fica **visível na tela**, não escondido num comentário: quem publica precisa saber que
neste canal a conferência não acontece, para ir olhar quando importar.

---

## DEC-107 — A prova possível é o `status` do vídeo, e ela acontece ANTES do post

A ordem não é escolha de estilo: criar o post antes de o vídeo ficar `AVAILABLE` devolve
`MEDIA_ASSET_WAITING_UPLOAD` ou `MEDIA_ASSET_PROCESSING_FAILED`. O passo de conferir o vídeo é
obrigatório de qualquer jeito — e é ele que carrega a prova que dá para ter.

```
publicar()   → inicializa, sobe as partes, finaliza. Guarda o URN do vídeo. NÃO cria o post.
conciliar()  → lê o status do vídeo:
               AVAILABLE         → cria o post → guarda o URN do post → publicado, com link
               PROCESSING        → ainda processando
               PROCESSING_FAILED → falha, com o motivo que a rede deu
               WAITING_UPLOAD    → ainda processando
```

⭐ Mesmo desenho do Threads (DEC-103), e pelo mesmo motivo: esperar dormindo seguraria um worker.

---

## DEC-108 — O URN do vídeo é o `handle_externo`, guardado antes do primeiro byte

`initializeUpload` devolve o URN **antes** de qualquer byte subir. Guardar ali, via `Retomada`, é o
que impede um job reentregue de criar um segundo vídeo — e o `Retomada` já existe para isso, escrito
para o envio em pedaços do YouTube.

⚠️ Aqui ele guarda **duas** coisas, e é a primeira vez que isso acontece: o URN do vídeo e o
`uploadToken`. Vão juntos no mesmo campo, separados, porque o passo de finalizar exige os dois.

---

## DEC-109 — Os pedaços saem do `firstByte`/`lastByte` da API, NUNCA do exemplo da documentação

A documentação manda `split -b 4194303` e devolve o intervalo `0`–`4194303`, que **inclusive** dá
4.194.304 bytes. Os dois não fecham.

⛔ Seguir o exemplo deixaria cada parte um byte curta, e o erro só apareceria em arquivo grande — com
o vídeo montado errado no fim, depois de tudo responder sucesso.

⭐ **O código lê o intervalo que a API mandou.** Se um dia a LinkedIn mudar o tamanho da parte, nada
aqui precisa mudar.

---

## DEC-110 — Os `ETag` das partes vão na ORDEM, e a ordem é a das instruções

Cada parte devolve um `ETag` no cabeçalho, e `finalizeUpload` recebe a lista. Fora de ordem, o vídeo
monta embaralhado — e nada na resposta avisa.

---

## DEC-111 — O URN do post vem do CABEÇALHO `x-restli-id`

O corpo do `201` vem **vazio**. Um publicador que procure o id no JSON acha `null`, conclui que
falhou, e na tentativa seguinte publica de novo — com o primeiro post já no ar.

⚠️ Aceita `urn:li:share:{id}` e `urn:li:ugcPost:{id}`. Os dois montam o mesmo endereço:
`https://www.linkedin.com/feed/update/{urn}/`.

---

## DEC-112 — A renovação do token é AVISO, não serviço

Sem ser parceiro aprovado não existe renovação em segundo plano. O token vive 60 dias e a renovação
passa pelo navegador da pessoa.

⛔ **Não existe `RenovarTokensDoLinkedin`.** Escrever um comando que "renova" e não renova seria pior
que não ter: a conexão morreria em silêncio com um serviço verde dizendo que está tudo bem.

⭐ O que existe é o semáforo de conexão, que já sabe mostrar prazo — e o `expira_em` do LinkedIn
significa **a data em que a pessoa vai precisar reconectar**, não a data de um trabalho nosso.

---

## DEC-113 — O limite é medido em REQUISIÇÕES, não em posts

150 requisições por membro por dia. Uma publicação nossa gasta 1 inicializar + N partes + 1 finalizar
+ 1 conferir + 1 postar — um vídeo de 40 MB são 14. O teto real é perto de **10 publicações por dia**.

⚠️ **Cada conciliação também conta.** Conferir de minuto em minuto queimaria a cota da pessoa sem
publicar nada.

⛔ `429` vira **espera** (DEC-24), nunca falha.

---

## DEC-114 — Sem campo de "é passageiro", a separação sai do código HTTP — e está escrita

Diferente da Meta, o LinkedIn não diz se o erro passa. A lista é curta e não deixa dúvida:

**Volta para a fila:** `409`, `429`, `500`, `503`, `MEDIA_ASSET_WAITING_UPLOAD`.
**Falha:** o resto dos `400`, `403 ACCESS_DENIED`, `404`.

⛔ **`401` não volta para a fila.** Aqui ele quer dizer token vencido, e como não existe renovação em
segundo plano, repetir só queima tentativa. Marca a conexão como vencida e pede para reconectar.

---

## DEC-125 — Criar post é irreversível: tempo esgotado NÃO se repete

⛔ É o oposto do que o resto do código faz com tempo esgotado, e é de propósito.

Criar post não é idempotente e o LinkedIn não aceita chave de repetição. Um tempo esgotado **depois**
de a rede ter recebido o pedido significa post publicado e resposta perdida — e a conciliação roda
até **vinte vezes**. Devolver "ainda processando" ali criaria um segundo post, um terceiro, um
quarto.

⛔ E não dá para conferir antes de criar: reler post exige `r_member_social`, que é restrita
(DEC-106).

⭐ Entre repetir e duplicar, ou parar e avisar, o produto **para e avisa**: *"O LinkedIn não
respondeu a tempo depois de receber o post. Confira no LinkedIn antes de publicar de novo: ele pode
ter subido."*

⚠️ Ler o status do vídeo continua repetindo normalmente — ali repetir não cria nada.

---

## As fases

### Fase 1 — Conexão

- [x] **1.1** `ConexaoComLinkedin`: autorização, troca do código, `state` na sessão
- [x] **1.2** Escopos **concedidos**, nunca os pedidos — o `scope` da resposta é a verdade
- [x] **1.3** URN da pessoa pelo `sub` do OpenID Connect
- [x] **1.4** `expira_em` = 60 dias, e ele significa **reconectar** (DEC-112)
- [x] **1.5** `CanalDeUmGrupoSo` — a mesma trava das outras redes
- [x] **1.6** Guardiões da conexão

### Fase 2 — Publicação

- [x] **2.1** `PublicadorLinkedin`: inicializar → partes → finalizar, sem criar o post (DEC-107)
- [x] **2.2** Pedaços pelo `firstByte`/`lastByte` da resposta (DEC-109)
- [x] **2.3** `ETag` na ordem das instruções (DEC-110)
- [x] **2.4** URN do vídeo + `uploadToken` no `handle_externo`, antes do primeiro byte (DEC-108)
- [x] **2.5** `conciliar()`: `AVAILABLE` → cria o post → URN do cabeçalho (DEC-111)
- [x] **2.6** `EspecificacaoDaRede`: MP4, 3 s a 30 min, 75 KB a 500 MB (o menor dos dois números)
- [x] **2.7** Erros traduzidos, com a separação da DEC-114
- [x] **2.8** `429` vira espera (DEC-113)
- [x] **2.9** Guardiões da publicação

### Fase 3 — Dizer a verdade na tela

- [x] **3.1** O grau de certeza próprio do LinkedIn (DEC-106) — estado, frase e o porquê visível
- [x] **3.2** Guardião: a tela **não** diz "conferido" para um destino do LinkedIn

**Pronto:** 34 guardiões verdes — 14 da conexão, 18 da publicação, 2 da frase da tela.
**Falta a prova de campo:** nenhum post saiu no LinkedIn de verdade ainda, e o aplicativo no portal
da LinkedIn ainda não existe. Ver *"O que falta do lado da LinkedIn"* abaixo.

---

## O que falta do lado da LinkedIn

Nada disso é código — é cadastro no portal, e só Gabriel pode fazer.

1. **Criar o aplicativo** em `linkedin.com/developers/apps`. Ele exige uma **Página de empresa** no
   LinkedIn para ser o dono do app — mesmo que a publicação seja no perfil pessoal.
2. **Adicionar os dois produtos**, na aba *Products*: *Sign In with LinkedIn using OpenID Connect* e
   *Share on LinkedIn*. Os dois são self-serve — **não passam por análise**.
3. **Cadastrar o endereço de retorno** na aba *Auth*: `{APP_URL}/conexoes/linkedin/retorno`.
   ⚠️ HTTPS e absoluto, sem `#`, e igual ao que o painel manda — diferente, a rede recusa.
4. **Preencher no `.env`:** `LINKEDIN_CLIENT_ID` e `LINKEDIN_CLIENT_SECRET`.

⚠️ **`LINKEDIN_VERSAO` envelhece.** O cabeçalho `LinkedIn-Version` usa uma data (`202607`), e a
própria documentação avisa que versões são aposentadas — a de julho de 2025 já foi. Uma versão morta
derruba a publicação inteira de uma vez, então ela mora no `.env` para ser trocada sem deploy.

---

## ⛔ O que fica de fora, de propósito

**Publicar em página de empresa.** Exige `w_organization_social`, que é do programa de Marketing e
depende de aprovação da LinkedIn. ⚠️ É o caminho que devolveria a prova completa — com
`r_organization_social`, dá para reler o post. Fica como pedido a fazer, não como código a escrever.

**Legendas e miniatura** (`uploadCaptions`, `uploadThumbnail`). O produto publica um vídeo vertical
por vez; os dois são outra conversa.

**Post com imagem, artigo, enquete, carrossel.** Não é o que o produto faz.

**Segmentar quem vê** (`targetEntities`). Exige público acima de 300 pessoas e API de anúncios.
