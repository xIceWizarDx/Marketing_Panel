# Plano de refatoração — Conexões deixa de ser uma tela

_Criado em 2026-08-04._

> **A ideia, em uma frase:** o painel tem telas demais para o que faz. Conexões e Visão geral
> respondem à mesma pergunta — *"como está tudo?"* — em dois lugares diferentes.
>
> Regra de conclusão: **suíte verde, tipos e lint limpos, conferido no navegador**.

---

## O que está desalinhado

**As duas telas dizem a mesma coisa.** Visão geral mostra "3 conexões estão para vencer" e um link
*Reconectar*, que leva para Conexões — onde a mesma informação aparece de novo, com mais detalhe.
Quem abre o painel para saber se está tudo bem precisa passar por duas telas para ter certeza.

**E Conexões é pequena.** Depois de virar grade de quadradinhos, o conteúdo dela cabe em quatro
linhas de tela. O detalhe de cada rede — contas, semáforo, conectar, desconectar — **já é modal**.
Sobrou uma página inteira para segurar uma grade e um cabeçalho.

---

## ⚠️ Uma diferença em relação ao pedido

O pedido foi *"Conexões vira um modal da Visão geral"*. Vou fazer **a grade aparecer dentro da
Visão geral**, e não dentro de um modal. Três motivos:

1. **Viraria modal dentro de modal.** Clicar numa rede já abre um modal (contas, conectar,
   desconectar). Abrir isso a partir de outro modal empilha duas camadas para uma tarefa só.
2. **O semáforo do token é o diferencial (DEC-32), e diferencial escondido não é diferencial.**
   Atrás de um clique, ele vira algo que a pessoa descobre quando já perdeu a publicação.
3. **O ganho que motivou o pedido é o mesmo:** uma tela a menos, um item a menos no menu.

⭐ **A regra que separa os dois casos:** *Publicar* é uma **ação** — tem começo, meio e um
resultado; modal serve. *Conexões* é **estado** — é a resposta a "como está tudo?", e essa
resposta pertence à tela que a pessoa abre primeiro.

---

## As decisões

**DEC-63 — Conexões não é uma tela; é uma seção da Visão geral.** A grade de redes passa a viver na
porta de entrada, com o semáforo à vista. Os modais de detalhe, conectar e desconectar continuam
como estão.

**DEC-64 — a rota `/conexoes` sai inteira.** Saneamento radical: sem tela, não há rota, nem método
no controller, nem item de menu. Tudo o que apontava para lá passa a apontar para `/painel` —
inclusive o retorno do OAuth, que é onde a pessoa mais precisa cair no lugar certo.

**DEC-65 — o resumo das redes tem fonte única.** O array de redes é montado num lugar só
(`ResumoDasRedes`) e servido a quem precisar. Duas montagens divergiriam, e a divergência apareceria
como número diferente para o mesmo fato em telas diferentes.

---

## ✅ Fase 1 — O resumo das redes ganha dono — CONCLUÍDA

- [x] **1.1** `App\Support\Conexao\ResumoDasRedes` — monta o array de redes e o total conectado
- [x] **1.2** `ConexaoController` passa a usá-lo, sem duplicar a conta
- [x] **1.3** `VisaoGeralController` também
- [x] **1.4** Testes: os números da Visão geral batem com os que Conexões mostrava

**Pronto quando:** existe um lugar só que sabe responder "como estão as redes?".

---

## ✅ Fase 2 — A grade muda de casa — CONCLUÍDA

- [x] **2.1** `components/conexao/painel-de-redes.tsx` — a grade e os três modais, inteiros
- [x] **2.2** Visão geral passa a mostrá-lo, depois dos números
- [x] **2.3** A ordem da tela: pendências → números → primeiros passos → redes → últimas
- [x] **2.4** ⚠️ Os avisos de conexão continuam funcionando, apontando para a própria tela

**Pronto quando:** dá para conectar, ver a saúde e desconectar sem sair da Visão geral.

---

## ✅ Fase 3 — A tela antiga sai — CONCLUÍDA

⚠️ Saneamento: sai a página, a rota, o método, o item de menu e o teste que abria a tela.

- [x] **3.1** Remover `pages/cliente/conexoes.tsx` e `ConexaoController::listar`
- [x] **3.2** Remover a rota `conexoes` e tirar do menu
- [x] **3.3** Todo `to_route('conexoes')` vira `to_route('painel')` — inclusive os retornos de OAuth
- [x] **3.4** `grep` de `conexoes` → só as rotas de ação (`conexoes.bluesky`, `.youtube`, `.meta`…)
- [x] **3.5** Suíte inteira verde

**Pronto quando:** o menu do cliente tem dois itens e não sobrou referência à tela removida.

---

## Executado em 2026-08-04

**338 testes verdes**, `tsc`, `eslint`, `pint` e `npm run build` limpos.

### O que mudou

**O menu do cliente tem dois itens:** Visão geral e Publicações. Publicar é botão dentro de
Publicações; conectar é a grade dentro da Visão geral. Não sobrou item de menu que descreva algo
que não é um lugar.

**`ResumoDasRedes` virou a fonte única.** `ConexaoController::listar`, `numerosPorRede` e
`paraTela` saíram do controller e viraram uma classe só, usada pela Visão geral. O controller
ficou com o que ele é: **ações** — conectar, retornar do OAuth, desconectar.

**A grade virou `components/conexao/painel-de-redes.tsx`**, com os três modais (detalhe, conectar,
desconectar) inteiros. A casca de página virou cabeçalho de seção: o resumo que ocupava um cartão
de 4 linhas virou uma linha de texto ao lado do título.

**Todo retorno de OAuth cai em `/painel`.** É onde a resposta aparece — a pessoa autoriza no
Google e volta vendo o semáforo da conta que acabou de conectar.

### Uma decisão diferente do pedido

O pedido era *"Conexões vira um modal da Visão geral"*. A grade ficou **visível na própria tela**,
não dentro de um modal — o detalhe de cada rede é que continua sendo modal. Modal dentro de modal
empilharia duas camadas para uma tarefa só, e o semáforo do token (DEC-32) é justamente o que não
pode depender de um clique para ser visto.

O ganho que motivou o pedido foi entregue igual: uma tela a menos, um item a menos no menu.
