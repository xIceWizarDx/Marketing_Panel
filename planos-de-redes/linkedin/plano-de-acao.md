# LinkedIn — plano de ação

> As fases vivem em [`../../documentação-inicial/22-plano-linkedin.md`](../../documentação-inicial/22-plano-linkedin.md),
> junto com as decisões DEC-106 a DEC-114. Aqui fica só o resumo do que é **desta rede**.
>
> Os achados que geraram as decisões estão em [`achados.md`](achados.md); a documentação copiada,
> em [`documentacao/`](documentacao/).

---

## O que muda em relação a todas as outras

| | Outras redes | **LinkedIn** |
|---|---|---|
| Reler o post publicado | sim — é a prova (DEC-31) | ⛔ **não** — exige permissão restrita |
| Renovar o token | serviço nosso, de madrugada | ⛔ **navegador da pessoa**, a cada 60 dias |
| Identificador do post | no corpo da resposta | ⚠️ no **cabeçalho** `x-restli-id` |
| Envio da mídia | inteiro, ou a rede vem buscar | ⚠️ **em pedaços**, com recibo por pedaço |
| Limite | contado em posts | ⚠️ contado em **requisições** (150/dia) |
| Erro passageiro | a rede diz (`is_transient`) | ⚠️ sai do **código HTTP** |

---

## O estado

**Código pronto, 32 guardiões verdes** — 14 da conexão, 18 da publicação, 2 da frase da tela.

⚠️ **Nenhum post saiu no LinkedIn de verdade**, e o aplicativo no portal da LinkedIn ainda não existe.
O passo a passo do que falta cadastrar está no fim do
[plano 22](../../documentação-inicial/22-plano-linkedin.md).

---

## ⛔ O caminho que devolveria a prova completa

Publicar em **Página de empresa** exige `w_organization_social`, e ler o post de volta exige
`r_organization_social` — as duas do programa de Marketing, com aprovação da LinkedIn.

⚠️ É o único jeito de esta rede ter a mesma prova das outras. Fica registrado como **pedido a
fazer**, não como código a escrever: aprovação incerta não vira funcionalidade prometida.
