# Levantamento de Requisitos

> **Documento vivo.** Objetivo: alinhar O QUE vamos construir antes de escrever código.
> Plataforma de publicação em redes sociais, com painel de admin e painel de cliente.
> O que estiver com ❓ é **decisão pendente sua** — a gente preenche junto.
> _Enriquecido com pesquisa de ferramentas reais (fontes no fim)._

---

## 1. Visão

Plataforma com **dois painéis (admin + cliente)** para: **subir um vídeo curto uma vez e
publicar em várias redes de uma vez**, acompanhando o que deu certo e o que falhou.
Cada cliente enxerga **só os próprios dados**; o admin gerencia e pode **impersonar** um
cliente pra dar suporte.

**Futuro possível:** virar produto pago (SaaS). As entidades já nascem separadas — mas
**sem construir andaime de cobrança agora** (nada de planos, faturamento ou multiempresa).
> Decisões travadas em `05-plano-de-acao.md` (DEC-01 a DEC-19). Este documento descreve
> **o que** o produto faz; o plano descreve **como** e em que ordem.

### Princípio do MVP (pra não inflar)
Um MVP bom tem **UMA função central** que resolve a dor principal — aqui:
**"subir um vídeo curto ou uma imagem e publicá-lo nas redes escolhidas"**. Todo o resto
(calendário, agendamento, IA, métricas) é **melhoria posterior**, não bloqueia o lançamento.

### Foco de conteúdo (TRAVADO)
- **MVP:** **shorts / vídeos curtos verticais (9:16)** e **imagens**. Só isso.
- **Depois (atualização futura):** outros formatos — vídeo longo, carrossel, texto.
- **Redes-alvo (vídeo):** **YouTube Shorts, TikTok, Instagram Reels, Facebook Reels**.
- **Redes-alvo (imagem — realidade das APIs, pesquisa 27/07 · doc 07):** **Facebook +
  Instagram** (feed, **JPEG**, vertical máx 4:5). **YouTube não recebe imagem via API**;
  TikTok tem post de foto mas exige domínio verificado — fica pra depois.
- **Visão do produto:** motor de **"tendência → conteúdo curto → publicação multi-rede"**.

---

## 2. Escopo — priorização MoSCoW

### 🟢 MUST — sem isso o produto não existe
- [ ] **Login com dois papéis** (admin e cliente) + cadastro do cliente (e-mail confirmado
      ou Google) + **admin cria cliente e impersona** (DEC-02/03/04/05).
- [ ] **Isolamento:** cada cliente vê e mexe só no que é dele (DEC-01).
- [ ] **Aviso do resultado:** e-mail + sininho no painel quando a publicação termina (DEC-19).
- [ ] Conectar contas das redes (guardar a conexão com segurança).
- [ ] Upload de **um vídeo** + título + legenda + hashtags.
- [ ] Escolher em quais contas/redes publicar.
- [ ] Botão **"Publicar"** → publica em todas as escolhidas.
- [ ] Processamento **em fila** (não trava a tela; publica em segundo plano).
- [ ] **Status por destino**: mostrar, por rede, se **publicou** (com link) ou **falhou**
      (com o motivo). Reprocessar o que falhou.

### 🟡 SHOULD — importante, mas o MVP roda sem no dia 1
- [ ] **Pré-visualização** de como o post fica em cada rede antes de publicar.
- [ ] **Personalização por rede** (legenda/hashtags diferentes por destino — ver seção 6).
- [ ] **Agendamento** (publicar em data/hora futura).
- [ ] Rascunhos (salvar sem publicar).

### 🔵 COULD — legal ter se sobrar tempo
- [ ] Calendário visual de conteúdo.
- [ ] Fila de horários fixos ("joga o próximo post na próxima vaga").
- [ ] Sugestão de melhor horário pra postar.
- [ ] Sugestão de legenda/hashtags por IA.
- [ ] Métricas básicas (views/curtidas por post) — via snapshot, não ao vivo.
- [ ] Publicação em massa (vários vídeos de uma vez).

### 🔴 WON'T (agora) — adiado de propósito
- **Outros formatos:** vídeo longo, carrossel, texto puro (ficam pra atualização futura).
- Planos / cobrança / limites de uso.
- Multiempresa / multi-tenant / workspaces.
- Equipe dentro da conta do cliente (vários usuários, permissões, aprovação).
- Caixa de entrada unificada (responder comentários/DMs).
- **Web push** (aviso no celular com o site fechado) — 1º pacote pós-MVP, junto com PWA
  (DEC-19). *E-mail + sininho ficam no MVP.*

---

## 3. Ordem de desenvolvimento (uma rede por vez)

1. **YouTube** — primeiro e sozinho, pra validar o fluxo ponta a ponta.
2. **Facebook** — barreira baixa e compartilha a base técnica com o Instagram.
3. **Instagram** — aproveita o trabalho do Facebook.
4. **TikTok** — por último (exige auditoria do app, ~1–2 semanas).

> Regra: **não** integrar todas ao mesmo tempo. Detalhes de barreira e monetização de cada
> rede em `03-...` (técnico) e `04-...` (guia leigo).

---

## 4. Requisitos funcionais

### 4.1 Contas / Conexão
- [ ] Conectar cada conta via login oficial da rede.
- [ ] Ver status da conta (conectada / expirada / com erro / desconectada) e reconectar.
- [ ] **Desconectar conta** — revoga o token na rede e mantém o histórico do que já foi
      publicado por ela (a conta fica `desconectada`, não é apagada).
- ✅ **DECIDIDO (DEC-10):** **várias contas da mesma rede** (ex.: 2 canais no YouTube).

### 4.2 Mídia (o vídeo)
- [ ] Upload de vídeo + guardar metadados (nome, tamanho, duração, formato, proporção).
- [ ] Validar pelo **perfil canônico** (doc 07 §6): MP4 H.264+AAC 9:16, **3–180s**, ≤300MB;
      imagem **JPEG**. Aviso por destino: ≤90s = todas as redes; 91–180s = sem Facebook;
      >60s = elegível a monetizar no TikTok.
- ✅ **DECIDIDO:** vídeo curto (9:16) + imagem JPEG no MVP (carrossel/texto ficam pra depois).
- ✅ **DECIDIDO (DEC-07):** disco local — **mas** o Instagram baixa a mídia via URL pública
      no momento de publicar (a Meta faz cURL): o app expõe URL pública temporária durante o
      job; no dev local, túnel pra testar IG.
- ✅ **DECIDIDO:** limites = 3–180s e ≤300MB (tetos reais das APIs, doc 07 §6).

### 4.3 Publicação
- [ ] Criar publicação: vídeo + título + legenda + hashtags.
- [ ] Escolher as contas de destino.
- [ ] **Pré-visualizar** por rede (Should).
- [ ] Publicar → dispara os jobs por destino.
- [ ] **Personalização por rede** (Should) — ver seção 6.
- ✅ **DECIDIDO:** MVP = publicação **imediata**; agendamento é fase futura (05 Fase 8).
- ❓ **Primeiro comentário automático** (colocar hashtags/link no 1º comentário)? Muito usado.

### 4.4 Fila e histórico
- [ ] Cada destino vira um job na fila (processa em segundo plano).
- [ ] Registrar resultado por destino: **sucesso** (link do post publicado) ou **erro** (motivo).
- [ ] **Reprocessar** um destino que falhou.
- ✅ **DECIDIDO:** guardar **todas as tentativas** (tabela `tentativas` — glossário §6).

---

## 5. Requisitos não-funcionais
- **Dois papéis** (admin/cliente) com isolamento por cliente (DEC-01/02).
- **Rodar em:** ❓ máquina local, ou servidor (qual)?
- **Fila:** um "trabalhador" processando as publicações — ❓ rodando sempre, ou sob demanda?
- **Segurança:** conexões das redes guardadas de forma protegida; segredos fora do repositório
  (já garantido).
- **Simplicidade acima de tudo:** nada de camada que não sirva ao MVP.

---

## 6. Formato do conteúdo (MVP: 9:16 + imagem)

**No MVP o formato é único: vertical 9:16** (shorts) — o mesmo vídeo serve pra YouTube Shorts,
TikTok e Reels **sem adaptação**, o que simplifica tudo. **Imagens** vão pro feed do
Instagram/Facebook. A tabela abaixo fica de referência pra quando adicionarmos outros
formatos no futuro.

| Rede | Proporção | Limites via API (verificados 27/07/2026 — doc 07 §6) |
|---|---|---|
| YouTube Shorts | 9:16 (ou 1:1) | **até 3 min** conta como Short; título 100 chars; sem imagem via API |
| Instagram Reels | 9:16 | 3s–15min, ≤300MB |
| Instagram Feed (foto) | 4:5 a 1.91:1 | **só JPEG**, ≤8MB, largura ≤1440 (9:16 é rejeitado no feed) |
| Facebook Reels | 9:16 | **3–90s** (o teto mais apertado), ~30 posts/24h |
| Facebook (foto) | livre | via `/photos` da Página |
| TikTok | 9:16 | duração máx **dinâmica por criador** (3/5/10 min — consultar por conta); ≤4GB |

**Personalização por rede:**
- **Legenda** pode variar por destino (limites de caracteres diferentes).
- **Capa (thumbnail)** pode ser diferente (16:9 no YouTube, 4:5 no Instagram).
- **Hashtags** e **primeiro comentário** podem ser específicos.
- ✅ **DECIDIDO (DEC-11):** o banco já guarda legenda/hashtags **por destino**; a tela começa
  "igual pra todas" + botão de personalizar.
- ✅ **DECIDIDO (DEC-09):** o app **valida e recusa**, **não converte** — o cliente sobe já
  em 9:16 (conversão automática é fase futura).

---

## 7. Modelagem de dados (entidades já separadas)

| Entidade | Papel |
|---|---|
| **usuários** | o dono (login) |
| **contas sociais** | cada conta conectada (rede, nome, status) |
| **credenciais** | dados de conexão da conta (guardados com segurança, com validade/renovação) |
| **publicações** | vídeo + título + legenda + hashtags |
| **destinos** | publicação × conta (1 destino por rede escolhida, **com status próprio**) |
| **tentativas de envio** | resultado de cada tentativa (sucesso/erro + motivo) |
| **mídia** | o arquivo de vídeo e seus metadados |

> A casca antiga do projeto (caminho no README) já tinha uma boa versão dessas tabelas — vale usar
> de referência, adaptando ao que decidirmos aqui.

---

## 8. Perguntas em aberto (pra você responder)

1. ✅ **DECIDIDO:** MVP = **shorts (9:16) + imagens**. Outros formatos ficam pra depois.
2. ✅ **DECIDIDO:** publicação **imediata** no MVP; agendamento é futuro.
3. ✅ **DECIDIDO (DEC-11):** legenda **por destino** no banco; tela começa igual pra todas.
4. ✅ **DECIDIDO (DEC-09):** o app **não converte** — valida e recusa o incompatível.
5. ✅ **DECIDIDO (DEC-21):** dev local; **produção em host público com domínio próprio** —
   exigência dos trâmites (site + política de privacidade) e do Instagram (baixa a mídia por
   URL pública). Vídeos em disco do servidor, com URL temporária na hora de publicar.
   *(Falta só escolher onde hospedar.)*
6. ✅ **DECIDIDO (DEC-10):** **várias contas** da mesma rede.
7. ❓ Quer **primeiro comentário automático** (hashtags/link)?
8. 🔴 **EM ABERTO.** Pro YouTube: criar o acesso de desenvolvedor no Google. São **DOIS
   trâmites** (doc 07 §1-2): a **verificação OAuth** E a **auditoria de compliance** do
   projeto — sem a auditoria, todo vídeo enviado fica preso como **privado**. Os dois exigem
   a integração **já funcionando** (pedem vídeo demonstrando o uso), então a ordem real é:
   integrar → submeter → esperar (~10 dias).
9. ✅ **DECIDIDO (DEC-20): PLATAFORMA ABERTA a clientes** — ver §10.

---

## 10. Modelo do produto: PLATAFORMA ABERTA (DEC-20, travada em 28/07/2026)

Qualquer pessoa se cadastra e conecta **as próprias contas** de rede social. Isso define os
trâmites obrigatórios — **todos no caminho crítico, medidos em semanas**:

| Trâmite | Por quê | Precisa do código pronto? |
|---|---|---|
| **Domínio + site + política de privacidade + termos** | exigido por Google, Meta e LGPD | ❌ **começar hoje** |
| **Verificação da empresa na Meta** (documentos/CNPJ) | publicar em conta de terceiro | ❌ **começar hoje** |
| **Projeto Google Cloud + verificação de domínio** | base de tudo | ❌ **começar hoje** |
| **Aumento de quota do YouTube** | 100 uploads/dia é **por projeto**, somando todos os clientes | ❌ **começar hoje** |
| **Verificação OAuth do Google** | remove o teto de 100 usuários vitalícios | ✅ pede vídeo demonstrando |
| **Auditoria de compliance do YouTube** | sem ela, todo vídeo fica **privado** | ✅ |
| **App Review da Meta** | permissões de publicar | ✅ pede gravação da tela |
| **Audit do TikTok** | sem ele, todo post sai privado | ✅ audita a tela pronta |

**Por que não dava pra ficar no meio:** com cadastro aberto, cada cliente que conecta o
YouTube gasta uma das **100 vagas vitalícias** de app não verificado — no cliente 101,
ninguém mais conecta, sem volta.

---

## 8.1 Requisitos futuros — automação (só quando o Gabriel pedir)

> **Ordem definida:** a plataforma nasce **nos parâmetros ideais** — conforme, aprovável,
> vendável. As automações abaixo são **atualizações posteriores**, ligadas **por decisão dele**,
> nunca automaticamente. Ficam registradas agora só pra não perder o raciocínio.

### 8.1.1 O que já está claro que PODE, sem ressalva

| Automação | Situação |
|---|---|
| **Gerar legenda e hashtags com IA** | ✅ permitido — basta o texto final ser visível/editável antes de publicar |
| **Agendar** publicação | ✅ permitido (o YouTube tem campo nativo) |
| **Publicar sozinho no horário**, nas contas do próprio dono | ✅ **quem configurou o fluxo consentiu** — é o mesmo princípio do agendamento |
| **Radar de tendências** (ler dado público) | ✅ permitido — respeitando 30 dias de retenção e sem agregar entre donos |
| **Sugerir conteúdo** a partir da tendência | ✅ livre |

⚠️ **A ressalva só aparece quando houver clientes:** aí cada um precisa aprovar o **próprio**
conteúdo antes de ir ao ar. Automação sem revisão vale para as contas **do próprio operador**.

### 8.1.2 O que fica FORA do painel (mas pode existir)

**Baixar vídeo e cortar** — o que importa é **onde mora**, não se existe:

| Onde | Situação |
|---|---|
| **Dentro do painel** (com credencial, sob auditoria) | 🔴 risco real — pode aparecer na auditoria e derrubar o app |
| **Ferramenta separada**, sem credencial de API | 🟡 fora da política de desenvolvedor (não é API Client); risco prático de uso pessoal ≈ zero |

→ **Decisão: se for construído, mora fora do painel.** As duas coisas rodam na mesma máquina; o
painel continua limpo e aprovável.

### 8.1.3 Fora de escopo, por decisão do Gabriel

**Automatizar visualizações, curtidas e inscrições** — *"é falsificação"*. Sem valor real,
punição certa. **Não entra nunca.**

---

## 8.2 Corte de vídeo com IA (a decidir)

**A ideia:** entregar um vídeo longo (podcast, live, aula), a IA identifica os melhores momentos,
gera os cortes verticais 9:16 com legenda, e eles seguem pro fluxo de publicação.

**⚠️ Restrição obrigatória — entrada por ARQUIVO, nunca por link:**
Baixar vídeo do YouTube é proibido pela política de desenvolvedor (**YT-C02 / III.E.1**:
*"proibido baixar, importar, fazer backup, cachear ou armazenar cópias de conteúdo audiovisual
do YouTube"*) — e vale **até para o próprio vídeo**. Baixar por link também exige raspagem, que
é vedada por outra regra (III.E.6).
→ **O usuário sobe o arquivo original.** Mesmo recurso, mesmo valor, zero risco.

**Duas formas de fazer — decisão pendente:**

| | Onde vive | Consequência |
|---|---|---|
| **A — Ferramenta separada** | fora do painel, sem credencial de API | ✅ **não está sujeita a política de desenvolvedor** (não é API Client) · pode sair antes de qualquer aprovação · se o painel demorar, ela já funciona |
| **B — Integrada ao painel** | dentro do app auditado | ⚠️ tudo que ela fizer passa a ser responsabilidade do **API Client** — inclusive na auditoria |

**Custo, que é o fator decisivo:** transcrição + análise + renderização é **processamento pesado
e pago por vídeo**. Não é recurso, é **produto** — o OpusClip cobra US$ 15/mês justamente por
isso. Só faz sentido **depois** do painel funcionando.

**Status:** ❓ **em aberto** — o Gabriel decide depois. A restrição do arquivo, porém, vale nas
duas formas.

---

## 9. Futuro (quando virar produto pago)

Ordem segura: **usar de verdade → padronizar o que funcionou → cobrança/planos/limites →
SaaS.** A base (dois papéis + isolamento por cliente) já está pronta pra isso; falta só a
camada de cobrança — que **não muda** a arquitetura (DEC-14).

---

## Fontes (pesquisa de features)
- Ferramentas de agendamento e features — https://buffer.com/resources/social-media-scheduling-tools/ · https://sproutsocial.com/insights/social-media-scheduling-tools/
- Especificações de vídeo por rede / cross-posting — https://www.sendible.com/insights/social-media-video-specs · https://influencermarketinghub.com/cross-posting-matrix/
- Priorização de MVP (MoSCoW / must vs nice-to-have) — https://crowdbotics.com/posts/blog/how-to-know-which-features-you-should-or-shouldnt-include-in-mvp/

## Notas / decisões (log)
- 2026-07-27 — Documento criado e depois enriquecido com pesquisa de ferramentas reais
  (priorização MoSCoW, specs de vídeo por rede, personalização por destino). Projeto novo
  Laravel 12; os caminhos reais (projeto, repositório, casca antiga) ficam no README.
- 2026-07-27 — **Escopo travado: foco em SHORTS (9:16) + IMAGENS.** Outros formatos (vídeo
  longo, carrossel, texto) ficam pra atualização futura. Visão: motor de "tendência →
  conteúdo curto → publicação multi-rede".
- 2026-07-27 — **Pesquisa profunda verificada (doc 07) aplicada:** imagem = FB+IG (JPEG;
  YouTube não recebe imagem; TikTok só com domínio verificado); vídeo 3–180s ≤300MB com
  avisos por destino (91–180s = sem Facebook; >60s = monetiza no TikTok); tabela de specs
  refeita com números verificados; perguntas 1/4-parcial/5-parcial resolvidas (perfil
  canônico + storage local com URL pública temporária p/ IG).
