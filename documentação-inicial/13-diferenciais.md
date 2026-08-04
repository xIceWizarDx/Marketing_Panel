# DIFERENCIAIS — o que faz o produto valer mais de R$ 30

> Pesquisa de 28/07/2026 em reviews, Reclame Aqui, boards de feature request, issues do GitHub
> e docs de suporte dos próprios concorrentes (onde eles confessam as limitações).
> **22 diferenciais fortes · 15 promissores · 8 já são commodity.**

---

## 🎯 A TESE: "o painel que PROVA que publicou"

**A descoberta técnica que sustenta tudo:** quando o painel diz "publicado", ele está mentindo —
e **por design**, não por bug.

O TikTok e o YouTube aceitam o upload de forma **assíncrona**. A doc do TikTok é literal:
*"developers are not provided with the post_id until this process is complete"* — a moderação
roda **depois** do aceite. No YouTube, o status vai para `rejected`/`failed` **depois** de um
upload bem-sucedido.

**Ou seja:** todo painel que marca "publicado" ao receber HTTP 200 está marcando **"aceito para
processamento"**. E nenhum dos nove concorrentes comparados (mLabs, Buffer, Later, Publer,
Metricool, Hootsuite, SocialBee, Postiz, Blotato) **relê o post pra confirmar**.

Pior: no Metricool, *"o status 'enviado' NÃO significa publicado"* — é a notificação a caminho.
mLabs e Buffer usam o mesmo vocabulário otimista. **O falso positivo está no dicionário do
produto.**

**A frase de venda:** *"se falhar, a gente conserta antes de você perceber — e te mostra o link
provando que subiu."*

---

## 🥇 Bloco 1 — Prova de entrega (o coração)

| # | Diferencial | Quem já faz | Esforço |
|---|---|---|---|
| 1 | **Verificação pós-publicação** — relê o post na rede, confirma que existe e guarda o **permalink como prova** | **Ninguém** (dos 9 comparados) | médio |
| 2 | **Status honesto** — Agendado · Enviado · Processando · **NO AR (com link)** · Falhou (motivo em português). Proibido dizer "publicado" sem link verificado | **Ninguém** — todos escolheram o oposto | **baixo** |
| 3 | **Monitor proativo de token** — semáforo por conta + aviso **7 dias antes** de vencer (Meta vence em 60 dias) | **Ninguém** — todos só reagem depois da falha | **baixo** |
| 4 | **Relatório de prova de entrega** (white-label) — PDF mensal por marca, com cada post, horário real e **link vivo** | Todos fazem relatório de **métricas**; **ninguém** faz de **prova** | médio |
| 5 | **SLA com crédito automático** — post que não foi ao ar e não foi avisado vira crédito na fatura | **Ninguém** no segmento | baixo |

> **Por que isso vende:** a agência não compra economia de tempo — compra **não perder cliente**.
> Existe relato em português de cancelamento com a frase *"isso me fez perder clientes"*.
> E o item 4 é o único que a agência **repassa no preço dela**.

## 🥈 Bloco 2 — Vídeo sem degradação

| # | Diferencial | Quem já faz | Esforço |
|---|---|---|---|
| 6 | **Pré-voo de mídia** — valida codec, resolução, duração e bitrate contra a regra de **cada rede** no upload, não na hora do disparo | Ninguém previne — o Buffer joga a responsabilidade no usuário | médio |
| 7 | **Aceitar vídeo de iPhone (HEVC)** sem conversão manual | ⚠️ **Buffer e Metricool RECUSAM** — mas a Meta **aceita oficialmente** | médio |
| 8 | **Passthrough inteligente** — só recomprime o que está fora da spec, e só a faixa problemática | Ninguém. O Upload-Post vende o oposto (transcodifica tudo) | médio |
| 9 | **Laudo transparente** — mostra o arquivo que entrou, o que foi enviado e o que a rede devolveu | **Ninguém** — todos transcodificam em silêncio | baixo |

> **A confissão que abre a brecha:** o Buffer admite na própria doc — *"There is no setting to
> turn transcoding off"* — e o teto dele é 1080p, então **4K vertical é rebaixado sem avisar**.
> Do outro lado, a mLabs não converte nada e simplesmente **rejeita**. O espaço vazio é o
> meio-termo.
>
> ⚠️ **Honestidade:** qualidade de vídeo **sozinha não vende** — é invisível até comparar lado a
> lado. Vale como reforço, não como bandeira.

## 🥉 Bloco 3 — Vantagens que só um brasileiro tem

| # | Diferencial | Por quê |
|---|---|---|
| 10 | **WhatsApp** para alerta de falha, token vencendo e **aprovação do cliente** | **Nenhum concorrente global faz** — o mercado deles não usa WhatsApp assim. É a vantagem estrutural mais barata que existe |
| 11 | **Stories do Instagram realmente automático** | ⚠️ A **mLabs descontinuou** o automático e empurrou todo mundo pro modo "notificado" (abrir o app no horário). **Há cancelamento documentado no Reclame Aqui por isso** — e a API do Instagram **suporta** Stories automático em conta Business |

## 💰 Bloco 4 — O modelo de negócio com maior teto

**12. Vender a publicação como API, em português, com nota fiscal e Pix**

Para quem monta automação em n8n/Make e hoje só tem opção gringa: Ayrshare, Blotato,
upload-post, bundle.social — **todas em inglês, em dólar, sem nota fiscal**.

Referência de preço do mercado: **US$ 149/mês por perfil**. Cobrando **1/5 disso em reais**,
ainda é **10× o R$ 30** do mercado brasileiro. E roda em cima do **mesmo motor** de publicação.

⚠️ **Verificar antes:** a política do YouTube proíbe sublicenciar acesso à API — precisa checar
se esse modelo é permitido pra rede deles.

---

## ❌ Commodity — não perca tempo

- **Alerta de falha por e-mail** — Publer, Metricool e GoHighLevel já têm (o Metricool nem
  deixa desligar)
- Relatório de **métricas** (alcance, engajamento) — todo mundo tem
- Agendamento básico, calendário, fila de horários
- Sugestão de hashtag por IA

## 🚫 Não prometa o que a API não permite

- **Editar post publicado** — impossível em **todas** as redes
- **Figurinha, enquete ou link em Stories** · **música em Reels** · carrossel acima de 10 ·
  thumbnail custom em Shorts — nada disso existe via API
- ⚠️ **"Não perde alcance"** é mito — o próprio Mosseri (Instagram) desmentiu

---

## ✅ Recomendação

**Comece pelos 4 de esforço BAIXO** — juntos já formam o posicionamento:

1. **Status honesto** (#2) — decisão de produto, quase sem código
2. **Monitor de token** (#3) — um cron diário + semáforo
3. **Laudo de mídia** (#9) — `ffprobe` + tabela por rede
4. **WhatsApp** (#10) — alerta e aprovação

Depois: **verificação pós-publicação** (#1) e **relatório de prova** (#4) — que é o que a
agência paga.

**Preço sustentável:** R$ 49–79 por marca (vs. R$ 29,90 da mLabs), porque o cliente não está
comprando conveniência — está comprando **a eliminação de um risco que já custou cliente a ele**.

_2026-07-28 — 5 frentes de pesquisa, com citações de reviews e docs oficiais dos concorrentes._
