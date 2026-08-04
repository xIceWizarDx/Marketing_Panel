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

| Rede | Situação | Plano |
|---|---|---|
| **Bluesky** | 🟢 publicando · correções aplicadas | [plano](bluesky/plano-de-acao.md) · [lexicons oficiais](bluesky/documentacao/lexicons/) |
| **YouTube** | 🟡 **código pronto, falta a credencial do Google** | ⚙️ **[como configurar](youtube/como-configurar.md)** · [plano](youtube/plano-de-acao.md) · [16 achados](youtube/achados.md) · [conformidade](youtube/achados-de-conformidade.md) |
| LinkedIn (perfil) | ⚪ decidida, não começada | — |
| Instagram · Facebook · Threads | ⚪ decididas, dependem de App Review | — |
| TikTok | ⚪ decidida, depende de audit | — |
| Pinterest · X · Mastodon · Discord · LinkedIn Página · Snapchat · Google Meu Negócio | ⚪ em estudo | — |

O mapa completo de barreira por rede está em
[`../documentação-inicial/10-redes-adicionais.md`](../documentação-inicial/10-redes-adicionais.md);
as regras de conformidade, em
[`../documentação-inicial/09-regras-das-redes.md`](../documentação-inicial/09-regras-das-redes.md).
