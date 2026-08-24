# YouTube — estatísticas de canal e de vídeo

_Cópia local do que a documentação oficial dizia em **2026-08-05**._
_Fontes: `developers.google.com/youtube/v3/docs/channels` e `.../docs/videos`._

> ⚠️ Esta é a cópia exigida pelo contrato (`CLAUDE.md` → *Antes de integrar QUALQUER rede*).
> O que não estiver aqui **não foi verificado** e não pode virar certeza no código.

---

## `channels.list` — `part=statistics`

| Campo | Tipo | O que a documentação diz |
|---|---|---|
| `viewCount` | unsigned long | Soma das visualizações de todos os formatos do canal. A partir de **31/03/2025**, a contagem de Shorts inclui **plays e replays** |
| `subscriberCount` | unsigned long | *"This value is rounded down to three significant figures."* — **arredondado para baixo, 3 algarismos significativos** |
| `hiddenSubscriberCount` | boolean | Diz se a contagem de inscritos é publicamente visível |
| `videoCount` | unsigned long | Conta **só vídeos públicos**, inclusive para o dono do canal |
| `commentCount` | unsigned long | ⛔ **Descontinuado.** Não usar |

**Custo:** 1 unidade de cota. **Cota padrão do projeto:** 10.000 unidades/dia.

### O que isso obriga no código

- ⭐ **`subscriberCount` arredondado é regra da plataforma, não defeito nosso.** 1.234 inscritos
  chega como 1.230. A tela precisa dizer isso, ou o número parece errado ao lado do YouTube Studio.
- ⭐ **Inscritos ocultos: o campo `subscriberCount` some da resposta.** Não vem `0`. Ausente vira
  `null` (DEC-95).
- ⛔ **`videoCount` nunca vai bater com o que o painel publicou** enquanto os vídeos subirem
  privados — ele só conta público. Por isso não entra.

---

## `videos.list` — `part=statistics`

| Campo | Tipo | O que a documentação diz |
|---|---|---|
| `viewCount` | unsigned long | A partir de **31/03/2025**, para Shorts conta **quando o Short começa a tocar ou retocar**, sem tempo mínimo de exibição |
| `likeCount` | unsigned long | Quantas pessoas curtiram |
| `commentCount` | unsigned long | Quantidade de comentários |
| `dislikeCount` | unsigned long | Desde **13/12/2021**, visível **só para o dono do vídeo** |
| `favoriteCount` | unsigned long | ⛔ **Descontinuado desde 28/08/2015. O valor é sempre `0`.** Não usar |

**Custo:** 1 unidade de cota.

### O que isso obriga no código

- ⛔ **`favoriteCount` sempre vale 0** — guardar esse zero seria guardar uma mentira.
- ⚠️ **`dislikeCount` está disponível para nós**, porque o token é o do dono do canal. Ficou de fora
  por decisão de produto, não por indisponibilidade: seria um campo que existe em uma rede só.
- ⚠️ **A definição de "visualização" mudou em 31/03/2025** e não é comparável com a de outras redes.
  É por isso que o produto não soma views entre redes (DEC-94).

---

## ⛔ O que NÃO está aqui, e por quê

**Série por dia** (`views` e `subscribersGained` ao longo do tempo) vive na **YouTube Analytics
API**, que é outra API e exige o escopo `yt-analytics.readonly`. Fora do escopo atual (DEC-93):
acrescentar escopo a um aplicativo em verificação recomeça a fila.

**Métrica derivada.** As Políticas do Desenvolvedor proíbem criar métrica a partir dos dados do
YouTube. Calcular *"ganhou 12 inscritos hoje"* subtraindo duas leituras nossas é exatamente isso —
o caminho permitido é `subscribersGained`, da Analytics API (DEC-97).
