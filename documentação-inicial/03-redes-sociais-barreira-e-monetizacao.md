# Redes sociais — barreira de integração × monetização

> Comparativo por rede pra decidir a ordem de desenvolvimento, olhando o custo de
> integrar **e** o potencial de ganhar dinheiro. Dados de meados de 2026 — validar
> requisitos na doc oficial antes de agir (mudam com frequência). Fontes no fim.

## Resumo (da menor pra maior fricção de integração)

| Rede | Barreira técnica | Monetização (entrada) | Quanto paga | Ordem sugerida |
|---|---|---|---|---|
| **YouTube** | Média (**2 trâmites Google**: verificação OAuth + auditoria de compliance — sem ela, vídeo fica privado) | 1.000 subs + 4.000h/ano *ou* 10M views Shorts/90d | US$ 2–10 / 1.000 views (nicho tech/finanças até US$ 30) | **1º** |
| **Facebook** | Baixa (Graph API; Reels **3–90s** via API) | **Só por convite** em 2026 (piso: 10.000 seguidores + 5 vídeos/30d) | Programa unificado (Reels/vídeo/foto/texto) | 2º |
| **Instagram** | **Média (caiu!)** — desde 2024 publica **sem Facebook Page** (Instagram Login); review só p/ contas de terceiros | Gifts a partir de **500** seguidores | Gifts US$ 50–500/mês; grosso vem de brand deals | 3º |
| **TikTok** | **Alta** (audit do app ~5–10 dias úteis; sem ele, posts privados) | 10.000 seguidores + 100k views/30d + **vídeos >1 min** | US$ 0,40–1,00 / 1.000 views qualificados | 4º |

---

## 🎥 YouTube — começar por aqui
- **Barreira (integrar) — atualizada (doc 07 §1-2):** quota **deixou de ser problema**
  (`videos.insert` = 1 unit, **100 uploads/dia** em bucket próprio desde 06/2026); upload
  resumável; vídeo processa de forma assíncrona. **A fricção real são 2 trâmites do
  Google:** (a) verificação OAuth do scope sensível `youtube.upload` (domínio + privacy
  policy + vídeo demo, ~10 dias); (b) **auditoria de compliance do projeto de API — sem
  ela, TODO vídeo enviado fica travado como privado.** Iniciar os dois cedo.
- **Monetização (ganhar $):**
  - **Nível 1** (só fan funding — memberships/Super Chat): 500 subs + 3.000h/ano *ou* 3M views Shorts/90d.
  - **Nível 2** (anúncios): **1.000 subs + 4.000h/12 meses** *ou* **10M views Shorts/90d**.
  - Paga **US$ 2–10 por 1.000 views** (YouTube fica com 45%, criador 55%). Nichos como
    tech/finanças chegam a US$ 30/1.000; entretenimento/vlog ~US$ 5. Shorts: US$ 30–200 por 1M views.
  - Saque mensal a partir de US$ 100.
- **Outras infos:** melhor casa pra **vídeo longo + Shorts**; o **nicho** muda muito o pagamento;
  Shorts viraram atalho pra monetizar (10M views/90d).

## 📘 Facebook — 2º
- **Barreira (integrar) — atualizada (doc 07 §4):** baixa — Graph API; Reels de Página via
  `video_reels` (upload direto em 3 fases, **duração 3–90s**, ~30 posts/24h); fotos via
  `/photos`. **Page token de longa duração não expira por tempo** (só por eventos — senha
  trocada, permissão revogada). Publicar em Páginas de **terceiros** exige App Review +
  Business Verification (dias a semanas).
- **Monetização — atualizada:** o **Facebook Content Monetization** (unifica in-stream ads +
  Reels + performance bonus; paga por Reels, vídeos, fotos e texto) segue **SÓ POR CONVITE
  em 2026** — os requisitos (10.000 seguidores + 5 vídeos/30d) são o piso, mas a entrada
  não é automática.
- **Outras infos:** desde jun/2025 todo vídeo no Facebook é exibido como Reel; **novidade
  mar/2026: Creator Fast Track da Meta** paga US$ 1.000–3.000/mês por 3 meses pra quem já
  tem audiência em outra rede e começa a postar na Meta.

## 📸 Instagram — 3º
- **Barreira (integrar) — CAIU (doc 07 §3):** desde 23/07/2024, a **"Instagram API with
  Instagram Login"** publica Reels e fotos em conta profissional **sem Facebook Page**
  (OAuth direto no IG). Continua exigindo conta profissional (conversão grátis); e como
  **toda conta da plataforma é de terceiro (DEC-20)**, **App Review + Business Verification
  são obrigatórios** — não é caso de borda. Limite 100 posts via API/24h;
  Reels 9:16 (3s–15min, ≤300MB); foto **só JPEG** (4:5 a 1.91:1); fluxo de container
  (esperar FINISHED); **mídia precisa estar em URL pública** (a Meta baixa via cURL).
- **Monetização:**
  - **Gifts (Stars):** só **500 seguidores** (barreira baixa!). ~US$ 0,01/Star; US$ 50–500/mês.
  - **Subscriptions:** 10.000+ seguidores; US$ 4,99–49,99/mês, criador fica com 70% (Apple/Google descontam in-app).
  - **Reels Bonus:** só por convite; US$ 100–10.000/mês.
  - **Live Badges:** 10.000+ seguidores.
- **Outras infos:** a plataforma paga pouco — **o dinheiro grande vem de brand deals**
  (parcerias). Bom pra construir audiência que depois fecha parceria. Brasil é elegível.

## 🎵 TikTok — deixar por último
- **Barreira (integrar) — detalhada (doc 07 §5):** **a maior.** A Content Posting API exige
  **auditoria do app (~5–10 dias úteis)**; até passar, **todo post sai privado**
  (SELF_ONLY, máx 5 usuários/24h — só serve pra testar). E a **UX é normatizada e
  auditada**: consultar `creator_info` ao abrir a tela de publicar, privacidade sem valor
  padrão, disclosure comercial e confirmação de música. Duração máxima **varia por criador**
  (3/5/10 min — consultar por conta). Áudio AAC; ≤4GB; rate limit 6 req/min por token.
- **Monetização (Creator Rewards):** **10.000 seguidores + 100.000 views/30d**, 18+, país
  elegível (**Brasil incluído**), conta em bom estado, **vídeos originais de 60s+**, 2FA.
  - Paga **US$ 0,40–1,00 por 1.000 views qualificadas** (views reais de 5s+; vídeo só paga
    após 1.000 qualificadas).
- **Outras infos:** melhor payout por view que o Fund antigo (era US$ 0,02–0,04), mas exige
  vídeos **60s+**; o audit é o gargalo que justifica deixar por último.

---

## Leitura estratégica
- **Ordem YouTube → Facebook → Instagram → TikTok** equilibra fricção de integração e retorno:
  YouTube tem a melhor relação (barreira contornável + bom CPM); Facebook e Instagram
  aproveitam a mesma Graph API (fazer os dois de uma vez rende); TikTok fica por último por
  causa do audit obrigatório.
- **Para a plataforma em si**, a monetização das redes não muda o código — mas orienta o
  cliente sobre **onde publicar** e ajuda a desenhar métricas úteis (views por destino).
- Em todas as redes, **o maior ganho tende a vir de brand deals/audiência**, não do programa
  da plataforma. A ferramenta acelera a distribuição; o dinheiro depende de conteúdo + nicho.

---

## Fontes
- YouTube requisitos/pagamento — https://www.studiobinder.com/blog/youtube-monetization-requirements/ · https://stan.store/blog/how-much-does-youtube-pay/
- TikTok Creator Rewards — https://hellothematic.com/monetize-on-tiktok/ · https://postlinkapp.com/blog/tiktok-creator-rewards-program
- Instagram monetização — https://influencermarketinghub.com/instagram-subscriptions-gifts/ · https://stan.store/blog/how-to-monetize-instagram-2026/
- Facebook Content Monetization — https://creators.facebook.com/introducing-facebook-content-monetization · https://multilogin.com/blog/facebook-content-monetization/

_2026-07-27._
