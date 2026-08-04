# MERCADO — a realidade, sem otimismo

> Pesquisa de 28/07/2026 em 4 frentes (concorrentes BR, reclamações reais, nichos, casos de
> dev solo). Instrução dada aos pesquisadores: **ser brutalmente honesto**.
> **Este documento existe para evitar 6 meses de trabalho num negócio que não fecha.**

---

## 🔴 VEREDITO: não construa "mais um mLabs"

O mercado brasileiro de agendamento está **saturado, consolidado e com preço destruído**.
Entrar como "mais um publicador multi-rede" é suicídio comercial. Os números:

| Concorrente | Preço | Escala |
|---|---|---|
| **mLabs** | **R$ 49,90/mês** por perfil (R$ 34,90 no anual) — já conecta 8 redes | 145–230 mil clientes · nota **4,7/5** no Capterra |
| **KingHost** (absorveu o Etus) | **R$ 15,90/mês** | — |
| **Postgrain** | R$ 19,90 | "Não Recomendada" no Reclame Aqui |
| **Postiz** (open-source) | **grátis**, roda em VPS de US$ 5 | 29,6 mil estrelas · #1 do Product Hunt em mai/2026 |

**Você não tem espaço pra competir por preço** (o piso é R$ 15,90) **nem por marca** (o líder
tem nota 4,7 — não há revolta que facilite a troca).

## 🔴 O mercado está FECHANDO, não abrindo

- **Etus** (fundada 2015, ~100 mil marcas) — comprada pela Locaweb em 2020, **dissolvida dentro
  do KingHost em nov/2025**. Os domínios nem resolvem mais.
- **DashGoo** (42 mil clientes) — engolida pela mLabs em 2021.
- **Scup** — vendida à Sprinklr, carteira repassada adiante.
- **RD Station Social** — não existe mais como produto; virou funcionalidade.
- **Nenhum novo entrante brasileiro relevante em 2025–2026.**

Padrão: independente não sobrevive sozinho — vira add-on de hospedagem ou linha de produto de
alguém maior.

## 💀 O risco existencial (o mais importante deste documento)

**Em 2021 o Facebook cortou o acesso da Etus e da mLabs — de uma tarde pra outra.**
A Etus tinha **340 mil contas conectadas**, incluindo SBT, Globosat e Arezzo. A mLabs, 300 mil.

**As duas sobreviveram porque tinham caixa e advogados. Um dev solo não sobrevive a isso.**

Some-se: o **App Review da Meta piorou** — era de 1 a 3 dias até 2025, hoje chega a **20 dias**,
porque a Meta está sendo inundada de apps gerados por IA. *O perfil que está sendo barrado na
porta é exatamente o nosso.*

## 🟢 MAS: existe uma dor real que NINGUÉM resolveu

A reclamação **número 1**, que aparece em mLabs, Etus, Metricool, Hootsuite, Adobe e outras:

> **A publicação falha, o painel mostra "publicado", e ninguém avisa.**

Casos documentados:
- Agência com **37 contas conectadas → 9 falhando sem nenhuma notificação**.
- Na Etus: **3 semanas** com posts marcados como publicados que nunca foram ao ar.
- Depoimento literal de usuário: *"perdi um cliente por causa disso"*.

**Nenhum player grande trata publicação como sistema observável** — com alerta de falha,
tentativa automática e prova de entrega.

**Outras dores confirmadas:**
- **Qualidade de vídeo**: a mLabs **degrada o vídeo** (usuários relatam que não sai em Full HD
  e precisam postar manualmente). Vídeo de iPhone (HEVC) falha em silêncio em várias.
- **Suporte**: mLabs responde em **6 dias e 10 horas** em média, e **não atende fim de semana**.

## 🎯 O ângulo que sobra (o único defensável)

**Não é "agendador mais bonito". É confiabilidade auditável, vendida caro para poucos.**

- **Alerta imediato** quando falha · **tentativa automática** · **prova de publicação com o
  link do post** · **verificação de token antes do horário agendado**
- **Vídeo sem recompressão** — entrega na qualidade original
- **Suporte humano rápido em português**, inclusive fim de semana

**Modelo:** R$ 150–300/mês para **dezenas** de clientes que sofrem com isso — **não** R$ 30
para milhares. Um dev solo cabe nesse negócio; competir com a mLabs não cabe.

> ⚠️ **Coincidência importante:** o plano que já desenhamos **é exatamente isso**. Status por
> destino, tentativa com espera, watchdog de travado, healthcheck da fila, aviso por e-mail,
> link de prova. Construímos a resposta à dor nº 1 sem saber que era a dor nº 1.

## ⚠️ Alertas de execução

1. **Submeter as auditorias ANTES de escrever tela.** O cronograma **delas** é o caminho
   crítico, não o seu código.
2. **Se um portão reprovar, o negócio não existe** — valide os três (Meta, TikTok, YouTube)
   antes de investir meses.
3. **O nicho 9:16 já tem concorrente:** Blotato (US$ 29), OpusClip (US$ 15–29), Repurpose.io,
   Postiz (grátis). Não é território virgem.
4. **Teto duro do YouTube:** 100 uploads/dia **por projeto** — para toda a sua base junta.

## ✅ Caminho de menor risco

**Comece por Bluesky + LinkedIn + Threads** (doc 10): sem porteiro, valida demanda em semanas
enquanto as auditorias tramitam. Mas seja honesto: **o dinheiro está no Instagram e no TikTok
— e é lá que estão os porteiros.**

_2026-07-28._
