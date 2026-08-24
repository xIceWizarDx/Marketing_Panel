# Meta — o que Facebook e Instagram têm em comum

> Escrito uma vez, referenciado pelos dois planos. Duplicar isso seria garantir que um dia as
> duas cópias discordem.
>
> ⛔ **O Threads NÃO entra aqui.** Ele é da Meta, mas tem janela de autorização própria
> (`threads.net`), servidor próprio (`graph.threads.net`), permissões próprias (`threads_*`) e
> **não aceita envio de arquivo** — só URL pública. De comum com esta página, sobra o formato do
> erro. Ver [`threads/achados.md`](threads/achados.md).

As duas redes são **a mesma API por baixo**: mesmo login, mesmo servidor de upload, mesmo
formato de erro, mesmo modelo de token. O que muda é o endpoint final e os limites.

---

## A decisão que define tudo: **Login do Facebook para Empresas**

Existem dois caminhos para o Instagram, e eles **não** são equivalentes:

| | Login do Instagram | **Login do Facebook (escolhido)** |
|---|---|---|
| Servidor | `graph.instagram.com` | `graph.facebook.com` |
| Envio do vídeo | **só por URL pública** | **arquivo direto** (`rupload.facebook.com`) |
| Precisa de Página do Facebook | não | sim |
| Serve para o Facebook também | **não** | **sim** |

A documentação é explícita sobre o envio direto: *"Only for apps that have implemented Facebook
Login for Business."*

**Por que isso decide:** nosso vídeo mora no servidor, em `storage/app`. Pelo Login do
Instagram, a Meta teria que **baixar o vídeo de uma URL pública** — ela não enxerga `localhost`,
e em produção exigiria expor os arquivos dos clientes na internet aberta. Seria trocar uma
integração por um vazamento.

E como o Gabriel quer as duas redes, o Login do Facebook resolve as duas de uma vez: **uma
conexão só acende o Facebook e o Instagram juntos.**

---

## Dá para testar agora, sem aprovação

Mesma lógica do YouTube, nome diferente. A Meta tem dois níveis:

- **Acesso Padrão** — *"será aprovado automaticamente para todas as permissões"*. Funciona
  **apenas para quem tem papel no aplicativo** (administrador, desenvolvedor, testador).
- **Acesso Avançado** — exige Análise do Aplicativo, e aí funciona para qualquer pessoa.

A página de publicação de conteúdo lista **os dois** como suficientes. Ou seja: **o Gabriel
publica de verdade, na conta dele, sem análise nenhuma** — basta ter papel no próprio app.

A análise só é necessária quando outras pessoas forem usar o produto.

---

## ⭐ O token não morre toda semana (diferente do YouTube)

| Token | Validade |
|---|---|
| Curto (o que volta do login) | 1 a 2 horas |
| Longo, de usuário | ~60 dias |
| **Longo, de Página** | ***"não têm data de validade"*** |

O caminho é: token curto → troca por longo (`grant_type=fb_exchange_token`) → dele sai o **token
da Página, que não expira**.

Isso é uma vantagem real sobre o YouTube, onde em modo de Testes a autorização cai a cada 7
dias. Mas tem uma armadilha: ***"não é possível usar um token expirado para pedir um token
longo"***. Se deixar vencer os 60 dias do token de usuário sem ter trocado, só reconectando.

**Consequência para o código:** trocar pelo token longo **na hora da conexão**, nunca depois.

---

## ⭐ O erro já vem com is_transient — a rede diz se vale tentar de novo

Todo erro da Meta tem esta forma:

```json
{
  "error": {
    "message": "The image size is too large.",
    "code": 36000,
    "error_subcode": 2207004,
    "is_transient": false,
    "error_user_title": "Image size too large",
    "error_user_msg": "The image is too large to download. It should be less than 8 MiB.",
    "fbtrace_id": "A6LJylpZRKw2xKLFsAP-cJh"
  }
}
```

Dois campos mudam o motor:

**`is_transient`** — a própria rede dizendo se o erro passa. É exatamente a decisão que o motor
toma entre `devolverParaFila` e `marcarFalha`, e aqui não precisamos adivinhar. No YouTube tivemos
que deduzir isso do código HTTP.

**`error_user_msg`** — mensagem que a Meta escreveu *para o usuário final ler*. Nossa tradução em
português vem primeiro; quando não houver correspondência, esta é melhor do que um código seco.

**`fbtrace_id`** — o identificador que o suporte da Meta pede. Guardar em `tentativas` custa
nada agora e evita um "não temos como rastrear" depois.

---

## O que vale para as duas na hora de enviar

- **Envio retomável** com os mesmos cabeçalhos: `offset`, `file_size`, `Authorization: OAuth ...`
- Retomar é reenviar com o `offset` que a rede informou — mesmo desenho do YouTube, então o
  contrato `Retomada` do motor serve sem mudança
- **`moov atom` no início do arquivo** e *sem edit lists* — é o `-movflags +faststart` do ffmpeg.
  Vídeo sem isso é recusado, e a mensagem não diz o motivo
- **Áudio AAC**, 48 kHz, 128 kbps, mono ou estéreo
- **Vídeo H.264 ou H.265**, scan progressivo, GOP fechado, 4:2:0

---

## O que muda entre as duas

| | Facebook (Reels) | Instagram (Reels) |
|---|---|---|
| Duração | **3 a 90 segundos** | **3 s a 15 min** |
| Tamanho | — | **300 MB** |
| Publicações por 24 h | **30** | **100** |
| Fluxo | 3 fases (`start`/upload/`finish`) | container → upload → `media_publish` |
| Agendamento | `scheduled_publish_time` (10 min a 29 dias) | não tem |
| Onde publica | **só em Páginas** | conta profissional |

⚠️ **A diferença de duração é a que mais vai doer.** Um corte de 2 minutos publica no Instagram
e é recusado no Facebook. O motor precisa recusar **antes** de enviar, dizendo qual rede não
aceita e por quê — não depois de subir 300 MB.

---

## Duas condições que não dá para detectar por código

**Autorização de Publicação da Página (PPA)** — algumas Páginas exigem, e a documentação admite:
*"não há como você determinar se a Página de um usuário exige PPA"*. A recomendação oficial é
avisar para fazer preventivamente. Vira aviso na tela, não tratamento de erro.

**Autenticação em dois fatores** — se a Página exige, e a pessoa não fez, a publicação falha.

_Fonte: `instagram/documentacao/` e `facebook/documentacao/`, baixadas em 2026-07-31._
