# ESTRATÉGIA DE CONSTRUÇÃO — a ordem que evita retrabalho

> Pesquisa de 28/07/2026 (Fowler, Sandi Metz, casos reais de quem construiu N integrações).
> **Objetivo: gastar as noites nas decisões que doem, e correr nas que não doem.**

---

## 🧭 O critério: quantas fronteiras a decisão já atravessou

Fowler define arquitetura como *"as decisões importantes **e difíceis de mudar**"* — e
complementa: *"se você pode mudar suas decisões facilmente, é menos importante acertá-las."*

**A pergunta certa não é "isso é importante?" — é "quanto custa desfazer em 6 meses?"**

| Gaveta | O que é | Como decidir |
|---|---|---|
| **A — só código** | nome de classe, estrutura de pastas, driver de fila, storage, componente de UI | **5 minutos.** Errar é grátis |
| **B — schema com dados** | coluna, tabela, tipo | cuidado moderado — recuperável com *expand/contract* |
| **C — saiu pra fora ou é irrecuperável** | id do post gravado na rede · permalink já entregue como prova · redirect URI registrada no app · token que o usuário autorizou · **evento que você NÃO gravou** | 🔴 **só essas merecem uma noite de pensamento** |

> ⚠️ **A armadilha:** gastar sexta-feira decidindo se a classe chama `Publicador` ou
> `PublisherService` (gaveta A, grátis) e resolver em 3 minutos qual é a chave de cliente
> (gaveta C, semanas de retrabalho).

---

## 🔴 O que É caro neste projeto (em ordem de dor)

1. **Chave de cliente** (`usuario_id`) — retrofit de isolamento é caçar vazamento pra sempre
2. **Idempotência de efeito externo** — ⚠️ post duplicado no perfil do cliente é irreversível
   **no mundo real**, não só no banco
3. **Histórico não gravado** — o que você não registrou hoje **não existe amanhã**; não há backfill
4. **Guarda das credenciais** — cifra + rotação de refresh token
5. **Semântica de data/hora** do agendamento
6. **Modelo de autorização** · 7. **i18n** · 8. **formato de API pública**

**Menos caro do que a fama sugere:** autenticação e modelo de dados comum — desde que haja
disciplina de migration.

**Barato, mas todo mundo trata como caro:** escolha de fila, storage, framework de UI,
estrutura de pastas, nome de classe. **Decida rápido e siga.**

---

## ⚠️ Os 3 achados que MUDAM nosso plano

### 1. O contrato do `Publicador` tem que nascer com DOIS verbos

Eu ia esperar a segunda rede pra desenhar o contrato. **Correto — mas com uma exceção que não
é especulação, é fato:**

O Bluesky publica e pronto. **O Instagram exige criar container → esperar `FINISHED` → publicar.
O TikTok exige iniciar → consultar até `PUBLISH_COMPLETE`.** Uma assinatura
`publicar(): string` nascida do Bluesky síncrono é **o retrabalho nº 1 garantido**.

✅ **Assumir desde o dia 1:** `iniciar()` e `verificar()` — dois verbos, forma assíncrona.
*(Isso não é abstração prematura: é fato conhecido de 3 das 4 redes.)*

### 2. A SEGUNDA rede deve ser a mais DIFÍCIL, não a mais fácil

Contra-intuitivo, mas certo por dois motivos: o Instagram é **o mais diferente
arquiteturalmente** (é ele que valida o contrato de dois verbos), e é o de **maior prazo
burocrático** — quanto antes começar, antes a fila anda.

*(Isso inverte o que eu tinha sugerido. A pesquisa está certa.)*

### 3. 🐛 Global Scope + fila = o bug clássico do Laravel multi-tenant

**O worker não tem usuário autenticado.** O escopo global vira `WHERE usuario_id IS NULL` e o
job não acha nada — ou pior, acha coisa errada. E `Queue::fake()` **esconde isso em todos os
testes**.

⚠️ **Nosso motor é inteiramente em fila.** Então: o job carrega o `usuario_id` explicitamente e
o resolve **sem depender de sessão** — e existe **teste que roda o job de verdade**, não
`Queue::fake()`.

---

## 📋 A ordem de construção

**Passo 1 — Esqueleto irreversível (2–3 noites)**
Só o que é gaveta C. Nada de tela bonita.
- `usuario_id` + Global Scope + **teste de isolamento**
- **ULID público** nas rotas (nunca expor id sequencial)
- **Unique de idempotência escopado por INTENÇÃO**, não por tentativa
- **Log append-only de eventos** (o que aconteceu, quando)
- **`payload_bruto` (json)** em `tentativas`: requisição + resposta crua de cada chamada, com
  token redigido — *é o insumo do nosso diferencial e do suporte*
- Data/hora: **hora local + fuso IANA**, nunca só timestamp
- `encrypted` nas credenciais · **Policy** no lugar de `if` espalhado

**Passo 2 — UMA fatia vertical, fim a fim (Bluesky)**
Do upload até **a conciliação gravar o permalink como prova**. Sem abstração nenhuma, código
concreto. É o "esqueleto que anda": prova que banco, fila, job, conciliação e tela conversam.

**Passo 3 — Segunda e terceira redes, DUPLICADAS**
Instagram e Facebook escritos de forma concreta, **aceitando duplicação**. É aqui que se
descobre o que de fato varia.
> A Nango construiu **centenas** de integrações e pivotou por causa disso: *"quanto mais
> abstraímos, menor a taxa de sucesso dos clientes"*. Núcleo comum **pequeno** + objeto de
> opções tipado por rede.

**Passo 4 — Só agora extrair o contrato**
Com três redes concretas na frente, a interface sai certa.

**Passo 5 — Por último:** UI caprichada, i18n, API pública, webhooks.

---

## 🔒 Não-negociáveis (caros de retrofitar)

- **Idempotência por intenção**, não por tentativa — sem isso, retry sobre timeout **duplica o
  post no perfil do cliente**
- **Estado "verificar" separado de "falhou"** *(já temos: `processando`)*
- **Token como sub-esquema por rede** — a Meta não tem par access/refresh, o TikTok tem, o
  Google depende do status do app
- **Limite por (rede, conta)**, não global
- **Leitor tolerante** na conciliação: campo faltando → *"conciliação pendente"*, **nunca
  "falhou"**

## 🧪 Testes de integração externa

- **Gravação de resposta real** (*self-initializing fake* do Fowler): a primeira execução grava
  a resposta verdadeira, as seguintes reproduzem.
  ⚠️ **A primeira gravação NÃO vem redigida** — limpar token antes de commitar.
- **Suíte de contrato semanal** contra a API real, agendada — pega mudança de API antes do cliente.
- ❌ Pact/CDC descartado: exige participação do provedor.

## ⏱️ A janela mais barata da vida do projeto

> *"A janela antes do primeiro cliente pagante é o momento mais barato da vida do projeto —
> é lá que se atravessam as portas de mão única."*

**Estamos exatamente nela.** Depois de existir dado real de cliente, tudo na gaveta C fica 10×
mais caro.

_2026-07-28 — 2 de 4 frentes concluídas (2 caíram por erro de conexão); as concluídas cobrem o
essencial._
