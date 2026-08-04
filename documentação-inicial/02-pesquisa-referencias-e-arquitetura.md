# Pesquisa — Projetos similares, problemas técnicos e arquitetura

> Levantamento de como ferramentas de publicação multi-rede são construídas, onde
> costumam quebrar, e o que adotar/evitar no nosso MVP. Fontes ao final.

---

## 1. Projetos de referência

### Open-source (pra estudar o código)
- **Mixpost** — Laravel + Vue, licença MIT, self-hosted, 11 redes, contas ilimitadas.
  **É a referência mais próxima do nosso stack** (Laravel). Instalável como pacote
  Laravel, Docker ou VPS. Melhor fonte de código pra copiar padrões.
- **Postiz** — NestJS + Next.js + **Temporal** (orquestração de workflows), AGPL-3.0.
  Muito popular (~33k★) mas stack TypeScript, arquitetura mais pesada. Bom pra ideias
  de UX/features, não de código PHP.
- **TryPost** (artigo dev.to, Laravel) — arquitetura Laravel muito bem documentada;
  a maior parte dos padrões da seção 3 vem dele.

### Comerciais (referência de features/UX)
- Buffer, Hootsuite, Later, Metricool, Publer.
- **"Social API as a service"** (Ayrshare, bundle.social, Outstand): fazem a integração
  com cada rede por você, cobrando por isso. Alternativa se um dia não quisermos manter
  cada integração — mas cobram por post/conta, o que não fecha numa plataforma própria.

---

## 2. Problemas técnicos por rede (na ordem do nosso MVP)

### 🥇 YouTube (primeiro)
- **Quota — RESOLVIDO (verificado na doc oficial, 27/07/2026 · doc 07 §1):** desde
  01/06/2026 o `videos.insert` tem **bucket próprio: 1 unit por chamada, 100 uploads/dia**
  (a redução de ~1600→~100 veio em 04/12/2025; depois virou bucket granular). Os 10.000
  units/dia valem pro restante dos endpoints. Quota deixou de ser gargalo.
- **Upload resumável** obrigatório pra vídeo (init → pega URL → PUT dos bytes → confirma).
  Recupera de queda de rede sem reenviar tudo.
- **Processamento assíncrono:** o vídeo não fica pronto na hora (segundos a minutos).
- **⚠️ Fricção não-técnica (a maior):** o scope `youtube.upload` é **sensível**. App
  **não verificado** pelo Google → **teto de 100 usuários no ciclo de vida** (não reseta)
  + tela de aviso "app não verificado". "Test users" precisam **reautorizar a cada 7 dias**.
  A verificação do Google pede um **vídeo demonstrando o uso do scope**.
  → **Como o projeto é plataforma aberta a clientes (DEC-20), a verificação é obrigatória:**
  cada cliente que conecta consome uma das 100 vagas vitalícias, que não resetam.
- **⚠️ SEGUNDO gate, separado do OAuth (verificado · doc 07 §1):** projeto de API **não
  auditado** pelo YouTube → todo vídeo enviado pelo `videos.insert` fica **travado como
  privado**. A **auditoria de compliance** é obrigatória pra publicar público — iniciar
  cedo, junto com a verificação OAuth.
- Token pode expirar **no meio do upload** — tratar.

### Instagram (depois)
- **🆕 ATUALIZADO (verificado · doc 07 §3):** desde 23/07/2024 existe a **"Instagram API
  with Instagram Login"** — publica Reels e fotos em conta profissional (Business/Creator)
  com OAuth direto no Instagram (`graph.instagram.com`), **SEM Facebook Page**. Scopes:
  `instagram_business_basic` + `instagram_business_content_publish`. A via com Facebook
  Login continua existindo (necessária só p/ ads/tagging). **É a nossa via na Fase 5.**
- Conta **pessoal** continua fora — precisa ser profissional (conversão é grátis).
- **App review + Business Verification** obrigatórios pra publicar em contas de terceiros.
- **A mídia precisa estar em URL pública** na hora de publicar (a Meta faz cURL do arquivo)
  — storage local expõe URL temporária durante o job; túnel no dev.
- Limite: **100 posts via API/24h** por conta (consultável em `content_publishing_limit`).
- Fluxo de **container**: criar → **poll `status_code` até FINISHED** → publish (container
  expira em 24h). Não publicar antes de FINISHED (vídeo ainda encodando).
- Reels: **9:16** (3s–15min, ≤300MB); foto de feed: **só JPEG**, 4:5 a 1.91:1.

### TikTok (depois)
- **Content Posting API** exige **audit do app**. Sem audit (sandbox), **todo post é
  forçado a privado** — dá pra testar, não dá pra publicar público. Audit leva ~1–2 semanas.
- Áudio precisa ser **AAC**.

### Facebook
- **Tokens podem mudar completamente no refresh** — sempre gravar a resposta nova no banco.

---

## 3. Arquitetura — padrões recomendados (adotar)

- **Fila por plataforma** (`social-youtube`, `social-instagram`…): isola a lentidão/erro de
  uma rede sem travar as outras.
- **Idempotência:** o job de publicar vira **no-op se já publicado** — retry nunca duplica.
- **Status por destino (sucesso parcial):** uma publicação pode ir pro YouTube e falhar no
  IG; a UI mostra **por rede** o que precisa de atenção, não um "tudo ou nada".
- **Retry com backoff exponencial + jitter** (especialmente em 429/rate limit).
- **Ciclo de vida do token OAuth:**
  - Guardar **encriptado** (`encrypted` cast do Laravel); **nunca logar**.
  - **Refresh serializado** (`Cache::lock()` — um refresh por token por vez) pra evitar
    corrida entre workers (plataformas rotacionam o refresh token a cada uso).
  - **Lazy refresh** (só após um 401) ou cron ~3 dias antes de expirar.
- **Mídia:** validar com **FFmpeg** (formato/codec/aspect ratio) **antes** de enviar à API;
  **poll do status até READY** antes de publicar.
- **Atomic-claim** pra agendamento: `UPDATE ... WHERE status='Scheduled'` → `'Publishing'`;
  só o processo cujo update afetou 1 linha dispara o job. Exactly-once **sem lock distribuído**.
- **Abstração de plataforma:** `enum Platform` → classe **Publisher** por rede com interface
  única `publish(destino): {id, url}`. Validação (limites de caracteres, mídia) por rede.

---

## 4. Anti-patterns (o que NÃO fazer)

- ❌ Publicar **logo após finalizar o upload** (o vídeo ainda está encodando) → falha.
- ❌ Assumir que o **token não muda** no refresh (Facebook muda).
- ❌ Consultar **analytics ao vivo** no dashboard (rate limit) → usar snapshot noturno.
- ❌ Pedir **scopes OAuth amplos** demais.
- ❌ Ignorar **requisitos de mídia por rede** (codec, aspect ratio).
- ❌ Não tratar o **handshake de verificação de webhook** (falha silenciosa).
- ❌ **Over-engineering:** o TryPost tem `Account → Workspace` pra multi-tenant/billing.
  **Nós não precisamos disso:** cada cliente é um `usuario` com isolamento lógico (DEC-01/02)
  — workspaces e cobrança são fase futura, não MVP.

---

## 5. Recomendações pro nosso MVP

1. **Stack já está certo** (Laravel + Inertia + React). Estudar o **Mixpost** como
   referência de código (mesmo stack).
2. **Modelagem** que já temos (contas, tokens OAuth, publicações, destinos, tentativas,
   mídia) está alinhada. Garantir: **destino com status próprio** + **registro de tentativas**.
3. **Publicação:** 1 job por destino, **fila por plataforma**, **idempotente**, retry backoff.
4. **OAuth:** `encrypted` cast + `Cache::lock` no refresh desde o começo.
5. **YouTube primeiro:** resolver a **verificação/publicação do app no Google Cloud** logo —
   é a maior fricção não-técnica (o código é a parte fácil).
6. **NÃO** construir agora: workspaces, billing, multi-tenant. Fica pra fase SaaS.

---

## Fontes
- Mixpost vs Postiz — https://openalternative.co/compare/mixpost/vs/postiz · https://postiz.com/compare/postiz/mixpost
- Mixpost (open-source Laravel) — https://mixpost.app/blog/why-open-source-social-media-management-tools-are-perfect-for-startups
- Arquitetura de publicação multi-rede — https://bundle.social/blog/social-media-api-architecture-guide
- Scheduler em Laravel (TryPost) — https://dev.to/paulocastellano/how-i-built-an-open-source-social-media-scheduler-with-laravel-3g5k
- YouTube Data API — upload — https://bundle.social/blog/youtube-api-upload-guide · quota — https://www.getphyllo.com/post/youtube-api-limits-how-to-calculate-api-usage-cost-and-fix-exceeded-api-quota
- Verificação de app Google/OAuth — https://support.google.com/cloud/answer/7454865 · https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification
- Instagram/TikTok APIs — https://www.getphyllo.com/post/instagram-api-vs-tiktok-api-vs-linkedin-api-which-should-you-integrate-first · https://www.netrows.com/blog/tiktok-content-posting-api-guide-2026

_2026-07-27 — pesquisa inicial. Atualizado no mesmo dia com a varredura profunda verificada
(**doc 07**): quota YouTube resolvida, auditoria de compliance do YouTube, Instagram API
with Instagram Login, URL pública p/ mídia no IG, e specs por rede._
