# Auditoria do módulo Meta contra a documentação oficial

_Feita em 2026-08-11, com a documentação da Meta lida no mesmo dia. Gatilho: a conexão do Facebook
passou a devolver **lista de Páginas vazia** depois de já ter funcionado._

> **O que esta auditoria procurou:** desalinhamento entre o que o código faz e o que a plataforma
> documenta — em endereço, nome de campo, permissão, ordem de chamada e significado do número.
>
> **O que ela achou:** cinco defeitos **silenciosos** — nenhum deles aparecia como erro em lugar
> nenhum. O quinto era a causa do bloqueio que gerou a auditoria, e só apareceu no fórum da Meta:
> uma permissão obrigatória desde 2024 que nenhuma mensagem de erro menciona.

---

## Método

Lidos por inteiro: `ConexaoComMeta`, `PublicadorFacebook`, `PublicadorInstagram`, `EnvioRetomavel`,
`ErroDaMeta` e o caminho de status da conta. Cada endereço, campo, métrica e permissão foi conferido
contra a página oficial correspondente:

- *Facebook Login for Business* — tipos de token e herança de acesso
- *Pages API — Get Started* — `GET /me/accounts` e tokens de Página
- *Reels Publishing* — `video_reels`, fases do envio e conferência de status
- *Video Insights (referência)* — **duas** listas de métricas: *Video* e *Reels*
- *Instagram Content Publishing* e *Resumable Uploads*
- *Instagram Media Insights (referência)* — métricas válidas por tipo de mídia

---

## ⛔ Achado 1 — a métrica do REEL não é a métrica do VÍDEO (DEC-157)

**O código pedia `total_video_views`.** A referência do `video_insights` separa as métricas em duas
listas: *Video metrics*, onde `total_video_views` mora, e *Reels metrics*, que usa outros nomes —
`blue_reels_play_count`, `fb_reels_total_plays`.

⛔ **E o produto só publica reel**, por `/video_reels`. Ou seja: **toda** leitura de visualização do
Facebook pedia uma métrica que não existe para o objeto lido.

⚠️ **Por que ninguém viu:** a chamada não quebra. Ela responde `200` com a lista vazia, o código lê
`null` — e `null` tem significado legítimo aqui (*"a rede não publica esse número"*, DEC-95). A tela
dizia **"sem leitura"** para o Facebook, para sempre, e isso é indistinguível de uma rede que não
respondeu.

**Corrigido:** pede `blue_reels_play_count` e cai para `fb_reels_total_plays`. A ordem é decisão
nossa: o primeiro é *quantas vezes o reel começou a tocar*; o segundo conta **reprises** junto.

---

## ⭐ Achado 2 — a retomada do Instagram estava desligada por engano (DEC-158)

O `EnvioRetomavel` trazia escrito: *"Só o Facebook documenta isso. No Instagram não há mecanismo
descrito."* **Documenta.** O guia de *Resumable Uploads* do Instagram descreve exatamente a retomada,
e ainda dá o exemplo: `"from here you can resume your upload in step 2 with offset=50002"`.

O que muda entre as duas redes é **o nome do campo**: o Facebook responde em `status`, o Instagram em
`video_status`. Perguntar pelo campo errado não devolve nulo — o Graph derruba a chamada inteira com
o erro 100.

⚠️ **O preço do engano:** todo tropeço de rede reenviava o vídeo **inteiro**, do zero — que é
exatamente o que essa classe existe para evitar.

⚠️ **E há uma armadilha de grafia**, agora coberta: a Meta escreve `bytes_transfered` (com um `r`) no
exemplo do Facebook e `bytes_transferred` no do Instagram, e chama o bloco de `upload_phase` no texto
corrido e de `uploading_phase` na tabela. Ler só uma grafia devolve `0` **em silêncio** — e `0` aqui
não parece defeito, parece envio novo.

---

## ⛔ Achado 3 — o semáforo estava morto (DEC-159)

**O diferencial declarado do produto (DEC-32) é o semáforo do token.** A auditoria contou quantos dos
onze publicadores acendem esse semáforo:

| Publicadores que marcam a conta com problema | 1 (YouTube) |
|---|---|
| Publicadores que **nunca** marcam | 10 |

E mais: `ContaSocialService::marcarComProblema()` — escrito com o comentário *"alimenta o semáforo
(DEC-32)"* — **não era chamado por ninguém**. Código morto no lugar exato do diferencial.

⚠️ **Por que passou despercebido na Meta:** o token de Página **não expira por tempo**. Sem data de
vencimento, parecia não haver o que vigiar. Mas ele não vence — **ele morre**: senha trocada,
aplicativo removido, papel perdido na Página, verificação pendente. A Meta responde `190`
(`OAuthException`) com subcódigos que dizem qual dos casos é.

⛔ **O comportamento antigo, por inteiro:** a conta ficava **verde e "Conectada"** na tela enquanto
recusava toda publicação. Pior, `is_transient` da Meta podia dizer "passageiro" — e o motor gastava as
três tentativas contra algo que só um humano resolve.

**Corrigido para a Meta:** `190` e os subcódigos de morte acendem o vermelho, põem a conta em
"Precisa de você" e **nunca** são tratados como passageiros. ⚠️ Com contraponto guardado: erro comum
(formato, proporção) **não** derruba a conta — alarme falso de "reconecte sua conta" é o mais caro
que existe aqui.

⚠️ **As outras 9 redes seguem sem isso.** Está anotado como pendência, não corrigido: cada rede
sinaliza credencial morta do seu jeito, e chutar o sinal de nove APIs de uma vez seria trocar um
silêncio por nove alarmes falsos.

---

## ⛔ Achado 4 — a causa da lista vazia: uma viagem economizada (DEC-160)

**O registro (DEC-156) fechou o caso.** A Meta respondeu:

```
corpo:      {"data":[]}
permissoes: read_insights, pages_show_list, instagram_basic, instagram_manage_insights,
            instagram_content_publish, pages_read_engagement, pages_manage_posts, public_profile
            — TODAS granted
```

Sete permissões concedidas, `pages_show_list` inclusive, e **nenhuma Página**. Isso elimina
permissão, elimina "não tem Página" e elimina o passo de seleção.

⛔ **O que sobrou foi a nossa própria chamada.** O código pedia tudo numa viagem só:

```
GET /me/accounts?fields=id,name,access_token,tasks,picture{url},
                        instagram_business_account{id,username,profile_picture_url}
```

⭐ **E a documentação descreve DUAS chamadas separadas:** primeiro `/me/accounts` sozinho, depois
`GET /{pagina}?fields=instagram_business_account`, com o **token da Página**.

⚠️ **Por isso funcionou antes e parou depois.** Enquanto a Página não tinha Instagram ligado, o campo
aninhado não tinha nada para resolver e a lista vinha inteira. **No dia em que ganhou**, o campo
passou a exigir uma leitura que o token de usuário não faz naquele contexto — e a Meta não devolveu
erro nem o campo nulo: devolveu **a lista vazia**, derrubando a Página junto.

⛔ **E a suíte provava o caminho errado com convicção.** O `Http::fake` devolvia o Instagram aninhado,
ou seja, reproduzia uma forma de API que a Meta não documenta. Teste verde sobre um `fake` inventado
é pior que teste ausente: ele afirma.

**Corrigido:** duas chamadas, como documentado. E falhar na segunda **não derruba a Página** — o
Instagram é acréscimo, e perder as duas porque a segunda viagem tropeçou transformaria acréscimo em
requisito.

---

## ⛔ Achado 5 — a CAUSA de verdade: `business_management` (DEC-164)

**Encontrada no fórum oficial da Meta, com duas confirmações independentes:**

> *"`business_management` é obrigatória para todas as versões da API"* — desde a v19, janeiro de 2024.
>
> *"Para acessar contas de um usuário que possui Páginas de negócio, o app precisa da permissão
> `business_management`. O problema acontece especificamente quando os usuários usam o **Meta
> Business Suite** — as Páginas deles não aparecem em `/me/accounts` sem essa permissão."*

⭐ **E a linha do tempo do banco fecha com precisão de dia:**

| Quando | O quê |
|---|---|
| **10/08 13:22** | conexão funcionou — a Página entrou no banco |
| **11/08** | o Instagram foi vinculado **pelo Business Suite**; a Página virou ativo de negócio |
| **11/08 em diante** | `/me/accounts` vazio em toda tentativa |

⛔ **O defeito é invisível por construção:** a Página existe, a pessoa é administradora, todas as
outras permissões são concedidas, o diálogo abre, e a lista chega vazia com `200`. **Nada, em lugar
nenhum, diz "faltou esta permissão".**

⚠️ **E teve uma ironia cruel no meio:** `business_management` **estava** nas dez permissões
selecionadas na primeira configuração — e o diálogo quebrava com *"Sorry, something went wrong"*
porque ela não estava habilitada nos **casos de uso** do app. Ao reduzir para as sete "necessárias",
o diálogo passou a abrir e **foi embora justamente a permissão que resolvia**.

**Corrigido:** `business_management` entrou nos escopos, e a mensagem de lista vazia passou a nomear
essa causa em vez de mandar a pessoa "marcar a Página" — conselho que a fazia refazer a autorização
em laço, procurando um passo que a Meta não ia oferecer.

⚠️ **Risco de lançamento registrado:** essa permissão é notoriamente difícil de aprovar na análise
("caso de uso pouco claro"), e exige vídeo mostrando a tela onde é usada. **Sem ela aprovada, cliente
com Página de negócio não conecta — e hoje isso é a maioria.**

### ✅ Confirmado em campo (2026-08-14)

Com `business_management` adicionada ao caso de uso **e** à configuração, a conexão passou:
**Página `Teste` e `@gabrielmoraes1997` conectadas na mesma autorização.** Três contas no painel.

⭐ **E a mesma sessão provou a regra da ordem, pelo avesso:** `pages_manage_engagement` marcada na
configuração **sem** estar habilitada no caso de uso derruba o diálogo com *"Sorry, something went
wrong"*; desmarcada, a autorização passa. **Primeiro no caso de uso, depois na configuração — nunca o
contrário.**

⚠️ **E isso resolve pela metade a pendência 2:** `pages_manage_engagement` **não está disponível**
nos casos de uso deste app. Se a leitura de visualização do Facebook falhar, é por aí — e agora se
sabe que habilitá-la exige mexer no caso de uso antes, não só na configuração.

---

## 🔍 O que o registro descartou

As hipóteses que o registro **matou** — e que teriam custado horas se tivessem sido "corrigidas" no
chute:

- a Página **1305185759343415** já foi lida com sucesso por este mesmo código antes;
- a conferência de permissões roda **antes** da busca e **passou** — `pages_show_list` foi concedida;
- portanto não é falta de Página nem de permissão.

⛔ **A restrição da Página não era a causa** — era a coincidência mais convincente da investigação, e
estava errada. O aviso de "Página não recomendada" apareceu no mesmo dia, o que fazia dele o suspeito
óbvio. Perseguir isso teria levado a confirmar identidade, mandar documento e esperar 48 horas por um
problema que era nosso.

⭐ **Foi o registro que decidiu, não o raciocínio.** `200` com lista vazia é o silêncio mais caro que
existe: sem ele, a única pista seria a frase que a pessoa leu na tela — que é justamente a nossa
suposição sobre o que aconteceu.

---

## ✅ Conferido e alinhado

Vale registrar o que **não** estava errado, para a próxima auditoria não refazer o caminho:

- `upload_phase: finish` — a documentação se contradiz (o texto diz `complete`), mas o exemplo
  executável e a tabela de enum usam `finish`. O código está certo, e o comentário já explicava.
- `Authorization: OAuth <token>` no `rupload` — correto; este host não aceita `Bearer`.
- Instagram: `views` e `shares` **são** os nomes atuais das métricas de reel; `impressions`, `plays` e
  `video_views` estão aposentados. O código já usa os novos.
- `is_ai_generated` é parâmetro documentado do container.
- `upload_type=resumable` exige *Facebook Login for Business* — que é o que o app usa.
- Conciliação do Facebook por `publishing_phase.publish_status`, e não por `processing_phase`.
- Conciliação do Instagram por `media_product_type`, e não por `media_type`.
- Pedir só campo documentado no nó Video: campo inexistente derruba a chamada inteira (erro 100).

---

## 📌 Pendências que a auditoria abriu

1. **O semáforo nas outras 9 redes** — cada uma sinaliza credencial morta do seu jeito.
2. **`pages_manage_engagement`** — a referência do `video_insights` lista essa permissão junto de
   `read_insights`; o guia de publicação de reels diz que `pages_read_engagement` basta. **As duas
   páginas se contradizem.** Só o campo prova quem está certo, e pedir permissão a mais tem custo na
   tela de autorização e na análise do aplicativo.
3. **Tarefa `ANALYZE`** — a referência exige que o token seja de quem pode analisar a Página. O código
   confere as tarefas de **publicação**, não a de análise: uma Página onde a pessoa publica mas não
   analisa conecta e nunca mostra número, sem dizer por quê.
