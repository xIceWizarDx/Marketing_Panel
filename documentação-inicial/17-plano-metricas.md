# Plano de ação — o contador ao lado da prova

_Criado em 2026-08-05. Pesquisa da documentação oficial das 9 redes feita na mesma data._

> **A ideia em uma frase:** o produto já prova que o post está no ar. Agora ele mostra, **ao lado
> dessa prova**, o número que a própria rede publica sobre ele — e escreve por extenso quando a rede
> não publica nenhum.
>
> Regra de conclusão: **suíte verde, tipos e lint limpos, grep-zero, conferido no navegador**.

---

## Por que agora, e por que só até aqui

O pedido foi *"métricas de cada canal: número de inscritos, gráfico de inscrições e visualizações em
cada rede"*. A consulta à documentação oficial das nove redes respondeu três coisas, e as três mudam
o tamanho do que dá para fazer:

1. **O número de agora sai hoje, praticamente de graça.** No YouTube é uma palavra a mais em duas
   chamadas que o produto já faz. No Bluesky é uma chamada nova, sem autorização nenhuma.
2. **O gráfico ao longo do tempo não sai.** Ele depende de um escopo novo do Google, e mexer na
   submissão de um aplicativo que já está em verificação recomeça a fila. Fora isso, ele exige uma
   máquina ligada todo dia — e cada dia desligado é um buraco que não se recupera depois.
3. **"Visualização" não é a mesma coisa em duas redes.** O Bluesky não tem esse número; o YouTube
   conta play e replay de Short; Instagram, TikTok e Pinterest contam de outros jeitos. Somar tudo
   numa barra só é tecnicamente correto e comercialmente enganoso.

⭐ **O que este plano entrega é o recorte que continua a tese**, e não um relatório ao lado dela:
número de inscritos a pessoa já vê no aplicativo da rede; o que só este produto consegue dar é *a
prova de que o post está no ar **e sendo visto***.

---

## A matriz que a pesquisa devolveu

| Rede | Seguidores | Views do post | Série por dia | O que exige |
|---|---|---|---|---|
| **Bluesky** | sim | **não existe no protocolo** | não | nada |
| **YouTube** | sim (arredondado) | sim | sim, pronta | escopo novo + 2 aprovações |
| Instagram | sim | sim | parcial | App Review + verificação de empresa |
| Facebook | sim | sim (só acumulado) | sim | mesmo App Review + página com 100+ curtidas |
| Threads | sim | sim | parcial | mesmo App Review |
| TikTok | sim | sim | não | 2 App Reviews + auditoria |
| LinkedIn | sim (página) | sim | parcial (2 dias atrás) | aprovação Community Management |
| X | sim | sim | não | pré-pago, ~US$ 0,01 por leitura |
| Pinterest | sim | sim | sim — **mas é proibido guardar** | App Review |

**Só as duas primeiras linhas entram neste plano.** As outras sete estão bloqueadas por aprovação,
por dinheiro ou por cláusula de contrato — nenhuma por falta de código.

---

## As decisões

**DEC-93 — o produto mostra o NÚMERO DE AGORA, não a curva ao longo do tempo.** Três motivos
somados, e cada um sozinho já bastaria: o gráfico do YouTube exige `yt-analytics.readonly`, um escopo
novo num aplicativo que **já está em verificação** — e mexer na submissão durante a revisão recomeça
a fila que trava o produto inteiro; a curva exige coleta todo dia, e dia com a máquina desligada é
buraco permanente, porque nenhuma rede devolve "quantos seguidores eu tinha em tal dia"; e nas outras
redes ela ou não existe ou é proibido guardar. ⛔ **Um gráfico furado num painel que se vende por não
mentir é pior que nenhum gráfico.**

**DEC-94 — um bloco POR REDE, nunca uma tabela comparativa.** Coluna igual para todas obriga a
inventar um valor para a célula que não existe — e é exatamente aí que o painel começa a mentir. Cada
rede mostra o que ela publica; o que ela não publica vira **frase escrita**: *"o Bluesky não publica
contagem de visualizações"*. ⭐ Isso não fica feio: fica sério. É o mesmo argumento do *"HTTP 200 não
é publicado"*, aplicado ao número em vez de aplicado ao post.

**DEC-95 — `null` é uma resposta com frase própria, e ZERO é outra coisa.** Quatro situações
diferentes viram `0` se ninguém separar: *a rede não tem esse número* (visualização no Bluesky), *o
dono escondeu* (inscritos ocultos no YouTube — o campo **some** da resposta, não vem zero), *a rede
ainda não calculou*, e *nós ainda não lemos*. Coluna nula nunca vira `0` na tela; ela vira texto.

**DEC-96 — métrica NÃO entra no contrato do `Publicador`, nem em `ResultadoConciliacao`.** Interface
separada, `LeitorDeMetricas`, implementada só por quem tem o que ler. Dois motivos: o contrato do
publicador trava as 15 redes e mexer nele é decisão cara (0.L); e amarrar o contador à prova faria a
**prova** depender de uma cota que pode acabar — a prova é o que o produto vende, e ela não pode
ficar refém de um número enfeite.

**DEC-97 — não guardamos histórico: a coluna é sobrescrita.** Sem gráfico não há foto diária para
guardar. ⛔ E há um motivo além da simplicidade: as Políticas do YouTube **proíbem criar métrica
derivada** dos dados deles — *"ganhou 12 inscritos hoje"*, calculado subtraindo duas fotos nossas, é
exatamente isso. O caminho permitido é `subscribersGained` da Analytics API, que é o escopo do
DEC-93. Quando o gráfico existir, ele nasce de lá, não de conta nossa.

**DEC-98 — ler métrica NUNCA pode derrubar a publicação — e por isso um defeito sai antes.** Hoje
`ReconferirContasDoYoutube` transforma **qualquer** resposta que não seja 2xx numa lista vazia, e
lista vazia marca a conta como `Erro` com *"O canal não está mais acessível"* — o que bloqueia
publicar até alguém reconectar na mão. Um 403 de cota ou um 500 do Google, que são passageiros,
desligariam o produto para todo mundo do YouTube. ⚠️ Ler métrica consome a **mesma cota** que
publicar; aumentar a leitura sem consertar isso é aumentar a chance de o dia ruim do Google virar
apagão.

---

## ⚠️ Uma correção à pesquisa, confirmada no código

A pesquisa afirmou que os contadores do Bluesky viriam **de graça na conciliação**, porque ela usaria
`getPosts`. **Não é o caso aqui:** a nossa conciliação usa `com.atproto.repo.getRecord`, que lê o
repositório do autor direto — prova mais forte, porque não depende do índice do AppView ter alcançado
o post. `getRecord` devolve o **registro**, não os contadores.

Consequência: no Bluesky o contador é **uma chamada a mais**, e a conciliação **não muda**. Trocar o
endereço da conciliação para ganhar o contador de brinde seria enfraquecer a prova para ganhar um
enfeite.

---

## O que a tela mostra

**Na janela de detalhe de uma rede** — abaixo do nome de cada conta, o número de seguidores e
**quando ele foi lido** (*"lido hoje às 04:20"*). Sem leitura ainda, a linha não existe.

**Na linha de cada publicação** — ao lado do link que já prova que o post está no ar, os contadores
que aquela rede publica. Só os que ela publica.

⛔ **Nada disso aparece na Visão geral.** Ela responde *"como está a entrega"*; contador de rede é
outra pergunta, e misturar as duas é como nasce a tela que ninguém sabe para que serve.

---

## Fase 0 — O defeito que sai antes de tudo (DEC-98)

- [x] **0.1** `ReconferirContasDoYoutube` separa **"a API respondeu que não"** de **"a API não
      respondeu"**
- [x] **0.2** Passageiro (5xx, 403 de cota, 429): não mexe no status, tenta amanhã
- [x] **0.3** Autorização mesmo (401, 403 de acesso): marca a conta, como já faz
- [x] **0.4** ⚠️ O 403 do Google serve para as duas coisas — a diferença está em `error.errors[].reason`

**Pronto quando:** um dia ruim da API do Google não desliga a publicação de ninguém.

---

## Fase 1 — O contrato e onde o número mora

- [x] **1.1** `LeitorDeMetricas` — interface separada do `Publicador` (DEC-96)
- [x] **1.2** `MetricasDaConta` e `MetricasDoPost` — objetos de valor, todo campo `?int`
- [x] **1.3** ⭐ `null` significa **"esta rede não publica este número"** e atravessa até a tela
      (DEC-95)
- [x] **1.4** `contas_sociais`: `seguidores`, `metricas_lidas_em`
- [x] **1.5** `destinos`: `visualizacoes`, `curtidas`, `comentarios`, `compartilhamentos`,
      `metricas_lidas_em`
- [x] **1.6** `RegistroDePublicadores::leitorDe()` devolve o leitor **ou `null`** — sem exceção:
      rede sem leitor é o caso normal, não erro
- [x] **1.7** Glossário atualizado (é a fonte única dos nomes, DEC-18)

**Pronto quando:** existe um lugar só que sabe o que é uma métrica, e ele não sabe o que é rede.

---

## Fase 2 — YouTube

- [x] **2.1** Conta: `channels.list` com `part=snippet,statistics` → `subscriberCount`
- [x] **2.2** Post: `videos.list` com `part=statistics` → `viewCount`, `likeCount`, `commentCount`
- [x] **2.3** ⚠️ **Inscritos ocultos: o campo SOME da resposta** (`hiddenSubscriberCount`). Ausente
      vira `null`, jamais `0` (DEC-95)
- [x] **2.4** ⚠️ O número vem **arredondado para 3 algarismos** — é assim para todo mundo, inclusive
      para o dono do canal. A tela diz isso
- [x] **2.5** ⛔ `favoriteCount` **sempre vem 0** e não significa nada: não entra
- [x] **2.6** ⛔ `dislikeCount` não existe mais publicamente: não entra
- [x] **2.7** ⚠️ Enquanto a auditoria não passar, todo vídeo sobe **privado** — e vídeo privado tem
      view zero **de verdade**. A tela precisa saber dizer isso, ou a primeira demonstração parece um
      sistema quebrado

**Pronto quando:** o número do YouTube aparece sem que a cota mude de patamar.

---

## Fase 3 — Bluesky

- [x] **3.1** Conta: `app.bsky.actor.getProfile` → `followersCount`
- [x] **3.2** Post: `app.bsky.feed.getPosts` → `likeCount`, `repostCount`, `replyCount`
- [x] **3.3** ⛔ **Visualização não existe no protocolo.** `visualizacoes` fica `null` e a tela
      escreve a frase — não dá para calcular nem estimar
- [x] **3.4** ⚠️ Os contadores são **opcionais** no protocolo: ausente é `null`, não `0`
- [x] **3.5** ⛔ A conciliação **não muda** (ver a correção acima)

**Pronto quando:** o Bluesky mostra o que tem e escreve o que não tem.

---

## Fase 4 — Quando o número é lido

- [x] **4.1** Comando `metricas:atualizar`, diário
- [x] **4.2** Contas: todas as de rede que tem leitor
- [x] **4.3** Posts: só os que estão **no ar** e foram publicados nos **últimos 30 dias** — post de
      seis meses não muda mais, e ler tudo cresce sem limite
- [x] **4.4** ⛔ Nunca dentro da requisição da tela: a tela mostra o que está guardado, com a data da
      leitura. Chamar a rede no meio do carregamento faz a tela travar quando a rede está lenta
- [x] **4.5** ⚠️ `ContextoDoUsuario` definido por conta antes de consultar — comando não tem sessão
- [x] **4.6** Falha de uma conta não derruba as outras (DEC-98 de novo, agora no comando novo)

**Pronto quando:** o número se atualiza sozinho e ninguém percebe que ele veio de fora.

---

## Fase 5 — A tela

- [x] **5.1** Detalhe da rede: seguidores + *"lido …"*, frase pronta do servidor
- [x] **5.2** Linha da publicação: os contadores ao lado do link da prova
- [x] **5.3** ⭐ Só o que aquela rede dá. O que ela não dá vira **frase**, nunca `0` (DEC-94/95)
- [x] **5.4** Sem leitura ainda: nada aparece — não um traço, não um zero

**Pronto quando:** olhar um post do Bluesky e um do YouTube lado a lado não deixa dúvida sobre por
que os números são diferentes.

---

## Fase 6 — Guardiões

- [x] **6.1** ⛔ 403 de cota e 500 do Google **não** mexem no status da conta
- [x] **6.2** ⛔ 401 e 403 de acesso **mexem**
- [x] **6.3** Inscritos ocultos viram `null`, não `0`
- [x] **6.4** Contador ausente no Bluesky vira `null`, não `0`
- [x] **6.5** Visualização no Bluesky é sempre `null`
- [x] **6.6** O comando não vaza métrica de um dono para outro
- [x] **6.7** Conta que falha não impede as seguintes de atualizar

---

## Fase 7 — O gráfico que dá para fazer hoje _(acrescentada em 2026-08-06)_

⚠️ Um gráfico **só de visualizações** seria hoje um gráfico de zeros: o Bluesky não conta
visualização e o YouTube sobe tudo privado. Por isso ele compara **na medida de cada rede**.

- [x] **7.1** `Plataforma::metricaDeComparacao()` — YouTube por visualização, Bluesky por curtida
- [x] **7.2** Um gráfico **por rede**, nunca uma tabela com as duas
- [x] **7.3** Todas as barras na medida compartilhada (a do post que mais teve)
- [x] **7.4** Sai da lista **inteira do grupo**, não da página aberta — senão o gráfico muda de
      conclusão ao virar a página
- [x] **7.5** Só na aba "Tudo": gráfico que ignora o filtro ao lado de uma lista que o obedece são
      dois números para o mesmo fato
- [x] **7.6** Teto de 8 barras; **menos de 2 não vira gráfico** — uma barra de 100% ao lado de nada
      não compara
- [x] **7.7** ⭐ Zero em tudo é **estado com frase**, não gráfico vazio
- [x] **7.8** Reusa `BarraDeEntrega` — zero código de gráfico novo (DEC-92)

**Pronto quando:** dá para ver qual post funcionou sem abrir rede nenhuma.

---

## ⛔ O que fica de fora, de propósito

**Gráfico ao longo do tempo** (DEC-93). Reabre quando: a auditoria do YouTube tiver passado, houver
servidor rodando 24h, e existirem pelo menos três redes publicando. Aí `yt-analytics.readonly` entra
com a tela já pronta para a filmagem que a submissão exige.

**Tabela comparando redes** (DEC-94). Ela obriga a inventar célula.

**Visualizações somadas de todas as redes.** Cada rede define *view* de um jeito, e duas nem têm.
O total seria um número que não existe em lugar nenhum.

**Métrica derivada** — *"ganhou 12 inscritos hoje"*, *"crescimento de 3%"* (DEC-97). Proibido por
escrito pelo YouTube, e sem base nas outras.

**Instagram, Facebook, Threads, TikTok, LinkedIn, Pinterest, X.** Nenhuma por falta de código: cinco
esperam aprovação, uma proíbe guardar o dado (Pinterest diz literalmente *"call the API each time"*)
e uma cobra por leitura (X).

**Visualização de perfil** — não existe no TikTok nem no Bluesky.

**Métrica na Visão geral.** Ela responde "como está a entrega". Contador é outra pergunta.
