# Achados na especificação do YouTube

> Varredura completa da especificação oficial (`00-especificacao-oficial.json`, revisão
> 20260729) em **31/07/2026**. O que está aqui **não estava** no plano nem na pesquisa
> anterior — saiu de ler a spec campo a campo.

---

## 🔴 1. O YouTube pode ALTERAR o vídeo — e o padrão não é claro

O `videos.insert` aceita dois parâmetros que **modificam a imagem**:

| Parâmetro | O que faz |
|---|---|
| `autoLevels` | "Should auto-levels be applied to the upload" — corrige brilho e cor |
| `stabilize` | "Should stabilize be applied to the upload" — estabiliza tremor |

**Isso vai contra a promessa central do produto.** Vendemos *"seu vídeo não é degradado em
silêncio"* (DEC-33), e aqui existem duas alavancas que mexem na imagem sem o cliente saber.

**O que fazer:** mandar **`autoLevels=false` e `stabilize=false` sempre, explicitamente.**
A spec não declara o valor padrão — e depender de padrão não declarado é o oposto do que o
produto promete. Se um dia virarem opção na tela, é escolha da pessoa, nunca nossa.

---

## ⭐ 2. O YouTube agenda sozinho — não precisamos de agendador para ele

`status.publishAt`:

> "The date and time when the video is scheduled to publish. It can be set only if the privacy
> status of the video is **private**."

Ou seja: sobe como privado com `publishAt`, e o **próprio YouTube publica na hora marcada**.
Sem worker nosso rodando de madrugada, sem risco de o servidor estar fora do ar na hora.

**Consequência boa:** agendamento no YouTube é mais confiável que qualquer coisa que a gente
construísse. **Consequência a tratar:** a conciliação precisa entender que um vídeo `private`
com `publishAt` **não é falha** — está esperando a hora.

---

## ⭐ 3. O YouTube diz o que há de errado com o vídeo — de graça

A parte `suggestions` do `videos.list` devolve o diagnóstico **da própria plataforma**:

**`processingErrors`** — o arquivo nem é vídeo:
`audioFile` · `imageFile` · `projectFile` · `notAVideoFile` · `docFile` · `archiveFile`

**`processingWarnings`** — vai transcodificar mal:
`unknownContainer` · `unknownVideoCodec` · `unknownAudioCodec` · `inconsistentResolution` ·
`hasEditlist` · `problematicVideoCodec` · `problematicAudioCodec`

**`processingHints`** — dicas de qualidade:
- **`nonStreamableMov`** — *"o MP4 não é transmissível, isso vai atrasar o processamento. O
  átomo MOOV não estava no começo do arquivo."*
- **`sendBestQualityVideo`** — *"provavelmente existe uma versão melhor deste vídeo"*
- `sphericalVideo` · `spatialAudio` · `vrVideo` · `hdrVideo`

**`editorSuggestions`** — o que o YouTube acha que dá pra melhorar:
`videoAutoLevels` (brilho estranho) · `videoStabilize` (tremido) · `videoCrop` (tarjas nas
laterais) · `audioQuietAudioSwap` (áudio mudo)

### 🎯 Por que isso é ouro para o nosso diferencial

O **laudo de mídia** hoje é a nossa opinião sobre o arquivo. Com isto, ele passa a ter também
**a opinião do YouTube sobre o mesmo arquivo** — e de graça, numa chamada que já fazemos.

E dois desses achados **a gente consegue prever antes de enviar**, com o `ffprobe` que já temos:

| O YouTube reclama de | Dá pra detectar antes? |
|---|---|
| `nonStreamableMov` (átomo MOOV no fim) | ✅ **sim** — o `ffprobe` mostra |
| `inconsistentResolution` | ✅ sim |
| `unknownVideoCodec` / `unknownAudioCodec` | ✅ sim |
| `videoCrop` (tarjas laterais) | ⚠️ parcial — dá pra inferir da proporção |

**Avisar antes de subir 300 MB para o YouTube reclamar depois** é exatamente a promessa do
produto.

---

## 🔴 4. Declaração de conteúdo gerado por IA é campo da API

`status.containsSyntheticMedia`:

> "Indicates if the video contains **altered or synthetic media**."

**Liga direto com o interesse em cortes com IA.** Publicar conteúdo gerado ou alterado por IA
**sem declarar** é violação de política do YouTube — e o campo existe justamente para declarar.

**O que fazer:** quando o recurso de corte com IA existir, este campo é **obrigatório**, não
opcional. E precisa ser marcado na tela pela pessoa, não decidido por nós.

---

## 🔴 5. `notifySubscribers` vem LIGADO por padrão

> "Notify the channel subscribers about the new video. **As default, the notification is
> enabled.**"

Publicar vários cortes seguidos **notifica os inscritos a cada um**. É jeito rápido de irritar
a audiência de quem usa o produto — e o cliente culparia a ferramenta, com razão.

**O que fazer:** expor como escolha na tela, com padrão pensado. Provavelmente **desligado**
para publicação em lote e ligado para post único.

---

## ⭐ 6. Progresso real do processamento

`processingDetails.processingProgress` traz `partsTotal`, `partsProcessed` e **`timeLeftMs`**.

Dá para mostrar *"processando, faltam 2 minutos"* em vez de *"processando…"* indefinido — que
é a tela que todo concorrente entrega.

---

## 🟡 7. Miniatura pode ser possível — vale verificar

`thumbnails.set` aceita o escopo **`youtube.upload`**, que já pedimos. Tamanho até 50 MB,
JPEG ou PNG.

⚠️ **Não confirmado para Shorts.** A pesquisa do doc 20 registra a queixa de que *"nenhuma
ferramenta põe miniatura em Shorts"*, e a causa apontada é limite da plataforma. Mas a spec
mostra o método disponível no escopo que temos.

**Vale testar assim que houver canal conectado.** Se funcionar, é resposta direta à queixa nº 1
dos concorrentes. Se não funcionar, a gente sabe **por quê** e diz na tela.
*(Nota: miniatura personalizada costuma exigir canal verificado — outro ponto a confirmar.)*

---

## 🟡 8. Legenda exige o escopo que recusamos

`captions.insert` aceita **só** `youtube.force-ssl` e `youtubepartner`.

`force-ssl` é justamente o que **não pedimos** (DEC-41) — ele permite **apagar vídeos**, e o
medo disso é a queixa documentada nº 1 dos usuários.

**Decisão consciente:** ficamos sem envio de legenda. Vale mais não pedir poder de apagar o
canal de alguém. Se algum dia houver demanda real, a saída é pedir o escopo **só para quem
quiser**, com explicação — nunca por padrão.

---

## 🟡 9. Parar de chutar a categoria

Hoje o código manda `categoryId: '22'` fixo. Existe **`videoCategories.list`**, com
`regionCode` — a lista válida muda por país.

⚠️ A documentação **não afirma** que `categoryId` é obrigatório no `insert` (só no `update`).
Enquanto não confirmarmos, manter o envio, mas buscar a lista real em vez de fixar um número.

---

## 🟡 10. `madeForKids` é declaração legal, não preferência

`status.selfDeclaredMadeForKids` é a declaração de conteúdo infantil (COPPA). Hoje mandamos
`false` fixo.

**Declarar errado tem consequência legal para o dono do canal, não para nós.** Isso precisa ser
escolha explícita na tela — nunca um padrão nosso escondido no código.

---

## 💰 11. A conta da cota — e onde ela aperta de verdade

Fonte: <https://developers.google.com/youtube/v3/determine_quota_cost> (31/07/2026).

**São três bolsos separados, não um só:**

| Bolso | Limite diário |
|---|---|
| `videos.insert` | **100 envios** |
| `search.list` | 100 buscas |
| Todo o resto, somado | **10.000 unidades** |

**Custo por chamada:**

| Método | Custo |
|---|---|
| `videos.list` (a conciliação) | 1 |
| `channels.list` (ao conectar) | 1 |
| `videoCategories.list` | 1 |
| **`thumbnails.set`** | **50** ⚠️ |

⚠️ *"Toda requisição, mesmo inválida, custa pelo menos um ponto."* Retentativa também gasta.

### A conta do nosso motor

A conciliação consulta até 20 vezes por vídeo, com espera crescente:

```
100 vídeos/dia × 20 consultas × 1 unidade = 2.000 de 10.000    ✅ folga confortável
```

**O gargalo não é a cota geral — são os 100 envios/dia.** Isso já estava mapeado (DEC-24), e é
o teto que some com ~30 clientes ativos.

### ⚠️ Mas a miniatura muda a conta

```
100 miniaturas × 50 unidades = 5.000 de 10.000    🔴 metade do orçamento diário
```

Somando com a conciliação: **7.000 de 10.000**. Fica apertado, e sem margem para erro.

**Consequência:** se a miniatura funcionar (achado 7), ela **não pode ser automática**. Tem que
ser escolha por publicação — o cliente decide onde vale gastar. Ligar por padrão consumiria
metade da cota do projeto inteiro, para todos os clientes.

---

## ✅ 12. Nosso perfil de vídeo JÁ é o de Shorts — e sem gambiarra

Fonte: <https://support.google.com/youtube/answer/15424877> (31/07/2026).

> Vídeos enviados com **proporção quadrada ou vertical** e **até três minutos** são
> categorizados como Shorts no YouTube.

**A classificação é automática.** Não é preciso escrever `#Shorts` no título nem na descrição —
prática comum em ferramentas por superstição, e que só suja o texto do cliente.

Nosso perfil canônico (9:16, 3–180s) **produz Short por construção**. Nada a mudar.

**O que a tela precisa dizer:** vídeo **deitado** ou **acima de 3 minutos** vira vídeo comum,
não Short. A pessoa precisa saber disso **antes** de publicar — é informação que o laudo já tem
(proporção e duração vêm do `ffprobe`).

⚠️ A página não distingue explicitamente envio por API de envio pelo Studio. O critério
declarado é do **arquivo**, não do caminho — mas vale confirmar no primeiro envio real.

---

## 🔴 13. Não existe escopo que edite sem poder apagar

Comparando os escopos método a método:

| Método | Escopos aceitos |
|---|---|
| `videos.insert` | `upload` · `youtube` · `force-ssl` · `youtubepartner` |
| `videos.list` | `readonly` · `youtube` · `force-ssl` · `youtubepartner` |
| `thumbnails.set` | `upload` · `youtube` · `force-ssl` · `youtubepartner` |
| **`videos.update`** | `youtube` · `force-ssl` · `youtubepartner` |
| **`videos.delete`** | `youtube` · `force-ssl` · `youtubepartner` |

⚠️ **`update` e `delete` pedem exatamente os mesmos escopos.** Não há como pedir "editar" sem
levar junto "apagar".

### O que isso custa com o escopo mínimo (o que temos hoje)

Com `upload` + `readonly`, **não conseguimos**:
- corrigir título ou legenda de um vídeo já enviado
- 🔴 **virar um vídeo de privado para público depois que a auditoria sair**

Essa segunda dói: tudo o que for publicado antes da aprovação **fica privado para sempre**, do
nosso lado. A pessoa teria que abrir cada vídeo no YouTube Studio e mudar à mão.

### A escolha

| | Escopo mínimo (hoje) | Somar `youtube` |
|---|---|---|
| Publicar | ✅ | ✅ |
| Conferir (a prova) | ✅ | ✅ |
| Miniatura | ✅ | ✅ |
| Corrigir texto depois | ❌ | ✅ |
| Virar público após auditoria | ❌ | ✅ |
| **Poder apagar o canal da pessoa** | ✅ **não pedimos** | 🔴 **passamos a poder** |

⚠️ **Decisão do Gabriel, não minha.** O DEC-41 escolheu não pedir poder de apagar porque esse é
o medo nº 1 documentado nas entrevistas — gente que desistiu de conectar ferramenta por causa
disso. Trocar isso por conveniência é decisão de produto.

**Caminho intermediário, se um dia doer:** pedir o escopo maior **só para quem quiser**, com a
explicação do que muda — nunca por padrão, nunca escondido.

---

## ⭐ 14. O YouTube devolve a prova de degradação

`videos.list` com `part=contentDetails` traz:

| Campo | O que diz |
|---|---|
| **`definition`** | **`hd`** ou **`sd`** |
| `duration` | duração final (ISO 8601) |
| `hasCustomThumbnail` | se a miniatura personalizada pegou *(só o dono enxerga)* |
| `caption` | se tem legenda |
| `licensedContent` | se é conteúdo licenciado |

🎯 **`definition` é a prova de degradação que o produto vende.** Enviamos 1080×1920; se o
YouTube devolver **`sd`**, a plataforma está admitindo que o vídeo perdeu qualidade — e a gente
mostra isso ao cliente com a palavra da própria rede.

Nenhum concorrente faz isso. É a diferença entre *"achamos que degradou"* e *"o YouTube disse
que está em SD"*.

E **`hasCustomThumbnail` resolve o achado 7**: é assim que se confirma se a miniatura pegou em
Shorts, sem depender de suposição.

---

## ⭐ 15. `fileDetails` — o YouTube conta o que leu do arquivo

`part=fileDetails` devolve o que o **motor do YouTube** entendeu: `container`, `videoStreams`,
`audioStreams`, `durationMs`, `fileSize` e **`fileType`** (`video` · `audio` · `image` ·
`archive` · `document` · `project` · `other`).

**Vira conferência cruzada do nosso laudo.** O `ffprobe` diz uma coisa, o YouTube diz outra —
se discordarem, algo está errado no arquivo ou na nossa leitura, e vale saber.

---

## 🟡 16. Campos legais que ainda não tratamos

| Campo | O que é |
|---|---|
| `VideoPaidProductPlacementDetails.hasPaidProductPlacement` | declaração de **publieditorial/patrocínio** — exigência legal em vários países |
| `VideoAgeGating.restricted` · `.alcoholContent` | restrição etária e conteúdo alcoólico |
| `VideoMonetizationDetails.access` | **se o vídeo pode ser monetizado** — o objetivo do Gabriel |
| `videoTrainability.get` | se terceiros podem treinar IA com o vídeo |

⭐ **`monetizationDetails.access` merece destaque:** o objetivo do projeto é ganhar dinheiro com
o canal. Mostrar na lista se cada vídeo está monetizável é informação que vai direto ao ponto —
e que nenhum concorrente entrega.

---

## 📋 O que entra no plano

| # | Achado | Prioridade |
|---|---|---|
| 1 | `autoLevels=false` + `stabilize=false` explícitos | 🔴 contradiz a promessa do produto |
| 4 | `containsSyntheticMedia` quando houver corte com IA | 🔴 política da plataforma |
| 5 | `notifySubscribers` como escolha, não padrão ligado | 🔴 irrita a audiência do cliente |
| 10 | `madeForKids` como escolha explícita | 🔴 declaração legal |
| 3 | Trazer `suggestions` para dentro do laudo | ⭐ diferencial |
| 2 | `publishAt` = agendamento nativo | ⭐ economiza construir agendador |
| 6 | Progresso real com `timeLeftMs` | ⭐ melhor que "processando…" |
| 7 | Testar miniatura com o escopo atual | 🟡 verificar |
| 9 | `videoCategories.list` em vez de `22` fixo | 🟡 |
| 8 | Legenda fica de fora (escopo) | ✅ decidido |
| 11 | Miniatura **nunca automática** — custa 50 unidades (5.000/dia se ligada) | 🔴 se o achado 7 funcionar |
| 12 | Avisar quando o vídeo **não** vai virar Short (deitado ou >3min) | ⭐ o laudo já sabe |
| 13 | **Sem `update`/`delete` no escopo mínimo** — vídeo privado não vira público depois | 🔴 **decisão do Gabriel** |
| 14 | `definition: sd\|hd` = **prova de degradação pela própria rede** | ⭐ diferencial forte |
| 15 | `fileDetails` = conferência cruzada do laudo | ⭐ |
| 16 | Publieditorial · restrição etária · **monetização** | 🟡 |

_2026-07-31 — varredura da especificação oficial._

---

## Achados da REVISÃO (2026-07-31)

Encontrados relendo o código já pronto contra a documentação — nenhum deles apareceria antes
de o Gabriel tentar usar de verdade.

### R-1 ⛔ `publishAt` com vídeo público: a API recusa

A spec: `publishAt` *"só pode ser definido se a privacidade do vídeo for **private**"*.
Mandávamos os dois como a pessoa escolhesse — agendar com "público" quebraria o envio inteiro.

**Corrigido:** agendar força privado. Não há conflito real — agendar **significa** privado até a
hora marcada.

### R-2 ⛔ Gravávamos os escopos PEDIDOS, não os CONCEDIDOS

A tela do Google deixa a pessoa **desmarcar permissões**. A documentação é literal:

> *"Your app must verify which scopes were actually granted."*

Gravávamos a nossa lista fixa. Se a permissão de envio fosse desmarcada, a conta ficava
conectada e verde no painel — e falhava só no primeiro vídeo.

**Corrigido:** grava o que o Google devolveu e recusa a conexão na hora se faltar o envio.

### R-3 ⭐ Modo de Testes: a autorização morre a cada **7 dias**

> *"A project with a publishing status of 'Testing' is issued a refresh token expiring in 7 days."*

É exatamente o modo que vamos usar. Sem saber disso, a conexão cairia toda semana e pareceria
defeito nosso — o tipo de coisa que faz desconfiar do produto inteiro.

**Corrigido:** `invalid_grant` agora explica a regra com essas palavras.

### R-4 ⛔ Um 500 do Google matava a conta

Qualquer resposta sem sucesso marcava a conta como revogada, exigindo reconectar. Um 500 é
passageiro: o próximo job funcionaria normalmente.

**Corrigido:** só `invalid_grant` encerra a conta.

### R-5 ⛔ Erro de cota mandava criar um canal que já existe

`channels.list` falhando e "não tem canal" caíam na mesma mensagem. Com a API desligada no
Google Cloud, a pessoa leria *"crie um canal no YouTube"* — e o canal dela existe.

**Corrigido:** 403 aponta a API/cota, 401 aponta a autorização.

### R-6 ⛔ As mensagens em português não chegavam à tela

O retorno do Google é um GET vindo de fora. O redirecionamento automático de erro de validação
mandaria a pessoa **de volta ao site do Google**. Todas as mensagens cuidadas do serviço eram
escritas e jogadas fora.

**Corrigido:** o controller captura e mostra na tela de Conexões.

### R-7 🧹 `qualidadeEntregue()` existia e nunca era chamado

A conciliação já lia `contentDetails` e **descartava** o `definition` — a prova de degradação,
um diferencial do produto, era buscada e jogada fora.

**Corrigido:** a conciliação guarda em `destinos.qualidade_entregue`.

### R-8 ⚠️ `APP_URL` divergia do endereço real

`http://localhost` no `.env`, `127.0.0.1:8000` no servidor. Daria `redirect_uri_mismatch` no
primeiro clique.

**Corrigido:** alinhado, **e travado por teste** que compara `services.google.redirect` com a
rota real — divergir de novo quebra a suíte.

---

## Achados da leitura da referência de erros (2026-08-01)

Lacuna que eu mesmo tinha marcado como pendente: a referência de erros do `videos.insert` nunca
tinha sido lida. Baixada em `documentacao/04-erros-do-insert.md`.

### R-9 — Os 14 erros do envio não estavam tratados

O publicador conhecia os motivos de **recusa** (que chegam depois, quando o YouTube já moderou o
vídeo) e **nenhum** dos erros do envio em si. Resultado: mensagem genérica justamente nos casos
em que existe algo a fazer — título com `<`, categoria que saiu do ar, agendamento no passado.

**Corrigido:** 9 traduzidos com o que fazer. Os outros 5 são de campos que nunca enviamos
(`invalidRecordingDetails`, `invalidVideoGameRating`, `invalidFilename`, `defaultLanguageNotSet`)
— para esses, o erro cru do YouTube é mais honesto que uma tradução inventada.

### R-10 — `mediaBodyRequired` estava indo para recusa definitiva

"O envio chegou sem o arquivo" é falha de **transporte**, não do conteúdo. Recusar de vez
descartaria um envio que a próxima tentativa faria sem problema.

**Corrigido:** vira retentativa.

### R-11 — Guardávamos o código HTTP, não o motivo

`400` não ajuda ninguém a investigar; `invalidTags` ajuda. Agora o motivo é o que fica gravado
na tentativa.

---

## Sobre Shorts: o que a documentação NÃO diz

⚠️ **A API não menciona Shorts em lugar nenhum.** Não está no contrato — é comportamento do
produto YouTube. A central de ajuda diz apenas: **até 3 minutos** e **vertical**.

O critério exato de proporção e a classificação automática de envios por API **não são
publicados**. Por isso o laudo diz o que dá para verificar no arquivo — *"vertical e com menos de
3 minutos, é o que o YouTube pede"* — e **não promete** que o vídeo vira Short.

Prometer o que não se pode garantir seria repetir, do nosso lado, o defeito que o produto combate.
