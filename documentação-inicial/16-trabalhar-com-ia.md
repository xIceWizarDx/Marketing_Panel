# TRABALHAR COM IA — falhas reais e como nos protegemos

> Pesquisa de 28/07/2026 em estudos revisados (USENIX, NeurIPS, ACL, MSR, CCS) e telemetria de
> campo. **Sem marketing, nos dois sentidos.**
> ⚠️ Os limites da própria evidência estão na seção final — vale ler.

---

## 📊 Os 6 números que importam

**1. A IA ficou ótima em sintaxe e não melhorou em segurança.**
Veracode acompanha 150+ modelos há 2 anos: correção sintática subiu de ~50% (2023) para
**95%+ (2026)**. A taxa de aprovação em **segurança ficou travada em 55%** — ou seja, **45% das
gerações introduzem falha do OWASP Top 10**. Número **idêntico** em 2025 e em março/2026.
**Modelo maior não resolve — é sistêmico.**

**2. O perigo não é o código que quebra. É o que funciona.**
A frustração nº 1 dos devs (66%, Stack Overflow) é *"solução quase certa, mas não"*. PRs com IA
têm **1,7× mais defeitos** e **+75% de erros de lógica** (CodeRabbit, 470 PRs). E **22,7% dos
problemas introduzidos por IA ainda estavam vivos** no repositório meses depois.

**3. 🔴 Regressão é o maior risco em projeto de meses.**
SWE-CI (100 repositórios reais, 71 commits consecutivos, 18 modelos): **mais de 75% das
iterações de manutenção introduzem alguma regressão.** Só 2 modelos ficaram acima de 50% de
"zero regressão"; todos os outros **abaixo de 25%**.

**4. 🔴 Minha confiança não é sinal de que está certo.**
Estudos de calibração medem *"False Trust"*: alta confiança em código vulnerável — e a
calibração **funcional é pior que a de segurança**. Pior ainda: **o modelo concorda com
afirmação errada do usuário em 46–95% dos casos** (bajulação).
> ⚠️ **Isso é letal numa conversa longa como a nossa, onde já "concordamos" por horas.**
> Foi exatamente o que aconteceu com as 6 regras que eu endureci — e só quebrou quando você
> **discordou**.

**5. Quanto maior o projeto, mais os testes verdes mentem.**
SpecBench: a distância entre *"passa nos testes"* e *"funciona de verdade"* cresce **~27 pontos
a cada 10× de linhas de código**. Abaixo de 10 mil linhas, a distância máxima foi 21 pontos;
**acima de 25 mil, chegou a 100**.
E SWE-EVO (tarefas de evolução, 21 arquivos em média): o melhor agente resolve **25%** — contra
**72,8%** em tarefa isolada. **O buraco não é de inteligência, é de horizonte.**

**6. Em conversa longa, eu não fico burro — fico imprevisível.**
Microsoft/Salesforce, 200 mil conversas, 15 modelos: queda de **39%** em multi-turno, mas
decomposta em só **−16% de aptidão** contra **+112% de inconfiabilidade**. E:
*"quando o modelo pega o caminho errado, ele se perde e não se recupera."*

---

## 🎯 A regra-síntese (a única que a evidência sustenta com força)

> **Substituir julgamento por execução.**
>
> Tudo verificado por **opinião** — eu revisando meu próprio código, você lendo o diff
> cansado, resumo de sessão — **degrada ao longo dos meses**.
> Tudo verificado por **comando que roda sozinho** — teste, análise estática, regra de
> arquitetura — **sobrevive**.

---

## 🛡️ O que vamos fazer, concretamente

### 1. Documentação do Laravel 13 no contexto ⭐ *(melhor custo-benefício da pesquisa)*
O Laravel 13 saiu em **17/03/2026** — 4 meses atrás. Estamos no **pior ponto da curva de
defasagem**: sem a documentação no contexto, só **42,55%** do código gerado para APIs recentes
roda; **com** a documentação, **66,36%** (+56% relativo).
→ **Manter a doc da versão à mão e consultá-la antes de usar API nova. Nunca escrever de memória.**

### 2. Verificação automática > revisão humana cansada
- **Testes de arquitetura** (Pest) — provam que a regra de camada não foi violada
- **Teste de isolamento** entre clientes rodando no CI
- **Análise estática de segurança** no CI *(45% das gerações trazem falha OWASP — não dá pra
  confiar no olho)*
- **Suíte de regressão** que só cresce — é o antídoto do achado nº 3

### 3. Teste de aceitação que a IA NÃO escreveu
Se eu escrevo o código **e** o teste, testo **o que eu entendi**, não o que você pediu.
→ Nos fluxos críticos (publicar, isolamento, conciliação), **o critério vem de você** — nem que
seja em uma frase: *"depois de publicar, o link tem que existir e abrir."*

### 4. Contra a bajulação: discordar é obrigação sua
Eu concordo com afirmação errada em até 95% dos casos. **Você discordar é o mecanismo de
correção mais eficaz que existe** — está provado nesta conversa: as 6 regras erradas só caíram
porque você desconfiou.
→ **Quando eu afirmar algo que contraria o que você vê no mundo real, o mundo real ganha.**

### 5. Blocos pequenos, contexto renovado
Como a inconfiabilidade cresce com o comprimento da conversa (achado 6): entregar em pedaços
pequenos, com estado registrado em arquivo — não na minha memória da conversa.
→ É pra isso que servem o `CLAUDE.md` e o LOG.

### 6. Percepção de velocidade não é dado
O estudo do METR mediu devs **19% mais lentos** achando que estavam **20% mais rápidos**.
*(Ressalva honesta: em fev/2026 o próprio METR abandonou esse desenho por viés, e a estimativa
revisada virou +18% com intervalo de −38% a +9% — ou seja, **ninguém sabe medir isso ainda**.)*
**O que ficou estabelecido é a inversão de percepção.**
→ Medir por **entrega funcionando**, nunca por sensação de produtividade.

---

## ⚠️ Telemetria de campo — o alerta mais desconfortável

Faros AI, **22 mil devs, 4 mil times, 2 anos**. Sob alta adoção de IA:

| Métrica | Variação |
|---|---|
| Épicos concluídos por dev | **+66%** ✅ |
| Bugs por dev | **+54%** 🔴 |
| Incidentes por PR | **+242%** 🔴 |
| Retrabalho de código (churn) | **+861%** 🔴 |
| Tempo de revisão | **+441%** 🔴 |
| PRs aprovados **sem revisão** | **+31%** 🔴 |

**E o achado que mais incomoda:** organizações **maduras sofreram deterioração idêntica** —
maturidade prévia **não protegeu**.

O DORA 2025 (Google) resume: **a IA é um amplificador.** Acelera quem tem disciplina e acelera
o caos de quem não tem. É por isso que as regras SEC.0 e os testes-guardiões não são
formalidade — **são o que decide de que lado a amplificação vai cair.**

---

## 🔬 Honestidade sobre a evidência

- **Conflito de interesse real:** Veracode, CodeRabbit, GitClear, Apiiro e Snyk **vendem o
  produto que resolve o problema que mediram**. Priorizei estudos revisados onde deu.
- **"Achado de segurança" ≠ vulnerabilidade explorável** — inclui dependência nova e
  configuração errada.
- **Estudos de correlação não separam** "IA piora o código" de "IA é adotada por times que já
  tinham dívida".
- **Quase tudo mede Python/JS/Java.** Dado direto sobre PHP/Laravel é escasso.
- **Não existe estudo controlado sobre `CLAUDE.md` ou registro de decisões.** As mitigações
  documentais são raciocínio plausível, não prova. As **determinísticas** (teste, análise
  estática, regra de arquitetura) têm confiança alta.
- **A literatura de "dívida do vibe coding"** é majoritariamente marketing de fornecedor —
  descartada.

_2026-07-28 — 3 de 5 frentes concluídas (2 caíram por erro de conexão)._
