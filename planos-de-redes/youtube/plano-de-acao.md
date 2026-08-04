# PLANO DE AÇÃO — YouTube

> Escrito **depois** de consultar a documentação oficial, não antes.
> Fontes: `guides/using_resumable_upload_protocol`, `docs/videos/insert`, `docs/videos`
> (consultadas em 31/07/2026).

---

## 🔴 O que a consulta corrigiu

Sete pontos em que a implementação escrita de memória **divergia do documentado**:

| # | Escrito de memória | O que a documentação diz | Impacto |
|---|---|---|---|
| 1 | `description` cortada em 5000 **caracteres** | máximo de 5000 **bytes** | 🔴 legenda com acento estoura o limite e a API recusa |
| 2 | Sessão expirada = qualquer resposta que não seja 308 | expirada é **404**; 5xx é retentativa | 🔴 erro passageiro virava "recomeçar do zero" |
| 3 | Sucesso do upload tratado como 200 | é **201 Created** | 🟡 `successful()` cobre os dois, mas o teste estava impreciso |
| 4 | `uploadStatus: failed` com mensagem genérica | há **`failureReason`** com 6 valores | 🟡 o cliente não sabia o motivo |
| 5 | 6 valores de `rejectionReason` tratados | são **10** | 🔴 faltavam `uploaderAccountClosed` e `uploaderAccountSuspended`, que significam **conta morta**, não vídeo recusado |
| 6 | `tags` limitadas por quantidade (15) | limite é de **500 caracteres no total**, contando vírgulas e aspas | 🟡 15 tags longas estouram |
| 7 | `Content-Type` da abertura implícito | exige `application/json; charset=UTF-8` | 🟡 provável que funcione, mas não é o documentado |

**Confirmado como estava certo:** custo de 1 unidade em bucket próprio (100/dia) · escopos
`youtube.upload` + `youtube.readonly`, sem `force-ssl` · `title` em 100 caracteres ·
`Content-Range`/`Range` do protocolo retomável · MIME `video/*`.

**Ficou em aberto:** a documentação **não afirma** que `snippet.categoryId` é obrigatório no
`insert` (só diz que é no `update`). Mantemos enviando — é inofensivo — mas anotado como não
verificado.

---

## 🗺️ O plano

### Fase A — Corrigir o publicador contra o documentado
- [ ] `description` cortada por **bytes** (`mb_strcut`), não por caracteres.
- [ ] `tags` cortadas pelo **orçamento de 500 caracteres**, contando vírgula e aspas.
- [ ] Sessão expirada **só em 404**; 5xx vira retentativa com o handle preservado.
- [ ] `failureReason` traduzido (codec · conversion · emptyFile · invalidFile · tooSmall ·
      uploadAborted).
- [ ] `rejectionReason` completo (10 valores).
- [ ] ⭐ `uploaderAccountClosed` / `uploaderAccountSuspended` → **marcam a CONTA como com
      problema**, não só o destino. É conta morta: insistir nos próximos envios é inútil.
- [ ] `Content-Type: application/json; charset=UTF-8` explícito na abertura da sessão.

### Fase A.2 — O que a varredura da especificação achou
> Detalhe e justificativa em [`achados.md`](achados.md).

- [ ] 🔴 **`autoLevels=false` + `stabilize=false` explícitos.** O YouTube pode corrigir brilho e
      estabilizar o vídeo. Isso contradiz a promessa central (DEC-33), e a spec **não declara o
      padrão** — depender de padrão não declarado é o oposto do que vendemos.
- [ ] 🔴 **`notifySubscribers` vira escolha.** Vem **ligado por padrão**: publicar vários cortes
      seguidos notifica os inscritos a cada um, e o cliente culparia a ferramenta com razão.
- [ ] 🔴 **`selfDeclaredMadeForKids` vira escolha explícita.** É declaração legal (COPPA), com
      consequência para o dono do canal. Não pode ser padrão nosso escondido no código.
- [ ] 🔴 **`containsSyntheticMedia`** obrigatório quando existir o corte com IA — publicar
      conteúdo alterado por IA sem declarar viola a política.
- [ ] ⭐ **`definition: hd|sd` = prova de degradação pela própria rede.** Enviamos 1080×1920;
      se o YouTube devolver `sd`, ele está admitindo perda de qualidade. É a diferença entre
      "achamos que degradou" e "o YouTube disse que está em SD". **Nenhum concorrente faz.**
- [ ] ⭐ **`hasCustomThumbnail`** confirma se a miniatura pegou em Shorts — sem suposição.
- [ ] ⭐ **`fileDetails`** vira conferência cruzada do laudo: o `ffprobe` diz uma coisa, o
      YouTube diz o que leu. Discordância é sinal de problema.
- [ ] 🟡 **`monetizationDetails.access`** — mostrar se o vídeo está monetizável. É o objetivo do
      projeto, e nenhum concorrente entrega.
- [ ] ⭐ **Trazer `suggestions` para o laudo.** O YouTube devolve o próprio diagnóstico do
      arquivo (`processingWarnings`, `processingHints`, `editorSuggestions`) numa chamada que já
      fazemos. E dois deles — `nonStreamableMov` e `inconsistentResolution` — a gente **prevê
      antes de enviar**, com o `ffprobe` que já temos.
- [ ] ⭐ **`publishAt` = agendamento nativo.** O YouTube publica sozinho na hora marcada; não
      precisamos de agendador para ele. A conciliação precisa saber que `private` + `publishAt`
      **não é falha**, é espera.
- [ ] ⭐ **Progresso real:** `processingProgress.timeLeftMs` permite "faltam 2 minutos" em vez de
      "processando…" indefinido.
- [ ] 🟡 **Testar miniatura.** `thumbnails.set` aceita o escopo `youtube.upload` que já pedimos.
      Se funcionar em Shorts, responde à queixa nº 1 dos concorrentes; se não, saberemos o porquê.
      ⚠️ **Custa 50 unidades** (contra 1 da conciliação). Ligada por padrão, consumiria
      **metade da cota diária do projeto**. Se funcionar, entra como escolha por publicação —
      nunca automática.
- [ ] ⭐ **Avisar quando o vídeo NÃO vai virar Short.** Vertical/quadrado até 3 min vira Short
      **automaticamente** — sem `#Shorts` no texto. Deitado ou acima de 3 min vira vídeo comum,
      e a pessoa precisa saber **antes** de publicar. O laudo já tem proporção e duração.
- [ ] 🟡 **`videoCategories.list`** em vez de `categoryId: '22'` fixo (a lista muda por país).
- [ ] ✅ **Legenda fica de fora:** `captions.insert` exige `force-ssl`, o escopo que recusamos
      de propósito (permite apagar vídeos). Decisão consciente, não esquecimento.

### Fase A.3 — Conformidade (o que a auditoria verifica)
> Detalhe em [`achados-de-conformidade.md`](achados-de-conformidade.md).
> ⚠️ Não é sugestão: é o documento que **reprova projeto**.

- [ ] 🔴 **PARAR DE CORTAR TEXTO EM SILÊNCIO.** A política proíbe *"modificar valores fornecidos
      pelo usuário (truncar, anexar, alterar) sem consentimento explícito"* — e o publicador faz
      isso **três vezes** (título em 100, legenda em 5000, tags em 15). **Recusar e avisar
      antes**, como o laudo já faz com o vídeo.
- [ ] 🔴 **Links obrigatórios na tela de conectar:** Termos do YouTube (com a frase de vínculo)
      + Política de Privacidade do Google + o que guardamos e por quanto tempo.
- [ ] 🔴 **Reconferência a cada 30 dias.** A política exige atualizar o dado **e** confirmar que
      a autorização continua válida. Um comando agendado com `channels.list` resolve os dois —
      e alimenta o semáforo (DEC-32) de brinde.
- [ ] 🔴 **Revogar apaga o dado do YouTube em 7 dias.** Conflito com o histórico resolvido como
      no DEC-44: apaga o dado da pessoa (nome do canal, avatar, id), preserva o evento.
- [ ] 🟡 **Nomear o canal** no botão e na confirmação — a política exige que a ação seja
      identificável como do YouTube e que se diga qual canal recebe.
- [ ] 🟡 **`selfDeclaredMadeForKids` como escolha explícita** (COPPA).
- [ ] ⚠️ **Anotado para o corte com IA:** publicar automaticamente o que a IA cortou, sem a
      pessoa aprovar, **é o caso proibido** pela política.

### Fase A.4 — Decisão pendente do Gabriel
- [ ] 🔴 **Escopo: aceitar não poder virar privado→público depois?**
      `videos.update` e `videos.delete` pedem **os mesmos escopos**. Não há como pedir "editar"
      sem levar "apagar". Com o escopo mínimo de hoje, **todo vídeo publicado antes da auditoria
      fica privado para sempre** do nosso lado — a pessoa teria que mudar à mão no Studio.
      *Quadro comparativo em [`achados.md`](achados.md#-13).*

### Fase B — O que o produto precisa dizer ao cliente
- [ ] ⚠️ **Enquanto a auditoria não sair, todo vídeo fica privado** — o `privacyStatus` que a
      pessoa escolher é ignorado pelo YouTube. A tela precisa **avisar antes**, não deixar
      descobrir depois.
- [ ] Consequência para a promessa do produto: um vídeo privado **tem link, mas ninguém abre**.
      A prova de entrega vale menos ali, e isso precisa estar escrito na tela — dizer
      "publicado" com um link que só o dono vê seria a meia-verdade que o produto critica.
- [ ] Cota de **100 envios/dia no projeto inteiro** (somando todos os clientes): quando estourar,
      o destino vai para `aguardando_janela`, não para `falhou`.

### Fase C — Conectar o canal (OAuth)
- [ ] Escopo mínimo: `youtube.upload` + `youtube.readonly`. **Nunca `force-ssl`** — ele
      permitiria apagar vídeos, e é o medo nº 1 documentado nas entrevistas (DEC-41).
- [ ] `access_type=offline` + `prompt=consent`: sem os dois, o Google não devolve o token de
      renovação e a conexão morre em uma hora.
- [ ] `state` na sessão contra CSRF.
- [ ] Renovação de token **serializada com trava** — dois jobs renovando juntos invalidam o
      token um do outro.

### Fase D — Revisar
- [ ] Reler o publicador inteiro contra este documento, item a item.
- [ ] Conferir se o contrato do `Publicador` continua servindo ao Bluesky sem remendo.

### Fase E — Testar
- [ ] Guardiões do envio retomável: abre sessão · guarda o handle **antes** de enviar · retoma
      de onde parou · sessão 404 recomeça · 5xx **não** recomeça.
- [ ] Guardiões da conciliação: `processed` publica · `uploaded` espera · `rejected` falha com
      motivo · conta suspensa derruba a conta.
- [ ] Guardiões de limite: título obrigatório · descrição em bytes · tags no orçamento.
- [ ] Guardião de escopo: a URL de autorização **não** contém `force-ssl`.

---

## ✅ Como saber que está pronto

Publicar um vídeo de teste **no canal real do Gabriel**, privado, e ver o destino chegar a
`publicado` com o link — passando pelo motor inteiro (fila, envio, conciliação).

**Depende dele:** criar o projeto no Google Cloud e gerar a credencial OAuth. É o único passo
que não dá para adiantar por código.

_2026-07-31 — plano escrito após consulta à documentação oficial._
