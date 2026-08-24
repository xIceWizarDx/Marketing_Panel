# Plano — métricas que valem alguma coisa, e a prova que continua

_Escrito em **2026-08-10**, com escopo em **YouTube, Instagram, Facebook e TikTok**._

> **A ideia em uma frase:** hoje o painel prova que publicou e para por aí. Este plano faz ele
> responder **"funcionou?"** e **"continua no ar?"** — as duas perguntas que vêm depois, e que hoje
> ficam sem resposta.

---

## O estado, sem enfeite

| | Hoje |
|---|---|
| Leitor de métrica | YouTube e Bluesky — **nenhuma das outras três do escopo** |
| Histórico | ⛔ **não existe** — o número é **sobrescrito** todo dia |
| Prova depois de publicado | ⛔ para em **3h30** e nunca mais confere |

⚠️ **A consequência dos três juntos:** o painel não consegue comparar redes (só uma responde), não
consegue mostrar evolução (não guarda ontem), e afirma "no ar" sobre um post que pode ter sido
removido há uma semana.

---

## ⛔ DEC-143 — Métrica custa permissão, e isso se pede explicando

As três redes exigem permissão **que hoje não pedimos**:

| Rede | Permissão nova | Para quê |
|---|---|---|
| Instagram | `instagram_manage_insights` | visualizações e compartilhamentos do Reels |
| Facebook | `read_insights` | `blue_reels_play_count` do reel da Página |
| TikTok | `video.list` | `view_count`, `like_count`, `comment_count`, `share_count` |

⚠️ Isso **contraria a primeira leitura** da DEC-41 (escopo mínimo) — e não contraria de verdade: o
mínimo é o mínimo **para o que o produto faz**. Se o produto passa a responder "funcionou?", a
permissão de leitura entra no mínimo.

⛔ **O que não muda:** continuamos sem pedir permissão de **apagar** ou **alterar**. A conta de quem
usa segue intocável.

⭐ **E a recusa não quebra nada.** A conferência de escopo só exige os de publicar. Quem negar a
leitura continua publicando e provando — só não vê número. A tela diz isso, em vez de o número
aparecer zerado.

⚠️ **Momento certo é agora:** nenhuma conta de produção está conectada. Mudar escopo depois obriga
todo mundo a reconectar.

---

## ⛔ DEC-144 — O número passa a ter ontem

Hoje `AtualizarMetricas` faz `forceFill` nas colunas do destino: **sobrescreve**. Sabemos quanto tem;
nunca soubemos quanto tinha.

⭐ Entra uma tabela de **leitura diária** — uma linha por destino por dia. Ela habilita:

- a **curva de vida** do post (Shorts acumula por semanas, Reels morre em 48h);
- responder *"ainda está subindo?"*, que é o que decide repostar;
- e o gráfico de evolução, que hoje é impossível.

⚠️ **Ela vale a pena mesmo sem tela.** Só começa a ter valor depois de coletar — quanto antes
guardar, antes existe. Criar agora e desenhar depois é a ordem certa.

⛔ E ela **não substitui** as colunas do destino: elas continuam sendo o "agora", lido pela tela sem
varrer histórico.

---

## ⭐ DEC-145 — A prova deixa de expirar em 3h30

A conciliação pergunta 20 vezes e para. Moderação de rede não trabalha nesse relógio: um vídeo
derrubado no dia seguinte continua marcado como "No ar".

⭐ Entra a **reconferência periódica**: um comando que relê o que está **publicado** e rebaixa o que
sumiu, guardando **quando** sumiu.

⚠️ **Isso não é métrica — é a promessa central do produto.** Ela aparece aqui porque a peça é a
mesma: reler o post. Fazer as duas na mesma passada custa uma chamada em vez de duas.

⛔ **E ela tem que ser barata**: relê o que foi publicado nos últimos 30 dias, uma vez por dia, e
nunca o acervo inteiro. No X, cada releitura custa US$ 0,001 — barato, mas não de graça.

---

## ⛔ DEC-146 — O total geral aparece, com três ressalvas

Somar visualização de redes diferentes é somar unidades diferentes. **Mas esconder o total também é
errado:** a pessoa quer sentir o tamanho, e a soma responde bem a *"estou crescendo?"* — a impureza é
a mesma mês a mês, então a direção fica certa.

⭐ Aparece, com três cuidados que não são negociáveis:

1. **É rotulado como soma bruta**, com uma linha dizendo que cada rede conta do seu jeito;
2. **Diz de quantas redes veio** — *"somando 3 de 4"*. Sem isso, uma rede que não respondeu vira
   queda de desempenho que não aconteceu;
3. **Nunca é usado para comparar redes.** A comparação fica ao lado, cada rede com o número dela.

⛔ A regra que decide: **o total serve para sentir o tamanho; o detalhe serve para decidir.**

---

## ⭐ DEC-147 — A comparação é do MESMO vídeo, e é o que ninguém mais tem

Publicamos o mesmo arquivo em N redes a partir de um lugar só. O vídeo é a variável de controle.

*"Esse corte foi 3× melhor no TikTok que no Reels"* não é número somado — é comparação com tudo o
mais igual. É acionável, e **nenhum concorrente pode fazer**, porque nenhum publica o mesmo arquivo
com a mesma origem e relê os dois.

---

## ⭐ DEC-148 — `Publicado` deixou de ser definitivo, e ganhou uma saída só

A máquina de estados dizia `Publicado => []`: uma vez no ar, para sempre no ar. **A reconferência
esbarrou nisso na primeira execução** — e o erro foi do modelo, não do teste.

⛔ As duas saídas óbvias mentem:

| Deixar como `Publicado` | diz que continua no ar o que a rede removeu — o defeito que o produto acusa nos concorrentes |
| Marcar `Falhou` | diz que **nunca subiu** o que subiu e foi tirado — e manda a pessoa publicar de novo sem entender |

⭐ Entra **`Removido` — "Saiu do ar"**. É a única saída de `Publicado`, é terminal e não volta:
republicar é outra publicação, com outra data e outra prova.

⚠️ Na contagem ele entra em "não subiram", porque é o balde do que **precisa de atenção**. Quem
diz qual dos dois casos é, é a frase do cartão — não o número.

---

## As fases

### Fase 1 — As três redes passam a responder

- [x] **1.1** Escopos novos (DEC-143), com a explicação na tela de conexão
- [x] **1.2** `PublicadorInstagram` implementa `LeitorDeMetricas` — `views`, `likes`, `comments`, `shares`
- [x] **1.3** `PublicadorFacebook` idem — métrica de **reel** via `video_insights` (corrigido na auditoria de 2026-08-11, ver [33](33-auditoria-meta.md))
- [x] **1.4** `PublicadorTiktok` idem — `/v2/video/query/`, até 20 ids por chamada
- [x] **1.5** ⛔ Métrica que a rede não publica continua `null`, nunca `0` (DEC-95)
- [x] **1.6** Guardiões dos três

### Fase 2 — O número ganha ontem

- [x] **2.1** Tabela de leitura diária (DEC-144)
- [x] **2.2** `AtualizarMetricas` grava a linha do dia **além** de atualizar o "agora"
- [x] **2.3** ⚠️ Uma linha por destino por dia — reexecutar no mesmo dia atualiza, não duplica
- [x] **2.4** Guardiões

### Fase 3 — A prova continua

- [x] **3.1** Comando de reconferência dos publicados (DEC-145)
- [x] **3.2** Post que sumiu é rebaixado, com a data
- [x] **3.3** ⛔ Só os últimos 30 dias, uma vez por dia
- [x] **3.4** Guardiões

### Fase 4 — A tela

- [x] **4.1** Total geral com as três ressalvas (DEC-146)
- [x] **4.2** Comparação do mesmo vídeo entre redes (DEC-147)
- [x] **4.3** Guardiões

**As quatro fases: prontas**, com 23 guardiões novos (641 no total).

⚠️ **A Fase 2 começa a valer sozinha:** cada execução do comando diário grava uma linha por destino.
A curva de vida existe daqui a uma semana **sem ninguém fazer nada** — e é por isso que ela foi
construída antes da tela.

---

## ⭐ DEC-149 — A comparação honesta é de cada rede contra ELA MESMA

A DEC-147 dizia "compare o mesmo vídeo entre redes", e a DEC-146 dizia "nunca compare redes pela
soma". **As duas estavam certas, e juntas não fechavam:** mostrar 900 do YouTube ao lado de 900 do
TikTok é comparar réguas diferentes, mesmo com o vídeo igual dos dois lados.

⭐ O que fecha as duas: comparar cada post com **a média dos seus posts naquela rede**. É a mesma
régua dos dois lados, e responde a pergunta que interessa — *"esse corte foi acima da minha média no
TikTok e abaixo no Reels"*.

⚠️ **Com base, ou calado.** Menos de três posts na rede e a frase não aparece: com dois, qualquer um
está "acima" ou "abaixo" por acaso. E existe uma faixa de 15% chamada **"na média"** — sem ela, um
post 2% acima viraria "acima da média", e a palavra perderia o sentido para quem confia nela.

---

## ⛔ O que fica de fora, de propósito

**Somar para comparar redes.** O total existe para sentir tamanho. Comparar é ao lado, por rede.

**Melhor horário.** Depende de volume que ninguém tem no começo — entra quando houver base, com a
frase honesta enquanto não houver.

**Pauta e tendências.** É outra frente (doc 31), e não depende desta.
