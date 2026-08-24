# Plano — Pinterest

> Escrito depois de ler a **spec OpenAPI oficial** (cópia dos achados em
> [`../planos-de-redes/pinterest/documentacao/`](../planos-de-redes/pinterest/documentacao)).

---

## ⭐ Leia isto antes do resto

**Esta rede tem um "para onde" que nenhuma outra tem: o quadro.**

No YouTube o vídeo vai para o canal, no X para o perfil, no Threads para o perfil. No Pinterest, uma
conta tem **N quadros**, e todo Pin **precisa** escolher um — `board_id` é obrigatório.

⭐ E o encaixe de formato é o melhor de todas: o Pinterest é nativamente vertical, e o 9:16 que o
painel já produz serve **sem reconversão**.

---

## DEC-134 — Uma conta do painel por QUADRO, como a Meta faz por Página

⭐ O painel já sabe fazer isso: `ConexaoComMeta` cria uma conta por Página do Facebook. Aqui é a mesma
forma — conectar o Pinterest traz **um canal por quadro**, e a pessoa escolhe para qual publicar do
mesmo jeito que escolhe entre duas Páginas.

⛔ A alternativa seria inventar uma tela de "escolha o quadro" no meio do compositor, ou fixar um
quadro escondido na conexão. A primeira muda o compositor por causa de uma rede; a segunda publica
num lugar que a pessoa não escolheu.

⚠️ **Quadro secreto fica de fora.** Pedimos só `boards:read`, não `boards:read_secret`: publicar num
quadro secreto é publicar onde ninguém vê, e o produto existe para provar que alguém pode ver.

---

## DEC-135 — O arquivo sobe para a AWS, e a ORDEM dos campos importa

O `upload_url` é um formulário assinado do S3, não um endereço do Pinterest. A spec manda enviar
**todos** os `upload_parameters` junto com o arquivo no campo `file`.

⛔ **O arquivo vai por último.** Formulário assinado do S3 ignora o que vier **depois** do campo
`file` — mandar `key` ou `policy` no fim faz a Amazon recusar com um erro de XML que não menciona
ordem nenhuma, e ninguém adivinha isso lendo a mensagem.

⚠️ E **não vai token nosso** nessa chamada: quem autoriza é a assinatura que veio dentro dos
parâmetros. Mandar o `Authorization` do Pinterest para a Amazon é pedir 403.

---

## DEC-136 — A capa sai de um quadro do próprio vídeo

`cover_image_key_frame_time` recebe um segundo do vídeo e o Pinterest usa aquele quadro como capa.

⭐ É a única das três formas de capa que **não** exige subir um segundo arquivo. As outras
(`cover_image_url`, `cover_image_data`) trariam uma imagem que o painel teria que gerar, guardar e
servir — três problemas para resolver o que a rede resolve sozinha.

---

## DEC-137 — Aqui o título tem campo próprio

`title` (100) e `description` (800) são campos separados, como no YouTube e no Facebook.

⚠️ Isso importa por causa da regra que a revisão de ontem criou: rede **sem** campo de título soma o
título na legenda. O Pinterest tem, então **não soma** — e o guardião que enumera quem tem campo
próprio cresce com ela.

---

## As fases

- [x] **1** `ConexaoComPinterest`: autorização, escopos concedidos, **uma conta por quadro** (DEC-134)
- [x] **2** `PublicadorPinterest`: registrar → subir para a AWS → conferir → fixar
- [x] **3** `EspecificacaoDaRede`: título 100, descrição 800 (da spec); o resto do perfil canônico
- [x] **4** `conciliar()`: `succeeded` → cria o Pin; a prova é `GET /v5/pins/{id}`
- [x] **5** Guardiões

**Pronto quando:** um Pin sai pelo painel, no quadro escolhido, e volta relido com link.

---

## ⛔ O que fica de fora

**Criar quadro** (`boards:write`). Escolher onde publicar é da pessoa; criar estrutura na conta dela
por conta própria, não.

**Quadro secreto e Pin secreto** (`*_secret`). Publicar onde ninguém vê contraria a promessa.

**Pin de imagem e carrossel.** O produto publica um vídeo vertical por vez.
