# TRÂMITES E CONFORMIDADE — checklist executável

> Como o produto é **plataforma aberta a clientes** (DEC-20), existem exigências que **não
> são burocracia opcional**: sem elas o sistema fica pronto e **não publica nada em público**.
> Pesquisa de 28/07/2026 em docs oficiais (Google/Meta/TikTok/ANPD).
> **Ordem de leitura:** §1 armadilhas → §2 fazer hoje → §3 depois do código → §4 para sempre.

---

## 🚨 1. As 6 armadilhas que matam o projeto depois de pronto

1. **Teto de 100 uploads/dia é do PROJETO, não do cliente.** Somando todos os clientes.
   20 clientes postando 1 short/dia = 20% do teto; ~100 clientes = serviço quebrado.
2. **Criar um projeto Google por cliente é PROIBIDO e derruba tudo.** A política é literal:
   multiplicar projetos pra somar quota faz o Google **suspender todos** — inclusive o
   legítimo. Solução é pedir aumento, nunca clonar projeto.
3. **Sem a auditoria do YouTube, todo vídeo sai PRIVADO.** O produto fica 100% pronto e não
   entrega o que promete. E a auditoria **não tem prazo oficial** (relatos: semanas a meses).
4. **TikTok não auditado = 5 clientes por dia, contas privadas, post só pro dono.**
   Comercialmente inútil. E são **duas aprovações separadas**, não uma.
5. **Imagem no TikTok só funciona por URL pública de domínio verificado.** Não existe upload
   de arquivo pra foto. **URL assinada que redireciona não funciona** → muda arquitetura.
6. **Revisões anuais que derrubam a operação se esquecidas:** o *Data Use Checkup* da Meta
   desativa o app e **todos os clientes param de uma vez**.

---

## 📅 2. Pode começar HOJE (sem uma linha de código)

### 2.1 Domínio e páginas públicas — destrava semanas depois
- [ ] **Registrar o domínio definitivo.** ⚠️ O nome **não pode conter "YouTube", "YT" ou
      "You-Tube"** (política do Google). ⚠️ *Conferir ao decidir o nome comercial (0.N).*
- [ ] Subir uma **landing simples** com link visível pras páginas legais.
- [ ] **Política de privacidade** (§2.2) e **Termos de uso** (§2.3) no **mesmo domínio**.
- [ ] Página **/exclusao-de-dados** (a Meta exige; pode ser página de instruções, sem código).
- [ ] **Verificar o domínio** em: Google Search Console · Meta Business Manager · TikTok
      (arquivo na raiz ou registro DNS TXT — propagação até 72h).
- [ ] Criar **e-mail no próprio domínio** (acelera muito a verificação da Meta) +
      `privacidade@` (canal obrigatório do titular).
- [ ] ⚠️ **Verificar também o domínio/CDN onde a MÍDIA vai ficar** — é ele que o TikTok
      valida pra publicar imagem.

### 2.2 Política de privacidade — o que ela obrigatoriamente contém
**Pela LGPD (art. 9):** finalidade · forma e duração do tratamento · identificação e contato
do controlador · **com quem os dados são compartilhados** (nomear: Meta, Google/YouTube,
TikTok, hospedagem, e-mail) · responsabilidades · **direitos do titular (art. 18)**.
**Pelo Google (seção obrigatória "YouTube API Services"):** avisar que o app usa YouTube API
Services · **linkar** a política do Google · explicar quais dados acessa/coleta/armazena/usa
· informar que dá pra revogar acesso em `security.google.com/settings/security/permissions`.
*O revisor pede print dessas seções.*
**Pela Meta:** citar **nominalmente** quais dados da API são coletados (política genérica é
motivo recorrente de rejeição).
**Transferência internacional:** nomear os destinos e a base legal (Resolução ANPD 19/2024 —
o prazo de adequação **já venceu em 23/08/2025**). 💡 **Servidor no Brasil reduz esse risco.**

### 2.3 Termos de uso — as cláusulas que protegem você
- [ ] **Conteúdo é do cliente:** ele declara ter os direitos (autoral, marca, trilha sonora).
- [ ] **Direito de imagem** (⚠️ Súmula 403 do STJ: uso comercial de imagem sem autorização
      gera dever de indenizar **mesmo sem prova de prejuízo**): o cliente declara possuir
      autorização de imagem e voz de todas as pessoas identificáveis, e responde por isso.
- [ ] **Autorização para publicar em nome dele** (exigido pelas plataformas).
- [ ] O cliente **aceita os termos das plataformas de destino** — o YouTube exige
      literalmente que seus termos digam isso.
- [ ] Conteúdos proibidos (espelhando as regras das 3 plataformas) + direito de remover.
- [ ] Suspensão: hipóteses **com** e **sem** aviso prévio; o que acontece com os dados no
      encerramento (janela de exportação, depois eliminação + revogação de tokens).
- [ ] **Limitar** responsabilidade (teto), não excluir — se o cliente for pessoa física, o
      CDC pode anular exclusão total.
- [ ] **DPA embutido:** "o CLIENTE é controlador do conteúdo; a PLATAFORMA é operadora,
      tratando conforme instruções dele" + cláusula de indenização.
      ⚠️ **Sem isso, a responsabilidade é solidária** (LGPD art. 42) — se o cliente publicar
      imagem indevida, você pode ser cobrado junto.

### 2.4 Meta — verificação da empresa (a etapa mais lenta; abrir já)
- [ ] Abrir **Business Verification** no Business Manager → Central de Segurança. **Grátis**
      (não confundir com o selo azul pago, que **não serve**). Prazo típico **3–10 dias
      úteis**, até 14.
- [ ] Documentos (PDF nítido, 300 DPI, sem cortes, em dia): **Cartão CNPJ** + **Contrato
      Social** (ou Certificado MEI) + **comprovante de endereço** dos últimos 90 dias.
- [ ] ⚠️ **Razão social e endereço no Business Manager devem bater LETRA POR LETRA** com o
      Cartão CNPJ (pontuação, "LTDA/ME", abreviações, formato do CEP). É a causa nº 1 de
      rejeição — e reenvio repetido atrasa a fila.

### 2.5 Contas de desenvolvedor
- [ ] **UM** projeto no Google Cloud pra plataforma inteira (+ um separado só pra
      desenvolvimento) — nunca um por cliente (armadilha nº 2).
- [ ] Publicar o app em **"Production"** cedo: em "Testing" o refresh token **expira em
      7 dias** (o cliente teria que reconectar toda semana).
- [ ] ⚠️ **Economizar as 100 vagas vitalícias:** cada conta Google nova que autoriza o app
      não verificado queima **uma vaga pra sempre**. Usar poucas contas de teste e reutilizar.
- [ ] Criar app na Meta (cenário **"Tech Provider servindo múltiplas empresas"** — quem marca
      "meu próprio negócio" fica preso ao acesso limitado).
- [ ] Criar app no TikTok + verificar URLs.

### 2.6 Documentos internos (1 página cada, sem código)
- [ ] **Plano de resposta a incidente:** quem decide, contato de acionamento, roteiro de
      contenção (vazou token → **revogar todos em massa na origem**), texto-modelo de aviso
      ao cliente, passo a passo do SEI!ANPD. *Prazo legal: 3 dias úteis — **6 dias úteis**
      por ser empresa de pequeno porte.*
- [ ] **Registro simplificado de tratamento** (planilha: finalidade, base legal, categorias,
      compartilhamentos, retenção) — exigido pelo art. 37.
- [ ] **Matriz de retenção** por tabela (o que apaga, o que anonimiza, o que retém e por quê).

> ✅ **Boa notícia:** **não** é preciso nomear DPO (empresa de pequeno porte é dispensada) —
> basta o canal de atendimento (`privacidade@`). E **não existe cadastro nem licença prévia**
> da ANPD pra operar.

---

## 🧑‍💻 3. Depois do código pronto (submissões)

> **Regra de ouro:** todas exigem o produto **funcionando e acessível na internet** (nunca
> localhost) + **conta de demonstração** com roteiro numerado pro revisor.

### 3.1 Google / YouTube (2 processos independentes — passar em um não libera o outro)
- [ ] **Verificação OAuth** (~10 dias, grátis): pedir **somente** `youtube.upload` — escopo
      largo demais é reprovação clássica. Vídeo demo do fluxo inteiro, sem cortes.
- [ ] **Auditoria de compliance** (`yt_api_form`, grátis, **sem prazo oficial**): libera
      vídeo público **e** aumento de quota. Preparar antes: conta demo funcional + prints da
      tela de upload **já com o logo do YouTube** visível.

> ⏱️ **Quanto demora, de verdade (pesquisa 28/07):** a doc oficial só diz *"entraremos em
> contato assim que possível"* — **sem SLA**. Na prática: **várias semanas** é o rotineiro;
> há **caso documentado de 5 meses** no suporte do Google, e relato no fórum de dev com
> *"lançamento em risco, sem resposta após o prazo crítico"*. **Submissão malfeita = semanas
> de vai-e-volta ou reprovação direta.**
> **O que acelera (e você controla):** site no ar com política e termos públicos · justificativa
> clara (nada que pareça raspagem) · conta de demonstração funcionando · submissão completa
> **de primeira**.
> **Planejar 2–3 meses.** Como é a única variável fora do seu controle, a estratégia do plano é
> não ficar parado: Fase 3 entrega produto sem depender de ninguém, o YouTube publica
> **privado** enquanto espera (funciona sem auditoria), e a fila roda em paralelo.
> 📎 [Auditorias (oficial)](https://developers.google.com/youtube/v3/guides/quota_and_compliance_audits) ·
> [caso de 5 meses](https://support.google.com/youtube/thread/381391908/youtube-api-quota-increase-delay-of-5-months?hl=en)

- [ ] 🗓️ **Não fechar contrato com cliente pagante antes da aprovação.**

### 3.2 Meta (Facebook + Instagram)
- [ ] **Antes de submeter:** executar de verdade **1 chamada bem-sucedida por permissão** nos
      últimos 30 dias (listar páginas, ler engajamento, publicar na página, publicar no IG).
- [ ] **Screencast por permissão** (sem ele a permissão **não** é aprovada): 1080p, **sem
      áudio**, interface **em inglês** (ou legendas), jornada completa, conta Instagram
      Business real.
- [ ] Justificativa **diferente por permissão**, sempre falando de **clientes terceiros**
      ("agências e empresas clientes agendam e publicam nas contas **delas**") — copiar texto
      entre permissões é rejeição.
- [ ] Submeter as permissões **na mesma leva** (senão os ciclos serializam).
- [ ] 🗓️ Prazo oficial < 1 semana, mas **planejar 4–8 semanas** até clientes reais publicando.

### 3.3 TikTok (duas aprovações, sequenciais)
- [ ] **1ª — App Review** (dias a 2 semanas): libera enviar como **rascunho** (o cliente
      finaliza no app do TikTok). *Dá pra lançar com esse modo.*
- [ ] **2ª — Auditoria da Content Posting API** (2–4 semanas, sem SLA): libera **publicação
      direta e pública**.
- [ ] ⚠️ **O que mais reprova é TELA, não código** — checklist oficial de UX: apelido e avatar
      do criador vindos do `creator_info` toda vez que a tela abre · privacidade **sem valor
      padrão** · comentário/duet/stitch desmarcados e cinzas quando o criador bloqueou ·
      "Your Brand"/"Branded Content" com os rótulos exatos · bloquear "só eu" quando Branded
      Content ligado · frase de consentimento de música mudando conforme a seleção.
      *A rejeição vem genérica: "did not follow our UX Guidelines".*
- [ ] ⚠️ **A estimativa de uso que você escrever no formulário vira o TETO de criadores por
      24h.** Projetar **12 meses** de crescimento, não o número de clientes de hoje.

---

## ♾️ 4. Obrigações contínuas (esquecer derruba a operação)
- [ ] **Data Use Checkup da Meta — anual.** Perder o prazo **desativa o app e derruba todos
      os clientes**. Criar lembrete com 30 dias de antecedência e manter a conta de demo
      sempre funcionando (ela é reexigida todo ano).
- [ ] **E-mail de contato do app na Meta precisa ser monitorado de verdade** — o aviso chega
      por lá.
- [ ] **Reauditoria periódica do YouTube** + guardar tudo que foi enviado (prints, textos,
      número do projeto, datas). Mudou o uso (ex.: entrar com métricas)? Novo pedido.
- [ ] **Responder o titular em até 15 dias** (SLA interno conservador; o legal é 30 por ser
      pequeno porte) e **registrar cada pedido** — sem registro não há como provar cumprimento.
- [ ] **Registrar TODO incidente por 5 anos**, inclusive os **não** comunicados (a ausência
      do registro é infração autônoma).

---

## 🧱 5. O que isso obriga DENTRO do produto (vira requisito, não opção)

| Exigência | Origem | Onde entra |
|---|---|---|
| **Controle de quota de upload** + estado "aguardando janela" e retry no dia seguinte | YouTube (100/dia no projeto) | Fase 4 (motor) |
| **Aprovação explícita antes de publicar** — proibido postar sozinho ou alterar o texto do cliente sem consentimento | YouTube (automação sem consentimento é proibida) | Fase 4 · impacta agendamento e IA |
| **Checkbox de conformidade** com as Diretrizes da Comunidade no upload | YouTube | Fase 2/4 |
| **Mostrar o canal/dono de destino** em cada publicação + **logo clicável do YouTube** | YouTube (branding + identificação) | Fase 3 |
| **Botão "Desconectar conta"** que revoga na origem e apaga o token (+ log) | LGPD art. 8 §5 · YouTube | Fase 3 |
| **Purga em até 7 dias** após revogação (não 30) | YouTube | Fase 4 |
| **Dados do YouTube (métricas/metadados) expiram em 30 dias** — proibido histórico eterno | YouTube | ⚠️ limita a Fase 8 (métricas) |
| **Mídia em URL pública HTTPS, sem redirect, em domínio verificado** | TikTok (imagem) · Instagram | ⚠️ **revisa DEC-07** |
| **Tela "Meus dados e privacidade"**: baixar dados · corrigir cadastro · ver compartilhamentos · excluir conta | LGPD art. 18 | Fase 0/8 |
| **Exportação sem token** (nunca incluir access/refresh token no pacote) | LGPD + segurança | Fase 8 |
| **Consultar limite de publicação do cliente** (100 posts/24h no IG) antes de enfileirar | Meta | Fase 5 |
| **Logs de acesso por 6 meses** (data/hora, IP, conta) — sobrevivem à exclusão da conta | Marco Civil art. 15 | Fase 1 (tabela) |
| **Registro de incidentes (5 anos)** e **registro de pedidos de titular** | ANPD | Fase 1 (tabelas) |
| **Canal de notificação e remoção rápida de conteúdo** | STF 2025 (notice and takedown) | Fase 8 |
| **Tokens:** criptografia com chave fora do banco · TLS · **nunca em log ou mensagem de erro** · acesso restrito | LGPD art. 46 | Fase 1 |

_2026-07-28 — pesquisa em 4 dimensões (quota/Google, Meta, TikTok, LGPD-Brasil)._
