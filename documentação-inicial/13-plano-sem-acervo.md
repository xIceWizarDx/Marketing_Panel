# Plano de refatoração — o produto não guarda nada

_Criado em 2026-08-04._

> **O propósito, sem meio-termo:** isto é um **caminho de publicação com prova**, não um lugar
> onde se guardam arquivos. O vídeo existe pelo tempo do envio. Terminou, sai.
>
> Regra de conclusão: **suíte verde, tipos e lint limpos, conferido no servidor**.

---

## O que estava desalinhado

O plano anterior parou no meio do caminho: tirou a biblioteca, mas deixou uma **carência de 7
dias** e uma **faixa de vídeos anteriores** no compositor. Na prática, continuava sendo um acervo
pequeno — e a tela sugeria arquivos guardados justamente onde a promessa é não guardar.

Meia decisão é pior que nenhuma: gasta disco, cria expectativa de acervo e ainda obriga a explicar
prazos que não deveriam existir.

### ⛔ E o argumento de fundo, que é mais simples que o de produto

Guardar é uma boa ideia; só não é uma **necessidade**. O cliente já tem o arquivo — saiu do celular
dele, está no computador dele. Seríamos a terceira cópia, a que ele menos precisa.

E as contas de custo eram estimativa própria. Armazenamento escala de formas que ninguém prevê
sentado, **não existe plano vendendo disco** e não há nem VPS de verdade nem cliente pagante. Assumir
custo aberto para sustentar um produto que ninguém comprou é trabalho caro e descartável.

⭐ **Quando revisitar:** VPS real + cliente pagante real + esse cliente **pedindo** armazenamento.
Aí é demanda, não suposição. Antes disso, guardar resolve um problema que ninguém tem.

---

## As decisões, corrigidas

**DEC-59 — o arquivo sai quando o último destino termina.** Sem espera, sem prazo. Ele existia
para subir; subiu, acabou a função. *(Revoga a carência da DEC-55.)*

**DEC-60 — o compositor não sugere nada.** Não há lista de vídeos anteriores. Quem publica envia o
arquivo ali, naquele momento. A ausência de biblioteca é a promessa aparecendo na tela.

**DEC-61 — republicar reaproveita o TEXTO, não o arquivo.** O título, a legenda e as hashtags vêm
prontos; o vídeo é reenviado. É o preço honesto de não guardar — e a assinatura do conteúdo
(DEC-58) reconhece o arquivo e devolve tudo ao mesmo registro.

**DEC-62 — prazo só para abandono.** Quem enviou e desistiu no meio deixa um arquivo sem dono. Isso
não é carência, é limpeza de lixo: existe só para o arquivo sobreviver enquanto a pessoa escreve a
legenda.

---

## ✅ Fase 1 — A regra: terminou, sai — CONCLUÍDA

- [x] **1.1** `LiberacaoDeArquivo` — publicação concluída libera **na hora**, sem somar dias
- [x] **1.2** Config passa a ter só `limpar_abandonado_em_dias`, com o nome dizendo o que é
- [x] **1.3** ⭐ O **motor** libera o arquivo assim que a publicação fica terminal — esperar o
  comando diário guardaria o vídeo um dia inteiro sem função
- [x] **1.4** O comando diário vira **rede de segurança**: pega abandono e o que escapou
- [x] **1.5** Testes: publicou → o arquivo sai no mesmo instante

**Pronto quando:** publicar e conferir o disco mostra que o vídeo não está mais lá.

---

## ✅ Fase 2 — O compositor não sugere nada — CONCLUÍDA

- [x] **2.1** Remover a lista de mídias do compositor e da rota
- [x] **2.2** O arquivo do momento aparece só depois de enviado, com prévia e laudo
- [x] **2.3** Trocar por outro arquivo é reenviar — sem lista para escolher
- [x] **2.4** Sumir com o aviso de prazo: não há mais prazo a avisar
- [x] **2.5** Testes

**Pronto quando:** abrir o compositor mostra só a área de envio.

---

## ✅ Fase 3 — Republicar leva o texto — CONCLUÍDA

- [x] **3.1** `paraRepublicar` devolve texto e as contas já usadas — **nunca** a mídia
- [x] **3.2** O botão aparece **sempre**, não só enquanto houver arquivo
- [x] **3.3** A janela diz, sem rodeio, que o vídeo precisa ser enviado de novo
- [x] **3.4** Testes

**Pronto quando:** republicar é enviar o arquivo e marcar a rede — o resto vem pronto.

---

## ✅ Fase 4 — Tirar o que virou mentira — CONCLUÍDA

⚠️ Saneamento: o que descrevia acervo sai do código, da tela e da documentação.

- [x] **4.1** `arquivoAte` e o aviso "sai em X dias" saem do compositor
- [x] **4.2** `podeRepublicar` deixa de depender do arquivo
- [x] **4.3** Textos que falam em guardar, biblioteca ou acervo
- [x] **4.4** DEC-55 marcada como revogada, com o motivo
- [x] **4.5** `grep` de "carência", "biblioteca" e "acervo" → só onde explica que **não** existe

**Pronto quando:** nada no projeto promete guardar arquivo.

---

## Executado em 2026-08-04

**337 testes verdes** (18 no guardião do arquivo), `tsc`, `eslint`, `pint` e `npm run build`
limpos.

### O que mudou de verdade

**O motor virou o dono da regra.** `PublicacaoService::recalcularStatus` apaga o arquivo no
instante em que a publicação fica terminal. Antes, quem apagava era um comando diário — e um
comando diário guarda um vídeo já inútil por até 24 horas. O comando continua existindo como
**rede de segurança**: pega envio abandonado no meio e o que escapar do motor.

**`liberar_arquivo_em_dias` virou `limpar_abandonado_em_dias`.** O nome antigo descrevia carência;
o novo descreve o que a coisa faz. Padrão 1 dia, e só alcança quem enviou e desistiu — arquivo
publicado nem chega a ver esse prazo.

**O compositor perdeu a faixa de miniaturas.** Não há mais lista, nem `MidiaDaLista`, nem
`arquivoAte`, nem o aviso "sai em X dias". Sobrou a área de envio; o arquivo aparece **depois** de
enviado, nesta mesma composição, e trocar é reenviar.

**Republicar passou a levar só o texto.** `paraRepublicar` não devolve mídia nenhuma, e
`podeRepublicar` deixou de depender do arquivo — o botão vale sempre. A janela diz, sem rodeio, que
o vídeo precisa ser enviado de novo.

### Dois defeitos que apareceram no caminho

**Enviar o vídeo apagava o texto já escrito.** O upload redirecionava para `/publicar` fixo, então
quem abriu *"publicar em outra rede"* (`/publicar/{ulid}`) voltava para o compositor vazio — perdia
exatamente o texto que o botão tinha ido buscar. Agora volta de onde saiu (`back()`), e o envio
preserva o estado do formulário.

**Código morto sustentando vocabulário morto.** `MidiaController::paraTela` e a interface `Midia`
do front eram da tela de Mídias, que já tinha saído; as duas ainda carregavam `arquivoAte` e
`temArquivo`. Saíram inteiras, junto com a variante compacta do `EnviarMidia` e a palavra
"galeria", que descrevia uma tela que não existe mais.

### O que ficou dito na cara, em vez de escondido

Quem publicou no YouTube hoje e quiser publicar no Instagram amanhã **envia o vídeo de novo**. Não
dá para baixar de volta — a API do YouTube não tem método de download, e o vídeo indexado depende
de ser público, enquanto os nossos sobem privados. Esse é o preço de não guardar, e ele aparece na
própria janela em vez de virar surpresa.
