# Plano — X

> Escrito depois de ler a documentação oficial inteira (cópia local em
> [`../planos-de-redes/x/documentacao/`](../planos-de-redes/x/documentacao)) e de registrar os
> achados em [`../planos-de-redes/x/achados.md`](../planos-de-redes/x/achados.md).

---

## ⛔ Leia isto antes do resto

**Esta é a única rede do painel em que publicar custa dinheiro — e em que uma escolha de texto muda o
custo em treze vezes.**

| Operação | Preço |
|---|---|
| Post: criar | US$ 0,015 |
| ⛔ **Post: criar (com URL)** | **US$ 0,200** |
| ⭐ Post: ler o que é seu (a prova) | US$ 0,001 |

Em 500 posts por mês: **US$ 7,50 sem link, US$ 100,00 com link em todos.**

⚠️ Não existe faixa gratuita. Os créditos são comprados antes, no console deles.

---

## DEC-126 — O painel avisa o custo do link ANTES de publicar, não depois

⛔ Descobrir na fatura que o mês custou treze vezes mais é o tipo de surpresa que faz alguém parar de
confiar na ferramenta — e o painel **sabe ler o texto antes de enviar**.

⭐ Quando a legenda tem link e o X está entre as redes escolhidas, a tela diz, com o número na frente:
*"tem link — no X isso custa US$ 0,20 em vez de US$ 0,015 por publicação"*.

⛔ **Aviso, não bloqueio.** Pode ser exatamente o que a pessoa quer; quem decide gastar é ela. O que
não pode é ela não saber.

⚠️ E o aviso é da **tela**, não do publicador: no momento em que o publicador roda, o gasto já
aconteceu.

---

## DEC-127 — A conciliação do X tem teto próprio, porque insistir aqui tem preço

⭐ Reler o post custa US$ 0,001 (*owned read*), e a conciliação roda até vinte vezes: US$ 0,02 no
pior caso, por publicação.

⚠️ Barato, mas é a primeira rede em que **perguntar de novo gasta crédito de alguém** em vez de só
gastar limite de uso. O teto que já existe (vinte consultas) passa a ter razão dupla — e fica
registrado que ele é, aqui, uma decisão de custo.

---

## DEC-128 — A troca do código é a PRIMEIRA coisa da volta

O código de autorização vive **30 segundos** — uma ordem de grandeza abaixo de qualquer outra rede.

⛔ Ler perfil, conferir grupo ou gravar no banco antes da troca pode consumir a janela inteira. E o
erro que aparece é o genérico *"a autorização não pôde ser confirmada"*, que manda a pessoa procurar
no lugar errado — o mesmo filme que a Meta já nos custou.

---

## DEC-129 — PKCE: o `code_verifier` vive na sessão, junto com o `state`

Primeira rede do painel com PKCE obrigatório. O segredo nasce na ida e é exigido na volta; sem ele a
troca falha **sem recuperação possível**.

⚠️ As outras redes guardam só o `state`. Aqui são dois, e esquecer o segundo só aparece na hora de
conectar de verdade.

---

## DEC-130 — Token de 2 horas: renova na hora de usar

Mais curto que o do TikTok. Mesma resposta da DEC-118: o publicador renova antes de publicar quando
falta pouco, e o comando diário é rede de segurança.

⛔ **`offline.access` é obrigatório no pedido de autorização.** Sem ele não existe token de renovação,
e a conexão morre em duas horas sem nada ter mudado.

---

## DEC-131 — `media.write` entra na lista de escopos, e a conexão confere

⚠️ O sintoma de esquecer engana: a conta conecta, o texto subiria, **e o vídeo não**. A conferência
de escopo concedido — que já existe nas outras redes — passa a exigir os dois: `tweet.write` e
`media.write`.

---

## DEC-132 — Os limites do arquivo NÃO são inventados

A documentação não declara tamanho, duração, proporção, taxa de quadros, codecs, quantidade de mídias
por post nem limite de caracteres do texto.

⛔ O painel aplica o limite do **perfil canônico do produto** (doc 07 §6), e a recusa da rede — quando
vier — ganha frase própria. Escrever um número que ninguém verificou faria o laudo perder exatamente
o que o torna útil.

---

## DEC-133 — Os preços moram no servidor; a tela só decide QUANDO mostrar

⛔ O aviso nasceu com os números escritos duas vezes: uma em PHP, outra em TypeScript. No dia em que
o X mudar a tabela, uma das cópias fica errada — **e é a errada que a pessoa vai ler.**

⭐ A frase vem pronta do servidor, junto com os limites de cada rede. A tela decide **quando**
mostrá-la, porque é ela que vê a pessoa digitando; o servidor decide **o que** dizer.

⚠️ O teste de "tem link?" continua existindo dos dois lados — é inerente a avisar durante a digitação.
O que não podia continuar duplicado era o preço.

---

## As fases

### Fase 1 — Conexão

- [x] **1.1** `ConexaoComX`: OAuth 2.0 com PKCE (`S256`), escopos separados por espaço
- [x] **1.2** `code_verifier` na sessão junto com o `state` (DEC-129)
- [x] **1.3** A troca do código é a primeira coisa da volta (DEC-128)
- [x] **1.4** Escopos **concedidos** conferidos — `tweet.write` **e** `media.write` (DEC-131)
- [x] **1.5** `CanalDeUmGrupoSo` — a mesma trava das outras redes
- [x] **1.6** Guardiões da conexão

### Fase 2 — Renovação

- [x] **2.1** `TokenDoX`: renova na hora de usar (DEC-130)
- [x] **2.2** Comando diário como rede de segurança
- [x] **2.3** Guardiões da renovação

### Fase 3 — Publicação

- [x] **3.1** `PublicadorX`: INIT → APPEND por `segment_index` → FINALIZE
- [x] **3.2** `media_id` no `handle_externo`, antes do primeiro byte — e relido antes de recomeçar
- [x] **3.3** `STATUS` até `succeeded`
      ⚠️ **Sem ler o `check_after_secs`**: a conciliação usa a espera progressiva dela (30 s, 1 min,
      2 min…), que é sempre **maior** que o que a rede pede (5 a 10 s). Nunca perguntamos cedo demais
      — perguntamos tarde. Ler o campo tornaria a primeira conferência mais rápida, e fica anotado
      como melhoria, não como feito.
- [x] **3.4** O post só nasce depois do `succeeded`
- [x] **3.5** `conciliar()`: reler o post é a prova (DEC-31), com teto de custo (DEC-127)
- [x] **3.6** Guardiões da publicação

### Fase 4 — O custo na tela

- [x] **4.1** Aviso de link com o número na frente (DEC-126)
- [x] **4.2** Guardião: legenda com link + X escolhido = aviso; sem link ou sem X = silêncio

**Pronto:** 43 guardiões verdes — 16 da conexão e do token, 20 da publicação, 7 do aviso de custo.
**Falta a prova de campo:** nenhum post saiu no X de verdade, e o aplicativo no console ainda não
existe.

---

## O que falta do lado do X

Nada disso é código — é cadastro e **dinheiro**, e só Gabriel pode fazer.

1. **Criar o aplicativo** em `console.x.com`.
2. **Ligar o OAuth 2.0** com tipo *Confidential client*, e marcar os cinco escopos:
   `tweet.read`, `tweet.write`, `users.read`, `media.write`, `offline.access`.
   ⚠️ Faltar `media.write` dá conta que conecta e não sobe vídeo; faltar `offline.access` dá conexão
   que morre em duas horas.
3. **Cadastrar o endereço de retorno:** `{APP_URL}/conexoes/x/retorno` (correspondência exata).
4. **Comprar créditos** e **definir o limite de gasto** no console — sem crédito, a publicação falha
   com `402`, e o painel já sabe dizer que o que faltou foi dinheiro, não qualidade de vídeo.
5. **Preencher no `.env`:** `X_CLIENT_ID` e `X_CLIENT_SECRET`.

⚠️ **Antes de revender:** US$ 0,015 por post e **US$ 0,200 com link**. Cinco grupos publicando duas
vezes por dia com link na legenda dão **US$ 60 por mês só de X**. O aviso da tela ajuda quem escreve;
a conta do plano é outra conversa.

---

## ⛔ O que fica de fora, de propósito

**Enquete, resposta, citação e comunidade.** O produto publica um vídeo vertical por vez.

**Apagar post.** `tweet.write` permite, mas apagar publicação alheia por engano não tem desfazer.

**Ler post de terceiro** (US$ 0,005). O painel só relê o que ele mesmo publicou — que além de ser a
promessa, é o preço de *owned read*.
