# Facebook — achados da documentação oficial

> O que está em [`../meta-compartilhado.md`](../meta-compartilhado.md) vale aqui e não se repete.

---

## ⛔ F-1 — 90 segundos. É o limite mais apertado de todas as redes

| Rede | Duração máxima |
|---|---|
| **Facebook Reels** | **90 segundos** |
| Instagram Reels | 15 minutos |
| YouTube Shorts | 3 minutos |
| Bluesky | 3 minutos |

Um corte de 2 minutos **publica em três redes e é recusado só no Facebook**. Se a recusa vier
depois do envio, a pessoa esperou o upload inteiro para receber um "não".

**Decisão:** entra em `EspecificacaoDaRede` como duração máxima, e a recusa acontece **antes de
qualquer byte subir**, dizendo qual rede não aceita e quanto passou.

---

## ⚠️ F-2 — Só em Páginas. Perfil pessoal não publica

*"You can only publish Reels to Facebook Pages."*

Não é limitação de permissão: **não existe** endpoint para perfil pessoal. Quem só tem perfil
não tem o que conectar.

**Consequência de produto:** a tela precisa dizer isso **antes** de a pessoa tentar, senão ela
conecta, não vê Página nenhuma e conclui que o sistema está quebrado.

---

## ⭐ F-3 — Três fases, e a terceira é a que publica

```
1. start   → POST /<page-id>/video_reels  { upload_phase: "start" }
             devolve  video_id  +  upload_url
2. upload  → POST rupload.facebook.com/video-upload/<video-id>
             cabeçalhos: offset, file_size, Authorization: OAuth ...
3. finish  → POST /<page-id>/video_reels  { upload_phase: "finish", video_state: "PUBLISHED" }
```

⚠️ **A própria documentação se contradiz na fase 3:** o campo é descrito como
`enum{start,finish}`, o texto ao lado diz *"para publicar, deve ser `complete`"*, e o exemplo usa
`finish`. **Vamos com `finish`** — é o que está no exemplo executável, e o exemplo é o que a Meta
testa.

O `video_id` da fase 1 é o nosso `handle_externo`: é ele que permite retomar sem republicar.

---

## ⭐ F-4 — O status vem separado por fase

`GET /<video-id>?fields=status` devolve três blocos:

- `upload_phase` — quanto subiu (e o `bytes_transfered` para retomar)
- `processing_phase` — a Meta gerando encodes e thumbnails
- `publishing_phase` — com `publish_status`: `draft`, `error`, `published`, `scheduled`

Cada um com `status`: `not_started` / `in_progress` / `completed` / `error`, e `error` traz
mensagem própria.

**É melhor que o YouTube:** lá o `uploadStatus` é um valor só. Aqui dá para dizer *"o vídeo subiu
inteiro e está sendo processado"* em vez de um "aguarde" genérico.

⚠️ **Conciliar pelo bloco errado marcaria publicado cedo demais.** `processing_phase: completed`
**não** é publicado — só `publishing_phase.publish_status == published` é.

---

## ⭐ F-5 — Agendamento nativo, com regras próprias

`scheduled_publish_time` (timestamp Unix) + `video_state: SCHEDULED`:

- **mínimo 10 minutos** à frente
- **máximo 29 dias**

Fora dessa janela a API recusa. Se a pessoa agendar para daqui a 5 minutos, quem tem que avisar
somos nós — antes de enviar.

`video_state: DRAFT` também existe: sobe sem publicar. É a resposta honesta para "quero conferir
antes" — sem inventar um rascunho nosso que a rede não conhece.

---

## Especificações do reel (números exatos)

| | |
|---|---|
| Formato | **.mp4** (recomendado) |
| Proporção | **9:16** |
| Resolução | 1080 × 1920 recomendado — **mínimo 540 × 960** |
| Taxa de quadros | **24 a 60 FPS** (fixa, não variável) |
| Duração | **3 a 90 segundos** |
| Vídeo | H.264/H.265 (VP9 e AV1 também), progressivo, GOP fechado de 2-5 s, 4:2:0 |
| Áudio | AAC-LC, 48 kHz, 128 kbps+, **estéreo** |

⚠️ Dois detalhes que o Instagram não exige: **taxa de quadros fixa** (o Instagram aceita
variável) e **áudio estéreo** (o Instagram aceita mono).

---

## Limite: 30 por 24 horas

Um terço do Instagram. É o teto que vai ser atingido primeiro em qualquer uso em lote — vira
`aguardando_janela` (DEC-24), não erro.

---

## O que ainda precisa ser confirmado

- **Limite de caracteres da descrição** — a documentação não publica o número. Mesmo tratamento
  do Instagram: sem número fixo no código, a recusa usa o que a rede responder.
- **Convidar colaborador** e **marcar local** existem (`place`), mas ficam fora do escopo agora.

_Baixado e lido em 2026-07-31, de `documentacao/`._

---

## Achados da REVISÃO (2026-07-31)

Encontrados relendo o código recém-escrito contra a documentação — e um deles não é da Meta.

### 🔴 F-R1 — A retentativa do motor estava **morta** *(bug antigo, todas as redes)*

`devolverParaFila` mudava o estado do destino para `pendente`… e mais nada. **Nenhum job era
criado.** O destino ficava parado para sempre, esperando uma tentativa que nunca vinha.

O teto de 3 tentativas nunca era alcançado, porque a **segunda nunca acontecia**. Todo o desenho
de retentativa era decoração — e o vigia de órfãos só varre `enviando` e `processando`, então
nem ele resgatava.

Valia para **YouTube e Bluesky também**, não só para a Meta. Um erro passageiro de rede — o mais
comum de todos — deixaria a publicação parada em "na fila", sem erro na tela e sem nada
acontecendo.

**Corrigido:** o job se reenfileira com espera crescente (1, 2, 3 min), e para quando o serviço
decide que acabou. Travado por dois testes.

### 🔴 F-R2 — Pedíamos um campo que não existe, e isso derruba a chamada INTEIRA

A conciliação pedia `permalink_url` no vídeo. Esse campo **não está na lista de campos do nó
Video**, e o Graph API não devolve nulo para campo inexistente: **recusa a requisição toda** com
o erro 100 (*"Tried accessing nonexisting field"*).

Ou seja: **toda** conciliação do Facebook falharia, e **toda** publicação seria marcada como
falha sem ter falhado — justamente o defeito que o produto existe para combater, invertido.

**Corrigido:** pedimos só `status`, que é documentado, e o endereço do reel é montado a partir
do identificador.

### 🔴 F-R3 — A retomada estava inventada

`jaRecebidos` fazia um POST vazio no `rupload` esperando um `offset` de volta. Isso **não existe
na documentação** — eu escrevi de cabeça, o mesmo erro que o Gabriel apontou no YouTube.

O caminho documentado é outro: `GET /<video-id>?fields=status` e ler
`uploading_phase.bytes_transfered`.

*(A mesma página chama esse bloco de `upload_phase` no texto e de `uploading_phase` na tabela de
campos — o código confere os dois.)*

**Corrigido.** E no Instagram, onde a documentação **não descreve retomada nenhuma**, o envio
recomeça do zero: reenviar é lento, continuar de um ponto adivinhado corrompe o arquivo.

### 🔴 F-R4 — Rejeitávamos Páginas da experiência NOVA

O filtro exigia a permissão `CREATE_CONTENT`. Só que existem **duas nomenclaturas**: Páginas
antigas devolvem `CREATE_CONTENT`, e as da experiência nova — hoje o padrão para Página recém
criada — devolvem `PROFILE_PLUS_CREATE_CONTENT`.

O resultado seria dizer *"você não tem permissão para publicar"* numa Página que a pessoa acabou
de criar e administra sozinha.

**Corrigido:** as duas nomenclaturas são aceitas.

### F-R5 — Versão da API atrasada

O código usava `v23.0`; a documentação usa **`v25.0`** em todos os exemplos. Atualizado.

### F-R6 — A verificação de permissões concedidas não existia de fato

A constante estava declarada e nunca usada. O Facebook **não devolve os escopos** na troca do
código — quem responde isso é `/me/permissions`, com `granted` ou `declined` por permissão.

**Corrigido:** conferido de verdade, com a mesma régua do YouTube (R-2).

### F-R7 — `interpretar()` sem tipo no parâmetro

Detalhe de rigor: recebia `$resposta` solto. Corrigido para `Response`.
