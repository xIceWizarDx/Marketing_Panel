# Revisão adversarial de produto — o que deveríamos entregar e não entregamos

_Feita em **2026-08-09**, com o escopo reduzido para **YouTube, Meta (Instagram e Facebook) e, no
máximo, TikTok**._

> ⛔ Este documento não lista desejos. Lista onde o produto **deixa a pessoa sozinha** — e começa
> pelo achado mais incômodo: três capacidades já foram construídas e **não chegam à tela**.

---

## O caminho de quem usa, e onde o painel não está

| Passo | O que a pessoa faz | O painel |
|---|---|---|
| 1 | **"O que eu posto?"** | ⛔ **ausente** |
| 2 | Grava e edita o vídeo | fora do escopo, e certo |
| 3 | Escreve título, legenda, hashtags | parcial — um texto só para três redes |
| 4 | **"Quando eu posto?"** | ⛔ **ausente** |
| 5 | Publica | ⭐ faz bem, e prova |
| 6 | **"Funcionou?"** | ⛔ só no YouTube |
| 7 | **"O que faço amanhã?"** | ⛔ **ausente** |

⚠️ **O painel cobre o passo 5 e metade do 6.** Os passos 1, 4 e 7 são justamente os que fazem alguém
abrir a ferramenta **todo dia** — e é disso que assinatura mensal vive.

---

## ⛔ 1. Três coisas já construídas que ninguém consegue usar

Isto é pior que funcionalidade faltando: é trabalho já pago, parado a um passo da entrega.

### O motor já sabe agendar. O produto não oferece.

`PublicadorFacebook` lê `opcoes['publicar_em']` e manda `video_state: SCHEDULED` com
`scheduled_publish_time`. O YouTube tem o mesmo caminho.

⛔ **Mas não existe campo de data na tela, nem coluna de agendamento, nem quem dispare na hora.** A
capacidade existe na ponta e não existe no meio.

⚠️ E sem agendar, o painel só é usado **no instante da publicação** — que é o pior instante, porque a
pessoa está com pressa. Quem grava cinco vídeos no domingo não tem o que fazer com eles aqui.

### O texto por rede existe no banco. Não existe na tela.

`destinos.titulo_override`, `legenda_override` e `hashtags_override` estão lá, com acessores prontos
(`Destino::titulo()`, `textoFinal()`), e **nenhuma linha de React os usa**.

⛔ **E isso é ativo, não teórico, nestas três redes:**

| Rede | O que ela quer |
|---|---|
| **YouTube Shorts** | **título** (aparece na tela), descrição e tags — três campos distintos |
| **Reels (IG/FB)** | uma legenda curta com hashtags |
| **TikTok** | legenda com hashtags, que são o mecanismo de descoberta |

⚠️ Hoje há **um** campo de título. Como o YouTube tem título próprio e as outras não, o título vai
para o campo certo no YouTube **e é colado na legenda** no TikTok e no Instagram. Quem escrever um
título pensado para o YouTube — *"Como fazer isso em 30 segundos"* — vê essa frase grudada no começo
da legenda do Reels.

⭐ A correção não é desfazer a colagem (ela está certa: sem ela o título sumia). É **deixar escrever
diferente por rede**, que é o que o banco já espera.

### O contrato de métricas existe. Só duas redes têm leitor.

`LeitorDeMetricas` está pronto, com comando diário e tela. Os leitores escritos são **YouTube** e
**Bluesky**.

⛔ Das três redes deste escopo, **duas não respondem "funcionou?"** — e o Bluesky, que responde, nem
está no escopo.

---

## ⛔ 2. A capa é sorteada

Nas três redes a capa decide o clique. O painel gera uma miniatura automática e **não deixa
escolher o quadro**.

⚠️ Já existe a peça: no Pinterest o publicador manda `cover_image_key_frame_time` — o segundo do
vídeo que vira capa. O YouTube e a Meta aceitam capa própria.

⭐ Um controle de "escolher o segundo da capa" é pequeno, aparece na prévia e muda resultado.

---

## ⛔ 3. Não há "quando" — e "quando" é o hábito

Sem agendamento não há: fila de horários, publicar em lote, respeitar o fuso de quem assiste, nem
espaçar dois vídeos para não competirem entre si.

⚠️ **É o que transforma a ferramenta de evento em rotina.** Quem usa Buffer não abre para publicar:
abre para **encher a fila**.

⭐ E aqui dá para fazer a versão honesta de "melhor horário", que todo mundo promete e quase ninguém
cumpre: **usar as métricas da própria conta** — *"seus posts das 19h tiveram mais visualização"* — e
**dizer quando ainda não há dados suficientes** em vez de inventar um número. É o mesmo princípio que
já governa o resto do produto.

---

## 4. A pauta — a ideia da tela de notícias, atacada e resgatada

**A intuição está certa e mira o passo 1**, que é o mais vazio de todos. Mas a versão ingênua morre.

### ⛔ Por que a versão ingênua morre

Uma lista de notícias solta é decoração: **ninguém abre uma ferramenta de publicação para ler
notícia**. Ela seria aberta duas vezes e esquecida.

⛔ E a versão que "funcionaria" — *o que está viralizando agora no TikTok/Reels* — **não existe em API
pública**. Prometer isso seria prometer o que a API não permite, que é a regra que o produto mais
defende.

### ⭐ A versão que se sustenta

Três exigências, e as três são obrigatórias juntas:

1. **Termina em ação.** Cada item tem *"criar publicação a partir desta pauta"*, que já abre o
   compositor com título e legenda começados. Sem esse botão, é um leitor de RSS.
2. **É do nicho da pessoa**, escolhido por ela — notícias de tecnologia, esporte, política local. Uma
   pauta genérica não serve a ninguém.
3. **É honesta na fonte.** Cada item mostra **de onde veio e de quando é**. Nada de "índice de
   viralização" inventado — é exatamente o tipo de número que o produto acusa os concorrentes de
   fabricar.

⚠️ **E o alcance dela é específico:** serve muito para quem faz conteúdo de **assunto do dia**
(notícia, esporte, mercado) e quase nada para quem faz humor ou receita. É funcionalidade de nicho —
o que é bom, desde que seja vendida como tal e não como "a IA que diz o que postar".

---

## 5. O que falta para a agência que presta contas

Se o público for o da [revisão de mercado](30-revisao-adversarial-de-mercado.md), duas coisas viram
produto:

- **Relatório exportável com as provas** — a lista de posts, com link conferido e data da
  conferência. ⭐ Para essa pessoa a prova **não é tranquilidade: é o entregável**.
- **Aprovação antes de publicar** — link de prévia para o cliente dela aprovar. Hoje ela faz isso por
  WhatsApp, na mão.

---

## A ordem, e o critério

O critério não é "o que é mais legal": é **o que falta para o produto ser um fluxo de trabalho em vez
de um botão**.

| # | Entrega | Por quê agora |
|---|---|---|
| 1 | **Agendamento + fila de horários** | sem "quando", não há hábito — e o motor já sabe |
| 2 | **Texto por rede** | o banco já espera; hoje o título do YouTube vaza para o Reels |
| 3 | **Escolher o quadro da capa** | pequeno, visível, muda resultado nas três |
| 4 | **Métricas de Meta e TikTok** | fecha o "funcionou?" nas redes do escopo |
| 5 | **Relatório de provas** | vira o entregável da agência |
| 6 | **Pauta com ação** | cria o motivo de abrir todo dia — na versão honesta |

⚠️ **O que fica de fora, de propósito:** sugerir legenda por IA, "índice de melhor horário" sem
dados, e qualquer número que a API não entregue. O produto vende não mentir — é a única coisa que
ele não pode comprar de volta.
