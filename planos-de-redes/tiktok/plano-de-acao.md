# TikTok — plano de ação

> As fases vivem em [`../../documentação-inicial/23-plano-tiktok.md`](../../documentação-inicial/23-plano-tiktok.md),
> junto com as decisões DEC-115 a DEC-123. Aqui fica só o resumo do que é **desta rede**.
>
> Os achados que geraram as decisões estão em [`achados.md`](achados.md); a documentação copiada,
> em [`documentacao/`](documentacao/).

---

## ⭐ O que muda em relação a todas as outras

| | Outras redes | **TikTok** |
|---|---|---|
| A prova | reler o post | ⭐ **a rede entrega o id só depois da moderação** |
| Token | 1 h a 60 dias | ⚠️ **24 horas** |
| Renovação | de madrugada | ⚠️ **na hora de publicar** |
| `refresh_token` | fixo | ⚠️ **gira a cada renovação** |
| Antes de publicar | nada | ⛔ **perguntar ao criador é obrigatório** |
| Limite de duração | da plataforma | ⚠️ **da CONTA** |
| Pedaços | para cima | ⛔ **para baixo** |
| Erro | no código HTTP | ⚠️ **dentro de um 200** |

---

## O estado

**Código pronto, 43 guardiões verdes** — 16 da conexão e do token, 20 da publicação, e 7 só da
aritmética de pedaços, que não toca em rede nenhuma porque aritmética se prova com números na mão.

⚠️ **Nenhum vídeo saiu no TikTok de verdade**, e o aplicativo no portal ainda não existe. O passo a
passo do que falta cadastrar está no fim do
[plano 23](../../documentação-inicial/23-plano-tiktok.md).

---

## ⛔ A auditoria muda tudo

Enquanto ela não sair:

- todo post é **privado** (`SELF_ONLY`) — a rede recusa qualquer outra coisa;
- post privado **nunca** recebe `publicaly_available_post_id`;
- logo, **não existe link de prova** — e por isso o painel **não publica** no TikTok até lá
  (DEC-124). Publicar sem poder provar só terminaria em "falhou" depois de o vídeo ter subido de
  verdade, com o painel ainda oferecendo republicar.

⭐ Depois dela, o TikTok passa a ser a rede com a prova mais forte do painel — a única que diz, com
todas as letras, se a moderação aprovou.
