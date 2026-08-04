# Facebook — plano de ação

> Leia antes: [`../meta-compartilhado.md`](../meta-compartilhado.md) e [`achados.md`](achados.md).
>
> **A conexão é compartilhada com o Instagram.** As fases 1 e 2 acendem as duas redes de uma vez;
> só a fase 3 em diante é específica do Facebook.

---

## Fase 1 — A conexão com a Meta *(vale para as duas redes)*

- [ ] **1.1** `ConexaoComMeta` — o fluxo de autorização
  - permissões: `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`,
    `instagram_basic`, `instagram_content_publish`
  - ⚠️ conferir os escopos **concedidos**, não os pedidos — mesma lição do YouTube (R-2)
- [ ] **1.2** Trocar o token curto pelo longo **na hora da conexão**
  - `GET oauth/access_token?grant_type=fb_exchange_token`
  - ⚠️ token expirado não pode ser trocado: deixar para depois é perder a conexão
- [ ] **1.3** Buscar as Páginas (`GET /me/accounts`) e guardar o **token da Página**
  - é o que não expira; o de usuário é descartável depois disso
- [ ] **1.4** Uma conta social **por Página**, não por pessoa
  - quem administra três Páginas conecta uma vez e escolhe onde publicar
- [ ] **1.5** Sem nenhuma Página → mensagem explicando que o Facebook só publica em Página (F-2)
- [ ] **1.6** Descobrir a conta do Instagram ligada à Página
  (`GET /<page-id>?fields=instagram_business_account`)
  - é isso que faz **uma conexão acender as duas redes**

## Fase 2 — Fundação compartilhada

- [ ] **2.1** `ErroDaMeta` — interpretar `code` + `error_subcode` + `is_transient`
  - `is_transient` decide entre `devolverParaFila` e `marcarFalha`, sem adivinhação
  - guardar `fbtrace_id` em `tentativas` (é o que o suporte da Meta pede)
- [ ] **2.2** `EnvioRetomavelDaMeta` — `offset` / `file_size` / `Authorization: OAuth`
  - reaproveita o contrato `Retomada` do motor, que já existe
- [ ] **2.3** Conferir `moov atom` no início via ffprobe, e recusar com motivo claro
  - é o `-movflags +faststart`; sem isso a rede recusa sem dizer por quê

## Fase 3 — Publicar o reel

- [ ] **3.1** `PublicadorFacebook` implementando `Publicador`
- [ ] **3.2** Fase `start` → guardar `video_id` como `handle_externo` **antes** de subir bytes
  - a mesma trava anti-duplicata do YouTube
- [ ] **3.3** Fase de upload, com retomada por `upload_phase.bytes_transfered`
- [ ] **3.4** Fase `finish` com `video_state: PUBLISHED`
  - ⚠️ `finish`, não `complete` — a documentação se contradiz, o exemplo manda (F-3)
- [ ] **3.5** ⛔ **Nunca cortar texto.** Recusar com o que sobra, como no YouTube
- [ ] **3.6** Declarar conteúdo feito por IA, escolha da pessoa

## Fase 4 — Conciliar *(o diferencial)*

- [ ] **4.1** `GET /<video-id>?fields=status`
- [ ] **4.2** ⛔ Só `publishing_phase.publish_status == published` marca publicado
  - `processing_phase: completed` **não** é publicado (F-4)
- [ ] **4.3** Erro em qualquer fase → mensagem da fase certa, em português
- [ ] **4.4** Montar a URL do post como prova

## Fase 5 — Limites e agendamento

- [ ] **5.1** Duração **90 s** em `EspecificacaoDaRede`, recusando antes de enviar (F-1)
- [ ] **5.2** 9:16, mínimo 540×960, 24-60 FPS fixo, áudio estéreo
- [ ] **5.3** Limite de 30/24 h → `aguardando_janela`, não erro
- [ ] **5.4** Agendamento: validar 10 min a 29 dias **antes** de enviar (F-5)

## Fase 6 — Revisar e testar

- [ ] **6.1** Reler este plano contra o código, como foi feito no YouTube
- [ ] **6.2** Guardião `FacebookTest` com as 3 fases, retomada e conciliação
- [ ] **6.3** Conferir no servidor real, com a credencial de verdade
- [ ] **6.4** Registrar os achados da revisão em `achados.md`

---

## O que o Gabriel precisa providenciar

1. **Uma Página do Facebook** (pode ser nova, para teste)
2. **Um aplicativo** em <https://developers.facebook.com/apps> — tipo **Empresa**
3. **Papel de administrador** no próprio app *(é o que dispensa a análise)*
4. Produtos **Login do Facebook** e **API do Instagram** adicionados
5. Endereço de retorno: `http://localhost:8000/conexoes/meta/retorno`

**Não precisa de Análise do Aplicativo para testar.**

_2026-07-31_
