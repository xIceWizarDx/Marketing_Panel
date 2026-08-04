# TELAS — especificação, uma a uma

> O que cada tela mostra, o que faz, e **o que muda no celular** (DEC-38).
> Navegação por **tarefa**, rede como **filtro** (DEC-17 revisada).
> Vocabulário PT-BR (DEC-15) · rótulo ≠ chave (DEC-18) · sistema fluido (DEC-37).
>
> ⚠️ Este documento descreve o que **existe**. O que ainda não foi construído vem marcado
> com 🔜 — nunca escrito no presente, para o documento não virar propaganda de si mesmo.

---

## 🧭 Navegação

**Sidebar do cliente — 3 itens fixos, independente de quantas redes existam:**

```
📊 Visão geral       ← landing pós-login; é aqui que ficam as redes
📋 Publicações       ← histórico com a prova; é daqui que se publica
⚙️  Minha conta
```

⛔ **Publicar não é item de menu.** Ele é um **botão dentro de Publicações**, que abre o
compositor por cima da lista (DEC-60). Item de menu descreveria uma tela; não há tela.

⛔ **Mídias não existe.** Enviar acontece dentro do compositor — o produto não guarda acervo
(DEC-59), então não há o que gerenciar numa tela própria.

⛔ **Conexões não existe como tela.** A grade de redes é uma **seção da Visão geral** (DEC-63):
"como está tudo?" é a pergunta da porta de entrada, e responder em duas telas obrigava a passar
pelas duas para ter certeza.

⭐ **A regra que separa os casos:** *Publicar* é **ação** — começo, meio e resultado; modal serve.
*Conexões* é **estado** — pertence à tela que a pessoa abre primeiro.

**Admin (esqueleto — DEC-16):** `👥 Clientes · 🕵️ Logs de impersonação · ⚙️ Minha conta`

**📱 No celular:**
- Sidebar vira **gaveta** (`--sidebar-width: 0`, DEC-37)
- Os 3 itens viram **barra inferior fixa** — alcance do polegar
- O compositor abre em tela cheia, com o botão de publicar fixo no rodapé
- Banner de impersonação fica **fixo no topo**, acima de tudo

---

## 1. 📤 O compositor *(modal por cima de Publicações)*

**A tela mais importante — e ela não é uma tela.** Abre pelo botão **Publicar**, dentro de
Publicações.

⚠️ **Modal, mas por rota de verdade** (`/publicar`). Recarregar a página reabre no mesmo ponto e
o botão voltar fecha o compositor em vez de sair do painel. Modal guardado só em memória perderia
o texto escrito à mão — o defeito U-9 do estudo de usabilidade.

**Três decisões numa coluna, um veredito ao lado.** A ação vive num rodapé que **não rola**:
botão que exige rolar até o fim é botão escondido, e é o que a pessoa veio clicar.

**Vídeo**
- ⛔ **Nada é sugerido.** Não há lista de vídeos anteriores, porque não há acervo (DEC-60)
- Arrastar/escolher do computador · o arquivo aparece **depois** de enviado, com miniatura,
  duração, tamanho e link de prévia **9:16** (tamanho fluido, DEC-37)
- Trocar de arquivo é **reenviar** — não há de onde escolher
- ⭐ **Laudo do arquivo** (DEC-32) no painel ao lado:
  `MP4 · H.264 · 1080×1920 · 45s · 12 MB · 30 fps` → **"passa intacto em todas as redes"**
  ou **"vamos recodificar o áudio (está em 256 kbps)"**, sempre **por rede** e em português

**Texto**
- Título · legenda · hashtags, com **contador** por rede (o menor limite manda)
- ⚠️ O teto do campo vem da **rede escolhida**, não de um 255 inventado: o YouTube corta em 100,
  e deixar digitar mais é deixar escrever para levar erro depois

**Para onde**
- Etiquetas com a **marca da rede**, lado a lado — mais rápidas de varrer que linhas inteiras
- Conta que não pode publicar aparece esmaecida, com o motivo
- Ao republicar, as contas onde **já** foi publicado vêm **desmarcadas e avisadas** —
  republicar na mesma conta é engano quase sempre, e publicação não tem desfazer

🔜 **Falta:** personalizar legenda por destino (DEC-11) e o bloco de confirmação por rede
(visibilidade do YouTube, privacidade do TikTok — regra YT-A07).

**📱 No celular:** ocupa a tela inteira; o rodapé com o botão continua fixo.

---

## 1b. 🔁 Publicar em outra rede

Botão no cartão da publicação. Abre o **mesmo** compositor com o texto pronto.

⚠️ **Leva o texto, não o vídeo (DEC-61).** O arquivo saiu quando a publicação terminou, e a
janela diz isso sem rodeio: *"O texto veio da publicação anterior. Envie o vídeo de novo e
escolha as redes."* Reenviar o mesmo arquivo o devolve ao **mesmo registro** (DEC-58).

---

## 2. 📋 Publicações

**Lista o histórico.** É aqui que o diferencial aparece.

- **Filtros fixos no topo:** busca · status · **chips de rede** (`Todas · YouTube · Instagram…`)
- **Colunas:** miniatura · título · **status por destino** · data · redes
- ⭐ **O status é honesto (DEC-31):**
  `Agendado` · `Enviando` · `Processando na rede` · **`No ar` 🔗** · `Falhou`
  → **"No ar" só aparece com o link verificado**
- Falhou → mostra o **motivo em português** + botão **reprocessar**
- Uma publicação com 4 destinos mostra os 4 status separados

**📱 No celular:** cada linha vira **card expansível** (padrão EmpiresCloud):
```
┌────────────────────────────────────┐
│ [thumb]  Lançamento produto    ⋮   │  ← ações no CABEÇALHO (nunca somem)
│          2 no ar · 1 falhou        │
│          ▼ toque para detalhes     │
├────────────────────────────────────┤
│ ▶️ YouTube      No ar 🔗            │
│ 📸 Instagram    No ar 🔗            │
│ 🎵 TikTok       Falhou — token…     │
│                 [Reprocessar]      │
└────────────────────────────────────┘
```
Filtros viram **chips roláveis** na horizontal, fixos no topo.

---

## 3. 🔌 Suas redes *(seção da Visão geral)*

⛔ **Só aparecem as redes CONECTADAS.** Mostrar as catorze de uma vez enchia a tela de coisa que
não é da pessoa — e o que é dela ficava do mesmo tamanho de um "em estudo". Conta desconectada
também não aparece: a linha dela sobrevive porque o histórico aponta para ela, mas quem
desconectou não quer a rede de volta na tela.

⭐ **O catálogo inteiro mora atrás do `+`**, num modal. Escolher rede acontece uma vez; olhar as
suas, todo dia.

**Grade de quadradinhos de tamanho fixo** — inspirada no bundle.social, mas com o que falta lá:

```
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│    ▶️ YouTube     │ │   📸 Instagram   │ │    🎵 TikTok     │
│  🟢 Conectado    │ │  🟡 Expira em 5d │ │  ⚪ Não conectado│
│  @meucanal       │ │  @minhaconta     │ │                  │
│  [Configurar]    │ │  [Reconectar]    │ │   [Conectar]     │
└──────────────────┘ └──────────────────┘ └──────────────────┘
```

- ⭐ **Semáforo do token** (DEC-32) — o que nenhum concorrente mostra
- Rede ainda não liberada aparece **esmaecida** com "em breve" (DEC-36)
- Várias contas da mesma rede = vários cartões (DEC-10)
- Clicar em **Configurar** abre os padrões **daquela rede**: conta padrão, legenda padrão,
  visibilidade padrão *(⚠️ nunca no TikTok — privacidade sem default é regra auditada)*

**📱 No celular:** 1 cartão por linha, empilhados.

---

## 4. 📊 Visão geral *(landing pós-login)*

⭐ **A ordem é a resposta**, de cima para baixo — quem abre o painel quer saber *o que aconteceu
enquanto eu não estava olhando*, e cada seção responde uma parte disso:

1. **O que espera você** — pendências. ⚠️ **Some quando não há nada:** um bloco que vive dizendo
   "está tudo bem" treina a pessoa a ignorá-lo, e no dia do problema de verdade ela não olha.
2. **Como está** — os três lados do mesmo fato: `no ar` · `a caminho` · `não subiram`. A falha do
   lado do acerto, no mesmo tamanho. "A caminho" e "não subiram" só aparecem quando existem.
3. **Suas redes** — a grade da seção 3, com o semáforo do token à vista.
4. **Primeiros passos** — só enquanto houver passo por fazer; some sozinho quando tudo está feito.
5. **Últimas publicações** — 5 cartões quadrados com a miniatura de fundo, iguais aos de
   Publicações. O link da prova cobre o cartão inteiro.

**📱 No celular:** as seções empilham na mesma ordem; os quadrados quebram de linha sozinhos.

---

## 5. ⚙️ Minha conta

`Perfil · Senha · Aparência (claro/escuro) · Notificações (e-mail · sininho · WhatsApp) ·
Meus dados e privacidade` *(baixar dados · excluir conta — regras BR-01/02)*

---

## 6. 👥 Admin — Clientes *(esqueleto, DEC-16)*

- Lista: nome · e-mail · status · contas conectadas · **botão Impersonar**
- Criar cliente = nome + e-mail → **envia link de definição de senha** (não digita senha)
- Durante impersonação: **banner fixo no topo** com "Modo impersonação — [nome]" + **Sair**

**📱 No celular:** card expansível, **Impersonar** no cabeçalho do card.

---

## 🎨 Padrões que valem em todas as telas

| Padrão | Regra |
|---|---|
| **A forma padrão é o QUADRADO** | Resumo, rede e publicação vivem em quadrados de lado fixo (`Quadro`). ⚠️ Grade que estica vira retângulo quando há poucos itens, e o painel muda de cara conforme o conteúdo |
| **Retângulo só quando é a forma certa** | Aviso com frase e ação, e lista de passos — texto quer largura. O que não existe é retângulo **por acidente de grade** |
| **Tabela → card** | No celular, toda listagem vira card expansível: resumo visível, detalhes ao expandir |
| **Ações no cabeçalho** | Ação de linha vai no topo do card — **nunca some no mobile** |
| **Filtros fixos** | Barra de filtros gruda no topo ao rolar |
| **Estado vazio** | Toda lista tem ilustração + frase + **ação sugerida** ("conecte sua primeira rede") |
| **Erro em português** | Nunca código cru da API — sempre motivo + o que fazer |
| **Carregamento** | Esqueleto (skeleton), não spinner |
| **Fonte crítica** | ⚠️ Nunca `text-xs` em status ou link de prova (vira ~9,7px — DEC-37) |
| **Marca do YouTube** | Logo **clicável** nas telas que mostram dados do YouTube (regra YT-G01/02) |
| **Movimento** | Toda animação respeita `prefers-reduced-motion` |

_2026-07-28 · revisto em 2026-08-04 para descrever o que existe: o compositor virou modal por cima
de Publicações, as telas de Mídias e Conexões saíram e a sidebar do cliente tem 3 itens._
