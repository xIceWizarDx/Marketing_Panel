# As redes que ficam de fora — e por quê

> Escrito depois de ler a documentação oficial de cada uma, em **2026-08-09**.
>
> ⭐ **Isto não é uma lista de "ainda não fizemos".** São três decisões, cada uma com o motivo na
> frente — porque rede que não entra sem explicação vira dúvida recorrente, e daqui a seis meses
> alguém pesquisa tudo de novo.

---

## ⛔ Snapchat — não existe API de publicação orgânica

A pergunta foi direta: existe alguma API que deixe um **servidor** publicar um vídeo no perfil
público ou no Spotlight, sem aplicativo de celular e sem a pessoa tocar em "compartilhar"?

**Não existe.** O que a Snap oferece é outra coisa:

| Produto | O que faz |
|---|---|
| Camera Kit / Lens Studio | realidade aumentada dentro do **seu** aplicativo |
| Snap Kit | integra recursos do Snapchat num aplicativo |
| Social Plugins | botão de compartilhar num site, e incorporar conteúdo |
| **Marketing API** | **anúncios** — *"tailored ad solutions"*, não conteúdo orgânico |

⛔ **O Creative Kit publica a partir do celular, com a pessoa tocando no botão.** Isso não é o que o
painel faz: o painel publica do servidor, sozinho, e depois relê para provar. Nenhuma dessas peças
serve.

⚠️ **E não é barreira de aprovação — é ausência de API.** Não há fila para entrar, não há parceria
para pedir. Enquanto a Snap não publicar um endpoint de conteúdo orgânico, esta rede não entra.

**Decisão: fora, sem prazo.** Volta a ser avaliada se a Snap lançar API de publicação.

---

## ⛔ Google Meu Negócio — publica lugar, não conteúdo

O endpoint existe (`POST /v4/{parent}/localPosts`), mas três coisas o inviabilizam aqui:

1. ⚠️ **Acesso por fila.** *"If you have a quota of 0 after enabling the API, please request for GBP
   API access."* Começa com cota zero e depende de aprovação do Google.
2. ⛔ **A documentação consultada não confirma vídeo** em post local — e escrever código apostando
   nisso seria inventar, que é justamente o que o projeto não faz.
3. ⛔ **E o encaixe é errado por natureza.** Post de Meu Negócio é aviso de estabelecimento: promoção,
   horário, novidade da loja. Não é vitrine de vídeo vertical, não tem feed e não tem descoberta.

⚠️ Mesmo que o vídeo passasse, a pessoa publicaria um corte de 9:16 na ficha do Google de um
comércio. **Não é o produto.**

**Decisão: fora.** Se um dia o produto atender comércio local, isto volta — como funcionalidade
daquele produto, não desta.

---

## 🟡 LinkedIn Página — o código já existe; falta a aprovação

⭐ Esta é diferente das outras duas: **tecnicamente já está pronta.** Publicar numa Página é a
**mesma** API de posts do LinkedIn que o painel já usa, trocando o autor de
`urn:li:person:{id}` para `urn:li:organization:{id}`.

⛔ O que falta é permissão, e ela não é self-service:

| Permissão | Para quê | Como se obtém |
|---|---|---|
| `w_organization_social` | publicar na Página | **aprovação da LinkedIn** |
| `r_organization_social` | **reler o post** | **aprovação da LinkedIn** |

⭐ **E é justamente aqui que o LinkedIn deixaria de ser a rede sem prova.** Com
`r_organization_social`, o painel releria o post da Página e a DEC-106 — a ressalva de que no
LinkedIn não houve conferência — deixaria de valer para este canal.

⚠️ **Por isso não escrevemos o código ainda.** Não é falta de vontade: é que código para escopo que
não temos não pode ser testado nem contra a rede nem contra um sandbox — ele seria três telas de
suposição esperando por uma aprovação incerta.

**Decisão: fica como pedido a fazer**, registrado no
[plano do LinkedIn](22-plano-linkedin.md). Quando a aprovação sair, o trabalho é pequeno: um
publicador que herda o de perfil, trocando o autor e ganhando a releitura.

---

## ⛔ Kwai — não é escolha nossa, é ausência de porta

**Perguntado em 2026-08-14, e a resposta é dura:** o Kwai **não tem API pública de publicação**.

O que existe com o nome de API é outra coisa:

- **Kwai for Business / API de eventos** — anúncios e rastreamento de conversão. Nada de publicar
  conteúdo orgânico.
- **Kuaishou Open Platform** — a plataforma aberta do irmão chinês, que exige entidade jurídica na
  China e não atende o Kwai internacional.

⛔ **Então não é questão de esforço nem de fila:** não há endereço para chamar. Enquanto isso não
mudar, Kwai só entra por automação de navegador — que este projeto recusa por princípio (ver acima:
publicar fingindo ser gente é o que derruba conta e some sem aviso).

⚠️ **E ele merece este registro justamente por ser grande no Brasil.** Quem usar o painel vai
perguntar, e a resposta precisa estar escrita — senão vira "esqueceram do Kwai".

---

## O que sobrou, e por quê

| Rede | Situação |
|---|---|
| Bluesky, YouTube | publicando |
| Facebook, Instagram, Threads | código pronto, conta conectada |
| LinkedIn, TikTok, X, Pinterest | código pronto, falta cadastro no portal |
| **Mastodon, Discord** | ⭐ código pronto e **sem cadastro nenhum a fazer** |
| LinkedIn Página | pedido a fazer |
| Snapchat, Google Meu Negócio | fora, com motivo |
| **Kwai** | ⛔ **sem API pública de publicação** — não há porta |

⭐ **Onze redes com código, e duas delas testáveis hoje** — sem esperar aprovação de ninguém.
