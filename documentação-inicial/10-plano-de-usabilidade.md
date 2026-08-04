# Plano de ação — usabilidade

_Criado em 2026-08-04. Executa os achados de [08](08-estudo-de-usabilidade.md) e
[09](09-ideias-dos-concorrentes.md), na ordem de impacto._

> Regra de conclusão de cada fase: **suíte verde, tipos e lint limpos, e conferido no navegador**.
> Nada é dado como pronto sem os três.

---

## ✅ Fase 1 — Miniatura e prévia *(U-2, o mais grave)* — CONCLUÍDA

**Problema:** três vídeos do WhatsApp viram três linhas iguais. Publicar o errado não tem desfazer.

- [x] **1.1** Coluna `miniatura` em `midias` (migration)
- [x] **1.2** `GeradorDeMiniatura` — um quadro do vídeo via ffmpeg, JPEG, largura fixa
  - ⚠️ falhar a miniatura **não pode** derrubar o envio: mídia sem miniatura é aceitável,
    envio recusado por causa de uma imagem de apoio não é
- [x] **1.3** Gerar no `MidiaService`, junto do laudo
- [x] **1.4** Servir a miniatura por rota própria, com o mesmo cuidado de dono do arquivo original
- [x] **1.5** Mostrar em **Mídias** (grade), **Publicar** (escolha) e **Publicações** (lista)
- [x] **1.6** Prévia: tocar o vídeo antes de publicar
- [x] **1.7** Comando para gerar as miniaturas do que já existe
- [x] **1.8** Testes: gera, isola por dono, e sobrevive ao ffmpeg ausente

**Pronto quando:** dá para distinguir dois vídeos sem ler o nome do arquivo.

---

## ✅ Fase 2 — A tela inicial passa a saber das coisas *(U-1)* — CONCLUÍDA

**Problema:** a rota é uma função sem dados. Do 2º dia em diante, a porta de entrada é a única
página que não sabe de nada.

- [x] **2.1** `VisaoGeralController` de verdade
- [x] **2.2** Os três números (no ar · indo · falharam), reaproveitando o cálculo das Conexões
- [x] **2.3** Últimas publicações, com miniatura, estado e link
- [x] **2.4** ⭐ **"Precisa de você"** — conexão vencendo, publicação falhada, rede sem conta.
  Some quando não há nada; é o oposto de um aviso decorativo
- [x] **2.5** Primeiros passos **que se marcam** (ideia do Buffer), só enquanto fizerem sentido
- [x] **2.6** Atualização viva enquanto houver algo em andamento
- [x] **2.7** Testes: cada bloco aparece só quando deve, e nunca mistura dado de outro cliente

**Pronto quando:** abrir o painel responde *"o que aconteceu enquanto eu não estava olhando?"*.

---

## ✅ Fase 3 — O laudo diz o motivo *(U-3)* — CONCLUÍDA

**Problema:** a tela mostra "não aceita" e joga fora a explicação que o servidor já escreveu.

- [x] **3.1** Mostrar mensagem e providência de cada achado
- [x] **3.2** Erro em destaque; o que passou fica discreto — quem lê quer saber o que **impede**
- [x] **3.3** Teste: o texto do achado chega à tela

**Pronto quando:** dá para saber o que fazer sem perguntar a ninguém.

---

## ✅ Fase 4 — O botão não engana *(U-7)* — CONCLUÍDA

- [x] **4.1** Desabilitar enquanto faltar arquivo ou conta
- [x] **4.2** Dizer no próprio botão o que falta
- [x] **4.3** Teste

**Pronto quando:** não dá mais para clicar em "Publicar em 0 contas".

---

## ✅ Fase 5 — Abas com contador nas publicações *(C-4, resolve U-8)* — CONCLUÍDA

- [x] **5.1** **Em andamento · No ar · Falharam**, com o número na aba
- [x] **5.2** Filtro no servidor, preservado na URL (dá para compartilhar e voltar)
- [x] **5.3** Teste: cada aba conta e lista o que promete

**Pronto quando:** "Falharam (2)" responde sem clicar.

---

## ✅ Fase 6 — Limites e contagem certos *(U-5 e U-4)* — CONCLUÍDA

**Problema:** o campo deixa digitar o que o servidor vai recusar, e o contador conta emoji errado —
defeito que já foi corrigido no servidor e ficou para trás na tela.

- [x] **6.1** Levar os limites de cada rede para o front, de uma fonte só
- [x] **6.2** Contar grafema com `Intl.Segmenter`, como o servidor faz
- [x] **6.3** O contador olha as **contas escolhidas** e mostra o limite mais apertado entre elas
- [x] **6.4** Teto do campo vindo das redes escolhidas, não fixo em 255
- [x] **6.5** Teste: emoji de família conta 1, não 11

**Pronto quando:** o que a tela permite escrever é o que a rede aceita.

---

## Fora deste plano

**Republicar (U-10)**, **tempo relativo (U-6)**, **enviar do compositor (C-5)** e **conferir conexão
agora (C-2)** entram num segundo plano, depois destes seis. **Apagar mídia não usada (C-1)** e
**portal compartilhável (C-3)** dependem de assinaturas e do caminho de revendedor.

---

## Executado em 2026-08-04

As seis fases entregues, com **322 testes verdes**, tipos e lint limpos, e cada uma conferida no
servidor antes de ser dada como pronta.

**Três defeitos apareceram durante a execução, e todos eram nossos:**

1. **Eager-load parcial sem a coluna nova.** Ler `miniatura` numa seleção que não a incluía estoura
   500 com strict mode ligado — e o strict mode costuma estar ligado **só em produção**. A suíte
   pegou porque o teste roda com ele ativo.
2. **`RegistroDePublicadores` injetado e nunca usado** no controller novo (0.C).
3. **O nome da conta repetido em cada linha.** No painel de quem tem um canal só, mostrar o próprio
   nome não informa nada. Agora só aparece quando há mais de uma conta na mesma rede — quando ele
   de fato desambigua.

**Uma decisão de desenho que vale registrar:** o bloco *"precisa de você"* e as abas vazias
**somem** quando não há nada. Aviso que aparece sempre treina a pessoa a ignorá-lo, e no dia em que
houver problema de verdade ela não olha.
