# Threads — plano de ação

> As fases vivem em [`../../documentação-inicial/21-plano-meta.md`](../../documentação-inicial/21-plano-meta.md),
> junto com Facebook e Instagram, porque as três dependem do mesmo aplicativo Meta e a ordem entre
> elas importa. Aqui fica só o que é **desta rede**.
>
> Os achados que geraram as decisões estão em [`achados.md`](achados.md); a documentação copiada,
> em [`documentacao/`](documentacao/).

---

## O que muda em relação às outras duas da Meta

| | Facebook / Instagram | Threads |
|---|---|---|
| Autorização | Login do Facebook | `threads.net/oauth/authorize` |
| Servidor | `graph.facebook.com` | `graph.threads.net` |
| Permissões | `instagram_*`, `pages_*` | `threads_basic`, `threads_content_publish` |
| Mídia | arquivo direto (`rupload`) | **só URL pública** |
| Token | de Página, não expira | 60 dias, renovação obrigatória |

⛔ **Conectar o Instagram não acende o Threads.** É outra conexão, outro token, outra linha em
`contas_sociais`.

---

## As três coisas que mais podem dar errado

**1. O token morre de vez.** Renovação só entre 24 horas e 60 dias de idade. Passou dos 60 sem
renovar, não há renovação possível — só reconectar. É a única rede do produto com morte definitiva
por inatividade.

**2. O endereço da mídia vaza.** O Threads obriga a expor o arquivo para a Meta buscar. A URL é do
**envio**, não da mídia: assinada, curta, expira em minutos. Um endereço permanente para o arquivo
de quem paga é vazamento com data marcada.

**3. A legenda estoura sem parecer.** 500 **bytes**, não caracteres. Dez emojis comem 40 bytes, e
com modificador de tom de pele passam de 80.

---

## Especificações, para o laudo de mídia

| | |
|---|---|
| Vídeo | MOV ou MP4 · H.264 ou HEVC · **até 5 min** · **até 1 GB** · 23-60 FPS · largura ≤ 1920 px |
| Áudio | AAC, até 48 kHz, 1-2 canais, 128 kbps |
| Proporção | 0,01:1 a 10:1 — 9:16 recomendado |
| Imagem | **JPEG ou PNG** · até 8 MB · largura 320 a 1440 px · sRGB |
| Texto | **500 bytes UTF-8** |
| Publicações | 250 por 24 h |

⭐ **Aceita PNG**, o Instagram não. Mesmo arquivo, respostas diferentes — é o que o laudo de mídia
existe para dizer antes do envio.

⚠️ **Aceita 5 minutos**, mais que o Facebook (90 s) e o YouTube Shorts (3 min). O corte que o
Facebook recusa pode passar aqui.
