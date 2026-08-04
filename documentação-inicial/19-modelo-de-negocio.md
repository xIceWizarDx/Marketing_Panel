# MODELO DE NEGÓCIO — o molde

> **O documento mais importante da pasta.** Amarra: dor validada → nossa proposta →
> posicionamento → preço → quem compra. Tudo o que for construído responde a este documento.
> Evidências em [`20-evidencias-do-mercado.md`](20-evidencias-do-mercado.md).

---

## 🎯 Em uma frase

> **O painel que publica vídeo curto em várias redes — e PROVA que publicou.**

---

## 1. A dor (validada, não suposta)

Quem publica vídeo curto vertical em várias contas enfrenta **quatro problemas** que **nenhuma
ferramenta do mercado resolve**:

| # | Dor | Evidência |
|---|---|---|
| 1 | **"Publicado" é mentira** — o painel diz que foi, e não foi | TikTok e YouTube moderam **depois** do aceite; nenhum dos 9 concorrentes relê o post |
| 2 | **A conexão quebra em silêncio** — descobre semanas depois | agência com 37 contas, 9 falhando sem aviso; bundle.social tem botão de "conferir" manual |
| 3 | **O vídeo é degradado ou recusado** | 4 fontes: Later, Buffer (doc oficial), Metricool/Hootsuite, TikTok nativo |
| 4 | **Shorts é mal suportado por todos** | *"sem miniatura, sem descrição, sem tags — já falei três vezes"* |

**O tamanho do buraco:** a melhor solução que o mercado oferece hoje **ainda é parcialmente
manual** — a equipe que testou as três voltou a agendar Shorts pelo app do YouTube.

---

## 2. A proposta

**Não somos "mais um agendador". Vendemos entrega comprovada.**

| Prometemos | Como cumprimos |
|---|---|
| **"Se publicou, tem link"** | conciliação relê o post na rede e grava o permalink como prova (DEC-31) |
| **"Se vai quebrar, você sabe antes"** | semáforo de token + aviso 7 dias antes de vencer (DEC-32) |
| **"Seu vídeo não estraga em silêncio"** | laudo antes de agendar + só recomprime o necessário + aceita HEVC do iPhone (DEC-33) |
| **"A gente te avisa onde você lê"** | falha e token vencendo chegam no **WhatsApp** (DEC-32) |
| **"A gente não pede o que não precisa"** | escopo mínimo + explicação em português do que cada permissão faz (DEC-41) |

### O que NÃO prometemos
Miniatura e tags em Shorts · editar post publicado · figurinha e enquete em Stories · música em
Reels — **limites reais da API**. Dizemos **antes** de agendar, não depois.

---

## 3. Posicionamento

**Contra o mercado:**
- Eles vendem **conveniência** (poste em várias redes de uma vez) — commodity, preço R$ 25–55
- Nós vendemos **risco eliminado** (o post foi, e aqui está a prova) — justifica preço maior

**A frase de venda:**
> *"Se falhar, a gente conserta antes de você perceber — e te mostra o link provando que subiu."*

**O que nos protege de cópia:** copiar isso exige **admitir que mentiam** e **reescrever o
motor** (conciliação, máquina de estados, watchdog). Não é um botão a mais.

---

## 4. Quem compra

| Perfil | Por que sofre | Por que paga |
|---|---|---|
| **Social media com vários clientes** | gerencia N contas, precisa **provar serviço** | uma publicação perdida custa mais que um ano de assinatura |
| **Agência pequena** | idem, com equipe | relatório de prova é o que **repassa ao cliente dela** |
| **Criador com várias contas** | volume alto, vive de alcance | falha = dinheiro perdido direto |

**O gatilho de compra:** já perdeu (ou quase perdeu) cliente por post que não subiu.
*(Relato documentado em português: "isso me fez perder clientes".)*

---

## 5. Preço

**Referência do mercado:** R$ 25–55 por conta/mês *(mLabs R$ 29,90 · Buffer US$ 6 · Publer US$ 5–10)*

**Nossa faixa-alvo: R$ 49–79 por marca** — acima do mercado, porque não vende conveniência.

**Uma oportunidade anotada:** todo mundo cobra **por canal** ou **por assento**, e isso **pune
quem cresce** (caso extremo: US$ 20 mil no Zoho por precisar de 300 contas). **Preço que não
pune crescimento** é diferencial comercial real.
*(Decisão de preço só quando houver produto rodando.)*

---

## 6. O caminho

| Fase | O que é | Depende de aprovação? |
|---|---|---|
| **Agora** | uso próprio: Instagram + Facebook + Bluesky | ❌ não |
| **Depois** | YouTube (auditoria em paralelo) · TikTok (testado em privado) | parcial |
| **Quando decidir** | virar produto: cadastro aberto, planos, cobrança | ✅ sim |

**O princípio:** cada passo entrega valor **sem depender do seguinte**. Se uma auditoria travar
5 meses, o resto continua funcionando.

---

## 7. Os riscos que assumimos

| Risco | Mitigação |
|---|---|
| Mercado saturado, preço no chão | não competir por preço nem por número de redes — só por confiabilidade |
| Auditorias podem travar meses | fases que não dependem delas primeiro |
| Plataforma pode cortar acesso (aconteceu com Etus e mLabs em 2021) | não depender de uma só rede; nunca violar política |
| Dev solo, tempo limitado | escopo travado; nada de recurso que não sirva à promessa |
| Concorrente copiar | exige reescrever o motor e admitir a falha — não é trivial |

---

## 8. Como decidir se algo entra no produto

**Três perguntas. Se alguma falhar, não entra:**

1. **Serve à promessa?** — publicar com prova, avisar antes de quebrar, não estragar o vídeo
2. **Cabe num dev solo?** — construir e **manter**
3. **Sobrevive à auditoria?** — não viola política de nenhuma plataforma

> ✅ Exemplo aprovado: alerta de token vencendo — serve à promessa, é barato, é legítimo.
> ❌ Exemplo reprovado: painel de Meta Ads — não serve à promessa, exige 2 aprovações novas,
> e concorre com o Looker Studio grátis.

_2026-07-30 — molde do negócio. Evidências no doc 20; decisões no doc 05._
