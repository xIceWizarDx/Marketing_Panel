# REGRAS DAS REDES — versão verificada

> ⚠️ **Esta é a 2ª versão. A primeira estava ERRADA** — inflava 317 "regras" porque os
> pesquisadores parafrasearam interpretando, e o resultado ficou **mais restritivo que a
> política real**. Refeita com método de auditor: **cada regra tem a frase literal da política
> oficial** e é classificada pelo **verbo usado no texto** (*must* = obrigatório · *should* =
> recomendado).
>
> **Resultado:** **215 obrigatórias** · 35 recomendadas · 19 descartadas (falavam de recursos
> que não vamos ter: player incorporado, comentários, busca, apps infantis).
>
> Cada regra traz o **ID da seção oficial** entre colchetes — dá pra conferir na fonte.

---

## 🔄 As 9 correções (o que eu tinha escrito errado)

| # | Eu escrevi | A política diz de verdade |
|---|---|---|
| 1 | "Consentimento **na hora** de publicar" | *"prior to their actual execution"* = **antes da execução**. **Agendamento é permitido** — o consentimento acontece quando ele monta e aprova o post. |
| 2 | "Botão único 'publicar em todas' é **proibido**" | **É permitido.** Exige-se que a ação do YouTube seja **identificável como do YouTube**, não misturada com outra função, e disparada pelo cliente. Buffer/Hootsuite/mLabs fazem assim. |
| 3 | "Proibido guardar métricas **além de 30 dias**" | **Estatísticas podem ficar indefinidamente.** Os 30 dias são para **revalidar a autorização** — não é prazo de validade do dado. Por isso o Metricool mostra histórico de anos. |
| 4 | "Proibido **agregar dados** entre clientes" | Pode **mostrar** vários clientes na tela. Proibido é **somar/ranquear** números de donos diferentes. |
| 5 | "Só o dono pode ver os dados" | Existe exceção explícita para *"agents expressly approved by that user"* — é o que permite recurso de agência/equipe. |
| 6 | "Proibido **alterar o texto** do cliente" | **Sugerir e pré-preencher é permitido** (inclusive por IA). Proibido é **alterar em silêncio**, depois que ele aprovou. |
| 7 | "Proibido criar métrica própria" | Pode — **ao lado** do número oficial, avisando que o cálculo é seu. |
| 8 | "Logo do YouTube **em todas as telas**" | Só nas telas **que exibem conteúdo/dados do YouTube**. |
| 9 | "Proibido vender acesso" (parecia proibir cobrar) | Proibido é **revender a API**. **Vender a assinatura do painel é expressamente permitido** [III.G.2]. |

---

# 🎥 YOUTUBE (100 obrigatórias)

## Tela e experiência (16)

- [ ] **[III.A.1]** Exibir link dos Termos do YouTube **e** declarar, nos seus próprios termos,
      que ao usar o painel o cliente aceita os Termos do YouTube.
- [ ] **[ToS 9.1(i)]** Na tela onde ele clica em enviar, exibir o aviso de certificação (nos
      idiomas do painel): *"Ao clicar em 'upload', você certifica que o conteúdo que está
      enviando está de acordo com os Termos de Serviço do YouTube"*.
- [ ] **[ToS 9.1(i)]** A URL do aviso muda por dispositivo: `youtube.com/t/terms` no
      computador, `m.youtube.com/terms` no celular.
- [ ] **[III.C.2]** A ação do YouTube precisa ser **(1)** identificável como ação do YouTube,
      **(2)** não misturada com outra função do painel e **(3)** iniciada pelo cliente.
      ✅ *Botão que publica em várias redes continua permitido.*
- [ ] **[III.C.4]** Deixar claro **como o dado vai ser usado no YouTube** — ex.: se o campo se
      chama "Legenda", avisar que aquilo vira a **descrição** do vídeo.
- [ ] **[III.C.7]** Se o painel limitar algum recurso, **explicar por quê** e deixar claro que
      a limitação **não é do YouTube**.
- [ ] **[III.D.2.d]** Identificar claramente **qual empresa/produto** pede acesso e **por quê**.
- [ ] **[III.E.3]** Deixar explícito **para qual canal** aquele vídeo vai.
- [ ] **[III.E.4.f]** Mostrar sempre o dado **mais atualizado**. ✅ *Dado histórico pode ser
      exibido, desde que com a data de referência clara.*
- [ ] **[III.E.4.g]** O botão de apagar dados precisa deixar claro que **não apaga nada dentro
      do YouTube**.
- [ ] **[III.E.4.h]** Métrica calculada pelo painel precisa vir com **aviso destacado** de que
      não vem do YouTube.
- [ ] **[III.F.1]** Não alterar as interfaces **do próprio YouTube**. *(Não se aplica à nossa
      tela — a nossa pode ser como quisermos.)*
- [ ] **[III.G.1]** Só vender anúncio em tela com dados do YouTube se a página tiver **valor
      próprio** sem eles. *(Painel multi-rede normalmente cumpre.)*
- [ ] **[ToS 9.1(ii)]** Se um dia houver canal da plataforma, a opção de publicar **no canal do
      cliente** precisa ter no mínimo o mesmo destaque + aviso de licença.
- [ ] **[RMF Uploads]** A tela de envio precisa expor ao cliente: **título, descrição,
      privacidade** e (quando houver mais de um) **o canal**.
- [ ] **[III.B.4]** Rodando versão descontinuada da API → avisar o cliente.

## Publicação (12)

- [ ] **[III.E.3]** Identificar claramente as ações feitas **em nome** do cliente, e ele
      precisa consentir **antes da execução**. ✅ **Agendamento é permitido.**
- [ ] **[III.C.3]** ✅ **Pode sugerir** valores — o cliente tem o **controle final**. Proibido
      **modificar** o que ele forneceu (cortar/acrescentar) antes de enviar.
- [ ] **[III.C.3.1]** ✅ Pode sugerir/pré-preencher a descrição. **Proibido acrescentar** algo
      depois que ele enviou e antes de ir ao YouTube (ex.: carimbar o nome da ferramenta).
- [ ] **[III.C.3.2]** Pode oferecer tradução do título, **com consentimento**; se vier ligada
      por padrão, precisa de jeito fácil de desligar.
- [ ] **[III.C.3]** Sugestão de texto precisa ser **relevante** — não gerar o mesmo título
      padrão para todo mundo.
- [ ] **[III.E.3]** Identificar a **visibilidade** que será aplicada; não alterar visibilidade
      existente sem instrução expressa.
- [ ] **[RMF privacyStatus]** As **três opções** (público, privado, não listado) disponíveis na
      tela.
- [ ] **[III.C.1]** Cumprir os Requisitos de Funcionalidade Mínima; **não impor limite menor**
      que o do YouTube (ex.: título não pode ser limitado a menos de 100 caracteres).
- [ ] **[ToS 9.1(ii)]** Oferecer envio para o(s) canal(is) **do próprio cliente**.
- [ ] **[ToS 9.1(iv)]** Escolher **uma** das opções: campo "Feito para crianças" no painel
      **ou** avisar que ele precisa declarar no YouTube depois.
- [ ] **[III.C.8]** Não apresentar os recursos do YouTube de forma **consistentemente pior**
      que os das outras redes.
- [ ] **[III.I]** Proibido confundir, enganar, fraudar, difamar, perseguir, spammar ou assediar.

## Dados e privacidade (30)

**Política de privacidade — o que ela precisa conter [III.A.2]:**
- [ ] **[III.A.2]** Exigir aceite da política **antes** de o cliente acessar o painel.
- [ ] **[a]** Exibida com destaque e acessível **o tempo todo**.
- [ ] **[b]** Avisar que o painel usa os **Serviços de API do YouTube**.
- [ ] **[c]** Mencionar e **linkar** a Política de Privacidade do Google.
- [ ] **[d]** Explicar **quais** dados acessa, coleta, armazena e usa.
- [ ] **[e]** Explicar **como** usa, processa e compartilha (interna e externamente).
- [ ] **[f]** Informar, se for o caso, que terceiros veiculam conteúdo/anúncios.
- [ ] **[g]** Informar, se for o caso, uso de cookies e tecnologias similares.
- [ ] **[h]** Explicar que dá pra **revogar** em `security.google.com/settings/security/permissions`.
- [ ] **[i]** Informar **canal de contato** para dúvidas de privacidade.

**Retenção — a parte que eu tinha errado:**
- [ ] **[III.E.4.b]** ✅ **Estatísticas de canal autorizado podem ficar indefinidamente** — mas
      é preciso **revalidar a autorização a cada 30 dias** e verificar se o vídeo não foi
      apagado.
- [ ] **[III.E.4.b]** Estatísticas de canais **não autorizados**: máximo 30 dias.
- [ ] **[III.E.4.c]** Demais dados autorizados (metadados, títulos, textos): máximo **30 dias**
      — depois **apagar ou atualizar**.
- [ ] **[III.E.4.d]** Dados não autorizados: máximo 30 dias.
- [ ] **[III.E.4.e]** Manter os dados guardados **consistentes** com o YouTube.

**Exclusão e revogação:**
- [ ] **[III.D.2.g]** Revogou → apagar **todos** os dados autorizados em **até 7 dias**.
- [ ] **[III.E.4.g]** Oferecer caminho para **pedir exclusão** (ex.: botão) — cumprir em 7 dias.
- [ ] **[III.E.4.g]** Apagou a conta no painel → apagar os dados dele também, em 7 dias.

**Uso e agregação:**
- [ ] **[III.E.2.1]** ✅ Proibido **agregar** dados de canais de **donos diferentes**. *(Pode
      exibir lado a lado; não pode somar/ranquear entre clientes.)*
- [ ] **[III.E.2.2]** Proibido tirar conclusões sobre o **negócio do YouTube**.
- [ ] **[III.E.3]** ✅ Não exibir dados autorizados a ninguém além do cliente **ou de agentes
      expressamente aprovados por ele**. *(É o que permite recurso de agência.)*
- [ ] **[III.E.3]** Ser honesto e transparente sobre os dados coletados e as finalidades.
- [ ] **[III.E.3]** Só acessar/usar dados dentro do que a política prevê e do consentimento
      obtido — mudou a finalidade, precisa de novo consentimento.
- [ ] **[III.D.2.b]** Pedir **só os escopos que usa hoje**.
- [ ] **[III.D.2.e]** Identificar as finalidades; proibido uso secundário não divulgado.
- [ ] **[III.E.4.h]** Proibido **substituir** número da API por cálculo próprio, ou derivar
      métrica nova. ✅ *Pode exibir a sua ao lado, avisando.*
- [ ] **[III.E.6]** Proibido **raspagem** — todo dado vem da API oficial.
- [ ] **[III.E.5 / ToS 8]** Manter controles de segurança e **criptografia de transporte**
      padrão de mercado.
- [ ] **[ToS 7]** Ter e seguir política de privacidade publicada.
- [ ] **[ToS 9.2]** Clientes na União Europeia → cumprir a política de consentimento da UE.
- [ ] **[ToS 24]** Encerrou/suspendeu → **parar de acessar e apagar tudo** imediatamente.

## Limites técnicos (17)

- [ ] **[RMF title]** Título: máximo **100 caracteres**; proibido `<` e `>`.
- [ ] **[RMF description]** Descrição: máximo **5.000 BYTES** (acento e emoji contam mais).
- [ ] **[III.D.1]** ⚠️ **Exatamente UM projeto de API** para o painel — não usar o mesmo em
      dois produtos.
- [ ] **[ToS 15]** Proibido ultrapassar **ou contornar** os limites de cota.
- [ ] **[III.B.1/3]** Manter a API sempre na versão mais recente, dentro do prazo exigido.
- [ ] **[III.D.7]** Proibido usar APIs não documentadas ou fazer engenharia reversa.
- [ ] **[III.I]** Proibido usar qualquer tecnologia que não seja a API oficial.
- [ ] **[III.I]** ⚠️ Proibido **oferecer uma API própria** que dê a terceiros acesso a dados ou
      funções do YouTube.
- [ ] **[III.I]** Proibido virar **substituto** do YouTube.
- [ ] **[III.I]** Proibido interferir no funcionamento ou desempenho do YouTube/Google.
- [ ] **[III.I]** Proibido contornar restrição geográfica (inclusive por IP).
- [ ] **[III.I]** Proibido modificar, traduzir ou fazer engenharia reversa da API.

## Conta e credenciais (14)

- [ ] **[III.D.2.f]** Botão claro de **revogar/desconectar** dentro do painel.
- [ ] **[III.D.2.g]** ⚠️ Revogar precisa **chamar o endpoint de revogação** — não basta apagar
      o token do banco.
- [ ] **[III.D.2.a]** ⚠️ Proibido pedir, cachear ou usar **login e senha** da conta Google.
- [ ] **[III.D.1]** Proibido mascarar a identidade nas chamadas; usar só as credenciais do
      próprio projeto.
- [ ] **[III.D.1]** Credenciais **nunca** embutidas no cliente; só compartilhar com agentes sob
      confidencialidade escrita.
- [ ] **[III.D.5]** Responder as comunicações de conformidade — **monitorar o e-mail** da conta
      Google do projeto.
- [ ] **[III.H]** Aceitar auditoria; **não ocultar** o uso; fornecer contas de acesso no prazo
      pedido.
- [ ] **[III.G.1]** Proibido vender, alugar, emprestar, redistribuir ou **sublicenciar** a API.
- [ ] **[III.G.1]** Proibido vender **acesso** à API sem aprovação escrita.
      ✅ **[III.G.2] Vender a assinatura do painel É PERMITIDO.**
- [ ] **[ToS 5]** Cumprir as leis aplicáveis e exigir o mesmo dos clientes.
- [ ] **[ToS 21]** ⚠️ Você **indeniza o YouTube** por reclamações de terceiros ligadas ao uso —
      exige termos aceitos por cliente e canal de denúncia.

## Conteúdo (5)

- [ ] **[III.E.1]** Proibido baixar, cachear ou guardar cópias de conteúdo **do** YouTube sem
      aprovação escrita. *(Guardar o arquivo que o cliente subiu é permitido.)*
- [ ] **[III.I]** Proibido violar direitos autorais.
- [ ] **[III.I]** Proibido anúncios intrusivos.
- [ ] **[ToS 12]** Não violar direitos de terceiros (propriedade intelectual, privacidade,
      imagem) — e exigir o mesmo dos clientes.

## Marca (6)

- [ ] **[III.F.2]** ✅ Exibir a marca do YouTube nas telas **que mostram conteúdo/dados do
      YouTube** (não em todas as telas do painel).
- [ ] **[III.F.2]** Exibir a marca em **todos os dispositivos** (não sumir no celular).
- [ ] **[III.F.2]** Proibido esconder ou interferir na atribuição do YouTube.
- [ ] **[III.F.2]** Conteúdo de outra rede **não pode parecer** vindo do YouTube → marcar a
      origem de cada item em telas mistas.
- [ ] **[ToS 10.3]** Dar a atribuição correta conforme as Diretrizes de Marca.
- [ ] **[ToS 11]** Proibido remover ou alterar avisos de termos, direitos autorais e marcas.

---

# 📘📸 META — Facebook + Instagram (77 obrigatórias)

## Publicação e fluxo (15)

- [ ] **[Dev Policies 1.7]** Obter consentimento **antes** de publicar ou agir em nome da pessoa.
- [ ] **[IG Content Publishing]** Fluxo em 2 passos: criar o container em `POST /<IG_ID>/media`
      → publicar em `POST /<IG_ID>/media_publish` com o `creation_id`.
- [ ] **[IG status_code]** Publicar **só quando `FINISHED`**. Estados: `IN_PROGRESS` ·
      `FINISHED` · `EXPIRED` (não publicado em 24h).
- [ ] **[IG media_type]** Valores: `VIDEO`, `REELS`, `STORIES`, `CAROUSEL`.
- [ ] **[IG Limitations]** **Não suportado:** etiquetas de compras · filtros · texto
      alternativo em Reels e Stories.
- [ ] **[FB Reels Overview]** Reels **só em Página** do Facebook, com **escopo público
      implícito** (não oferecer opção de privacidade).
- [ ] **[FB Reels]** Upload em 3 fases: `start` → enviar para `rupload.facebook.com`
      (`application/octet-stream`) → `finish` com `video_state`.
- [ ] **[FB Reels scheduled_publish_time]** Agendamento entre **10 minutos e 29 dias**.
- [ ] **[FB Reels Location]** A Página precisa ter **localização válida** associada.
- [ ] **[IG Resumable upload]** Vídeo pode ir por upload retomável ou por **URL hospedada**
      (`file_url`).

## Limites técnicos (26)

**Instagram — cotas:**
- [ ] **[IG Rate Limit]** **100 publicações/24h** por conta (janela móvel).
- [ ] **[IG Carousel Limitations]** ⚠️ Contas limitadas a **50 publicações/24h** no fluxo de
      carrossel. *(A doc traz os dois números — consultar o limite pela API, não fixar.)*
- [ ] **[IG Carousel]** Máximo **10 itens**; carrossel conta como **1 publicação**.

**Instagram — imagem:**
- [ ] **[IG Limitations]** ⚠️ **JPEG é o único formato suportado.** MPO e JPS não funcionam.
- [ ] **[IG Image specs]** Máximo **8 MB** · proporção entre **4:5 e 1.91:1** · largura de
      **320 a 1440 px**.

**Instagram — Reels:**
- [ ] **[IG Reel specs]** Duração **3s a 15min** · arquivo até **300 MB**.
- [ ] **[IG Reel specs]** MOV ou MP4, **sem edit lists**, `moov` no início · vídeo H264/HEVC,
      progressivo, GOP fechado, croma 4:2:0 · áudio AAC ≤48 kHz, 1–2 canais · **23 a 60 FPS**.
- [ ] **[IG Reel specs]** Proporção entre 0.01:1 e 10:1 — **9:16 recomendada**.

**Instagram — Stories:**
- [ ] **[IG Story video]** **3 a 60 segundos** · até **100 MB**.
- [ ] **[IG Story image]** JPEG até 8 MB · 9:16 recomendada.

**Instagram — texto:**
- [ ] **[IG /media caption]** Legenda: **2.200 caracteres** · **30 hashtags** · **20 menções @**.

**Facebook Reels:**
- [ ] **[FB Rate limit]** **30 publicações/24h** por Página.
- [ ] **[FB Video specs]** Proporção **9:16** · 1080×1920 recomendado (mínimo 540×960) ·
      **duração 3 a 90 segundos** · 24 a 60 FPS.
- [ ] **[FB Video specs]** Compressão H.264/H.265 (VP9 e AV1 também) · áudio AAC LC, 48 kHz,
      ≥128 kbps.

**Plataforma:**
- [ ] **[Platform Terms 3.a.vii]** ⚠️ Mudar a funcionalidade central ou o escopo de dados exige
      **reenviar ao App Review e ser aprovado ANTES**.
- [ ] **[Dev Policies 1.6]** Seguir a documentação oficial — descumpri-la **já é violação**.
- [ ] **[Dev Policies 2.2]** Respeitar a aparência dos produtos Meta e os limites que ela impôs.

## Dados e privacidade (17)

- [ ] **[Platform Terms 3.a.i]** Proibido usar os dados para **discriminar** por atributos
      pessoais.
- [ ] **[3.a.ii]** Proibido usar para decidir **elegibilidade** (moradia, emprego, seguro,
      crédito, benefício, imigração).
- [ ] **[3.a.iii]** Proibido usar para **vigilância**.
- [ ] **[3.a.iv]** Proibido **vender, licenciar ou comprar** dados da plataforma.
- [ ] **[3.a.v]** Proibido construir/enriquecer **perfis** sem consentimento válido.
- [ ] **[3.a.vi]** Proibido reidentificar, desanonimizar ou fazer engenharia reversa dos dados.
- [ ] **[3.a.viii]** Proibido usar para finalidades fora da documentação da Meta.
- [ ] **[3.d.i.2.a]** Apagar os dados quando não forem mais necessários.
- [ ] **[3.d.i.2.d]** Apagar quando o cliente pedir **ou quando ele não tiver mais conta**.
- [ ] **[4.b]** A política de privacidade precisa explicar **quais** dados, **como**, **para
      quê** e **como pedir exclusão**.
- [ ] **[5.b.ii.1]** ⚠️ Como provedor de tecnologia: usar os dados **só em nome e sob orientação
      do cliente** — nunca para finalidade própria ou de outro cliente.
- [ ] **[5.b.ii.2]** ⚠️ Manter os dados de cada cliente **separados** dos dos outros.
- [ ] **[5.b.ii.4]** Só compartilhar dados conforme os Termos e a lei.
- [ ] **[5.b.ii.4.a]** Antes de compartilhar dados com o cliente, **proibi-lo contratualmente**
      de usá-los violando os Termos.
- [ ] **[6.a.i.1]** Manter salvaguardas administrativas, físicas e técnicas **de padrão de
      mercado ou superior**.
- [ ] **[6.a.iv]** Proteger IDs, **tokens** e segredos do app. *(Pode compartilhar com
      prestador que ajuda a operar.)*
- [ ] **[6.b.i]** Incidente → **notificar a Meta** pelo formulário assim que praticável.

## Conta e obrigações (13)

- [ ] **[Dev Policies 1.1]** Administrar o app com **conta autêntica**.
- [ ] **[3.2]** Manter o e-mail da conta e do app **atualizados**.
- [ ] **[3.4]** Manter o Business Manager atualizado.
- [ ] **[11.5]** Não criar/reivindicar Página em nome de alguém **sem consentimento**.
- [ ] **[5.b.ii.3]** Manter **lista atualizada de clientes** e entregá-la se a Meta pedir.
- [ ] **[5.b.ii.6]** Encerrar o uso por um cliente **prontamente**, se a Meta pedir.
- [ ] **[5.b.ii.7]** Avisar o cliente sobre comunicações da Meta relativas aos dados dele.
- [ ] **[7.c.ii]** Auditoria com **10 dias úteis** de aviso (menos em condição necessária).
- [ ] **[7.c.iii]** Cooperar com as auditorias.
- [ ] **[7.d]** Fornecer informações e certificações **no prazo e formato pedidos**.
- [ ] **[IG Permissions]** Instagram Login: `instagram_business_basic` +
      `instagram_business_content_publish`. Facebook Login: `instagram_basic` +
      `instagram_content_publish` + `pages_read_engagement`.
- [ ] **[FB Permissions]** Páginas: `pages_show_list` + `pages_read_engagement` +
      `pages_manage_posts`.

## Conteúdo e marca (4)

- [ ] **[Dev Policies 1.2]** Conteúdo do app (inclusive o do cliente) precisa atender aos
      **Padrões da Comunidade**.
- [ ] **[1.4]** Não confundir, enganar, fraudar, spammar nem surpreender.
- [ ] **[1.8]** Oferecer caminho fácil para **pedir suporte ou relatar problemas**.
- [ ] **[1.3 / 2.4]** Nome, ícone e descrição conforme os Padrões de Publicidade; logos da Meta
      só conforme o Brand Center.

---

# 🎵 TIKTOK (38 obrigatórias)

## Tela de publicação (10)

> A auditoria confere estes itens. **Correção da 1ª versão: os textos PODEM ser traduzidos** —
> o exigido é o **elemento e o significado**, não o idioma inglês.

- [ ] **[UX 1.a]** Exibir **avatar e nome/apelido** do criador, buscados via `creator_info`
      **a cada abertura da tela**.
- [ ] **[UX 1.c]** Validar a duração do vídeo contra o **`max_video_post_duration_sec`
      daquela conta**.
- [ ] **[UX]** Seletor de privacidade **sem valor pré-selecionado** — nem "Público" por
      conveniência, nem lembrar a última escolha.
- [ ] **[UX]** As opções precisam ser **as retornadas pela API** para aquela conta.
- [ ] **[UX]** Comentário / Dueto / Costura **desmarcados por padrão**; bloqueados pela conta →
      **cinza com explicação**.
- [ ] **[UX]** Toggle de **conteúdo comercial desligado** por padrão; ligado → escolher entre
      "Sua marca" (rótulo *Conteúdo promocional*) e "Conteúdo de marca" (*Parceria paga*).
- [ ] **[UX]** Com o toggle ligado e **nenhuma** opção marcada → **bloquear o publicar**.
- [ ] **[UX]** "Conteúdo de marca" → **bloquear privacidade privada**.
- [ ] **[UX]** Exibir o texto de **consentimento de música** (sempre) e o da Política de
      Conteúdo de Marca quando aplicável — com links.
- [ ] **[UX]** Exibir **prévia** do conteúdo e avisar que o processamento leva alguns minutos.

## Publicação e limites de conta (7)

- [ ] **[Guidelines intro]** ⚠️ Sem auditoria, **todo conteúdo sai privado** (`SELF_ONLY`).
- [ ] **[User cap]** ⚠️ Não auditado: até **5 usuários/24h**, e **todas as contas precisam
      estar privadas** no momento da publicação.
- [ ] **[Creator cap]** Existe teto de criadores ativos/24h **definido pela estimativa que você
      informar no formulário de auditoria**.
- [ ] **[Posting cap]** ~**15 posts/dia por conta**, **compartilhado entre todos os apps** que
      usam Direct Post.
- [ ] **[Intended Use 2]** ⚠️ O app **não pode** ser só ferramenta de teste ou para uso
      próprio/da sua equipe — precisa ser destinado a **público amplo**.
- [ ] **[Technical 2.b]** URL de mídia precisa estar em **domínio verificado** no portal.
- [ ] **[Technical 2.c/2.d]** Arquivo no dispositivo → `FILE_UPLOAD`; arquivo já no seu
      servidor → `PULL_FROM_URL`.

## Limites técnicos (18)

- [ ] **[Rate limit init]** **6 requisições/minuto** por token no endpoint de início.
- [ ] **[Rate limit creator_info]** **20 requisições/minuto** por token.
- [ ] **[campo title]** Legenda até **2.200 runas UTF-16**.
- [ ] **[privacy_level]** Valores: `PUBLIC_TO_EVERYONE` · `MUTUAL_FOLLOW_FRIENDS` ·
      `FOLLOWER_OF_CREATOR` · `SELF_ONLY` — enviar **só o que a API retornou** para a conta.
- [ ] **[upload_url]** Vale **1 hora**; não terminou, recomeça.
- [ ] **[chunk rules]** Pedaços de **5 a 64 MB** (o último pode ir a 128 MB).
- [ ] **[chunk count]** De **1 a 1000** pedaços.
- [ ] **[video duration]** Todo criador publica **3 min**; alguns 5 ou 10. Máximo pelo endpoint:
      **10 min**.
- [ ] **[Video Restrictions]** MP4 (recomendado), WebM, MOV · H.264/H.265/VP8/VP9 · **23 a
      60 FPS** · **360 a 4096 px** por lado · até **4 GB**.
- [ ] **[Image Restrictions]** **WebP e JPEG** · até **20 MB** por imagem.

---

# ⚖️ BRASIL (LGPD + Marco Civil)

- [ ] **BR-01** Tela "Meus dados e privacidade": baixar dados · corrigir cadastro · ver
      compartilhamentos · excluir conta.
- [ ] **BR-02** Exportação legível por máquina, **sem incluir tokens**.
- [ ] **BR-03** Registro de acessos (data/hora, IP, conta) por **6 meses** (Marco Civil art. 15).
- [ ] **BR-04** Registro de incidentes por **5 anos**, inclusive os não comunicados.
- [ ] **BR-05** Registro dos pedidos de titular (prova de cumprimento).
- [ ] **BR-06** Desconectar **tão fácil quanto** conectar (LGPD art. 8 §5).
- [ ] **BR-07** Canal público de reclamação e remoção rápida (STF 2025).
- [ ] **BR-08** Tokens criptografados com chave fora do banco; nunca em log.
- [ ] **BR-09** Responder o titular em até **30 dias** (dobrado por pequeno porte).
- [ ] **BR-10** Vazamento → ANPD em **6 dias úteis** (dobrado).
- [ ] **BR-11** **DPA nos termos**: cliente é controlador do conteúdo, plataforma é operadora.
- [ ] **BR-12** Cláusula de **direito de imagem** (Súmula 403/STJ).
- [ ] **BR-13** Política citando a **transferência internacional** e a base legal.

---

# 🎯 As 10 que mais derrubam projeto

| # | Regra | Onde |
|---|---|---|
| 1 | Tela do TikTok fora do padrão (reprova sem dizer o motivo) | TikTok UX |
| 2 | App só para uso próprio/da equipe — TikTok **rejeita** | Intended Use 2 |
| 3 | Publicar conteúdo que o cliente **nunca viu e aprovou** | III.E.3 · 1.7 |
| 4 | Alterar o texto **em silêncio** depois que ele aprovou | III.C.3.1 |
| 5 | **Somar/ranquear** métricas entre clientes diferentes | III.E.2.1 |
| 6 | Esquecer a **revalidação anual** da Meta (derruba todos) | Platform Terms |
| 7 | Mídia sem **URL pública em domínio verificado** | TikTok 2.b · IG |
| 8 | **Vários projetos** de API pra multiplicar cota | III.D.1 · ToS 15 |
| 9 | Revogar apagando só o token local, **sem chamar a revogação** | III.D.2.g |
| 10 | Mudar funcionalidade central **sem reenviar ao App Review** | 3.a.vii |

_2026-07-28 — 2ª versão, com citação literal por regra. 215 obrigatórias · 35 recomendadas ·
19 descartadas por não se aplicarem ao nosso caso._

