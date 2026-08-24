# Planos de implementação por rede

Uma pasta por rede. Dentro de cada uma:

- **`plano-de-acao.md`** — o que fazer, nesta ordem: consultar a documentação → planejar →
  executar → revisar → testar.
- **`documentacao/`** — cópia local do que a documentação oficial diz, com a fonte e a data da
  consulta.

## Por que a cópia local existe

Duas razões, e as duas custaram caro para aprender:

**Memória não serve.** A primeira versão do publicador do YouTube foi escrita de lembrança da
API. A consulta à documentação achou **7 divergências**, três delas graves — incluindo cortar a
descrição por caractere quando o limite é em **bytes**, o que faria toda legenda com acento ser
recusada depois do upload inteiro ter subido.

**A fonte muda e sai do ar.** O extrato local diz o que valia **na data em que foi consultado**.
Quando a implementação quebrar, dá para comparar o que mudou em vez de adivinhar.

### Dois tipos de arquivo, e a diferença importa

| Tipo | O que é | Confiança |
|---|---|---|
| **Especificação legível por máquina** (`.json`) | O que a plataforma publica: campos, tipos, valores possíveis, limites | ⭐ **fonte de verdade** |
| **Extrato de prosa** (`.md`) | Respostas às perguntas da implementação, tiradas das páginas | apoio |

**Um não substitui o outro.** A especificação tem os números exatos mas não explica
comportamento — o protocolo de upload retomável do YouTube (códigos 308, cabeçalho `Range`, o
que fazer em 5xx) só existe na prosa. E a prosa envelhece: no Bluesky ela dizia 1 MB de imagem
enquanto o lexicon já dizia 2 MB.

**Sempre que a plataforma publicar spec legível por máquina, baixar.** Foi ela que revelou que
o Bluesky aceita **só `video/mp4`** — algo que nenhuma página de prosa menciona, e que faria
todo `.mov` de iPhone ser recusado depois do upload inteiro.

## Estado

> ⭐ **A tabela abaixo é resumo humano; a fonte é o código.** Para o estado exato de hoje:
> `php artisan redes:situacao` (ou `--md` para colar aqui).
>
> ⚠️ Esta tabela já envelheceu quatro vezes — dizia que o YouTube faltava credencial depois de ele
> estar publicando, e listava o Pinterest como "em estudo" com o publicador pronto. Se ela divergir
> do comando, **quem está certo é o comando**.

| Rede | Situação | Plano |
|---|---|---|
| **Bluesky** | 🟢 publicando · correções aplicadas | [plano](bluesky/plano-de-acao.md) · [lexicons oficiais](bluesky/documentacao/lexicons/) |
| **YouTube** | 🟢 publicando · ⚠️ enquanto a auditoria do Google não sair, o vídeo sobe **privado** | ⚙️ **[como configurar](youtube/como-configurar.md)** · [plano](youtube/plano-de-acao.md) · [16 achados](youtube/achados.md) · [conformidade](youtube/achados-de-conformidade.md) |
| **Facebook · Instagram** | 🟡 código pronto e conta conectada · falta a prova de campo | [plano](../documentação-inicial/21-plano-meta.md) · [achados](meta-compartilhado.md) |
| **Threads** | 🟡 código pronto e app configurado · falta a prova de campo | [plano](../documentação-inicial/21-plano-meta.md) · [achados](threads/achados.md) |
| **LinkedIn (perfil)** | 🟡 código pronto · falta criar o aplicativo no portal da LinkedIn | [plano](../documentação-inicial/22-plano-linkedin.md) · [achados](linkedin/achados.md) |
| **TikTok** | 🟡 código pronto · falta criar o aplicativo no portal · ⛔ sem auditoria ele **não publica** (DEC-124): post privado nunca ganha link, e sem link não há prova | [plano](../documentação-inicial/23-plano-tiktok.md) · [achados](tiktok/achados.md) |
| **X** | 🟡 código pronto · falta o aplicativo no console · ⛔ **cada post custa** (US$ 0,015, e US$ 0,200 com link) | [plano](../documentação-inicial/24-plano-x.md) · [achados](x/achados.md) |
| **Pinterest** | 🟡 código pronto · falta o aplicativo no portal · ⭐ melhor encaixe de formato de todas (nativamente vertical) | [plano](../documentação-inicial/25-plano-pinterest.md) · [spec oficial lida](pinterest/documentacao/) |
| **Mastodon** | 🟢 código pronto · ⭐ **sem cadastro nenhum a fazer** — testável hoje | [plano](../documentação-inicial/26-plano-mastodon.md) |
| **Discord** | 🟢 código pronto · ⭐ **sem cadastro nenhum a fazer** — testável hoje · ⚠️ sem alcance: é aviso para quem já está no canal | [plano](../documentação-inicial/27-plano-discord.md) |
| LinkedIn Página | 🟡 pedido a fazer — o código é o mesmo do perfil, falta a aprovação da LinkedIn | [por quê](../documentação-inicial/28-redes-que-ficam-de-fora.md) |
| Snapchat · Google Meu Negócio | ⛔ **fora, com motivo** — não há API de publicação orgânica / não é o produto | [por quê](../documentação-inicial/28-redes-que-ficam-de-fora.md) |

O mapa completo de barreira por rede está em
[`../documentação-inicial/10-redes-adicionais.md`](../documentação-inicial/10-redes-adicionais.md);
as regras de conformidade, em
[`../documentação-inicial/09-regras-das-redes.md`](../documentação-inicial/09-regras-das-redes.md).
