# Instagram — plano de ação

> Leia antes: [`../meta-compartilhado.md`](../meta-compartilhado.md) e [`achados.md`](achados.md).
>
> **A conexão vem pronta do Facebook.** As fases 1 e 2 de
> [`../facebook/plano-de-acao.md`](../facebook/plano-de-acao.md) acendem as duas redes de uma vez
> — aqui começa depois disso. Repetir aquelas fases seria criar duas verdades sobre o mesmo login.

---

## Fase 1 — Publicar o reel

- [ ] **1.1** `PublicadorInstagram` implementando `Publicador`
- [ ] **1.2** Criar o container: `POST /<ig-id>/media`
  - `media_type=REELS`, `upload_type=resumable`, `caption`, `is_ai_generated`
  - guardar o container como `handle_externo` **antes** de subir bytes
- [ ] **1.3** Subir o arquivo em `rupload.facebook.com/ig-api-upload/<container-id>`
- [ ] **1.4** ⛔ **Publicar é o segundo passo** (`media_publish`) — criar container não publica
  - antes disso o destino continua `enviando`, nunca `processando` (I-3)
- [ ] **1.5** ⛔ **Nunca cortar texto.** Recusar com o que sobra
- [ ] **1.6** Container expira em **24 h** — mensagem própria, não "erro desconhecido" (I-2)

## Fase 2 — Conciliar *(o diferencial)*

- [ ] **2.1** `GET /<container-id>?fields=status_code,status`
- [ ] **2.2** Mapear `IN_PROGRESS` / `FINISHED` / `PUBLISHED` / `ERROR` / `EXPIRED`
- [ ] **2.3** Consultar **uma vez por minuto, por até 5 minutos** (recomendação oficial)
  - bem mais curto que as 20 consultas do YouTube
- [ ] **2.4** ⚠️ Ler `media_product_type`, **não** `media_type` — reel responde `VIDEO` (I-4)
- [ ] **2.5** Montar a URL do post como prova

## Fase 3 — ⭐ O aviso de direitos autorais

- [ ] **3.1** Ler `copyright_check_status` junto com o status (I-1)
- [ ] **3.2** `matches_found: true` → avisar **antes** de publicar
- [ ] **3.3** Mostrar como aviso próprio na tela, com a palavra da rede
  - é a tese do produto aplicada antes do fato, não depois

## Fase 4 — Limites

- [ ] **4.1** Duração 3 s a 15 min, 300 MB, 9:16, MP4 **ou MOV**
- [ ] **4.2** ⛔ Imagem **só JPEG** — PNG é recusado (I-6)
- [ ] **4.3** Consultar `content_publishing_limit` antes de publicar em lote
  - o número real da conta vale mais que o da documentação, que se contradiz (I-5)
- [ ] **4.4** Limite atingido → `aguardando_janela` (DEC-24), não erro
- [ ] **4.5** Legenda **sem número fixo** no código — a recusa usa o que a rede responder

## Fase 5 — Revisar e testar

- [ ] **5.1** Reler este plano contra o código
- [ ] **5.2** Guardião `InstagramTest`: container, upload, publicação, conciliação, expiração
- [ ] **5.3** Travar por teste que `media_publish` é obrigatório para marcar publicado
- [ ] **5.4** Conferir no servidor real
- [ ] **5.5** Registrar os achados da revisão em `achados.md`

---

## O que o Gabriel precisa providenciar

Tudo o que está em [`../facebook/plano-de-acao.md`](../facebook/plano-de-acao.md), **mais**:

1. Uma conta do Instagram **profissional** (Comercial ou Criador de Conteúdo) —
   conta pessoal não publica por API
2. Essa conta **vinculada à Página do Facebook**

**Não precisa de Análise do Aplicativo para testar.**

_2026-07-31_
