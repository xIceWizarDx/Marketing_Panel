# Plano — Mastodon

> Escrito depois de ler a documentação oficial (`docs.joinmastodon.org`, métodos `apps`, `media` e
> `statuses`) em **2026-08-09**.

---

## ⛔ Leia isto antes do resto

**O Mastodon não é um serviço: é um protocolo.** Não existe "o Mastodon" para integrar — existem
milhares de servidores independentes, cada um com endereço, regras, limites e **aplicativo próprios**.

⭐ **E por isso mesmo ele é a rede de barreira mais baixa de todas:** o protocolo permite registrar o
aplicativo **por API, sem autenticação**. É a única rede do painel que conecta sem ninguém precisar
criar conta de desenvolvedor em portal nenhum.

---

## DEC-138 — A pessoa diz ONDE a conta mora, e é uma terceira forma de conectar

O painel tinha duas formas: autorizar no site da rede, ou senha de aplicativo (Bluesky). Esta é a
terceira: **informa o servidor, depois autoriza lá**.

⛔ Quem responde qual é a forma continua sendo `Plataforma::formaDeConexao()`, não a tela. Foi
justamente uma decisão de tela (`if rede === 'youtube'`) que fez o modal do Facebook pedir senha de
aplicativo do Bluesky.

⚠️ E o servidor vira **coluna** (`contas_sociais.servidor`), não texto derivado do nome de exibição:
montar endereço de API a partir de texto de tela quebra no dia em que o nome mudar.

---

## DEC-139 — O aplicativo é registrado na hora, e o segredo não é guardado

`POST /api/v1/apps` não exige autenticação: cada servidor emite um par de credenciais para o painel.

⭐ O par vive **só o tempo da autorização**, na sessão. Depois do token ele não serve para mais nada
— o token do Mastodon não vence — e guardar segredo sem uso é aumentar a superfície à toa.

---

## ⭐ DEC-140 — A primeira rede que aceita chave de idempotência

`Idempotency-Key` vale uma hora, e repetir com a mesma chave devolve **o mesmo post**.

⛔ Isso **inverte** a regra do LinkedIn (DEC-125), do X e do Pinterest: lá, um tempo esgotado depois
de a rede receber o pedido obriga a **parar e avisar**, porque repetir criaria um segundo post. Aqui,
repetir é seguro — e a chave é o `ulid` do destino: estável entre tentativas, único entre destinos.

---

## ⛔ 206 é sucesso, e não quer dizer pronto

`GET /api/v1/media/{id}` devolve **200** quando terminou e **206** enquanto processa. Os dois são
códigos de sucesso.

⚠️ Um motor que trate `successful()` como "pronto" publicaria um post **sem vídeo** — e o post
ficaria lá, vazio, sem erro nenhum para investigar.

---

## ⛔ Os limites são do SERVIDOR, não da rede

Um Mastodon aceita vídeo de 40 MB, o vizinho aceita 200 MB, e nenhum número é "do Mastodon".

⚠️ O painel aplica o perfil canônico do produto (doc 07 §6) — o mais conservador — e a recusa de
verdade vem do servidor, **com o nome dele na frase**. Dizer "o Mastodon recusou" mandaria a pessoa
procurar uma regra geral que não existe.

---

## As fases

- [x] **1** `ConexaoComMastodon`: registrar aplicativo → autorizar → token (DEC-138, DEC-139)
- [x] **2** Coluna `servidor` em `contas_sociais`, e o identificador carrega o endereço
- [x] **3** A terceira forma de conectar na tela, com o campo do servidor
- [x] **4** `PublicadorMastodon`: subir (202) → conferir (200 vs 206) → publicar com chave
- [x] **5** Guardiões

**Pronto:** 11 guardiões verdes.
**Falta a prova de campo:** nenhum post saiu de verdade.

⭐ **E aqui não falta cadastro nenhum:** basta a pessoa ter conta em qualquer servidor Mastodon. É a
única rede do painel que pode ser testada hoje, sem esperar aprovação de ninguém.

---

## ⛔ O que fica de fora

**`read` inteiro.** Daria linha do tempo, notificações e mensagens diretas. Para publicar um vídeo,
`write:statuses`, `write:media` e `read:accounts` bastam.

**Visibilidade que não seja pública.** Publicar sem alcance é publicar onde ninguém vê.
