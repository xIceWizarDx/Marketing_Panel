# X — achados da documentação oficial

> Lido antes de escrever qualquer linha, como manda a regra.
>
> ⛔ E o primeiro achado não é técnico: **aqui publicar custa dinheiro, e uma escolha de texto muda o
> custo em treze vezes.**

---

## ⛔ X-1 — Link na legenda custa 13× mais

| Operação | Preço |
|---|---|
| Post: criar | US$ 0,015 |
| **Post: criar (com URL)** | **US$ 0,200** |

Numa operação de 500 posts por mês: **US$ 7,50 sem link, US$ 100,00 com link em todos.**

⛔ **A pessoa precisa saber antes de escrever, não na fatura.** É a única rede do painel em que o
texto muda o preço — e o painel sabe ler o texto antes de enviar.

⚠️ E a conta piora sozinha: hashtags não custam nada, mas um `linktr.ee` na legenda multiplica por
treze **cada** publicação daquele grupo.

---

## ⭐ X-2 — A prova custa um décimo de centavo

*"Owned Reads are requests made by your own developer app for your own data"* — **US$ 0,001**.

⭐ Reler o post que publicamos é exatamente isso. A tese do produto (DEC-31) sai por US$ 0,001 por
conferência — e a conciliação roda até vinte vezes, o que dá **US$ 0,02** no pior caso.

⚠️ Mas é a primeira rede em que **insistir tem preço**, e não só limite de uso. Conciliar sem teto
aqui seria queimar crédito de alguém.

---

## ⛔ X-3 — O código de autorização vive 30 SEGUNDOS

> *"code: Authorization code (expires in 30 seconds)"*

⛔ Uma **ordem de grandeza** abaixo de qualquer outra rede do painel — o LinkedIn dá 30 minutos.

⚠️ Qualquer coisa feita antes da troca (ler perfil, conferir grupo, gravar no banco) pode consumir a
janela e queimar a autorização. E o erro que aparece é o genérico *"a autorização não pôde ser
confirmada"*, que manda a pessoa procurar no lugar errado — já vimos esse filme com a Meta.

**A troca tem que ser a primeira coisa que acontece na volta.**

---

## ⛔ X-4 — O token vive 2 HORAS

Mais curto que o do TikTok (24 h) e que os 60 dias do LinkedIn.

⛔ **E sem `offline.access` não existe token de renovação nenhum.** Esquecer esse escopo dá uma
conexão que funciona por duas horas e morre — sem nada ter mudado.

⚠️ Renovar aqui é parte de publicar, não rotina de madrugada. Mesma resposta do TikTok (DEC-118).

---

## ⛔ X-5 — PKCE é obrigatório, e o segredo precisa sobreviver à ida e volta

`code_challenge` + `code_challenge_method=S256`. O `code_verifier` nasce na ida e é exigido na volta:
**ele vai para a sessão junto com o `state`**, ou a troca falha sem recuperação possível.

⚠️ É a primeira rede do painel com PKCE. As outras guardam só o `state`.

---

## ⛔ X-6 — `media.write` é escopo SEPARADO

> *"media.write — Upload media, such as photos and videos, on your behalf."*

⚠️ Fácil de esquecer, e o sintoma engana: a conta conecta, o texto sobe, **e o vídeo não**.

---

## ⚠️ X-7 — A documentação NÃO declara os limites do arquivo

Nenhuma página consultada diz, para vídeo de post: tamanho máximo, duração, proporção, taxa de
quadros, codecs, quantidade de mídias por post, nem o limite de caracteres do texto.

⛔ **Nada disso pode ser inventado.** O painel aplica o limite do perfil canônico do produto
(doc 07 §6), e a recusa da rede — quando vier — ganha frase própria em vez de virar palpite.

---

## ⭐ X-8 — Cada rede ordena os pedaços de um jeito diferente

| Rede | Como a ordem é dita |
|---|---|
| YouTube | faixa de bytes (`Content-Range`) |
| LinkedIn | ordem dos recibos (`ETag`) |
| TikTok | faixa de bytes, e o total arredonda para baixo |
| **X** | **`segment_index`, um número por pedaço** |

⚠️ Quatro protocolos de envio em pedaços, quatro convenções. Nenhum deles empresta código para o
outro, e tentar generalizar seria criar uma abstração que erra nos quatro.
