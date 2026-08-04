# Plano de ação — Grupo: a rede de canais de uma linha de conteúdo

_Criado em 2026-08-04._

> **A ideia em uma frase:** quem produz duas coisas diferentes — notícias e novelas — tem duas
> redes de canais, e hoje o painel trata as duas como uma pilha só. Marcar a conta errada publica
> no lugar errado, e publicação não tem desfazer.
>
> Regra de conclusão: **suíte verde, tipos e lint limpos, grep-zero, conferido no navegador**.

---

## O problema, com nome

A pessoa cria um canal de notícias no YouTube, no Instagram e no TikTok. Depois cria outro trio
para novelas. No compositor, os seis canais aparecem lado a lado, e a única coisa que separa um
grupo do outro é a atenção dela no momento de marcar as caixinhas.

**É o acidente que este produto existe para evitar.** Todo o resto do painel foi construído para
não mentir sobre o que aconteceu; não faz sentido deixar em aberto o erro que acontece *antes* de
publicar.

---

## O nome: por que **grupo**

O campo semântico está quase todo ocupado, e escolher errado obriga a renomear código que não tem
nada a ver com a feature:

| Palavra | Por que não |
|---|---|
| **rede** | é rede social em toda a UI: "Suas redes", "Conectar uma rede", `LogotipoDaRede` |
| **perfil** | é a tela `minha-conta/perfil` |
| **marca** | `<Marca />` desenha o **nome do produto** (0.N) e `MarcaDaRede` é o logo da rede. Pior: `marca_propria` é **termo auditado do TikTok** e "R$ 49–79/marca" é o preço — os dois intocáveis |
| **projeto** | "o projeto" significa o próprio software em 34 pontos da documentação. Não tem conserto |
| **canal** / **conta** | canal é do YouTube; conta é conta social e conta de usuário |

**Grupo** está livre, e é a palavra que o dono do produto usou naturalmente ao descrever o
problema. Custo total de adoção: renomear `GrupoDeMenu` → `SecaoDeMenu` (tipo do front, sem
consumidor de negócio) e reescrever um comentário de rota.

⭐ **A escolha do nome economizou um bloco inteiro de trabalho.** Com "Marca" seriam seis
renomeações em cascata — inclusive em texto que a auditoria das plataformas leu.

---

## ⚠️ O contrato proíbe isto, e o contrato vai ser corrigido

`CLAUDE.md` §0.G: *"**Proibido cedo:** billing, workspaces, multiempresa"*. Grupo é um workspace
leve, e o `CLAUDE.md` vence qualquer outro arquivo.

A regra não estava errada — ela impedia inventar estrutura antes de existir problema. O problema
apareceu: uma pessoa, duas linhas de conteúdo, e um acidente que não desfaz. **A regra é emendada
no mesmo commit**, com data e motivo. Billing e multiempresa continuam proibidos.

⛔ Regra viva contradizendo o código é o pior tipo de drift: a próxima sessão derruba a feature
citando o contrato.

---

## As decisões

**DEC-69 — grupo é a rede de canais de uma linha de conteúdo, e o grupo É seus canais.** Não é
pasta vazia que depois se enche: sem canal, um grupo não tem o que ser. Isso governa o estado
vazio, o fluxo de criação e a regra de arquivamento.

**DEC-70 — uma conta social pertence a UM grupo só.** Canal em dois grupos traria de volta
exatamente o risco de publicar no lugar errado. Adotar o mais apertado agora: afrouxar depois é
barato, apertar depois exige mexer em dado existente.

**DEC-71 — grupo é MODO, não filtro.** Filtro se esquece de marcar; modo se está dentro. O seletor
fica sempre visível, inclusive dentro do compositor — que cobre a tela inteira justamente durante o
gesto irreversível.

**DEC-72 — o grupo corrente vive na SESSÃO e nunca na URL.** Ele é preferência de visualização, não
recorte compartilhável. Trocar de grupo é `POST`: com `GET`, o prefetch do navegador trocaria o modo
sozinho.

**DEC-73 — ⭐ o grupo de uma publicação vem das CONTAS escolhidas, e o servidor recusa contas de
grupos diferentes.** A sessão só decide o que a tela mostra; a verdade vem das contas. É isto que
torna impossível uma aba velha publicar no grupo errado — e é a trava que sobrevive a qualquer
defeito de interface.

⚠️ Junto vem uma correção de um defeito antigo: hoje, conta que não é do dono é **descartada em
silêncio** da lista. Passa a lançar. Filtrar calado é a implementação errada desta mesma decisão.

**DEC-74 — dono é SEGURANÇA, grupo é ORGANIZAÇÃO.** Dono tem Global Scope que **lança** quando não
há contexto. Grupo tem filtro **explícito** nas telas. ⛔ **Não existe trait `PertenceAoGrupo` nem
global scope de grupo:** job e comando não têm grupo corrente, e um scope que lançasse quebraria o
motor inteiro.

**DEC-75 — `publicacoes.grupo_id` é gravado e imutável; toda contagem sai dele.** Mover um canal de
grupo não pode mudar retroativamente o número histórico. Contagem tirada de `contas_sociais.grupo_id`
faria o passado mudar quando o presente é reorganizado.

**DEC-76 — arquivar grupo é soft delete, e só vale para grupo sem canal.** Nunca o último grupo.
Arquivar um grupo com canais deixaria canal conectado e invisível — publicando por trás da tela, ou
falhando sem ninguém ver.

**DEC-77 — mover canal entre grupos existe; o histórico NÃO vai junto.** Sem mover, o primeiro erro
de cadastro vira permanente e a pessoa cria canal duplicado. O que já foi publicado fica onde
saiu — é o que sustenta a DEC-75.

**DEC-78 — criar grupo não troca de modo sozinho.** Trocar sozinho esvazia o painel inteiro sem
explicar, e a pessoa conclui que o sistema apagou o trabalho dela. O diálogo pergunta, e diz que
canais e posts continuam onde estavam.

**DEC-79 — publicar leva o modo junto.** Depois de enviar, o grupo corrente passa a ser o grupo das
contas, e o aviso o nomeia: *"Enviamos para 2 contas de Notícias."* Sem isso, publicar de uma aba em
outro grupo cai numa lista vazia com um aviso verde por cima — e o reflexo é publicar de novo.

**DEC-80 — aviso de SAÚDE ignora o filtro de grupo.** Autorização vencendo e conta parada aparecem
sempre, nomeando onde estão (*"«X», em Novelas, parou de publicar"*). Aviso de **volume** segue o
grupo corrente. Conta da outra ponta não pode morrer calada só porque a pessoa está olhando outro
grupo.

---

## ⛔ Fora de escopo, dito na cara

**Cobrança e limite por grupo.** É coluna que se acrescenta qualquer dia; não muda estrutura.

**Dashboard de métricas por grupo.** Continua fazendo sentido, mas está bloqueado por fora:
enquanto o aplicativo do YouTube estiver em modo de Testes, todo vídeo sobe privado, e vídeo privado
não tem métrica pública. O dashboard mostraria zero em tudo.

---

## ✅ Fase 1 — O nome livre — CONCLUÍDA

⚠️ Antes de existir tabela: a palavra tem que estar disponível, senão o conceito nasce ambíguo.

- [x] **1.1** `GrupoDeMenu` → `SecaoDeMenu` em `resources/js/types/index.ts` e consumidores
- [x] **1.2** Reescrever o comentário de `routes/admin.php` que usa "grupo" no sentido de middleware
- [x] **1.3** `grep` de "grupo" → só o sentido novo

**Pronto quando:** a palavra grupo significa uma coisa só no projeto.

---

## ✅ Fase 2 — O contrato e o glossário — CONCLUÍDA

⚠️ DEC-18: nome nasce no glossário **antes** do código.

- [x] **2.1** `CLAUDE.md` §0.G — emenda datada, com o motivo
- [x] **2.2** `CLAUDE.md` §0.M — a frase que separa dono=segurança de grupo=organização
- [x] **2.3** Glossário: bloco `grupos` coluna a coluna (molde `midias`, que é o único que
  documenta `ulid`)
- [x] **2.4** Glossário: `grupo_id` em `contas_sociais` **com a nota de que ela NÃO entra na única
  `contas_sociais_conta_unica`** — dentro dela, o mesmo canal poderia ser conectado em dois grupos
- [x] **2.5** Glossário: `grupo_id` em `publicacoes` **com o porquê ao lado** — senão a regra
  "derivado nunca vira coluna" apaga a coluna na próxima revisão
- [x] **2.6** Glossário: relações, regras de deleção, rotas, e `deleted_at` no território do
  framework (senão o próximo saneamento renomeia para `arquivado_em` e quebra o SoftDeletes calado)
- [x] **2.7** `05-plano-de-acao.md`: DEC-69 a DEC-80

**Pronto quando:** dá para implementar lendo só o glossário.

---

## ✅ Fase 3 — Banco — CONCLUÍDA

- [x] **3.1** `grupos`: id, ulid único, `usuario_id` restrict, nome (**sem única**), softDeletes,
  índice `(usuario_id, deleted_at)`
- [x] **3.2** `contas_sociais.grupo_id` **nullable**, restrict, índice `(usuario_id, grupo_id)`
- [x] **3.3** `publicacoes.grupo_id` nullable, restrict, índice `(usuario_id, grupo_id, created_at)`
- [x] **3.4** ⚠️ Todo `down()` derruba o índice **antes** do `dropColumn` — o SQLite recusa dropar
  coluna indexada, e a reversão quebrada só aparece no dia em que alguém precisa dela
- [x] **3.5** Model `Grupo` com `PertenceAoUsuario` + `SoftDeletes`; `grupo_id` no `$fillable` de
  `ContaSocial` e `Publicacao`
- [x] **3.6** Factories e seeder

**Pronto quando:** `migrate:fresh --seed` sobe e `migrate:rollback` volta.

---

## ✅ Fase 4 — Nascimento e migração de quem já tem dados — CONCLUÍDA

⚠️ É o único ponto do projeto que escreve em massa atravessando clientes. Aqui mora o risco.

- [x] **4.1** `GrupoService` — escritor único de `contas_sociais.grupo_id`. ⛔ **Proibido
  `Grupo::withoutGlobalScopes()`**: derruba o escopo de dono E o de arquivado de uma vez. Onde
  precisar furar, `withoutGlobalScope(EscopoDoUsuario::class)` com o `where('usuario_id')` na mão
- [x] **4.2** Conta nova nasce com um grupo — no cadastro, no seeder e na factory (um ponto só)
- [x] **4.3** Comando `grupos:garantir-principal` que itera **usuários**, nunca "linhas sem grupo" —
  cliente sem conta social nenhuma também precisa de grupo
- [x] **4.4** ⛔ O comando **nunca escreve dentro de `semEscopo`**: define o dono no contexto por
  cliente. `semEscopo` só para ler a lista de usuários
- [x] **4.5** Ao conectar, a conta nasce no grupo corrente — em todas as portas (Bluesky, YouTube,
  Meta), lembrando que uma conexão da Meta cria várias contas de uma vez
- [x] **4.6** Migration de apertar para NOT NULL em **commit separado**, começando por uma guarda
  legível: se sobrou linha sem grupo, lança mandando rodar o comando **antes** de tocar em schema

**Pronto quando:** um banco com dados reais atravessa a migração sem linha órfã.

---

## ✅ Fase 5 — A trava do envio — CONCLUÍDA

⭐ O coração da feature. Tudo o mais é conveniência; isto é regra.

- [x] **5.1** `recusarGruposMisturados()` como **primeira** das recusas em `EnvioDePublicacao`
- [x] **5.2** Comparar a **coluna** `grupo_id`, nunca `$conta->grupo?->id` — grupo arquivado
  devolve null, e dois nulos parecem "o mesmo grupo"
- [x] **5.3** Recusar quando alguma conta pedida não voltou da consulta (hoje some em silêncio)
- [x] **5.4** `Publicacao::create` grava o `grupo_id` derivado
- [x] **5.5** Depois de publicar, o modo passa a ser o grupo das contas, e o aviso o nomeia
- [x] **5.6** ⛔ `PublicarRequest` **não** aceita campo de grupo — a verdade não vem do cliente

**Pronto quando:** um POST montado à mão com contas de dois grupos é recusado.

---

## ✅ Fase 6 — As telas leem por grupo — CONCLUÍDA

- [x] **6.1** ⚠️ Nas consultas com `join` cru, **somar** o filtro de grupo — nunca trocar o
  `whereIn` escopado que já existe: ele é a única coisa que aplica o escopo de dono ali
- [x] **6.2** Proibido `when()` no filtro de grupo: grupo não resolvido é bug, tem que estourar
- [x] **6.3** Grade de redes, números da visão geral, lista e abas de publicações, contas do
  compositor
- [x] **6.4** Aviso de saúde fora do filtro, nomeando o grupo (DEC-80)
- [x] **6.5** Republicar deriva o grupo da publicação anterior **sem escrever na sessão** — a URL é
  compartilhável, e a sessão em outro grupo abriria texto de um com contas de outro
- [x] **6.6** Toda coluna nova lida entra no select parcial no mesmo passo (strict mode)

**Pronto quando:** trocar de grupo troca tudo, e nenhuma contagem discorda da lista.

---

## ✅ Fase 7 — O seletor e o resto da tela — CONCLUÍDA

- [x] **7.1** Seletor na barra superior, visível **mesmo com um grupo só** — é a única porta de
  entrada da funcionalidade: não há item de menu nem tela
- [x] **7.2** Estado sempre do servidor. Nunca `useState`, nunca `localStorage`
- [x] **7.3** Criar, renomear e arquivar em diálogo. Criar **pergunta** antes de trocar de modo
- [x] **7.4** Mover canal no detalhe da rede, dizendo o que vai e o que fica
- [x] **7.5** ⭐ Estados vazios **próprios** de grupo sem canal — o texto atual afirma que a pessoa
  não tem conta conectada quando ela tem cinco, e a empurra para reconectar em laço
- [x] **7.6** Conectar um canal que já está em outro grupo não dá sucesso genérico: oferece trazer
  para cá ou deixar onde está
- [x] **7.7** O nome do grupo aparece dentro do compositor — ele cobre a tela e esconde o seletor
  justamente durante o gesto que não desfaz

**Pronto quando:** dá para viver com dois grupos sem se perder.

---

## ✅ Fase 8 — Guardiões — CONCLUÍDA

- [x] **8.1** Grupo de um cliente não aparece para outro
- [x] **8.2** Publicar com contas de grupos diferentes é recusado
- [x] **8.3** `publicacoes.grupo_id` não muda quando o canal troca de grupo
- [x] **8.4** Não arquiva grupo com canal, nem o último grupo
- [x] **8.5** Backfill com **dois** clientes com dados: nenhum recebe grupo do outro
- [x] **8.6** Cliente sem conta social nenhuma também ganha grupo
- [x] **8.7** Job continua rodando sem grupo no contexto
- [x] **8.8** Suíte inteira verde

**Pronto quando:** o que foi decidido aqui não volta a quebrar sozinho.

---

## Executado em 2026-08-04

**357 testes verdes** (17 novos no guardião do grupo), `tsc`, `eslint`, `pint` e `npm run build`
limpos. `migrate:fresh`, `migrate` e `migrate:rollback` conferidos com dados dentro.

### O que o mapeamento em paralelo economizou

Antes de escrever a primeira linha, seis leitores varreram o código em paralelo e três críticos
adversariais tentaram derrubar o desenho. Acharam **10 bloqueadores** — todos previstos no código
final. Os que mais teriam custado:

**`Grupo::withoutGlobalScopes()` derruba DOIS escopos.** O de dono e o de arquivado, de uma vez. O
gesto é idioma corrente no projeto (e inofensivo nos models que têm um scope só), então entraria
sem ninguém notar — e um grupo arquivado voltaria a ser eleito o principal da pessoa.

**O `whereIn` escopado nas consultas com `join` não é conveniência, é a trava.** O Global Scope não
acompanha um `->join()` cru, então trocar aquela subconsulta por um filtro de grupo teria
substituído isolamento por preferência de tela.

**O backfill é a única escrita em massa que atravessa clientes.** O molde óbvio a copiar
(`GerarMiniaturasPendentes`) usa `semEscopo` sem filtro de dono — copiado, entregaria as contas de
todo mundo ao grupo do primeiro cliente do laço.

**Criar o segundo grupo esvaziaria o painel sem explicar**, e a tela de vazio existente diz
literalmente que a pessoa nunca publicou nada.

### A escolha do nome pagou sozinha

"Marca" tinha **quatro** sentidos vivos, dois deles intocáveis: `marca_propria` é termo auditado do
TikTok, e "R$ 49–79/marca" é a unidade de preço. Adotá-la exigiria seis renomeações em cascata.
"Grupo" custou apagar um tipo morto do front e reescrever um comentário.

### Três defeitos que apareceram no caminho

**Conta que não era do dono sumia em silêncio** da lista de destinos: a pessoa escolhia 4 contas e
recebia "enviamos para 3".

**Apagar a conta estourava erro de banco** para quem tivesse qualquer dado — e passaria a valer
para todo mundo, já que agora todo usuário tem grupo.

**Deslogar depois de apagar ressuscitava a conta.** O guard cicla o *remember token* do usuário que
ainda tem em mãos e chama `save()`; `save()` num model apagado vira INSERT. A pessoa voltava com id
novo, ganhava um grupo em branco, e a tela dizia que deu tudo certo. Só apareceu com sonda.

### Um ponto que mudou de lugar durante a execução

O canal nasce no grupo pelo **gatilho do model**, não pelos serviços de conexão. São quatro portas
(Bluesky, YouTube, e a da Meta que cria Página e Instagram de uma vez), e espalhar por quatro
garantiria esquecer uma — com a conta nascendo invisível **depois** de a pessoa já ter autorizado
no Google, quando não há mais como voltar atrás.

### O que ficou de fora, de propósito

Cobrança por grupo (é coluna, não estrutura) e o dashboard de métricas — este bloqueado por fora:
enquanto o aplicativo do YouTube estiver em modo de Testes, todo vídeo sobe privado, e vídeo
privado não tem métrica pública.
