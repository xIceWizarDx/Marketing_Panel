# PESQUISA PROFUNDA 2026 — fatos verificados nas docs oficiais

> Varredura de 27/07/2026: 15 agentes de pesquisa em 10 dimensões + verificação dos achados
> críticos direto nas documentações oficiais (Google/Meta/TikTok/Laravel). 107 achados;
> os que **mudam decisão** estão aqui com fonte. Este doc alimenta o plano (05), os
> requisitos (01) e os docs de redes (03/04).

---

## ⚡ O QUE MUDA NO PROJETO (resumo executivo)

1. **Laravel 13 (não 12).** Laravel 13 saiu em 17/03/2026; o 12 perde bug fixes em
   **13/08/2026** (daqui a ~2 semanas). Projeto novo nasce no 13 — upgrade trivial. → DEC-06.
2. **YouTube: quota deixou de ser problema.** `videos.insert` agora custa **1 unit** em
   bucket próprio com **100 uploads/dia** (era 1600 units ≈ 6/dia). Verificado na doc oficial.
3. **YouTube: 2 gates externos, não 1.** Além da verificação OAuth (scope sensível), existe a
   **auditoria de compliance do projeto de API** — sem ela, TODO vídeo enviado fica **travado
   como privado**. São processos separados. → Fase 3.
4. **Instagram SEM Facebook Page.** Desde 23/07/2024 existe a **"Instagram API with Instagram
   Login"**: publica Reels e fotos em conta profissional com OAuth direto no Instagram
   (graph.instagram.com), sem Page vinculada. → simplifica a Fase 5.
5. **Instagram exige URL pública da mídia.** A Meta faz cURL do arquivo — storage local puro
   não basta na hora de publicar no IG: o app precisa expor a mídia por **URL pública
   temporária** (e o dev local precisa de túnel p/ testar). → DEC-07 complementada.
6. **Imagens: escopo real.** YouTube **não tem endpoint de imagem**; IG só aceita **JPEG**
   (PNG não publica) e **rejeita 9:16 no feed** (vertical máx 4:5); TikTok tem photo post
   (até 35 imagens) mas só via URL de **domínio verificado**. → imagem = FB + IG (+ TikTok
   depois); YouTube é só vídeo. → requisitos.
7. **Duração: NÃO travar em 60s.** Facebook Reels via API = **3–90s** (o teto do "upload
   único nas 4"); YouTube Shorts = até **3 min**; TikTok paga só vídeo **>1 min**. Sweet spot
   de monetização: **61–180s** (sem Facebook) ou 3–90s (com as 4). → validação por destino.
8. **TikTok: audit é bloqueante e a UX é normatizada.** App não auditado só posta privado
   (SELF_ONLY, máx 5 usuários/24h); audit ~5–10 dias úteis. E a UI de publicar TikTok é
   obrigatória por contrato: `creator_info` antes da tela, privacidade sem default,
   disclosure comercial, confirmação de música. → Fase 6.
9. **Radar de tendências (Fase 7): fontes reais.** YouTube matou a página Trending
   (21/07/2025); `chart=mostPopular` hoje = charts de Música/Filmes/Gaming. **Google Trends
   API oficial em alpha** (aplicar acesso JÁ). TikTok Creative Center = só navegador; Meta =
   nada comercial. Radar legal = YouTube API + Google Trends API + hashtag search IG +
   métricas das próprias contas.
10. **Sistemas de produto:** healthcheck da fila + notificações (mail+sininho) + polling
    Inertia + backup entram no **MVP**; Web Push + PWA viram o 1º pacote pós-MVP. (Seção 9.)

---

## 1. YouTube (Fase 3) — verificado ✅

- **Quota (doc oficial, atualizada 01/06/2026):** `videos.insert` = bucket próprio, **1 unit
  por chamada, 100/dia** default; `search.list` idem (100/dia); resto compartilha 10.000
  units/dia. Cronologia: custo caiu ~1600→~100 em 04/12/2025; buckets granulares em
  01/06/2026. [determine_quota_cost](https://developers.google.com/youtube/v3/determine_quota_cost) · [revision_history](https://developers.google.com/youtube/v3/revision_history)
- **⚠️ Auditoria de compliance (VERIFICADO):** "All videos uploaded via the videos.insert
  endpoint from unverified API projects created after 28 July 2020 will be restricted to
  private viewing mode." Auditoria à parte da verificação OAuth. [videos/insert](https://developers.google.com/youtube/v3/docs/videos/insert)
- **Short em 2026** = vídeo **≤3 min** com aspecto quadrado/vertical (regra 15/10/2024);
  não existe flag "isShort" na API — a classificação é automática.
- **Limites:** título 100 chars; descrição 5000 bytes. `selfDeclaredMadeForKids` e
  `categoryId` **não são obrigatórios no insert** (só no update) — mas enviar madeForKids
  sempre (COPPA).
- **Agendamento nativo:** `status.publishAt` + `privacyStatus=private` — o YouTube publica
  sozinho na hora marcada (útil pro nosso futuro agendamento).
- **Upload resumável:** chunks múltiplos de 256KB, retomada via HTTP 308; status pós-upload
  via `videos.list` (`uploadStatus`/`processingStatus`) a 1 unit.

## 2. OAuth Google (Fases 0 e 3) — verificado ✅

- `youtube.upload` é scope **SENSITIVE** (não restricted). Verificação exige: homepage em
  domínio verificado (Search Console), privacy policy no domínio, vídeo demo do fluxo OAuth;
  prazo oficial até ~10 dias. [sensitive-scope-verification](https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification)
- App External em **Testing**: refresh token **expira em 7 dias** + máx 100 test users.
  Produção **não verificada**: cap vitalício de 100 usuários + tela de aviso.
  **⚠️ Como o projeto é plataforma aberta (DEC-20), a verificação é OBRIGATÓRIA** — cada
  cliente que conecta o YouTube consome uma das 100 vagas vitalícias (não resetam).
- **Socialite:** `->with(['access_type' => 'offline', 'prompt' => 'consent'])` para garantir
  `refresh_token`; tratar `invalid_grant` como "reconectar conta".

## 3. Instagram (Fase 5) — verificado ✅

- **Instagram API with Instagram Login** (23/07/2024): OAuth direto no IG, base
  `graph.instagram.com`, scopes `instagram_business_basic` +
  `instagram_business_content_publish`. **"This API setup does not require a Facebook Page"**
  (doc oficial). A via com Facebook Login continua existindo (necessária p/ ads/tagging). [doc](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/)
- **Fluxo de container** (igual ao previsto): `POST /media` → poll `status_code` até
  `FINISHED` → `media_publish`. Container expira em 24h. Limite **100 posts via API/24h**
  (consultável em `content_publishing_limit`).
- **⚠️ URL pública (VERIFICADO):** "We cURL media used in publishing attempts, so the media
  must be hosted on a publicly accessible server". Upload direto (resumable) só existe na
  variante Facebook Login for Business. [content-publishing](https://developers.facebook.com/docs/instagram-platform/content-publishing/)
- **Specs:** Reels 3s–15min, ≤300MB, 9:16; **fotos só JPEG** (PNG/WebP não publicam), feed
  vertical máx **4:5**, ≤8MB, largura ≤1440px.
- **Servir clientes** (contas de terceiros) exige **App Review + Business Verification**.

## 4. Facebook (Fase 5)

- **Reels em Página:** `POST /{page-id}/video_reels` em 3 fases (start/upload/finish via
  `rupload.facebook.com` — upload direto, **sem** exigir URL pública). Duração **3–90s**;
  ~30 posts/24h. Fotos: `POST /{page-id}/photos`.
- **Permissões:** `pages_manage_posts` + `pages_show_list` + `pages_read_engagement`.
- **Page token de longa duração não expira por tempo** (só por eventos: senha trocada,
  permissão revogada…) — sem refresh agendado pro Facebook.
- **Advanced Access** (Páginas de terceiros) = App Review com screencast + Business
  Verification — lead time de dias a semanas, absorver antes do go-live da Fase 5.
- Desde jun/2025 todo vídeo no Facebook é exibido como Reel. Graph API atual: v25.0.
- **Não existe crosspost IG↔FB via API** — valida nosso design de 1 job por destino.

## 5. TikTok (Fase 6)

- **Dois fluxos:** Direct Post (`video.publish`) e Upload/Inbox (`video.upload`, vira draft
  no app, máx 5 pendentes/24h).
- **⚠️ Audit bloqueante:** não auditado = só SELF_ONLY (privado) p/ máx 5 usuários/24h.
  Pedir via Manage Apps; ~5–10 dias úteis.
- **UX obrigatória (auditada!):** chamar `creator_info/query` ao renderizar a tela de
  publicação; seletor de privacidade **sem valor default**; disclosure comercial (Your
  Brand/Branded Content); Music Usage Confirmation. → a tela do TikTok tem elementos
  específicos obrigatórios (nosso módulo por rede absorve isso).
- **Vídeo:** MP4/WebM/MOV (H.264 recomendado), ≤4GB, chunks 5–64MB, **duração máxima
  dinâmica por criador** (`creator_info.max_video_post_duration_sec` — validar por conta,
  não por constante). Rate limit 6 req/min por token.
- **Fotos:** até 35 imagens (20MB cada, WebP/JPEG), mas **só PULL_FROM_URL de domínio
  verificado** — fica pra quando houver host público.

## 6. Perfil canônico de mídia (validação do upload — Fase 2)

**Vídeo que passa nas 4 redes:** MP4 · H.264 + AAC-LC 48kHz estéreo · 1080×1920 (9:16) ·
moov atom no início · closed GOP · 4:2:0 · fps fixo 24–60 · **3–90s** (teto = Facebook) ·
**≤300MB** (teto = Instagram).
**Vídeo 91–180s:** passa em YouTube/IG/TikTok*, **Facebook fica indisponível** (mostrar isso
na UI por destino). *TikTok: conferir teto do criador via `creator_info`.
**Imagem:** **JPEG** (formato universal — IG rejeita PNG) · IG feed: 4:5 a 1.91:1, ≤8MB,
largura ≤1440 · **YouTube não recebe imagem** · TikTok foto = só com domínio verificado.

## 7. Radar de tendências (Fase 7) — fontes legais reais

- **YouTube:** página Trending morta (21/07/2025); `videos.list chart=mostPopular` segue
  funcional (regionCode/categoria) mas hoje reflete charts de Música/Filmes/Gaming.
- **Google Trends API oficial** — alpha desde 24/07/2025 (ainda alpha em 07/2026), 5 anos de
  dados, acesso por aplicação. **Ação: aplicar pro alpha desde já.**
- **TikTok Creative Center:** só navegador (sem API); Research API **vedada a uso comercial**.
- **Meta:** Content Library só p/ acadêmicos/ONGs. IG Hashtag Search: 30 hashtags/7 dias.
- **Conclusão:** radar oficial-sem-scraping = YouTube Data API + Google Trends API +
  hashtag search IG pontual + métricas das próprias contas conectadas.

## 8. Stack Laravel — o que usar no motor

- **Laravel 13** (17/03/2026; L12 perde bug fixes 13/08/2026). PHP 8.3+. Ganha
  `Queue::route()` (roteamento central de fila por job).
- **Middlewares de job nativos:** `RateLimited`, `WithoutOverlapping`, `ThrottlesExceptions`;
  `ShouldBeUnique` com atomic locks funciona com cache **database/file** (Redis não é
  necessário no MVP); backoff progressivo nativo; filas nomeadas com prioridade no worker.
- **Socialite:** Google e Facebook nativos, `Socialite::fake()` oficial pra testes; TikTok =
  provider comunitário `socialiteproviders/tiktok` (funcional, sem release desde 04/2024 —
  avaliar na Fase 6).
- **google/apiclient** ativo (v2.19.4, 06/2026): upload resumável via
  `Google\Http\MediaFileUpload` + `setDefer` + `nextChunk`.
- **Mixpost:** referência do padrão SocialProvider + pipeline de fila, mas a edição
  open-source NÃO traz YouTube/TikTok/Instagram (só no Pro pago) — referência de arquitetura,
  não de código de integração.

## 9. Notificações & sistemas de produto (pesquisa dedicada)

**Entram no MVP (baratos, alto retorno):**
- **Healthcheck da fila** — o risco nº 1 do produto ("publicamos em segundo plano" + worker
  morto em silêncio = falha total). Supervisor com autorestart + `spatie/laravel-health`
  `QueueCheck` (job-sentinela por minuto; sem processar em 5 min → alerta por e-mail).
  Horizon NÃO serve (só Redis).
- **Notificações Laravel** (canais `mail` + `database`): classe única
  `PublicacaoConcluida`/`PublicacaoFalhou` → e-mail + sininho no painel; na fase futura o
  canal webpush entra **sem reescrever nada**.
- **Status ao vivo via `usePoll`** (Inertia v2): polling 3–5s com reload parcial na tela da
  publicação; reduz 90% em aba background. **Reverb adiado indefinidamente** (daemon +
  proxy + restart por deploy — peso sem retorno pro nosso caso).
- **Backup automático** (`spatie/laravel-backup`): SQLite + mídia local num servidor só —
  zipar banco + `storage/app` pra fora da máquina. ~1h de setup.
- **De graça no starter:** rate limit de login (Fortify, 5/min), **2FA TOTP** (entrou no
  starter oficial em out/2025), dark mode.

**Fase futura (1º pacote pós-MVP):**
- **Web Push + PWA instalável** juntos (iPhone só recebe push com PWA instalada — iOS 16.4+):
  `laravel-notification-channels/webpush` v10.4.0 (mantido, compatível) + VAPID self-hosted +
  `vite-plugin-pwa`. Suporte global ~95%. Payload já compatível com Declarative Web Push
  (Safari 18.4+).
- **"Cancelar antes de publicar":** publicar com delay de ~60s + botão cancelar (job
  idempotente checa status antes de postar) — o único "desfazer" real; pós-publicação não
  existe undo.
- **Export de dados (LGPD):** `spatie/laravel-personal-data-export` quando houver volume.

**Descartados:** i18n (produto PT-BR); activity log genérico (nossas `tentativas` já são o
histórico que importa); Reverb; OneSignal/SaaS de push (dependência externa desnecessária).

## 10. Monetização — o que mudou (docs 03/04)

- **Números-base seguem valendo** (YT 1.000 subs + 4.000h ou 10M Shorts/90d; TikTok 10k +
  100k views/30d, ativo no Brasil; IG Gifts 500 seguidores).
- **Duração paga:** TikTok Creator Rewards só monetiza vídeo **>1 min**; YouTube monetiza
  Shorts até 3 min → recomendar **61–180s** pra quem quer monetizar (e por isso o validador
  não trava em 60s).
- **Facebook Content Monetization:** segue **só por convite** em 2026 — não prometer entrada
  automática por métrica.
- **Novidades:** Meta **Creator Fast Track** (mar/2026): US$ 1.000–3.000/mês por 3 meses p/
  quem já tem audiência em outra rede; política YouTube de "conteúdo inautêntico" (jul/2025)
  pune publicação massificada — reforça nosso princípio de conteúdo original; desde mar/2025
  o contador público de views de Shorts difere das "engaged views" usadas pra monetização.

---

## Rastreabilidade
Íntegra dos 107 achados (JSON): resultado do workflow de pesquisa de 27/07/2026 (15 agentes,
6 críticos verificados — todos confirmados). Fontes principais citadas inline acima; docs
oficiais: developers.google.com · developers.facebook.com · developers.tiktok.com ·
laravel.com · webkit.org · inertiajs.com · spatie.be.

_2026-07-27._
