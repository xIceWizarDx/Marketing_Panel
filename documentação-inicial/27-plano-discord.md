# Plano — Discord

> Escrito depois de ler a documentação oficial (`docs.discord.com/developers/resources/webhook`) em
> **2026-08-09**.

---

## ⭐ Leia isto antes do resto

**É a conexão mais simples do painel inteiro, e a única sem autenticação nenhuma.** A pessoa cria um
webhook no canal dela, cola o endereço, e pronto — não há OAuth, não há aplicativo, não há portal.

⚠️ **E é também a de menor alcance.** O Discord não tem feed nem descoberta: aqui o vídeo vira
**aviso para quem já está no canal**, não distribuição. Isso está dito na tela, antes de conectar —
quem espera alcance com isso vai se decepcionar, e a decepção é evitável com uma frase.

---

## DEC-141 — O endereço do webhook É a credencial, e por isso é partido

Quem tem o endereço inteiro, publica. Guardá-lo num campo visível seria deixar a senha na tela.

⭐ Ele é partido na hora de guardar: o identificador vai para a conta, o segredo vai para a
credencial criptografada, e o endereço inteiro nunca mais existe junto em lugar nenhum.

⚠️ **E ele é conferido na conexão** (`GET /webhooks/{id}/{token}`). Sem isso, um endereço errado
conectaria, publicaria, e a publicação sumiria no vazio **sem erro nenhum** — o pior desfecho
possível para um produto cuja promessa é provar que publicou.

---

## ⛔ DEC-142 — `wait=true` é obrigatório

A documentação é literal: sem ele, *"unconfirmed messages don't generate errors"* e a resposta é
`204` sem corpo.

⛔ Ou seja: **a publicação poderia falhar em silêncio e o painel diria que deu certo.** É exatamente
o que o produto existe para não fazer. Com `wait=true`, a resposta traz a mensagem criada — e é dela
que sai o identificador da prova.

---

## ⚠️ Os limites são do SERVIDOR, não do Discord

O teto de arquivo sobe com o nível de impulsionamento do servidor. **10 MB é o piso** — servidor sem
impulso — e é o número que o painel confere.

⛔ Conferir pelo teto mais alto deixaria passar arquivo que a maioria dos servidores recusa, e a
recusa (`413`) só chega **depois** do envio inteiro.

⚠️ Isso faz o Discord ser a primeira rede do painel que **não aceita o vídeo do perfil canônico**
(20 MB). O guardião do laudo registra isso, com o motivo — para ninguém "consertar" o limite achando
que é engano.

---

## As fases

- [x] **1** `ConexaoComDiscord`: parte o endereço, confere o webhook, guarda o segredo cifrado
- [x] **2** A quarta forma de conectar na tela, com o aviso de que aqui não há alcance
- [x] **3** `PublicadorDiscord`: uma chamada com `wait=true`
- [x] **4** `conciliar()`: relê a mensagem — a prova (DEC-31)
- [x] **5** Guardiões

**Pronto:** 11 guardiões verdes.
**Falta a prova de campo:** nenhuma mensagem saiu de verdade.

⭐ **E aqui não falta cadastro nenhum** — basta um servidor do Discord onde a pessoa possa criar
webhook. É testável hoje, como o Mastodon.

---

## ⛔ O que fica de fora

**Bot do Discord.** Daria presença, comandos e leitura de canal — e exige aplicativo, permissões e
convite ao servidor. Para publicar um vídeo, webhook basta.

**Escolher servidor e canal pelo painel.** Quem escolhe é quem cria o webhook, no Discord. Listar
canais exigiria bot.
