# Instagram — achados da documentação oficial

> Lido antes de escrever qualquer linha, como manda a regra. O que está em
> [`../meta-compartilhado.md`](../meta-compartilhado.md) vale aqui também e não se repete.

---

## ⭐ I-1 — A rede avisa se o vídeo viola direitos autorais **antes de publicar**

O container tem um campo `copyright_check_status`:

```
matches_found: true  → o vídeo está violando direitos autorais
matches_found: false → não está
status: not_started | in_progress | completed | error
```

**Por que isso é grande:** o problema que o produto ataca é *"achei que publiquei e não
publiquei"*. Aqui a rede entrega algo melhor ainda — dá para dizer **antes** de publicar que o
áudio do corte vai derrubar o vídeo.

Nenhum painel de agendamento mostra isso. É a mesma tese da conciliação, só que preventiva.

**Decisão:** consultar junto com o `status_code` e mostrar como aviso próprio.

---

## ⭐ I-2 — `status_code` do container é a conciliação pronta

```
IN_PROGRESS → ainda processando
FINISHED    → pronto para publicar
PUBLISHED   → publicado
ERROR       → falhou (o campo `status` traz o subcódigo)
EXPIRED     → passou de 24 h sem publicar
```

Encaixa direto na nossa máquina de estados, sem adaptação. A recomendação oficial é consultar
*"uma vez por minuto, por no máximo 5 minutos"* — bem mais curto que as 20 consultas do YouTube.

⚠️ **`EXPIRED` é um estado que o YouTube não tem.** O container morre em 24 h. Se o motor ficar
preso numa fila lenta, o envio se perde — e a mensagem precisa dizer isso, não "erro
desconhecido".

---

## ⚠️ I-3 — Publicar é em **dois passos**, e só o segundo conta

Criar o container **não publica**. Publicar é uma segunda chamada (`media_publish`), e é nela
que o limite diário é cobrado.

**A armadilha:** o container é criado, o vídeo sobe, tudo responde sucesso — e o post não existe.
É exatamente o defeito que o produto vende como diferencial, e aqui é fácil cair nele.

**Decisão:** `marcarProcessando` só depois do `media_publish`; antes disso o destino continua
`enviando`.

---

## ⚠️ I-4 — Um reel publicado responde `media_type: VIDEO`

Da documentação: *"se você publicar um reel e depois pedir o campo `media_type`, o valor
retornado é `VIDEO`. Para saber se um vídeo é um reel, peça `media_product_type`."*

Conferir o campo errado faria a conciliação dizer "publicamos vídeo comum" para todo reel.

---

## ⚠️ I-5 — Dois limites diferentes, e a mesma página se contradiz

A página de publicação diz **100 publicações por 24 h** no início e **50** na seção de carrossel.
Além disso: **400 containers** por 24 h (criar container também tem teto).

Como decidir sem chutar: existe `GET /<IG_ID>/content_publishing_limit`, que devolve **o quanto
a conta já usou**. Consultar isso é melhor que confiar em qualquer número da documentação.

**Decisão:** consultar o limite real antes de publicar em lote e usar `aguardando_janela`
(DEC-24) quando não couber. O erro `9 / 2207042` é o mesmo caso vindo pelo outro lado.

---

## ⛔ I-6 — Só JPEG

*"JPEG é o único formato de imagem suportado. Formatos estendidos como MPO e JPS não são
suportados."* PNG não passa — e é o formato que mais sai de captura de tela.

Recusa antes de enviar, com mensagem dizendo qual formato usar.

---

## Especificações do reel (números exatos)

| | |
|---|---|
| Contêiner | **MOV ou MP4** — `moov atom` no início, sem edit lists |
| Duração | **3 segundos a 15 minutos** |
| Tamanho | **300 MB** |
| Taxa de quadros | 23 a 60 FPS |
| Largura máxima | 1920 px |
| Proporção aceita | 0,01:1 a 10:1 — **recomendado 9:16** |
| Taxa do vídeo | VBR, até 25 Mbps |
| Vídeo | H.264 ou HEVC, progressivo, GOP fechado, 4:2:0 |
| Áudio | AAC, até 48 kHz, 128 kbps, mono ou estéreo |

**Aceita MOV** — diferente do Bluesky, que só aceita MP4. O vídeo de iPhone passa aqui.

Capa do reel: JPEG, até 8 MB, sRGB, 9:16 (fora disso a Meta corta o meio).

---

## Erros que viram mensagem em português

Todos com `error_subcode`, que é o que identifica de verdade:

| Subcódigo | O que é | O que dizer |
|---|---|---|
| `2207020` | container expirado | passou de 24 h — vamos enviar de novo |
| `2207026` | formato de vídeo não suportado | use MP4 ou MOV |
| `2207042` | limite diário atingido | aguardando a janela do Instagram |
| `2207050` | conta restrita | entre no app do Instagram e confira a conta |
| `2207051` | suspeita de spam | a rede limitou a atividade da conta |
| `2207009` | proporção inválida | envie o vídeo em 9:16 |
| `2207010` | legenda longa demais | quanto passou e quanto cabe |
| `2207052` | não conseguiu buscar a mídia | o arquivo não estava acessível |
| `2207027` | ainda não está pronto | não é erro — é esperar |
| `2207008` | container ainda não existe | tentar de novo em ~30 s |

⚠️ **`2207027` e `2207008` não são falhas.** Tratar como falha derrubaria envios que iam dar
certo — é o mesmo erro que quase cometi no YouTube com o 5xx.

---

## O que ainda precisa ser confirmado

- **Limite exato da legenda** — a documentação cita o erro (`2207010`) mas não publica o número.
  O valor de 2.200 caracteres é sabido de fora, não da documentação; por isso **não vai virar
  número fixo no código**: a recusa usa o que a rede responder.
- **Limite de @ mencionados**: 20 (erro `2207040`).

_Baixado e lido em 2026-07-31, de `documentacao/`._
