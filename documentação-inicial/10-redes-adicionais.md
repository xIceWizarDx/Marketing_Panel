# REDES ADICIONAIS — mapa de barreira

> As 4 redes do plano (YouTube, Facebook, Instagram, TikTok) exigem auditorias de **semanas a
> meses**. Este documento mapeia as **outras 11** por barreira de entrada, pra responder:
> **existe rede que publica de verdade sem esperar ninguém?**
> **Resposta: existe — três delas.** Pesquisa de 28/07/2026 nas docs oficiais.

---

## 🏆 A descoberta que muda o plano

**Dá pra ter o produto publicando de verdade em DIAS, não meses.**

**Bluesky** e **LinkedIn (perfil)** não têm processo de aprovação nenhum: sem formulário, sem
auditoria, sem espera, sem custo. Publicam **vídeo vertical 9:16 e imagem**. Isso significa:

- O motor de publicação (fila, retry, status, agendamento) fica **testado em produção real**
  enquanto as filas do Google, da Meta e do TikTok correm em paralelo.
- Você **vê o produto funcionando em semanas**, não em meses — que era o maior risco do projeto.
- Quando as auditorias saírem, é só plugar as redes grandes numa base já rodada e validada.

**E a segunda descoberta:** **Threads pega carona** no mesmo App Review da Meta que já vamos
fazer pro Instagram e Facebook. Mesmo app, mesma verificação de empresa, mesma leva de
screencasts. Custo marginal **quase zero** — e é a rede com os limites mais generosos.

---

## 📊 Mapa por barreira

| Rede | Barreira | Vídeo 9:16 | Imagem | Custo | Veredito |
|---|---|---|---|---|---|
| **LinkedIn (perfil)** | 🟢 **nenhuma** | ✅ | ✅ | grátis | ⭐ **fazer já** |
| **Bluesky** | 🟢 **nenhuma** | ✅ | ✅ | grátis | ⭐ **fazer já** |
| Discord | 🟢 nenhuma | ✅ | ✅ | grátis | depois (pouco alcance) |
| **X (Twitter)** | 🟡 baixa | ✅ (máx 2min20) | ✅ | 💲 **pago por post** | avaliar |
| Mastodon | 🟡 baixa | ✅ | ✅ | grátis | depois (federado = N servidores) |
| **Threads** | 🟠 média (**carona da Meta**) | ✅ 5min/1GB | ✅ | grátis | ⭐ **junto com IG/FB** |
| **Pinterest** | 🟠 média | ✅ **nativo 9:16** | ✅ | grátis | depois (bom encaixe) |
| LinkedIn (Página) | 🔴 alta | ✅ | ✅ | grátis | depois (é onde está o valor) |
| Snapchat | 🔴 alta | ✅ (Spotlight) | limitado | grátis | só com contato interno |
| Google Business | 🔴 alta | ❌ **não aceita vídeo** | ✅ | grátis | talvez |
| Reddit | 🔴 alta | ✅ | ✅ | free tier proíbe uso comercial | ❌ não |
| Slack | 🟡 baixa | ✅ | ✅ | grátis | ❌ não (é chat interno) |

---

## 🟢 Barreira ZERO — publica esta semana

### LinkedIn (perfil pessoal)
- **Aprovação: nenhuma.** A permissão de publicar é classificada como **"Open Permission"** —
  self-service no portal, disponível em **minutos**. Sem formulário, sem CNPJ, sem screencast.
- **Vídeo:** MP4, 3s a 30min, até 500 MB, **9:16 aceito** (a API tem campo de proporção).
- **Imagem:** JPG, PNG, GIF.
- **Ressalva honesta:** publica no **perfil pessoal**, não na Página da empresa (essa é alta
  barreira). E LinkedIn não é rede de vídeo curto — o público é B2B.
- **Por que vale mesmo assim:** o código de upload é **100% reaproveitado** quando a Página for
  liberada. Não é trabalho jogado fora.

### Bluesky
- **Aprovação: nenhuma.** Não existe app review, formulário, programa de desenvolvedor nem
  tier de acesso. Publica em minutos.
- **Vídeo:** até 3 min / ~100 MB, MP4/WebM/MOV. **9:16 nativo** (envia-se a proporção e o app
  renderiza vertical).
- **Imagem:** até 4 por post, com texto alternativo. ⚠️ **Limite apertado: ~1 MB por imagem** —
  precisa comprimir antes.
- **Custo:** zero.

### Discord (barreira zero, mas alcance baixo)
- Publica por **webhook, sem nem precisar de OAuth** — o cliente cria o webhook e cola a URL.
- **Limite de 10 MB** por mensagem (sobe com boost do servidor).
- **Ressalva:** não tem feed nem descoberta — é notificação pra comunidade, não alcance de
  marketing. Fica pra depois.

---

## 🟡 Barreira baixa

### X (Twitter) — sem aprovação, mas **pago**
- **Aprovação: nenhuma** pra publicar (só conta de desenvolvedor + mudar o app para
  leitura+escrita).
- ⚠️ **Não é grátis:** **US$ 0,015 por post** — e **US$ 0,20 se o post tiver link** (13× mais).
  500 posts/mês ≈ US$ 7,50; com link em todos ≈ US$ 100.
- **Vídeo: máximo 2min20** em conta comum (3 min só com Premium).
- **Decisão:** o custo por post **precisa entrar na conta do plano** se for revender.

### Mastodon
- Aberto, sem aprovação — **mas é federado**: você não integra "o Mastodon", integra **N
  servidores**, cada um com credencial, limite e política própria.
- Imagem até 16 MB (bem mais folgado que Bluesky); vídeo até 99 MB.

---

## 🟠 Barreira média

### Threads — ⭐ carona da Meta
- **É a melhor relação custo/benefício do conjunto.** Exige App Review da Meta — **mas é o
  mesmo app, a mesma verificação de empresa e a mesma leva de screencasts** do Instagram e
  Facebook. Submetendo junto, o custo marginal é quase zero.
- **Tecnicamente é a mais barata de implementar:** o fluxo (criar container → aguardar →
  publicar) é **idêntico ao do Instagram** — reaproveita o código.
- **Limites mais generosos de todas:** vídeo até **5 minutos / 1 GB**, e a doc oficial
  **recomenda 9:16**. Imagem até 8 MB. Carrossel de 10.
- **Limite:** 250 posts/24h por perfil.
- ⚠️ **Antes da aprovação já dá pra construir e testar 100%** (publica na sua conta e nas
  contas convidadas como testadoras).
- **Decisão: submeter Threads no MESMO pacote de App Review do IG/FB.**

### Pinterest
- **Encaixe de formato é o melhor de todos:** Pinterest é **nativamente vertical**, e
  1080×1920 (9:16) é exatamente o que o painel já vai produzir — **o mesmo arquivo serve sem
  reconversão**.
- Vídeo de 4s a 5 min, até 2 GB. Imagem até 20 MB. API REST limpa, mais simples que Meta/TikTok.
- **Barreira:** dois portões (acesso trial → acesso padrão), com relatos de apps travados na
  fila. Exige conta Business.

---

## 🔴 Barreira alta ou não vale

| Rede | Problema |
|---|---|
| **LinkedIn (Página)** | Exige pessoa jurídica, e-mail corporativo, razão social conferida — **semanas a meses**. É onde está o valor comercial, mas não é vitória rápida. |
| **Snapchat** | O formato é perfeito (Spotlight = vídeo vertical), mas o acesso é **allowlist**: depende de **conseguir um contato dentro da Snap**. Não dá pra "aplicar e esperar". |
| **Google Business** | ⚠️ **A API rejeita vídeo** ("Video uploaded from post is not currently supported"). Só imagem. Formato errado pro nosso produto. |
| **Reddit** | O free tier **proíbe explicitamente uso comercial**, e desde 2025 todo token novo passa por aprovação manual. Barreira igual às grandes, com retorno menor. |
| **Slack** | É ferramenta de **comunicação interna** — sem audiência pública, sem alcance, e no plano grátis o arquivo **some em 90 dias**. Não é canal de publicação. |

---

## ✅ Recomendação

1. **Fase piloto (dias, não meses):** **Bluesky + LinkedIn perfil.** Zero burocracia, publica
   de verdade, valida o motor inteiro.
2. **Junto com a Meta:** **Threads** no mesmo App Review (custo marginal ~zero).
3. **Em paralelo:** as filas de YouTube / Meta / TikTok correndo.
4. **Depois, por ordem de valor:** Pinterest → LinkedIn Página → X (se o custo por post fechar).
5. **Fora:** Slack, Reddit, Google Business (não aceita vídeo).

_2026-07-28 — 12 redes avaliadas nas documentações oficiais de desenvolvedor._
