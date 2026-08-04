# Plano de ação — o arquivo vive enquanto tem função

_Criado em 2026-08-04._

> 🚫 **Este plano parou no meio do caminho.** A carência de 7 dias da DEC-55 foi **revogada** no
> mesmo dia pelo [plano 13](13-plano-sem-acervo.md): o arquivo sai no instante em que a publicação
> termina. O resto do plano continua valendo, e as marcas de "concluída" abaixo são o registro do
> que foi feito — não a descrição do produto de hoje.

> **A decisão em uma frase:** o produto não guarda acervo. O arquivo original existe enquanto tem
> serviço a fazer; cumprido o serviço, sai. Fica o registro — miniatura, laudo, links e prova.
>
> Regra de conclusão de cada fase: **suíte verde, tipos e lint limpos, conferido no servidor**.

---

## Por que, em três linhas

O fluxo em duas etapas (subir para a biblioteca, depois publicar) nos transformou num drive sem
ninguém decidir isso. Competir com o Google Drive é perder: eles guardam melhor e mais barato.

O custo não é o argumento — guardar tudo dá cerca de 1% da receita, e essa proporção não piora com
escala. **O argumento é o produto não ser isso.**

E o caminho de não guardar nada também não serve: está **verificado** que o YouTube não entrega
capa de vídeo privado (API vazia, 404 no endereço direto), que não existe método de download na
API dele, e que publicação falhada não tem nada hospedado em rede nenhuma.

---

## As decisões que sustentam o plano

**DEC-54 — o arquivo vive enquanto tem função, não por prazo.** Enquanto houver destino agendado,
tentando de novo ou aguardando alguma rede, ele fica. Prazo é consequência, não critério.

**DEC-55 — carência curta depois que tudo resolve.** 🚫 **REVOGADA no mesmo dia pela DEC-59.** A
aposta era que o arrependimento ("quero no TikTok também") acontece em dias. Acontece mesmo — mas a
resposta certa é reaproveitar o **texto** e pedir o vídeo de novo (DEC-61), não segurar o arquivo.
Carência era acervo pequeno com outro nome.

**DEC-56 — a miniatura é sempre nossa.** 40 KB contra 20 MB. É o único ponto que funciona nos
quatro estados: antes de publicar, privado, publicado e apagado. Indexar a capa da rede foi
testado e **não funciona**.

**DEC-57 — indexar serve para assistir, nunca para reconhecer.** Vídeo público em alguma rede,
embute o player de lá. Não havendo, não oferece — porque não há mesmo o que assistir.

**DEC-58 — a assinatura do conteúdo identifica o reenvio, e vira parte da prova.** Bytes idênticos
é afirmação com certeza absoluta. "Parece o mesmo vídeo" seria a primeira vez que o painel afirma
sem verificar — e isso não entra.

---

## ✅ Fase 1 — A assinatura do conteúdo — CONCLUÍDA

**Serve a duas coisas ao mesmo tempo:** reencontrar o arquivo quando ele voltar, e provar que o que
foi para duas redes era exatamente o mesmo.

- [x] **1.1** Coluna `assinatura` em `midias` (hash do conteúdo), indexada por dono
- [x] **1.2** Calcular no envio, lendo o arquivo que já está sendo gravado
- [x] **1.3** Reenvio do mesmo conteúdo **reaproveita o registro** em vez de duplicar
  - ⚠️ dentro do escopo do dono: assinatura igual de outro cliente é outro registro
- [x] **1.4** Testes: mesmo conteúdo com nome diferente reencontra; conteúdo diferente não

**Pronto quando:** enviar o mesmo arquivo duas vezes devolve o mesmo registro, com o histórico.

---

## ✅ Fase 2 — O arquivo sai quando cumpriu a função — CONCLUÍDA

- [x] **2.1** Coluna `arquivo_removido_em` em `midias` (nulo = o arquivo está aqui)
- [x] **2.2** `midia.liberar_arquivo_em` (config), padrão 7 dias
- [x] **2.3** `LiberarArquivosCumpridos` — comando diário que remove:
  - mídia com **todos** os destinos em estado final há mais que a carência
  - mídia **nunca publicada** e parada há mais que a carência (é custo sem contrapartida)
- [x] **2.4** ⛔ **Nunca** remover com destino pendente — nem agendado, nem tentando de novo
- [x] **2.5** A miniatura **fica**, sempre
- [x] **2.6** Testes: cobre os dois casos de remoção, e prova que pendente segura o arquivo

**Pronto quando:** o disco guarda o que está em andamento, não o histórico.

---

## ✅ Fase 3 — A tela diz a verdade sobre o arquivo — CONCLUÍDA

⚠️ A pessoa **nunca** pode ser pega de surpresa. É o oposto do que os concorrentes fazem, onde o
arquivo some sem aviso.

- [x] **3.1** Mídia com arquivo mostra até quando dá para republicar
- [x] **3.2** Mídia sem arquivo mostra *"arquivo removido · registro e provas mantidos"*
- [x] **3.3** Publicar só oferece mídia que ainda tem arquivo, e explica as outras
- [x] **3.4** Prévia e download só existem quando há arquivo
- [x] **3.5** Testes

**Pronto quando:** dá para saber o estado do arquivo sem clicar em nada.

---

## ✅ Fase 4 — Reenviar para o mesmo registro — CONCLUÍDA

- [x] **4.1** Reenvio recompõe a mídia sem perder publicações, laudo nem provas
- [x] **4.2** ⚠️ Recusar arquivo **diferente** no lugar de um removido — o registro afirma que
  aquele conteúdo foi publicado; trocar o conteúdo por baixo transformaria a prova em mentira
- [x] **4.3** Testes

**Pronto quando:** reenviar devolve a mídia ao estado publicável, com o histórico intacto.

---

## Fora deste plano

**Assistir pelo player da rede (DEC-57)** depende de vídeo público, o que só acontece depois da
auditoria do YouTube. Fica para quando houver conteúdo público para testar.

**Escolher a capa** (imagem própria ou quadro do vídeo) é plano separado: envolve os publicadores,
tem regra diferente por rede e o Instagram depende da aprovação da Meta.

---

## Executado em 2026-08-04 — e o que a revisão pegou

**335 testes verdes**, tipos e lint limpos, conferido no servidor.

A revisão depois de pronto encontrou **três defeitos meus**, e dois eram sérios:

### 🔴 A tela prometia uma data que a limpeza não respeitaria

A tela calculava a validade a partir do **envio** (`created_at`), e o comando contava do **fim da
publicação**. Um vídeo enviado hoje e publicado daqui a um mês teria na tela uma data que o
comando ignoraria.

**Duas contas separadas para a mesma pergunta sempre divergem.** Nasceu o `LiberacaoDeArquivo`
como fonte única — tela e comando leem dele, e um teste compara os dois.

### 🔴 Rascunho seria liberado com o arquivo ainda em uso

Publicação criada e não enviada **não tem destino nenhum**, então a conta ingênua a classificava
como "nunca publicada" e liberava o vídeo por baixo dela. Corrigido: rascunho segura o arquivo,
como destino pendente.

### 🟡 `--dias=0` caía no valor padrão

`?:` em vez de `??` — zero é falso em PHP, e zero é justamente o valor usado para conferir o
comando à mão. O teste que eu tinha feito passou por engano.

---

## O que fica para depois

**Assistir pelo player da rede (DEC-57)** — depende de vídeo público, e os nossos sobem privados
até a auditoria do YouTube.

**Escolher a capa** — plano próprio: regra diferente por rede, e o Instagram depende da aprovação
da Meta. Confirmado hoje que o YouTube **não aceita** capa personalizada em Short, e que a capa
escolhida **não pode ser trocada depois do envio**.
