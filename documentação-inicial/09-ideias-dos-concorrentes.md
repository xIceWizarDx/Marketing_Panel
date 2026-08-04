# O que os concorrentes ensinam — e o que não vale copiar

_2026-08-04. Estudo das 41 telas em `referencias-layout/` (Buffer, Publer, bundle.social)._

> Complementa o [estudo de usabilidade](08-estudo-de-usabilidade.md): lá eu olhei o que **nos**
> falta; aqui olhei o que **eles** fazem. Nem tudo que aparece nessas telas é bom — parte é
> excesso que atrapalha, e separar as duas coisas é o trabalho.

---

## ⭐ As cinco ideias que eu traria hoje

### C-1 — Apagar só a mídia que NUNCA foi usada

O Publer avisa, dentro da própria biblioteca:

> *"Free plan: Unused media is stored for 7 days. Photos and videos **not attached to a post** are
> removed after 7 days. Upgrade to keep your library forever."*

**É melhor que a política que eu propus** (DEC-53) num ponto que muda tudo: eles apagam só o que
**nunca virou publicação**. Quem usou o produto não perde nada.

Faz sentido dos dois lados. Arquivo que subiu e nunca foi publicado é custo puro, sem contrapartida.
Arquivo publicado tem um registro amarrado nele — e é justamente o histórico que faz o cliente
voltar.

E repare onde o aviso mora: **na biblioteca**, não escondido nos termos. A pessoa vê a regra no
lugar onde ela se aplica.

### C-2 — "Conferir conexão agora"

O bundle.social tem um botão por rede, com este texto:

> *"Rode uma verificação manual quando a publicação, a análise ou o perfil parecerem
> desatualizados. Se falhar, reautorize a conta."*

**Isso é exatamente a nossa tese aplicada à conexão.** Já temos a reconferência diária
(`youtube:reconferir`); falta o botão para a pessoa perguntar **agora**.

E resolve um problema concreto nosso: em modo de Testes o Google encerra a autorização a cada 7
dias. Com o botão, dá para conferir **antes** de gravar e publicar — em vez de descobrir no meio.

### C-3 — Portal de conexão compartilhável

Também do bundle.social: um **link que você manda para outra pessoa**, e ela conecta as contas
sociais dela num portal hospedado, sem entrar no seu painel.

**Encaixa direto no caminho de revendedor/agência** que você já mencionou. O intermediário manda o
link, o cliente final autoriza as próprias redes, e **nenhuma senha passa por ninguém**. Hoje a
única saída seria o cliente entregar a senha — que é o que a gente combateu desde o começo.

### C-4 — Abas por ciclo de vida, com contador

O Buffer divide as publicações em **Fila (0) · Rascunhos (0) · Aprovações · Enviados (0)**, com o
número na aba.

Resolve o meu U-8 (busca e filtro) de um jeito melhor que um filtro solto: o número na aba já
responde *"tem coisa parada?"* sem clicar. Para nós seria **Em andamento · No ar · Falharam** — e
"Falharam (2)" chamando atenção é onde há trabalho a fazer.

### C-5 — Enviar o arquivo de dentro do compositor

No Buffer, a área de arrastar arquivo fica **dentro** da janela onde se escreve.

Na época o nosso caminho era: Mídias → enviar → Publicar → escolher. **Duas telas para uma intenção
só.** Quem já tem o vídeo na mão quer publicar, não gerenciar arquivo.

✅ **Feito em 2026-08-04, e mais fundo que a ideia original:** a área de envio não ficou só dentro
do compositor — ela virou a **única** forma de escolher o arquivo. Não há lista de vídeos anteriores
porque não há acervo (DEC-60).

---

## 👍 Ideias boas, para depois

**Miniatura na lista de publicações** (bundle.social tem coluna própria). Reforça o U-2: a
miniatura vale em todo lugar onde se escolhe ou se reconhece um vídeo. ✅ **Feito** — e virou o que
sustenta o histórico depois que o arquivo sai (DEC-56).

**Grade ou lista, à escolha** (Publer). Grade para reconhecer pelo visual, lista para comparar
dados.

**Contador de uso visível** — *"Publicações do mês 0/20"* ao lado do botão de melhorar plano
(bundle.social). Honesto: a pessoa vê o limite antes de esbarrar nele.

**Preço por conta conectada** (Publer: US$ 5 ou 10 por conta, com contador +/− e total calculado ao
vivo). Modelo simples de entender e que cresce junto com o uso.

**Calendário com os dias passados desabilitados** (Publer). Detalhe pequeno: não deixa nem tentar
agendar para ontem.

---

## ⛔ O que eu NÃO copiaria

**Assistente de IA escrevendo o conteúdo** (Buffer e Publer têm). Não é só fora de escopo — é
**contra a promessa**. O produto se vende por *provar que publicou*, e a gente escolheu nem cortar
o texto da pessoa sem pedir. Gerar texto por ela é o oposto disso.

**Tour de 10 passos antes de usar** (Publer). Dez janelas seguidas antes de tocar em qualquer coisa.
Quem chegou quer publicar. Nossa aposta melhor é o produto se explicar sozinho, como o laudo já faz.

**Quatro perguntas no cadastro** — *qual seu papel, como conheceu, como pretende usar* (Publer).
Fricção antes de entregar valor, e a resposta serve ao marketing deles, não ao cliente.

**Quadro de ideias, Explorar, notícias e tendências** (os dois têm). São outro produto colado por
cima. Quanto mais coisa na barra lateral, mais difícil achar o que importa.

**Análise de desempenho.** É a tentação mais forte, porque todo concorrente tem. Mas seria entrar no
território deles em vez de defender o nosso — e, para fazer bem, precisaria de permissões de
leitura bem mais amplas do que as mínimas que a gente pede hoje. Entra só se um cliente pagar por
isso.

---

## O que já fazemos melhor

Vale registrar, porque é o que não pode se perder ao copiar ideia dos outros:

**A prova.** Nenhum dos três relê o post na rede para confirmar. Todos marcam "enviado" e pronto —
o defeito que originou o produto.

**O laudo antes de enviar.** Nenhum diz, antes, o que cada rede fará com aquele arquivo. Eles
enviam e o erro vem depois.

**A qualidade entregue.** Nenhum mostra a rede admitindo que degradou o vídeo.

**A falha do lado do acerto.** Os três exibem o que deu certo; nós mostramos os três números.

⚠️ Ao trazer as ideias daqui, nenhuma pode custar isso. Se uma tela ficar mais bonita e disser
menos verdade, ela piorou.

---

## Ordem, juntando com o estudo de usabilidade

| # | O que | De onde vem |
|---|---|---|
| 1 | Miniatura e prévia | U-2 · reforçado por C-1 e pela coluna do bundle |
| 2 | Tela inicial de verdade | U-1 · "First Steps" que se marcam, do Buffer |
| 3 | Motivo do "não aceita" | U-3 · nosso, ninguém faz |
| 4 | Botão que não engana | U-7 |
| 5 | Abas com contador nas publicações | **C-4** · resolve U-8 melhor que filtro solto |
| 6 | Limites e contagem certos | U-5 + U-4 |
| 7 | Enviar arquivo do compositor | **C-5** |
| 8 | "Conferir conexão agora" | **C-2** · nossa tese aplicada à conexão |
| 9 | Tempo relativo | U-6 |
| 10 | Republicar | U-10 |

**C-1** (apagar só o não usado) entra junto com o módulo de assinaturas, e **C-3** (portal
compartilhável) junto com o caminho de revendedor.
