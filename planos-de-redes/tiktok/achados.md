# TikTok — achados da documentação oficial

> Lido antes de escrever qualquer linha, como manda a regra.
>
> ⭐ E o primeiro achado é bom, ao contrário do LinkedIn: **esta rede implementa a tese do produto
> por conta própria.**

---

## ⭐ T-1 — A rede só entrega o identificador do post DEPOIS da moderação

> *"`publicaly_available_post_id`: Returns `post_id` only for public posts approved by moderation."*

A tese do produto (DEC-31) é que *HTTP 200 não é publicado*. O TikTok concorda tanto que separou os
dois estados na própria resposta:

| O que a rede diz | O que significa |
|---|---|
| `PUBLISH_COMPLETE` **sem** `publicaly_available_post_id` | subiu, **ainda não liberado** |
| `PUBLISH_COMPLETE` **com** `publicaly_available_post_id` | subiu **e a moderação aprovou** |

⭐ É a prova mais forte de todas as redes do painel — mais forte que a do YouTube, que só diz se o
vídeo processou. Nenhum concorrente mostra esta diferença, porque nenhum lê o post de volta.

---

## ⛔ T-2 — Enquanto o aplicativo não for auditado, NÃO EXISTE prova

*"All content posted by unaudited clients will be restricted to private viewing mode."*

E a consequência encadeia:

1. `privacy_level` precisa ser `SELF_ONLY`, senão o início devolve
   `unaudited_client_can_only_post_to_private_accounts`;
2. post privado **nunca** recebe `publicaly_available_post_id`;
3. logo, **não há link de prova** enquanto a auditoria não sair.

⚠️ É a mesma situação do YouTube antes da auditoria do Google — e o painel já sabe dizer isso, com a
frase que aparece ao conectar um canal. Aqui vale a mesma honestidade: publicado, privado, sem link,
**e a tela dizendo por quê**.

---

## ⛔ T-3 — O token vive 24 HORAS

O prazo mais curto de todas as redes do painel, por larga margem — YouTube renova sozinho, Threads
vale 60 dias, LinkedIn vale 60.

⚠️ **Renovar não é manutenção aqui, é parte de publicar.** Um vídeo agendado para amanhã encontraria
um token morto. O comando diário não basta sozinho: o token precisa ser renovado **na hora de usar**,
se estiver perto de vencer.

⭐ A boa notícia: ao contrário do LinkedIn, aqui existe `refresh_token` de verdade, e ele vale
**365 dias**.

---

## ⛔ T-4 — O `refresh_token` GIRA a cada renovação

> *"The returned `refresh_token` may be different than the one passed in the payload."*

⛔ Guardar o novo é obrigatório. Renovar e continuar guardando o antigo dá uma conexão que funciona
hoje, funciona amanhã, e um dia para de funcionar sem ninguém ter mexido em nada — o pior tipo de
defeito, porque não tem evento para investigar.

---

## ⛔ T-5 — `total_chunk_count` arredonda para BAIXO

> *"Total count calculation: `video_size ÷ chunk_size`, rounded down."*

Um vídeo de 12 MB com pedaço de 5 MB dá **2** pedaços, não 3 — e o último carrega 7 MB.

⚠️ Todo mundo escreveria `ceil()` aqui, porque é o que faz sentido em qualquer outro protocolo de
envio em partes. Arredondar para cima manda um número que não bate com o que sobe, e o envio falha
depois de o arquivo inteiro ter subido.

Regras que acompanham: pedaço de **5 MB a 64 MB** (o último pode ir a 128 MB), **1 a 1000** pedaços,
vídeo até **4 GB**, e os pedaços sobem **em sequência** — paralelo é proibido.

⚠️ Vídeo menor que 5 MB sobe em **um pedaço só**, com `chunk_size` igual ao arquivo inteiro.

---

## ⛔ T-6 — Perguntar ao criador antes de publicar é OBRIGATÓRIO

`POST /v2/post/publish/creator_info/query/` — e não é etiqueta: mandar privacidade fora de
`privacy_level_options` devolve `privacy_level_option_mismatch`.

⭐ **E ele traz `max_video_post_duration_sec`, que varia por conta.** Contas novas têm teto menor.

⚠️ Isso quebra uma suposição que valia para todas as outras redes: o limite de duração **não é da
plataforma, é da conta**. O nosso `EspecificacaoDaRede` guarda um número fixo por rede — aqui ele é
o teto máximo possível, e o teto real só se sabe perguntando.

---

## ⛔ T-7 — Erro com HTTP 200

O `status/fetch` devolve **200** com o erro dentro do corpo (`error.code`).

⚠️ Um motor que confie no código HTTP trataria `invalid_publish_id` como sucesso e ficaria esperando
para sempre por um post que não existe.

---

## ⚠️ T-8 — `reached_active_user_cap` não é culpa da pessoa

Este 403 quer dizer que **o nosso aplicativo** estourou a cota de usuários ativos do dia. A frase na
tela não pode culpar a conta dela — e a saída é esperar, não reconectar.

---

## ⭐ T-9 — `FILE_UPLOAD`, e não `PULL_FROM_URL`

O `PULL_FROM_URL` exige **verificar a posse do domínio** no portal, por DNS ou prefixo de URL — um
passo manual por servidor, e o painel muda de endereço.

⚠️ Também é a rota que a DEC-100 (URL temporária) atenderia, mas a verificação de domínio a torna
inviável enquanto não houver endereço fixo. O `FILE_UPLOAD` não precisa de nada disso, e o painel já
sabe subir arquivo em pedaços — é o que o YouTube faz.
