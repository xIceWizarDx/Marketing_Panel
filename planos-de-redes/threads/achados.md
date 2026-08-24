# Threads — achados da documentação oficial

> Lido antes de escrever qualquer linha, como manda a regra. O que está em
> [`../meta-compartilhado.md`](../meta-compartilhado.md) vale para Facebook e Instagram —
> **e quase nada dele vale aqui**, que é o primeiro achado.

---

## ⛔ T-1 — A carona da DEC-30 é menor do que parecia

A DEC-30 dizia que o Threads *"pega carona no mesmo App Review do Instagram e do Facebook (mesmo
app, mesma verificação; **fluxo idêntico ao IG**)"*. A parte do fluxo está errada.

| | Facebook / Instagram | **Threads** |
|---|---|---|
| Janela de autorização | Login do Facebook | **`threads.net/oauth/authorize`** |
| Servidor da API | `graph.facebook.com` | **`graph.threads.net`** |
| Permissões | `instagram_*`, `pages_*` | **`threads_*`** |
| Envio de mídia | arquivo direto (`rupload`) | **só URL pública** |
| Token | de Página, **não expira** | 60 dias, **renovação obrigatória** |

**O que sobra de carona:** o mesmo aplicativo Meta (acrescentando o caso de uso do Threads), a
mesma conta de desenvolvedor e, provavelmente, a mesma submissão de análise.

**O que não é carona:** o código. Conectar o Instagram **não acende** o Threads — é outra conexão,
outro token, outra linha em `contas_sociais`.

**Decisão:** o `PublicadorThreads` não herda nada do publicador da Meta. Compartilha o formato de
erro (que é o mesmo Graph) e mais nada.

---

## ⛔ T-2 — Não existe upload de arquivo. Só URL pública

`video_url` e `image_url` recebem **um endereço que a Meta vai buscar**. Não há `rupload`, não há
envio em pedaços, não há retomada.

⚠️ **E isso corrige outro documento nosso.** O `CLAUDE.md` descreve a URL pública temporária como
*"buraco que abrimos de propósito p/ Instagram e TikTok"* — mas o Instagram **não usa**: a escolha
do Login do Facebook deu upload direto justamente para evitar expor o arquivo
(ver `../meta-compartilhado.md`). **O Threads é a primeira rede do produto que realmente precisa
desse buraco.**

**Decisão:** a URL temporária nasce agora, com as quatro regras que o contrato já exige —
**assinada, curta, imprevisível, expira, e serve só o arquivo**. E ela é criada **por envio**, viva
o tempo do envio, nunca um endereço permanente do arquivo do cliente.

⛔ **Consequência dura:** enquanto o produto rodar em máquina local, o Threads **não publica**. A
Meta precisa alcançar o endereço pela internet, e ela não enxerga `localhost`. Isso não é
configuração — é a mesma dependência de servidor do ffmpeg e das métricas.

---

## ⚠️ T-3 — O token expira de vez se ninguém renovar

| | |
|---|---|
| Código de autorização | 1 hora, **uso único** |
| Token curto | 1 hora |
| Token longo | **60 dias** |
| Renovação | volta a valer 60 dias |

E a renovação tem janela: o token precisa ter **pelo menos 24 horas** e não estar vencido.
*"Tokens que não forem renovados em 60 dias expiram e não podem mais ser renovados."*

⛔ **Não existe token perpétuo aqui**, ao contrário do token de Página do Facebook. Sem uma rotina
de renovação, toda conta do Threads morre em 60 dias — e morre de vez.

**Decisão:** a renovação entra no comando diário que já existe, com a mesma folga do
`youtube:reconferir` (mexer antes do prazo, não em cima dele). O semáforo (DEC-32) passa a valer
para o Threads com um prazo real, não estimado.

---

## ⚠️ T-4 — Esperar 30 segundos antes de publicar

*"Recomenda-se esperar em média 30 segundos antes de publicar um contêiner do Threads, para dar ao
nosso servidor tempo suficiente de processar o upload."*

É o mesmo desenho de dois passos do Instagram (contêiner → publicar), com uma espera declarada.

**Decisão:** o motor **não dorme 30 segundos** segurando um worker. O destino vai para
`processando` e o segundo passo acontece na próxima passada — a espera vira agendamento, não
bloqueio.

---

## Os números

| | |
|---|---|
| Texto | **500 caracteres** — emoji conta como **bytes UTF-8**, não como caractere |
| Publicações | **250 por 24 h** |
| Vídeo | MOV ou MP4 · H.264 ou HEVC · **até 5 min** · **até 1 GB** · 23-60 FPS · largura máx. 1920 |
| Imagem | **JPEG ou PNG** · até 8 MB · largura 320 a 1440 px |
| Links | só em post de texto, até 5 |

⚠️ **O emoji contado em bytes é armadilha silenciosa.** Um emoji comum ocupa 4 bytes, e um com
modificador de tom de pele passa de 8. Uma legenda de 480 "caracteres" com dez emojis estoura os
500 sem parecer que estourou. A contagem do compositor precisa ser em bytes para esta rede.

⭐ **Aceita PNG** — o Instagram só aceita JPEG. Mesmo arquivo, redes diferentes, respostas
diferentes: é exatamente o que o laudo de mídia existe para dizer antes do envio.

---

## O que ainda precisa ser confirmado

- **Se a análise do aplicativo é a mesma submissão** de IG/FB ou uma separada dentro do mesmo app.
  A documentação diz "caso de uso do Threads", o que sugere separada — mas não afirma.
- **Métricas** (`threads_manage_insights`): existe o escopo, os campos não foram consultados.
- **Se conta do Threads exige conta do Instagram vinculada.** A documentação consultada não diz.

_Baixado e lido em 2026-08-06, de `documentacao/`._
