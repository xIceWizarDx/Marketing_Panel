# Plano de ação — o compositor, e o fim da biblioteca

_Criado em 2026-08-04._

> **A decisão:** publicar deixa de ser uma tela e vira uma **ação**, aberta por cima da lista de
> publicações. A tela de Mídias sai — ela existia porque éramos um drive, e não somos mais
> (DEC-54).
>
> Regra de conclusão: **suíte verde, tipos e lint limpos, conferido no servidor**.

---

## O que isso resolve

**Some o vaivém entre duas telas para uma intenção só.** Hoje é: ir em Mídias → enviar → ir em
Publicar → escolher o mesmo arquivo. Quem tem o vídeo na mão quer publicar, não gerenciar acervo.

**Some a tela que duplicava o histórico.** Mídias e Publicações mostram a mesma mídia com molduras
diferentes; um dia elas divergem.

**E republicar deixa de ser refazer tudo.** O compositor abre pré-preenchido a partir de uma
publicação — o U-10 do estudo de usabilidade, resolvido pelo mesmo movimento.

O menu cai de quatro itens para três: **Visão geral · Publicações · Conexões**.

---

## ⚠️ A condição inegociável: o modal tem endereço próprio

Modal comum guarda o estado só na memória do navegador. Atualizar a página no meio da escrita
**perderia tudo** — que é exatamente o defeito U-9 do estudo, e seria irônico criá-lo agora.

Então `/publicar` continua sendo uma rota de verdade. Ela renderiza a **lista de publicações** com o
compositor aberto por cima. Atualizar reabre no mesmo ponto, voltar fecha o modal em vez de sair do
painel, e o endereço pode ser enviado para alguém.

No celular vira tela cheia — modal pequeno com sete campos num telefone é pior que página.

---

## ✅ Fase 1 — O compositor vira componente — CONCLUÍDA

- [x] **1.1** Extrair o conteúdo de `publicar.tsx` para `components/publicacao/compositor.tsx`
- [x] **1.2** Receber tudo por propriedade; nenhum acesso direto a props de página
- [x] **1.3** Manter intactos o laudo com motivo, os limites por rede e o botão que não engana

**Pronto quando:** o compositor funciona igual, só que como componente.

---

## ✅ Fase 2 — `/publicar` vira rota de modal — CONCLUÍDA

- [x] **2.1** `/publicar` passa a renderizar `cliente/publicacoes` com a prop `compositor`
- [x] **2.2** A tela de Publicações abre o modal quando a prop existe
- [x] **2.3** Fechar volta para `/publicacoes`, preservando a aba escolhida
- [x] **2.4** Tela cheia no celular
- [x] **2.5** Testes: a rota devolve lista **e** compositor; atualizar mantém aberto

**Pronto quando:** dá para atualizar a página com o compositor aberto sem perder nada.

---

## ✅ Fase 3 — Enviar o arquivo de dentro do compositor — CONCLUÍDA

- [x] **3.1** `EnviarMidia` dentro do compositor
- [x] **3.2** Terminou de enviar, a mídia já fica **escolhida** — ninguém envia para depois procurar
- [x] **3.3** O laudo aparece na hora, sem trocar de tela
- [x] **3.4** Teste

**Pronto quando:** enviar e publicar acontecem sem sair do lugar.

---

## ✅ Fase 4 — Publicar em outra rede — CONCLUÍDA

- [x] **4.1** Botão no cartão da publicação, **só enquanto o arquivo estiver aqui** (DEC-55)
  — 🚫 revisto pela DEC-61: o botão vale **sempre**, porque o que ele leva é o texto
- [x] **4.2** Abre o compositor com vídeo, título, legenda e hashtags preenchidos
  — 🚫 revisto pela DEC-61: leva o **texto**; o vídeo é reenviado
- [x] **4.3** ⚠️ As contas onde **já** foi publicado vêm desmarcadas e avisadas — republicar na
  mesma conta é engano quase sempre, e publicação não tem desfazer
- [x] **4.4** Testes

**Pronto quando:** republicar é marcar uma rede e clicar.

---

## ✅ Fase 5 — A tela de Mídias sai — CONCLUÍDA

⚠️ Saneamento radical: sai a tela, a rota, o método do controller, o item de menu, o teste e o
tipo. **Sem sobra.**

- [x] **5.1** Remover a página, a rota de listagem e o método `listar`
- [x] **5.2** Tirar do menu
- [x] **5.3** Remover o que só existia para ela (`relaudar`, `remover`) — sem tela, é código morto
- [x] **5.4** Manter o que o compositor usa: enviar, miniatura, arquivo
- [x] **5.5** `grep` de `midias` em rotas, telas e testes → só o que sobrou de propósito
- [x] **5.6** Suíte inteira verde

**Pronto quando:** o menu tem três itens e não sobrou referência à tela removida.

---

## Executado em 2026-08-04

**332 testes verdes**, tipos e lint limpos, conferido no servidor: `/publicar` devolve a lista com o
compositor por cima, e `/publicar/{ulid}` volta pré-preenchido com vídeo, título e legenda.

### Uma decisão diferente do plano

O plano dizia que o menu ficaria com **três** itens. Ficou com quatro: **Publicar** continua lá.

Publicar é a ação principal do produto, e enterrá-la dentro de outra tela seria economizar uma
linha de menu ao custo do caminho mais usado. No celular, onde o menu é a barra de baixo, isso
pesaria ainda mais. A rota abre o compositor por cima da lista, então não há tela duplicada — só um
atalho para o que a pessoa mais faz.

### O que a execução ensinou

**Uma substituição minha engoliu dois métodos.** Ao trocar o bloco do `compor`, o recorte pegou o
`listar` e o `enviar` que estavam no meio. A suíte apontou na hora, mas o susto vale como lição:
recorte por marcador de início e fim só é seguro quando se sabe o que existe entre eles.

**O aviso de prazo precisou mudar de casa — e um dia depois deixou de existir.** Ele foi parar no
compositor, único lugar onde a pessoa via a mídia. Aí ficou claro que o problema não era **onde**
avisar do prazo, e sim o prazo existir: o [plano 13](13-plano-sem-acervo.md) tirou os dois. Não há
surpresa a evitar quando não há espera.

**Os primeiros passos caíram de três para dois.** "Enviar um vídeo" deixou de ser etapa: o arquivo
entra dentro do compositor. Manter o passo descreveria um caminho que não existe mais.
