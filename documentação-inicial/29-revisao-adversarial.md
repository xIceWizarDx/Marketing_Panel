# Revisão adversarial — o que está errado com o projeto

_Feita em **2026-08-09**, com onze redes escritas e 614 guardiões verdes._

> ⛔ **Este documento ataca o projeto de propósito.** Ele não lista melhorias desejáveis: lista onde
> as promessas do produto não se sustentam contra o próprio código. Cada achado tem a evidência ao
> lado, em ordem de dano.
>
> ⚠️ **Suíte verde não entrou como argumento.** Os oito achados abaixo convivem com 614 testes
> passando — três deles são defeitos que o cliente sentiria.

---

## ⛔ 1. A tese do produto tem prazo de validade de três horas e meia

**O que o produto promete:** *"HTTP 200 não é publicado"* — o painel só afirma que subiu depois de
reler o post na rede (DEC-31). É a frase que separa este produto de todos os concorrentes.

**O que o código faz:** `ConciliarDestinoJob` pergunta no máximo **20 vezes**, com espera crescente
(1, 2, 3… minutos) — cerca de **3h30 no total**. Depois **para para sempre**.

⛔ **Moderação de rede não trabalha nesse relógio.** Um vídeo derrubado no dia seguinte continua
marcado como "No ar", com a mesma confiança de sempre. **A crítica que fazemos aos concorrentes passa
a valer para nós a partir da quarta hora.**

⭐ **E o conserto é pequeno, porque a peça já existe.** Todo publicador já sabe reler o post — é o
`conciliar()` de cada um. Falta um comando periódico que releia o que está **publicado** e rebaixe o
que sumiu, com a data da última conferência visível na tela.

⭐ Isso muda a natureza da afirmação: de "conferimos uma vez" para **"conferimos e continuamos
conferindo"**. Nenhum concorrente tem a primeira; a segunda é uma categoria à parte.

**É a maior alavanca do projeto hoje.**

---

## ⛔ 2. O botão "Tentar de novo" é garantido a falhar

**O caminho, todo ele real:**

1. Publica no YouTube e no Instagram;
2. YouTube sobe, Instagram falha;
3. a publicação vira `concluida_com_falhas` — que `StatusPublicacao::ehTerminal()` considera **final**;
4. `PublicacaoService::liberarArquivo()` **apaga o arquivo na hora** (DEC-59);
5. a tela mostra "Tentar de novo" no destino que falhou;
6. o clique dispara o job, o publicador procura o arquivo, não acha, e falha de novo.

⚠️ `EnvioDePublicacao::reprocessarDestino()` confere **só o status** do destino. Não confere se ainda
existe arquivo.

⛔ O projeto tem uma regra explícita — *"botão que leva a erro é pior que botão ausente"* — aplicada
no YouTube sem credencial, no Threads sem endereço público e no TikTok sem auditoria. **Aqui ela está
sendo violada.**

**As duas saídas honestas:** ou o arquivo sobrevive enquanto existir destino recuperável, ou o botão
desaparece quando o arquivo já saiu — dizendo que para tentar de novo é preciso reenviar o vídeo.

---

## ⛔ 3. `conciliar()` publica em sete dos onze publicadores — o nome mente

Threads, LinkedIn, X, Pinterest, Mastodon, Instagram e Bluesky **criam o post dentro do método
chamado "conciliar"**. O nome diz conferência; o método faz publicação.

⚠️ **Isto não é preciosismo de nomenclatura — já cobrou o preço duas vezes.** Nesta mesma sessão
escrevi, no LinkedIn e no X, um tratamento de tempo esgotado que devolvia "tentar de novo" — porque
"conciliar" soa como leitura, e leitura é segura de repetir. Nos dois casos isso publicaria o post
**vinte vezes**. Os dois foram corrigidos em revisão, não em escrita.

⛔ Um nome que induz ao mesmo erro duas vezes é um defeito de projeto, não de atenção.

**Saída:** `avancar()` para o que muda estado, `conferir()` para o que só lê — ou um nome único que
não prometa leitura.

---

## 4. 614 testes, e nenhum contrato com a realidade

Toda resposta de rede nos testes é um `Http::fake` **escrito por quem leu a documentação**. Não há um
único corpo de resposta real gravado (`tests/Fixtures/` tem dois vídeos e nada mais).

⛔ **A suíte continuaria verde se as APIs mudassem amanhã.** Ela prova que o código concorda com a
minha leitura — não que ele concorda com a rede.

⚠️ E esta sessão provou que a documentação mente:

- a Meta escreve `INVALID_ASPEC_RATIO`, sem o `T`, e troca `INVALID_FRAME_RATE` por
  `FAILED_FRAME_RATE` entre duas leituras da mesma página;
- o X trocou assinatura por pagamento por uso, e a pesquisa antiga do projeto estava desatualizada;
- o TikTok manda arredondar o número de pedaços **para baixo**, ao contrário de todo mundo;
- o Pinterest não entrega documentação legível por máquina — só a spec salvou;
- LinkedIn e Threads declaram limites que se contradizem **na mesma página**.

⭐ **Conserto barato e de alto retorno:** na primeira conexão real de cada rede, gravar a resposta de
verdade em `tests/Fixtures/redes/` e testar os leitores contra ela. É a diferença entre *"meus dublês
concordam comigo"* e *"a rede concorda comigo"*.

---

## 5. Custo e cota são cegos

| Rede | O que ela cobra ou limita | O que o painel sabe |
|---|---|---|
| X | **US$ 0,015 por post; US$ 0,20 com link** | nada |
| TikTok | 6 inicializações por minuto | nada |
| LinkedIn | 150 requisições por dia | nada |
| Threads | 250 posts por 24 h | consulta só depois de falhar |

⚠️ Publicar em cinco grupos pode gastar dinheiro ou estourar cota **sem nenhum aviso prévio** — o
painel só descobre pelo erro. O aviso de link no X (DEC-126) é o único lugar onde custo aparece
antes, e ele é uma frase, não uma contabilidade.

---

## 6. A ressalva de prova só existe para o LinkedIn

`notaDaProva()` foi criada para dizer, na tela, que o LinkedIn não permite reler o post. Mas:

- **YouTube** sobe tudo privado enquanto a auditoria do Google não sair;
- **TikTok** nem publica enquanto não for auditado (DEC-124).

⚠️ Os três têm prova degradada, e a tela só diferencia um. **Meia honestidade é pior que nenhuma**,
porque ensina a confiar no silêncio.

---

## 7. Trinta e duas fugas do escopo do dono

`ContextoDoUsuario::semEscopo()` aparece **32 vezes**. Cada uma é justificável — job e comando não têm
sessão — e cada uma é, por construção, um lugar onde o isolamento entre clientes está desligado.

⚠️ Não existe teste que enumere as 32 e prove que cada uma continua justificada. A trigésima terceira
vai entrar sem ninguém perceber.

---

## 8. `trustProxies(at: '*')` confia em qualquer proxy

Necessário hoje: sem ele, atrás do túnel, o Laravel enxergava HTTP e o navegador bloqueava a página
inteira.

⚠️ Em produção, atrás de um proxy mal configurado, isso deixa forjar cabeçalhos de origem. **Precisa
virar lista fechada no deploy** — e o deploy ainda não existe, então dá para acertar antes.

---

## ⭐ O que o projeto acertou, e devia explorar mais

**Os guardiões que quebram de propósito.** Ligar uma rede nova sem decidir onde o título dela vai
parar, ou sem alinhar o laudo de imagem com o publicador, **derruba a suíte**. Foi o único mecanismo
que pegou defeito sozinho — três vezes seguidas, e antes de o defeito existir.

⚠️ Hoje só duas invariantes têm esse tratamento. Merecem o mesmo:

- **toda rede declara o que faz num tempo esgotado ao criar post** (repetir ou parar) — é o defeito
  que apareceu duas vezes;
- **toda rede tem prova, ou tem nota dizendo por que não tem** — resolve o achado 6 de vez;
- **toda rede diz se cobra, e quanto** — resolve o achado 5.

⭐ A diferença entre "a gente lembra" e "o teste reprova" foi, nesta sessão, a diferença entre achar
o defeito na escrita e achá-lo na revisão.

---

## Ordem recomendada

| # | Achado | Por quê primeiro |
|---|---|---|
| 1 | **Botão que falha** (#2) | é uma mentira ativa na tela hoje, e o conserto é pequeno |
| 2 | **Prova que continua** (#1) | é o produto; hoje ele expira em 3h30 |
| 3 | **Renomear `conciliar`** (#3) | barato, e impede o mesmo bug de voltar pela terceira vez |
| 4 | **Contratos gravados** (#4) | assim que houver conexão real de cada rede |
