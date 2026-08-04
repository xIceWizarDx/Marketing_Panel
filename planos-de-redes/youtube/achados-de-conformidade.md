# Achados de conformidade — YouTube

> Leitura das **Políticas do Desenvolvedor** — o documento que a auditoria de compliance
> verifica, e que decide se o projeto é aprovado.
> Fonte: <https://developers.google.com/youtube/terms/developer-policies> · 31/07/2026.
>
> **É o documento que reprova projeto.** O que está aqui não é sugestão.

---

## 🔴 1. Nosso código VIOLA a política — silenciosamente

> *"Proibido modificar valores fornecidos pelo usuário (truncar, anexar, alterar) **sem
> consentimento explícito**."*
> *"O usuário deve ter **controle final** sobre os dados que serão publicados no YouTube."*

**O `PublicadorYoutube` que escrevi faz exatamente isso, três vezes:**

```php
'title'       => mb_substr($titulo, 0, 100),                  // corta o título
'description' => mb_substr($destino->textoFinal(), 0, 5000),  // corta a legenda
'tags'        => array_slice($destino->hashtags(), 0, 15),    // corta as tags
```

A pessoa escreve um título de 120 caracteres, e nós **publicamos 100 sem avisar**. Ela só
descobre olhando o vídeo no ar.

**Isso é duas coisas ao mesmo tempo:** risco de reprovação na auditoria **e** exatamente a
meia-verdade que o produto existe para não contar.

**Correção:** **recusar e avisar antes**, nunca cortar. O laudo já faz isso com o vídeo — o
texto tem que seguir a mesma regra. Se passa do limite, a publicação não sai e a tela diz
quantos caracteres sobram.

---

## 🔴 2. Faltam dois links obrigatórios na tela

> *"Cliente **deve exibir link para os Termos de Serviço do YouTube**"* e declarar que
> *"usuários concordam em estar vinculados aos Termos de Serviço do YouTube"*.
> *"Deve **referenciar e vincular à Política de Privacidade do Google**"* e explicar
> *"que informações do usuário o cliente acessa, coleta, armazena e utiliza"*.

Hoje a tela de conexão explica o que pedimos e o que não pedimos (DEC-41) — mas **não tem os
dois links**, e eles são exigência literal.

**Correção:** na tela de conectar o YouTube, antes do botão:
- link para os Termos do YouTube, com a frase de vínculo
- link para a Política de Privacidade do Google
- o que guardamos: nome do canal, identificador e a autorização — e por quanto tempo

---

## 🔴 3. Retenção de 30 dias, e reconferência a cada 30 dias

> Dados do YouTube podem ficar guardados no máximo **30 dias** após a coleta, *"devendo ser
> deletados ou atualizados"*.
> *"A cada 30 dias, o cliente deve verificar se **ainda está autorizado** a acessar os dados
> do usuário."*

Guardamos `nome_exibicao` e `avatar_url` — que são dados do YouTube — e **nunca atualizamos nem
reconferimos**.

**Correção:** comando agendado que, a cada 30 dias, reconsulta `channels.list` de cada conta
ativa. Isso **atende as duas exigências de uma vez**: atualiza o dado e confirma a autorização.
E de brinde alimenta o semáforo (DEC-32) — se a autorização caiu, a pessoa fica sabendo antes
de tentar publicar.

---

## 🔴 4. Revogação: apagar em 7 dias

> *"Ao revogar, deve deletar todos os dados autorizados acessados ou armazenados em até
> **7 dias**."*
> Se a revogação for feita pela página de segurança do Google: **30 dias**.

Nosso "Desconectar" apaga a credencial e **preserva a conta** — de propósito, porque o histórico
de publicações aponta para ela.

⚠️ **Conflito real:** a política manda apagar; o histórico precisa da linha.

**Saída, e é a mesma do DEC-44:** apagar o **dado do YouTube** (nome do canal, avatar,
identificador do canal) e **preservar o evento** (que houve publicação, quando, e o link).
Dado pessoal se apaga; registro de entrega sobrevive anonimizado.

---

## 🟡 5. "Ação do YouTube" precisa ser identificável e iniciada pela pessoa

> *"Qualquer recurso que inicie uma ação do usuário relacionada ao YouTube deve ser claramente
> identificável como uma ação do YouTube e claramente iniciado pelo usuário."*
> *"O cliente deve informar qual canal está associado à solicitação."*

Nosso compositor manda para várias redes de uma vez. Precisa ficar claro **qual canal** recebe,
e que a ação é da pessoa.

**Correção:** no botão de publicar e na confirmação, nomear o canal — não só "YouTube".

---

## 🟡 6. Automação exige consentimento explícito

> Proibido *"automatizar ou disparar visualizações, uploads, comentários, curtidas"* **sem
> consentimento explícito**.

Vale para **upload**, não só para curtida. Nosso DEC-25 (aprovação explícita antes de publicar)
já cobre — **mas isso trava qualquer publicação automática futura**: cada envio precisa de um
"sim" da pessoa, não de uma regra ligada uma vez.

⚠️ **Anotar para o corte com IA:** publicar automaticamente o que a IA cortou, sem a pessoa ver
e aprovar, **é o caso proibido**.

---

## 🟡 7. Não recalcular métrica do YouTube

> Proibido *"substituir dados do YouTube com dados similares calculados independentemente"* e
> *"criar novas métricas derivadas"*. Se exibir métrica própria ao lado, *"deve incluir
> divulgação clara e destacada"*.

Nosso KPI de "posts confirmados no ar" é **contagem nossa de entregas**, não métrica do YouTube
— então está fora da proibição. Mas quando entrarem visualizações e curtidas, a regra pega:
mostrar o número do YouTube, sem recalcular, e separar visualmente o que é nosso.

---

## 🟡 8. Conferir o status "Made for Kids"

> *"Cliente deve verificar o status Made for Kids de cada vídeo que incorpora"* e desativar
> rastreamento conforme COPPA/GDPR.

Reforça o achado 10 da spec: `selfDeclaredMadeForKids` tem que ser **escolha explícita da
pessoa**, nunca um `false` fixo escondido no código.

---

## 📋 Prioridade

| # | Achado | Por quê |
|---|---|---|
| 1 | **Parar de cortar texto em silêncio** | viola política **e** a promessa do produto |
| 2 | Links de Termos e Privacidade na tela | exigência literal |
| 3 | Reconferência a cada 30 dias | exigência literal · alimenta o semáforo |
| 4 | Revogação apaga dado do YouTube em 7 dias | exigência literal |
| 5 | Nomear o canal na ação | exigência literal |
| 6 | Automação futura exige "sim" por publicação | trava o corte com IA automático |
| 8 | `madeForKids` escolha explícita | COPPA |
| 7 | Não recalcular métrica (quando houver) | futuro |

_2026-07-31 — leitura das Políticas do Desenvolvedor._
