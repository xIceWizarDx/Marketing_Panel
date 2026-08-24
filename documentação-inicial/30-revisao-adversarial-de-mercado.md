# Revisão adversarial de mercado — o produto se sustenta como negócio?

_Feita em **2026-08-09**, contra o que o [modelo de negócio](19-modelo-de-negocio.md) e os
[diferenciais](13-diferenciais.md) afirmam._

> ⛔ **Este documento ataca o próprio plano comercial.** Não questiona se a dor existe — ela está
> documentada com evidência. Questiona se **alguém paga por isto, sozinho, todo mês**.
>
> ⚠️ Sete achados. Os três primeiros mexem no produto, não no discurso.

---

## ⛔ 1. Isto é uma FUNCIONALIDADE, não um fluxo de trabalho

**O que o plano diz:** a dor é *"publicado é mentira"*, e ela é real — nenhum dos nove concorrentes
relê o post.

**O ataque:** essa dor é **episódica**. Um post não sobe algumas vezes por ano. As pessoas assinam
software mensal para a dor **diária** — calendário, fila de horários, aprovação do cliente,
rascunho, relatório.

⛔ E o plano **recusa explicitamente** construir isso: *"commodity — não perca tempo"*.

**A consequência comercial:** viramos a **segunda** ferramenta. A pessoa continua no mLabs para o
dia a dia e nos usa para... publicar de novo, no mesmo lugar? Segunda ferramenta é a primeira a ser
cancelada quando o cartão aperta.

⭐ **Prova de entrega é uma funcionalidade matadora DENTRO de um agendador.** Vendida solta, ela é
seguro contra evento raro — e seguro se vende barato ou não se vende.

**As três saídas, e só uma é fácil:**

| Saída | O que exige |
|---|---|
| Virar o agendador completo | construir calendário, rascunho, aprovação — meses, e entra na briga de preço |
| Vender para quem o evento raro é caro | trocar o público, não o produto (ver achado 5) |
| Ser camada sobre a ferramenta que já usam | integrar com mLabs/Buffer — depende deles, e eles não querem |

---

## ⛔ 2. O preço-alvo pede o dobro por menos funcionalidade

**O que o plano diz:** R$ 49–79 por marca, contra R$ 25–55 do mercado, *"porque não vende
conveniência"*.

**O ataque:** o comprador compara lista de recursos antes de comparar filosofia. Hoje o produto
**publica agora, uma vez**. Não agenda, não tem calendário, não tem rascunho, não tem aprovação.

⛔ **Ninguém paga R$ 79 por um botão de publicar** — por mais honesto que ele seja.

⚠️ E o argumento *"uma publicação perdida custa mais que um ano de assinatura"* é verdadeiro e
**não vende**: é o mesmo argumento do seguro, e seguro só se vende depois do sinistro. O plano
inclusive identifica isso ao falar do **gatilho de compra** (*"já perdeu cliente por post que não
subiu"*) — o que significa que o mercado endereçável real não é "social media", é **"social media
que já se queimou"**. Muito menor.

---

## ⛔ 3. O custo variável do X come a mensalidade inteira

Isto não é opinião — é aritmética com os preços que a documentação oficial declara.

| Cenário | Conta |
|---|---|
| Marca publica 2×/dia no X, **com link** na legenda | 60 posts × US$ 0,20 = **US$ 12/mês** |
| Em reais (câmbio ~5,4) | **≈ R$ 65/mês** |
| Mensalidade-alvo | **R$ 49–79** |

⛔ **Uma única rede consome de 82% a 133% da receita daquela marca.** E link na legenda é o padrão de
quem faz marketing — é assim que se manda a pessoa para algum lugar.

⚠️ O modelo de negócio **não tem repasse de custo variável**. Ele foi escrito quando o X cobrava
assinatura; hoje é pagamento por uso.

**As saídas:** X fora do plano base (só em plano superior, ou com crédito do próprio cliente), ou
repasse explícito. **O que não dá é manter como está.**

---

## ⛔ 4. Onze redes contradizem a própria estratégia

**O que o plano diz:** *"não competir por preço nem por número de redes — só por confiabilidade"*.

**O que foi construído:** onze redes.

⚠️ Cada rede é **manutenção perpétua**: token muda, API deprecia, política muda. Nesta sessão o X
trocou o modelo de cobrança inteiro e a pesquisa do projeto ficou obsoleta. Onze redes mantidas por
uma pessoa é passivo, não ativo.

⛔ **E rede marginal quase nunca vende.** O comprador escolhe ferramenta pelas 2–3 redes que ele
usa. Ninguém assina por causa do Mastodon.

⭐ **O que salva o trabalho já feito:** ele não foi desperdiçado — foi onde o conhecimento nasceu
(o arredondamento do TikTok, o erro de digitação da Meta, o 206 do Mastodon). Mas **manter** as onze
ligadas é decisão diferente de **tê-las escrito**. Dá para deixar as periféricas visíveis só sob
demanda, e assumir manutenção só das que vendem.

---

## 5. O comprador certo talvez não seja "social media"

O plano aponta três perfis. Nos três, o post perdido **incomoda**. Existe público onde ele
**custa dinheiro na hora**:

| Público | Por que o post perdido é caro |
|---|---|
| **Lançamento de infoproduto** | a semana de lançamento tem data; post que não subiu na quarta não vale na sexta |
| **Franquia / rede com unidades** | campanha nacional que falha em 8 de 40 unidades vira problema de contrato |
| **Agência que presta contas** | o relatório de prova **é o produto** que ela entrega ao cliente dela |

⭐ **A terceira é a mais promissora**, e por um motivo específico: para ela, a prova não é
tranquilidade — é **entregável**. Isso muda o produto de "seguro" para "insumo do serviço que ela
vende", e insumo se paga sem discussão.

⚠️ E muda o que falta construir: **relatório exportável com as provas**, não calendário.

---

## 6. O diferencial é copiável em uma sprint

Reler o post são ~50 linhas por rede. Qualquer concorrente com um desenvolvedor copia em duas
semanas, se decidir que importa.

⛔ **Defensabilidade da manchete: quase zero.**

⭐ **O que é difícil de copiar** — e por isso deveria ser o discurso:

1. **Conferência que continua** (achado 1 da [revisão técnica](29-revisao-adversarial.md)). Reler uma
   vez é fácil; reler para sempre é compromisso operacional que concorrente com 50 mil clientes não
   assume — o custo dele é linear no número de posts vivos.
2. **O acervo de achados por rede.** Vinte e tantas armadilhas documentadas com fonte e data. Isso
   não se copia lendo a tela do produto.
3. **A honestidade como posição de marca.** Dizer "no LinkedIn não dá para conferir, e é por isso"
   é algo que quem vende conveniência **não pode dizer** sem se contradizer.

---

## 7. Duas promessas do plano têm conta escondida

**"Avisamos no WhatsApp" (DEC-32).** A API oficial do WhatsApp cobra por conversa e passa por
aprovação de negócio. É promessa com custo variável e barreira — igual ao X, e ainda não orçada.

**"Preço que não pune crescimento".** O plano anota isso como diferencial comercial **e** define
preço **por marca** — que pune crescimento exatamente igual a preço por canal. ⚠️ **A contradição
está dentro do mesmo documento.**

---

## O que eu faria, em ordem

| # | Movimento | Por quê |
|---|---|---|
| 1 | **Escolher o público da agência que presta contas** | é onde a prova é entregável, não conforto |
| 2 | **Construir o relatório de provas exportável** | vira o produto para esse público — e é pequeno |
| 3 | **Fazer a conferência continuar** (achado 1 da técnica) | é o único diferencial difícil de copiar |
| 4 | **Tirar o X do plano base** | hoje ele pode consumir a mensalidade inteira |
| 5 | **Congelar as redes periféricas** | manter onze é passivo; mantê-las escritas não custa |
| 6 | **Só então decidir preço** | como o próprio plano manda |

⚠️ **O que eu NÃO faria:** virar agendador completo. É a briga de preço que o plano já identificou
corretamente como perdida — e entrar nela agora joga fora a única posição defensável que existe.
