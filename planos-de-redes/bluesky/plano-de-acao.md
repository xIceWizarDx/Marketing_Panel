# PLANO DE AÇÃO — Bluesky

> **Estado: publicando, com as correções da documentação aplicadas.** Foi a primeira rede do projeto (DEC-29), escolhida por não depender
> de auditoria nenhuma — a autorização é por senha de aplicativo que a própria pessoa gera.
>
> Este plano foi escrito **depois** da implementação, quando a consulta à documentação oficial
> revelou divergências. Documentação em [`documentacao/`](documentacao/).

---

## ✅ O que já funciona

- Conectar conta por senha de aplicativo, validada **antes** de guardar
- Publicar vídeo e imagem (`app.bsky.embed.video` / `app.bsky.embed.images`)
- ⭐ Conciliação: relê o post com `com.atproto.repo.getRecord` e grava o link como prova
- Erros interpretados: 429 e 5xx viram retentativa; 401 vira "reconecte"
- Desconectar preserva a conta e apaga só a credencial

---

## 🔴 O que a consulta à documentação corrigiu

| # | Escrito de memória | Documentado | Impacto |
|---|---|---|---|
| 1 | limite de texto conferido com `mb_strlen` (pontos de código) | são **grafemas** (`maxGraphemes: 300`) | 🔴 **recusa texto válido**: emoji de família conta 1 grafema e vários pontos de código |
| 2 | vídeo limitado a **50 MB** | **100.000.000 bytes** (lexicon) | 🟡 limite inventado, metade do real |
| 3 | imagem sem limite próprio | **2.000.000 bytes** (lexicon) · até **4 por post** | 🟡 falta conferir |
| 5 | vídeo enviado com o MIME do arquivo (aceita `.mov`) | o lexicon aceita **só `video/mp4`** | 🔴 **`.mov` do iPhone é recusado** — e a mensagem vem em inglês, sem explicação |
| 4 | `createdAt` com `toIso8601ZuluString()` | o `Z` final **é o preferido** | ✅ estava certo |

---

## ✅ Corrigido em 31/07/2026

- [x] 🔴 **Só `video/mp4`.** O `.mov` do iPhone precisa ser recusado **antes** de gastar o
      upload, com mensagem em português dizendo o porquê — hoje ele sobe e a rede recusa.
      *(A biblioteca aceita `.mov` porque a Meta aceita; o Bluesky não.)*
- [x] 🔴 Contagem de texto por **grafemas** (`grapheme_strlen`, extensão `intl`).
      Conferir se a extensão está ativa no servidor — se não estiver, degrada, não quebra.
- [x] 🟡 Teto de vídeo: **100.000.000 bytes** (era 50 MB no código, metade do real).
- [x] 🟡 Teto de imagem: **2.000.000 bytes**, até **4 por post**.
- [x] Teste com emoji: 300 grafemas de emoji de família passam.

---

## ✅ Nada em aberto

Todos os números vieram do **lexicon oficial** — a fonte que o próprio projeto publica, e onde
os limites são declarados. Cópia local em [`documentacao/lexicons/`](documentacao/lexicons/).

_2026-07-31 — plano escrito após consulta à documentação oficial._
