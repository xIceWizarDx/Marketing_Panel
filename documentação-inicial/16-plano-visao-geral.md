# Plano de ação — a Visão geral passa a ver tudo

_Criado em 2026-08-05._

> **A ideia em uma frase:** a tela chamada *geral* mostrava um grupo só. Ela passa a somar todos, e
> os três quadrados viram a legenda de uma barra que responde o que nenhum concorrente responde:
> *"36 de 40 posts estão no ar"*.
>
> Regra de conclusão: **suíte verde, tipos e lint limpos, grep-zero, conferido no navegador**.

---

## Por que agora

A tela ficou crua, e não por acaso. Cada remoção estava certa sozinha — a lista de publicações
duplicava outra tela (DEC-68), os primeiros passos ensinavam o que a tela já mostrava (DEC-86), o
botão de conectar virou porta dupla (DEC-87). **A soma deixou uma tela que responde "como está"
com quase nada.**

E com grupo, ela ficou com um nome que mente: *Visão geral* mostrando um grupo só. Criar um
dashboard ao lado seria a terceira tela respondendo a mesma pergunta — e no dia em que os números
divergissem, nenhum dos dois seria confiável.

⭐ **A regra que governa o desenho:** esforço se mede por comprimento; **problema nunca**. A barra
carrega o volume, e o ponto de saúde tem o mesmo tamanho no grupo de 40 e no de 5.

---

## As decisões

**DEC-88 — a Visão geral soma TODOS os grupos, e por isso o total deixa de ser link.** Com mais de
um grupo, *"ver publicações"* abriria uma lista filtrada por um grupo só, que não bate com o número
mostrado. Mandar a pessoa para um recorte diferente do que ela clicou é onde o painel começa a
deixar de ser confiável.

⚠️ Isto muda o espírito da DEC-80: o aviso de saúde furava o filtro porque a tela não via tudo.
Agora ela vê — a exceção deixa de ser exceção.

**DEC-89 — aviso sobre conta de OUTRO grupo entra no grupo, em vez de abrir uma janela vazia.**
⛔ **Isto conserta um beco sem saída que existe hoje:** os avisos carregam contas de todos os
grupos, mas a grade de redes é filtrada pelo grupo em foco. Clicar *"Resolver"* num aviso sobre
conta de outro grupo abre a janela daquela rede com **zero contas dentro** e a frase "0 posts
confirmados no ar nesta rede". A ação passa a ser **"Entrar em «Novelas»"**; depois de entrar, o
mesmo aviso reaparece com "Resolver" e a conta está lá.

**DEC-90 — na Visão geral a unidade é o POST, não a publicação.** Publicação é o vídeo que você
mandou; ela vira **um post por canal**. ⛔ Hoje o aviso conta **destinos** e escreve *"3 publicações
não subiram"* — está errado, e a aba de Publicações mostra outro número. O aviso passa a dizer *"3
posts não subiram"*.

**DEC-91 — o grupo em foco só muda por gesto rotulado com verbo.** Gráfico não troca modo, e
segmento de barra não é botão: 10px de altura é um vinte e quatro avos do alvo mínimo de toque, e
no celular não existe `hover` para avisar antes. A porta é sempre um botão que diz *"Entrar neste
grupo"*.

**DEC-92 — o gráfico mora atrás de um contrato, e o contrato não sabe o que é grupo.** Ele recebe
número absoluto, rótulo, cor já resolvida e a medida compartilhada; não formata texto, não navega,
não guarda estado, não escolhe cor. ⭐ É isso que permite trocar CSS puro por ApacheECharts depois
**sem tocar na tela** — e é isso que impede o gráfico de virar dono da regra de negócio.

---

## O que a tela mostra

**Como está** — os três quadrados de hoje, agora somando tudo, e logo abaixo a **barra do total**
em largura cheia com a frase escrita ao lado.

**Seus grupos** — uma linha por grupo, na ordem fixa do seletor: as marcas empilhadas, o nome, a
cadência (*"3 canais · publicou hoje"*), a barra **na mesma medida das outras**, os números
escritos, e *"Entrar neste grupo"* nos que não estão em foco.

⚠️ **A seção não existe com um grupo só** — ela repetiria a barra do total palavra por palavra.

**Suas redes** — inalterado, do grupo em foco. É o semáforo diário (DEC-32), e precisa ser visto
antes de a conexão quebrar.

---

## Fase 1 — Uma fonte para os números

⚠️ Hoje duas classes contam a mesma coisa por conta própria. Com grupo, elas divergiriam.

- [ ] **1.1** `ResumoDoPainel` — **uma** consulta agrupada por grupo, plataforma e status, servindo
  o total, cada grupo e cada rede
- [ ] **1.2** `ResumoDasRedes` passa a **pedir** os números a ela, em vez de contar de novo
- [ ] **1.3** ⛔ O `whereIn` escopado **não sai** do `join` cru: é a única coisa que aplica o escopo
  do dono ali (DEC-74)
- [ ] **1.4** ⭐ O grupo vem de `publicacoes.grupo_id`, nunca da conta (DEC-75)

**Pronto quando:** existe um lugar só que sabe contar, e ele faz uma consulta a menos que hoje.

---

## Fase 2 — O gráfico, atrás de um contrato

- [ ] **2.1** `components/grafico/barra-de-entrega.tsx` — barra empilhada em CSS puro
- [ ] **2.2** O contrato: valor absoluto, rótulo, cor resolvida, medida compartilhada, sem px, sem
  classe, sem JSX, sem callback
- [ ] **2.3** ⭐ Fatia maior que zero **nunca fica invisível** (piso de 4px) — e soma zero não
  desenha esqueleto nenhum
- [ ] **2.4** O que está a caminho é **listrado**: é o único estado que não terminou, e é o segundo
  canal para quem não distingue verde de âmbar. ⛔ Cor nunca é o único canal
- [ ] **2.5** `aria-label` obrigatório

**Pronto quando:** dá para trocar o desenhista mexendo em um arquivo só.

---

## Fase 3 — A tela

- [ ] **3.1** "Como está" soma tudo; a barra do total e a frase nascem com o primeiro envio
- [ ] **3.2** "Seus grupos", só com dois ou mais
- [ ] **3.3** A barra de cada grupo em **coluna de largura fixa** — sem isso as barras não se
  alinham entre si, e a medida compartilhada deixa de ser legível
- [ ] **3.4** No celular a barra nunca divide a linha com texto
- [ ] **3.5** Grupo sem post não ganha barra vazia: ganha frase no mesmo lugar
- [ ] **3.6** ⭐ Caso especial: nenhum subiu de N — a coluna vira texto vermelho de tamanho fixo, em
  vez de pouca tinta vermelha

**Pronto quando:** um grupo parece hoje; três se comparam em dois segundos.

---

## Fase 4 — Os dois defeitos de hoje

- [ ] **4.1** O aviso passa a contar **posts** (DEC-90)
- [ ] **4.2** Aviso de outro grupo vira "Entrar em «X»" (DEC-89)
- [ ] **4.3** Falhas em dois ou mais grupos: **sem ação** — escolher uma seria decidir por conta
  própria qual é o problema da pessoa
- [ ] **4.4** `grupos.usar` ganha `ver=falhas`, no mesmo padrão de `conectar`

**Pronto quando:** nenhum aviso leva a uma janela vazia.

---

## Fase 5 — Guardiões

- [ ] **5.1** Primeiro dia, um grupo comum, três grupos, grupo com conta quebrada
- [ ] **5.2** Com um grupo, a seção não renderiza
- [ ] **5.3** O aviso de outro grupo não abre janela vazia
- [ ] **5.4** ⛔ A consulta nova vê todos os grupos **do dono**, e nenhum de outro
- [ ] **5.5** Os dois aceites do gráfico: soma zero não desenha; 1 em 400 continua visível

---

## ⛔ O que fica de fora, de propósito

**Métricas de rede** — bloqueado por fora: com o aplicativo do YouTube em Testes, todo vídeo sobe
privado e não tem métrica pública. A tela mostraria zero em tudo.

**Porcentagem, anel, medidor.** Com 3 posts, "100%" vira "67%" e a pessoa acha que o painel piorou.
*36 de 40* é a mesma verdade sem o número que oscila.

**Ranking, meta, projeção, ordenação por volume.** Respondem uma pergunta que ninguém fez, e
condenam a linha de conteúdo pequena a ser sempre a última.

**Escala logarítmica.** Endireita o gráfico mentindo sobre a proporção — o defeito que o produto
existe para não ter.

**Lista de publicações** (DEC-68), **primeiros passos** (DEC-86) e **botão de conectar** (DEC-87),
que já saíram e não voltam.
