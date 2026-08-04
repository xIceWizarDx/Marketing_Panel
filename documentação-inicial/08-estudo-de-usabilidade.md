# Estudo de usabilidade — o produto pelos olhos de quem usa

_2026-08-04, depois da primeira publicação real no YouTube._

> Percorri as cinco telas do cliente na ordem em que ele as encontra, procurando o momento em que
> ele **para, hesita ou erra**. Cada achado diz o que a pessoa vive, por que acontece e o que
> resolve.
>
> Ordenado por impacto, não por esforço. O primeiro bloco é o que faz o produto parecer quebrado.

---

## 🔴 U-1 — A tela inicial é um cartaz de boas-vindas eterno

**O que a pessoa vive:** ela conecta o canal, publica um vídeo, volta ao painel — e a primeira
tela continua dizendo *"Vamos começar conectando uma rede"*. Do segundo dia em diante, a página
que abre primeiro é a única que nunca sabe de nada.

**Por que acontece:** a rota é uma função sem dados nenhum:

```php
Route::get('painel', fn () => Inertia::render('cliente/visao-geral'))
```

Não existe controller. A tela é um texto fixo.

**Por que é o pior de todos:** é a porta de entrada. Quem abre o painel quer saber *"o que
aconteceu enquanto eu não estava olhando?"* — e recebe o mesmo convite de sempre. Passa a
impressão de que o sistema não registrou nada do que ela fez.

**O que resolve:** a tela vira um resumo do que importa **hoje**:

- o que está no ar, o que está indo e o que falhou (os números que a tela de Conexões já calcula)
- as últimas publicações, com link e estado
- **o que precisa de você**: conexão vencendo, publicação falhada, rede sem conta
- o convite de boas-vindas **só enquanto** não houver conexão nenhuma

---

## 🔴 U-2 — Não dá para saber qual vídeo é qual

**O que a pessoa vive:** na hora de publicar, os arquivos aparecem como um ícone cinza igual para
todos, mais o nome do arquivo. Com três vídeos baixados do WhatsApp na mesma tarde, a lista fica
assim:

```
▢ WhatsApp Video 2026-08-03 at 13.43.37.mp4    11s · 2,6 MB
▢ WhatsApp Video 2026-08-03 at 13.44.02.mp4    14s · 3,1 MB
▢ WhatsApp Video 2026-08-03 at 13.44.51.mp4    9s · 2,2 MB
```

Não há como escolher sem adivinhar — e **publicar o vídeo errado não tem desfazer**.

**Por que acontece:** não geramos miniatura no envio, e não existe prévia em lugar nenhum do
sistema (`<video>` não aparece em nenhuma tela).

**O que resolve:**

- **miniatura** gerada no envio (um quadro do vídeo, ~40 KB — 500× menor que o original)
- **prévia** ao clicar, para conferir antes de publicar
- a lista deixa de ser nomes soltos e vira algo que se reconhece de relance

⭐ É também o que permite apagar o arquivo original sem esvaziar o histórico — hoje é exatamente
assim que funciona (DEC-56 + DEC-59).

✅ **Resolvido em 2026-08-04.**

---

## 🔴 U-3 — O laudo diz "não aceita" e não diz por quê

**O que a pessoa vive:** escolhe o arquivo e lê:

```
✕ facebook   não aceita
✓ youtube    aceita
```

E agora? Cortar o vídeo? Trocar o formato? Girar? A tela sabe a resposta e não conta.

**Por que acontece:** cada achado carrega **mensagem** e **providência** — *"Vídeo de 120s, o
máximo é 90s"* e *"Corte o vídeo ou publique só nas outras redes"*. A tela usa só o **nível**
(erro/ok) e joga o texto fora.

**O que resolve:** mostrar o motivo junto do "não aceita". O trabalho já está feito no servidor;
é só parar de descartá-lo.

---

## 🟠 U-4 — O contador de caracteres conta errado, e conta a rede errada

**O que a pessoa vive:** escreve a legenda e vê *"O Bluesky aceita no máximo 300"* — mesmo
publicando só no YouTube, que aceita 5.000. E se usar emoji, o número mostrado não bate com o que
a rede vai contar.

**Por que acontece:** dois defeitos no mesmo lugar.

O limite é fixo em 300 e cita o Bluesky, sem olhar quais contas foram escolhidas. E a contagem usa
`texto.length`, que conta **unidades de código**, não caracteres visíveis:

| Texto | `.length` diz | O Bluesky conta |
|---|---|---|
| `👨‍👩‍👧‍👦` | 11 | **1** |
| `🇧🇷` | 4 | **1** |

⚠️ **Este defeito exato já foi corrigido no servidor** (`Medida::Grafemas`), justamente porque a
contagem ingênua rejeita texto que caberia. A tela ficou para trás.

**O que resolve:** o contador olha as contas escolhidas e mostra o **limite mais apertado entre
elas**, contando do jeito que aquela rede conta. `Intl.Segmenter` faz isso no navegador.

---

## 🟠 U-5 — O campo deixa digitar o que o servidor vai recusar

**O que a pessoa vive:** escreve um título de 180 caracteres, o campo aceita, ela clica em
publicar — e leva erro.

**Por que acontece:** `maxLength={255}` no título, enquanto o YouTube aceita **100**.

**O que resolve:** o teto do campo sai das redes escolhidas, e o contador avisa antes de estourar.
Quem digita precisa saber onde é o fim **enquanto** digita, não depois de enviar.

---

## 🟠 U-6 — Nada diz há quanto tempo

**O que a pessoa vive:** a publicação mostra `03/08/2026, 15:39`. Ela precisa olhar o relógio e
fazer a conta. E se um destino está "na fila", não há nada dizendo se entrou agora ou se está
parado há vinte minutos — que é exatamente a diferença entre *aguardar* e *pedir socorro*.

**O que resolve:** tempo relativo (*"há 5 minutos"*), com a data exata ao passar o mouse. E, no
que está em andamento, **desde quando** — em fila há muito tempo é sinal de que o worker caiu.

---

## 🟠 U-7 — Dá para clicar em "Publicar em 0 contas"

**O que a pessoa vive:** o botão fica ativo sem arquivo escolhido e sem conta marcada. Clica, e o
formulário devolve erro de validação em campo que ela nem viu.

**Por que acontece:** o botão só desabilita durante o envio (`disabled={processing}`).

**O que resolve:** desabilitar enquanto faltar arquivo ou conta, e dizer o que falta no próprio
botão. Erro que dá para evitar não deveria virar mensagem de erro.

---

## 🟡 U-8 — Sem busca e sem filtro

Hoje há três mídias e uma publicação, então tudo cabe na tela. Com cinquenta, procurar vira
rolagem. Não existe campo de busca nem filtro em nenhuma lista.

**O que resolve:** busca por nome nas mídias; filtro por estado nas publicações (*só as que
falharam* é o mais pedido, porque é onde há trabalho a fazer).

---

## ✅ U-9 — Sair da tela apaga o que foi escrito

Escreveu título, legenda e hashtags, saiu para conferir uma coisa — perdeu tudo. Não havia aviso ao
sair com o formulário preenchido.

**O que resolveu:** o compositor virou **modal por rota de verdade** (`/publicar`). Recarregar
reabre no mesmo ponto, e o botão voltar fecha a janela em vez de sair do painel. Enviar o vídeo
também deixou de apagar o texto: o envio preserva o formulário.

✅ **Resolvido em 2026-08-04** — e a causa raiz sumiu junto: não há mais outra tela para onde ir.

---

## ✅ U-10 — Não dá para republicar

Publicou no YouTube e agora quer o mesmo vídeo no Facebook. Era preciso refazer tudo: escolher o
arquivo de novo, reescrever título, legenda e hashtags.

**O que resolveu:** botão **"publicar em outra rede"** no cartão da publicação, que abre o
compositor com o texto pronto e as contas onde já subiu **desmarcadas e avisadas**.

⚠️ **Metade do trabalho continua com a pessoa, e isso é proposital:** o vídeo é reenviado, porque
ele saiu do disco quando a publicação terminou (DEC-61). O que economiza é o texto — que é onde
está o trabalho de verdade. Reenviar o mesmo arquivo o devolve ao mesmo registro (DEC-58).

✅ **Resolvido em 2026-08-04.**

---

## ✅ U-11 — Não dá para ver onde cada vídeo já foi parar

A mídia mostrava nome, tamanho e laudo — não **se já foi publicada e onde**.

**O que resolveu:** a tela que separava as duas informações **deixou de existir**. Publicações é a
única lista, e nela cada item já mostra a miniatura e o status por rede, com o link da prova.

✅ **Resolvido em 2026-08-04**, por eliminação — o melhor jeito de resolver "duas telas não
conversam" é não ter duas telas.

---

## Ordem sugerida

Da maior dor para a menor, e com o barato primeiro dentro de cada nível:

| # | O que | Por quê |
|---|---|---|
| 1 | **U-2** miniatura e prévia | resolve escolher errado, que não tem desfazer |
| 2 | **U-1** tela inicial de verdade | é a porta de entrada, hoje inútil a partir do 2º dia |
| 3 | **U-3** motivo do "não aceita" | o texto já existe, só está sendo descartado |
| 4 | **U-7** botão que não engana | evita erro em vez de explicá-lo |
| 5 | **U-5** + **U-4** limites e contagem certos | erro que aparece **depois** de escrever tudo |
| 6 | **U-6** tempo relativo | separa "aguardando" de "travado" |
| 7 | **U-10** republicar | o caminho mais natural é hoje o mais trabalhoso |
| 8 | **U-11** onde cada vídeo já foi | a prova aparecendo onde a pessoa mora |
| 9 | **U-9** não perder o que escreveu | fricção rara, mas dolorosa |
| 10 | **U-8** busca e filtro | só incomoda com volume |

**Os quatro primeiros mudam a percepção do produto.** Os demais são conforto que aparece com o
uso.

---

## O que NÃO entrou, de propósito

**Recodificar vídeo automaticamente.** Resolveria o "não aceita" sozinho, mas contraria o DEC-33:
o vídeo passa intacto, e é isso que sustenta a promessa de qualidade. Recodificar tem que ser
escolha explícita da pessoa, nunca conserto silencioso nosso.

**Agendamento na tela.** O motor já sabe agendar (`publicar_em`), e a interface não expõe. É
funcionalidade nova, não melhoria de usabilidade — merece decisão própria.

**Tema claro/escuro e ajustes visuais.** Já existem e funcionam. Mexer em cor antes de resolver
"não sei qual vídeo é qual" seria arrumar a prateleira com a casa alagando.

---

## Situação em 2026-08-04

Os onze pontos foram atacados nos planos 10, 12 e 13. ⚠️ **U-9, U-10 e U-11 mudaram de resposta no
caminho:** o remédio deixou de ser "avisar antes de sair" / "trazer tudo pronto" / "cruzar as duas
telas" e passou a ser **tirar a segunda tela do caminho**. Enviar e publicar viraram um gesto só, e
boa parte dos defeitos que este estudo catalogou eram sintoma da separação entre eles.
