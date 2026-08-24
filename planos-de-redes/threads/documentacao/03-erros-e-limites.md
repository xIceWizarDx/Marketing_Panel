# Threads — situação do contêiner, erros e limites

_Cópia local do que a documentação oficial dizia em **2026-08-08**._
_Fonte: `developers.facebook.com/docs/threads/troubleshooting`._

---

## ⭐ O campo que diz se pode publicar

`GET /{threads-media-id}?fields=status,error_message`

| `status` | O que é |
|---|---|
| `IN_PROGRESS` | ainda processando |
| `FINISHED` | **pronto para publicar** |
| `PUBLISHED` | publicado |
| `ERROR` | falhou — o motivo vem em `error_message` |
| `EXPIRED` | **não foi publicado em 24 h** e morreu |

É o mesmo desenho do `status_code` do Instagram, com os mesmos nomes de estado. O `EXPIRED` também
existe aqui: contêiner criado e não publicado em 24 horas se perde.

---

## ⛔ Todos os erros documentados são PERMANENTES

| Código | O que é |
|---|---|
| `FAILED_DOWNLOADING_VIDEO` | não conseguiu **baixar** o vídeo |
| `FAILED_PROCESSING_VIDEO` | falhou ao processar o vídeo |
| `FAILED_PROCESSING_AUDIO` | falhou ao processar o áudio |
| `INVALID_ASPEC_RATIO` | proporção fora do aceito |
| `INVALID_BIT_RATE` | taxa de bits fora do aceito |
| `INVALID_DURATION` | duração fora do limite |
| `INVALID_FRAME_RATE` | taxa de quadros não suportada |
| `INVALID_AUDIO_CHANNELS` | número de canais de áudio errado |
| `INVALID_AUDIO_CHANNEL_LAYOUT` | disposição dos canais inválida |
| `UNKNOWN` | não especificado |

### ⛔ A lista oficial tem erro de digitação — e ela muda entre leituras

`INVALID_ASPEC_RATIO` está assim na fonte, **sem o `T`**. Numa segunda leitura da mesma página, no
mesmo dia, `INVALID_FRAME_RATE` apareceu como `FAILED_FRAME_RATE`.

⚠️ Consequência para o código: **casar o código inteiro não serve.** A recusa mais comum de todas —
a proporção do vídeo — cairia no genérico *"o Threads recusou este post"*, que não diz o que
arrumar. O publicador casa por **pedaço estável** (`ASPEC`, `FRAME_RATE`, `BIT_RATE`…), que funciona
com a grafia errada de hoje e com a corrigida de amanhã.

⚠️ **Nenhum deles passa sozinho.** São recusas de conteúdo e falhas de codificação — tentar de novo
com o mesmo arquivo dá o mesmo resultado. O motor precisa marcar falha, não devolver para a fila.

### ⛔ `FAILED_DOWNLOADING_VIDEO` é o erro DESTA arquitetura

Ele é o que aparece quando a Meta **não conseguiu buscar o arquivo** no endereço que demos. As
causas prováveis, em ordem:

1. **A URL temporária expirou** antes de ela buscar (DEC-100 — ela vive 15 minutos);
2. o servidor não está alcançável pela internet (DEC-101);
3. o arquivo já foi liberado do disco.

⚠️ Ele **não** significa vídeo ruim, e a mensagem para a pessoa não pode dizer isso. É o único erro
da lista cuja causa é **nossa**, não do arquivo dela.

---

## Limites

| | |
|---|---|
| Publicações | **250 por perfil a cada 24 h** (janela móvel); carrossel conta como uma |
| Respostas | 1.000 por 24 h |
| Exclusões | 100 por 24 h |
| Buscas de local | 500 por 24 h |

_Confirmado na fonte oficial em **2026-08-08**. Na primeira leitura (2026-08-08, mais cedo) as
páginas devolveram 404 e estes números constavam aqui como não confirmados._

## ⭐ Dá para perguntar quanto já foi gasto

```
GET /{threads-user-id}/threads_publishing_limit
    ?fields=quota_usage,config,reply_quota_usage,reply_config,
            delete_quota_usage,delete_config,
            location_search_quota_usage,location_search_config
```

`config` traz `quota_total` e `quota_duration`.

⚠️ **A rede não devolve código de erro próprio para cota estourada.** Quem sabe é este endpoint — e
é por isso que o publicador só o consulta **depois** de uma recusa acontecer: uma chamada a mais no
caminho do erro, nenhuma no caminho normal. Sem ela, a publicação de número 251 do dia seria marcada
como falha permanente e queimaria as três tentativas contra um limite que só volta amanhã (DEC-24).

⛔ Na dúvida a resposta é "não estourou": se a consulta da cota falhar, o motivo que a pessoa vê
continua sendo o que a rede deu. Inventar "limite do dia" a partir de uma chamada que nem respondeu
esconderia o erro real.
