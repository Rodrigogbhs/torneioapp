# RELATÓRIO DE DESENVOLVIMENTO — SESSÃO DE HOJE

> Registro histórico desta etapa. O estado consolidado e as correções posteriores estão no [README](README.md#status-atual).

**Data:** 01/10/2026 — horário de Brasília.

**Projeto:** `torneio` (raiz do projeto).
**Escopo:** revisão funcional dos fluxos existentes, correções pontuais e continuidade do ajuste de participantes iniciado em 30/09, antes da virada da data.

**Resultado:** as 14 rotas de negócio foram exercitadas por testes HTTP automatizados. A execução final da suíte encontrou 122 casos e realizou 605 asserções, sem falhas. Há um teste antigo pulado e outro antigo vazio, marcado como `risky`; essas ressalvas estão detalhadas abaixo. PHPStan, sintaxe e compilação Blade passaram. O PostgreSQL foi consultado somente para leitura.

Legenda: **APROVADO** significa comportamento verificado no escopo indicado; **FALHOU** identifica uma falha observada, inclusive antes de sua correção; **NÃO TESTADO** identifica uma condição sem comprovação nesta sessão.

## 1. OBJETIVO DA SESSÃO

Revisar navegação, criação de torneios, cadastro de participantes, inscrições, gerenciamento, remoção, exclusão de torneios e Pix. Testar autenticação, propriedade dos registros, validação, limite de quatro participantes, duplicidade, CSRF e tentativas de falsificação de campos protegidos.

Preservar a separação entre `User` (conta), `Athlete` (participante), `Tournament` (torneio) e `Registration` (inscrição). As alterações mantêm a arquitetura existente e a unicidade por `tournament_id + athlete_id`.

## 2. ESTADO INICIAL

- **Participantes e `/manage`:** na investigação anterior havia um participante inscrito, apesar de existirem outros participantes cadastrados na conta. O gerenciamento corretamente consultava as inscrições do torneio. O problema do fluxo era não deixar claro que cadastrar um participante não o inscreve, além de selecionar por padrão um nome já inscrito. O usuário posteriormente concluiu outras inscrições e confirmou a lista com três nomes.
- **Remoção de inscrições:** o código já restringia a ação ao dono do torneio e à inscrição daquele torneio. Precisava comprovação de que removia somente `Registration`, preservava `Athlete` e permitia reinscrição.
- **Exclusão de torneio:** havia uma verificação de inscrições antes da exclusão. Precisava comprovação do fluxo completo: bloquear, remover inscrições e então permitir excluir.
- **Sessão no navegador:** o usuário relatou acesso sem a sessão esperada ao alternar `localhost` e `127.0.0.1`. Não foi tratado como motivo para enfraquecer autenticação.
- **Novas falhas:** URLs com IDs não numéricos causavam erro de servidor; a taxa aceitava valores acima da capacidade da coluna; erro de validação preservava `pix_key` na sessão; o limite de quatro não preservava a escolha do participante; uma inscrição legada sem atleta quebrava o gerenciamento; uma factory tinha método com retorno obrigatório e corpo vazio.
- **Concorrência:** a consulta da quantidade e a criação da inscrição eram operações separadas, sem bloqueio. Duas requisições poderiam observar três inscrições e criar mais duas.
- **Análise estática:** a primeira execução completa do PHPStan encontrou 18 diagnósticos, incluindo tipos de relacionamentos incompletos e o retorno ausente da factory.

Na abertura desta auditoria, depois das inscrições realizadas pelo usuário, a consulta somente leitura encontrou:

| Entidade | Antes da auditoria | Depois das correções |
| --- | ---: | ---: |
| User | 1 | 1 |
| Tournament | 1 | 1 |
| Athlete | 18 | 18 |
| Registration | 3 | 3 |

As inscrições de IDs 5, 6 e 7 mantiveram os mesmos vínculos com conta, atleta e torneio 4, e o mesmo status `pending`. A verificação não encontrou inscrições sem atleta, atletas inexistentes ou divergência entre a conta do atleta e a conta da inscrição. Essa comparação comprova as contagens e vínculos consultados; não é uma auditoria de cada coluna do banco.

## 3. ROTAS TESTADAS

`php artisan route:list -v --no-interaction` identificou **56 rotas**. A tabela apresenta as **14 rotas de negócio** e seu resultado final. Os testes exercitam respostas HTTP, conteúdo, redirecionamentos, sessão e persistência no SQLite em memória.

| Método | URL | Nome da rota | Resultado final | Correção necessária? |
| --- | --- | --- | --- | --- |
| GET | `/dashboard` | `dashboard` | **APROVADO:** login exigido; página 200 e links principais exercitados | Não |
| GET | `/tournaments/available` | `tournaments.available` | **APROVADO:** pública; estados vazio/preenchido; navegação sem Pix nem controles administrativos | Não |
| GET | `/tournaments` | `tournaments.index` | **APROVADO:** somente torneios da conta; links e exclusão disponíveis ao dono | Não |
| GET | `/tournaments/create` | `tournaments.create` | **APROVADO:** formulário, erros visíveis e campos antigos preservados | Não; o teste precisou propagar o cookie de sessão |
| POST | `/tournaments` | `tournaments.store` | **APROVADO:** criação gratuita/paga, validação e proprietário definido pela sessão | Sim: limite superior da taxa |
| GET | `/tournaments/{tournament}` | `tournaments.show` | **APROVADO:** pública; login para inscrição; apenas atletas da conta; escolha explícita | Sim: fluxo de seleção e IDs numéricos |
| GET | `/athletes/create` | `athletes.create` | **APROVADO:** exige login; contexto do torneio e formulário preservados | Sim: orientação do fluxo |
| POST | `/athletes` | `athletes.store` | **APROVADO:** valida nome/contexto; ignora proprietário enviado; não cria inscrição automaticamente | Sim: retorno com o novo participante selecionado |
| POST | `/registrations` | `registrations.store` | **APROVADO:** valida IDs/propriedade; duplicidade idempotente; limite por conta e torneio | Sim: transação, bloqueio da conta e preservação da seleção |
| GET | `/registrations/{registration}` | `registrations.show` | **APROVADO:** somente inscrição da conta; dados do torneio/atleta/status; Pix condicionado | Sim: IDs numéricos |
| GET | `/tournaments/{tournament}/manage` | `tournaments.manage` | **APROVADO:** somente dono; lista inscrições; suporta registro legado sem atleta | Sim: orientação, nome alternativo e IDs numéricos |
| DELETE | `/tournaments/{tournament}/registrations/{registration}` | `tournaments.registrations.destroy` | **APROVADO:** escopo de dono/torneio; preserva atleta; redireciona com mensagem | Sim: IDs numéricos e tipo de retorno |
| DELETE | `/tournaments/{tournament}` | `tournaments.destroy` | **APROVADO:** bloqueia com inscrições; permite após remoção; recusa outro dono | Sim: IDs numéricos |
| PATCH | `/tournaments/{tournament}/pix-key` | `tournaments.pix-key.update` | **APROVADO:** somente dono; validação, criptografia e ausência de chave em old input | Sim: exclusão de `pix_key` do flash de validação e IDs numéricos |

Os GETs também têm HEAD no inventário; HEAD não foi testado separadamente. A tabela não aprova os 56 endpoints indiscriminadamente. As demais 42 rotas pertencem à página inicial, autenticação, configurações, Livewire/Flux, armazenamento, saúde e Laravel Boost. A suíte existente cobre partes de autenticação e configurações; assets, upload/storage, saúde, logs do Boost e a totalidade dessas rotas auxiliares **NÃO foram auditados individualmente**.

## 4. BUGS ENCONTRADOS

### 4.1. Cadastro confundido com inscrição e seleção de participante inadequada

**Descrição e causa:** o cadastro criava somente `Athlete`, como previsto, mas o fluxo não orientava suficientemente a etapa de inscrição. O seletor podia assumir o primeiro atleta, já inscrito; o envio então redirecionava para a inscrição existente.

**Impacto:** outros nomes cadastrados não apareciam em `/manage`, causando a impressão de que a listagem estava quebrada.

**Correção:** orientação nas telas de cadastro, torneio e gerenciamento; retorno com `selected_athlete_id`; opção inicial vazia quando não existe seleção anterior; uso de `old()` para preservar escolha após erro. O gerenciamento continua baseado em `Registration`.

**Resultado:** **APROVADO** nos testes de fluxo e confirmado pelo usuário na captura enviada antes desta auditoria.

### 4.2. IDs não numéricos causavam erro de servidor

**Causa:** as rotas aceitavam qualquer texto, enquanto os parâmetros dos controllers eram `int`.

**Impacto:** uma URL inválida podia produzir `TypeError`/500.

**Correção:** `whereNumber()` nas rotas com IDs, inclusive nos dois parâmetros da remoção de inscrição.

**Resultado:** **FALHOU antes; APROVADO depois**. Sete combinações de parâmetros inválidos agora retornam 404, sem alterar os registros de teste.

### 4.3. Taxa maior que a capacidade da coluna

**Causa:** a validação tinha `numeric` e `min:0`, mas não o teto da coluna `decimal(10,2)`.

**Impacto:** a requisição aceitava um valor incompatível com o esquema. A aceitação foi reproduzida no SQLite; uma escrita desse valor no PostgreSQL real não foi executada. O risco de rejeição pelo PostgreSQL decorre da definição da coluna.

**Correção:** `max:99999999.99`, preservando valores gratuitos e positivos válidos.

**Resultado:** **FALHOU antes; APROVADO depois** para a rejeição do valor acima do teto.

### 4.4. Pix ficava em `_old_input` após erro

**Causa:** o tratamento de validação preservava o campo junto com os demais dados enviados.

**Impacto:** retenção desnecessária de um dado sensível na sessão, embora o campo do formulário não o exibisse.

**Correção:** `$exceptions->dontFlash(['pix_key'])` em `bootstrap/app.php`, mantendo as exclusões padrão do framework.

**Resultado:** **FALHOU antes; APROVADO depois**. O teste verifica erros de validação, ausência de `_old_input.pix_key`, preservação de um campo comum e ausência de alteração da chave armazenada.

### 4.5. Quinta inscrição perdia a seleção enviada

**Causa:** a recusa pelo limite retornava com uma mensagem, sem preservar os IDs do formulário.

**Impacto:** perda da escolha do participante na volta à página do torneio.

**Correção:** preservar somente `tournament_id` e `athlete_id` nesse retorno.

**Resultado:** **FALHOU antes; APROVADO depois**. A quinta inscrição é bloqueada e a seleção fica na sessão.

### 4.6. Inscrição legada sem atleta quebrava `/manage`

**Causa:** `athlete_id` é nullable no esquema, mas a view acessava diretamente `athlete->name`.

**Impacto:** um registro legado nessa condição podia impedir a abertura do gerenciamento.

**Correção:** acesso com `?->` e texto “Participante não informado”, compatível com a tela da inscrição.

**Resultado:** **FALHOU antes; APROVADO depois** em um registro sintético. Não há registros nessa condição no PostgreSQL consultado. A remoção desse registro também foi testada.

### 4.7. Risco de ultrapassar quatro inscrições em requisições simultâneas

**Causa:** contagem e inserção separadas permitem que duas requisições leiam a mesma quantidade antes de gravar.

**Correção:** executar busca de duplicidade, contagem e criação numa transação, bloqueando a linha da conta com `lockForUpdate()`. A linha da conta existe mesmo quando ainda não há inscrições; pedidos dessa conta aguardam o mesmo bloqueio. A unicidade por torneio e atleta permanece intacta.

**Resultado:** regra sequencial, duplicidade e independência entre contas/torneios **APROVADAS**. A proteção segue o mecanismo de bloqueio documentado; a corrida anterior foi identificada por análise do código, não por teste paralelo no banco real. Um teste simultâneo em PostgreSQL isolado permanece **NÃO TESTADO**. SQLite em memória não comprova o comportamento de `FOR UPDATE`. Referências: [Laravel — bloqueio pessimista](https://laravel.com/framework/docs/13.x/queries#pessimistic-locking) e [PostgreSQL — bloqueios explícitos](https://www.postgresql.org/docs/current/explicit-locking.html).

### 4.8. Factory de usuário com retorno inválido

**Causa:** `withTwoFactor(): static` tinha corpo vazio.

**Impacto:** `TypeError` ao utilizar esse estado da factory; também falhava no PHPStan.

**Correção:** retornar um estado de factory com segredo e códigos fictícios gerados e criptografados pelas APIs existentes do Fortify.

**Resultado:** **FALHOU antes; APROVADO depois**. O teste cria o objeto e verifica os oito códigos de recuperação. Isso não habilita autenticação de dois fatores no produto nem comprova seu fluxo completo.

### 4.9. Problemas de tipagem na análise estática

**Causa:** relacionamentos não declaravam os modelos genéricos; uma consulta recebia um valor validado ainda inferido como `mixed`; `destroy()` não declarava o retorno.

**Correção:** PHPDoc dos relacionamentos, leitura inteira do ID já validado e retorno `RedirectResponse`. Nenhum relacionamento foi removido.

**Resultado:** **APROVADO**. PHPStan passou de 18 diagnósticos para zero. Esses ajustes de tipagem não representam, por si só, 18 bugs de execução.

**Investigação sem alteração do produto:** o primeiro teste de erro visível não mantinha o cookie entre POST e GET. Com a serialização JSON da sessão, isso não reproduzia corretamente a navegação do navegador. O teste foi corrigido para enviar o cookie e passou, incluindo a mensagem visível e os valores dos campos. A view de criação de torneio não precisou mudar.

## 5. ARQUIVOS ALTERADOS

Os links apontam para os caminhos completos no projeto. A lista reúne os ajustes anteriores de participantes nesta mesma sessão e as correções da auditoria.

| Caminho | Alteração e motivo |
| --- | --- |
| [app/Http/Controllers/AthleteController.php](app/Http/Controllers/AthleteController.php) | Preserva o contexto do torneio e retorna com o novo atleta selecionado; orienta a inscrição explícita |
| [app/Http/Controllers/RegistrationController.php](app/Http/Controllers/RegistrationController.php) | Transação/bloqueio da conta, preservação restrita dos IDs no limite, leitura inteira do atleta e retorno tipado |
| [app/Http/Controllers/TournamentController.php](app/Http/Controllers/TournamentController.php) | Limita a taxa à capacidade da coluna existente |
| [app/Models/User.php](app/Models/User.php) | Documenta os três relacionamentos `HasMany` e organiza imports |
| [app/Models/Athlete.php](app/Models/Athlete.php) | Documenta o modelo relacionado em `BelongsTo` |
| [app/Models/Tournament.php](app/Models/Tournament.php) | Documenta `BelongsTo` e `HasMany`, mantendo criptografia e ocultação do Pix |
| [app/Models/Registration.php](app/Models/Registration.php) | Documenta os três `BelongsTo`, sem ampliar os campos permitidos |
| [routes/web.php](routes/web.php) | Restringe parâmetros de identificação a números |
| [bootstrap/app.php](bootstrap/app.php) | Exclui `pix_key` do flash causado por falha de validação |
| [database/factories/UserFactory.php](database/factories/UserFactory.php) | Restaura o retorno do estado de dois fatores para testes |
| [resources/views/athletes/create.blade.php](resources/views/athletes/create.blade.php) | Explica cadastro versus inscrição e apresenta retorno contextual |
| [resources/views/tournaments/show.blade.php](resources/views/tournaments/show.blade.php) | Explica o fluxo; preserva seleção; exige escolha quando não há seleção prévia |
| [resources/views/tournaments/manage.blade.php](resources/views/tournaments/manage.blade.php) | Explica a lista, oferece link para inscrever outro participante e trata ausência de atleta |
| [tests/Feature/ParticipantRegistrationFlowTest.php](tests/Feature/ParticipantRegistrationFlowTest.php) | 15 cenários para cadastro, seleção, listagem, remoção, reinscrição, autorização e exclusão; inclui a sequência A/B solicitada |
| [tests/Feature/BusinessRoutesAuditTest.php](tests/Feature/BusinessRoutesAuditTest.php) | 67 cenários da auditoria de rotas, validação, segurança, Pix, unicidade e factory |
| [RELATORIO_DESENVOLVIMENTO_2026-10-01.md](RELATORIO_DESENVOLVIMENTO_2026-10-01.md) | Este relatório solicitado |

As migrations foram inspecionadas e verificadas quanto à sintaxe; não foram alteradas. `.env`, APP_KEY, credenciais, configuração do Fortify e chaves reais não foram alterados.

## 6. FLUXO DE PARTICIPANTES

**APROVADO** em testes HTTP e persistência isolada:

1. Cadastrar cria um `Athlete` associado à conta autenticada.
2. Ao cadastrar a partir de um torneio, a aplicação retorna para esse torneio com o novo nome selecionado.
3. O usuário clica em “Inscrever-se”; o formulário envia `tournament_id` e `athlete_id`.
4. A aplicação cria `Registration`, com `user_id` da sessão e status inicial `pending`.
5. O gerenciamento passa a mostrar o participante porque existe a inscrição nesse torneio.
6. O dono remove a inscrição; o participante desaparece da lista do torneio.
7. `Athlete` permanece cadastrado e disponível para reinscrição.

O cenário A/B foi verificado explicitamente: A inscrito aparece; B recém-cadastrado não aparece; após inscrever B, ambos aparecem. Um terceiro atleta sem inscrição continua fora da lista. Também passaram a rejeição de atleta de outra conta, IDs ausentes/inválidos, duplicidade, quatro inscrições válidas, quinta bloqueada e limite independente por conta e torneio.

A duplicidade foi testada tanto pela rota, que retorna a inscrição existente, quanto pela restrição do banco de teste, que rejeita uma inserção duplicada de `tournament_id + athlete_id`. O índice correspondente do PostgreSQL foi inspecionado somente para leitura.

## 7. FLUXO DE EXCLUSÃO DO TORNEIO

**APROVADO** no banco isolado:

1. Torneio com inscrição: excluir é recusado com mensagem; o torneio permanece.
2. Remover a inscrição: somente `Registration` é removida.
3. Torneio sem inscrições: excluir é permitido e retorna para “Meus torneios”.
4. O atleta continua existente após a exclusão do torneio.

Outro usuário não consegue excluir o torneio. Uma inscrição de outro torneio não pode ser removida pela troca de IDs na URL. Exclusão e remoção reais no PostgreSQL **NÃO foram executadas como teste**, para preservar os dados. Operações de exclusão concorrentes com novas inscrições não foram submetidas a teste de carga.

## 8. SEGURANÇA VALIDADA

| Verificação | Resultado e evidência |
| --- | --- |
| Autenticação | **APROVADO:** os 12 endpoints protegidos redirecionam convidados ao login; as duas páginas públicas abrem |
| Propriedade do torneio | **APROVADO:** outra conta recebe 404 ao gerenciar, alterar Pix, remover inscrição ou excluir |
| Propriedade do Athlete | **APROVADO:** seletor contém apenas atletas da conta; atleta de outra conta é recusado na inscrição |
| Propriedade da Registration / IDOR | **APROVADO:** trocar o ID não revela inscrição de outra conta; remoção precisa pertencer ao torneio autorizado |
| `user_id` falsificado | **APROVADO:** cadastro de torneio, atleta e inscrição usa a conta autenticada |
| Mass assignment / pagamento | **APROVADO:** `payment_status` e `payment_confirmed_at` enviados não alteram o estado inicial; atualização Pix ignora campos administrativos adicionais |
| CSRF nos formulários | **APROVADO:** tokens presentes em todos os formulários de negócio renderizados |
| Rejeição CSRF | **APROVADO:** seis mutações autenticadas sem token, com origem cruzada, retornam 419 e preservam os registros de teste; token válido é aceito |
| Escape de conteúdo | **APROVADO:** nomes com HTML são escapados nas listagens e páginas de detalhes/gerenciamento |

Para testar a rejeição CSRF, o middleware real foi ativado somente dentro do teste, retirando a dispensa automática do ambiente de testes. A proteção do aplicativo não foi desativada. Laravel 13 também considera a origem da requisição; o cenário de origem cruzada exercita a exigência do token. [Referência oficial de CSRF](https://laravel.com/framework/docs/13.x/csrf).

**Hosts e sessão:** cookies podem ser restritos ao host que os criou. Assim, autenticar em `127.0.0.1` não garante sessão autenticada em `localhost`, mesmo apontando para a mesma máquina. Isso é consistente com o comportamento relatado pelo usuário; não foi reproduzida uma nova troca de hosts no navegador nesta auditoria. [Referência MDN sobre domínio dos cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie#domain).

A execução final automatizada usa `APP_URL=http://127.0.0.1:8000` somente no processo de teste. A configuração permanente foi preservada: no console, sem essa substituição temporária, o gerador de URLs ainda usa `localhost`. Para continuar o uso manual, mantenha o mesmo host desde o login: `/tournaments/{tournament}/manage` no mesmo host usado no login. Nenhuma proteção de sessão ou autenticação foi removida para contornar o problema.

## 9. PIX

Somente valores fictícios foram utilizados no banco isolado; nenhum valor de chave real foi incluído nas consultas ou neste relatório.

- **APROVADO:** convidados precisam entrar e somente o dono pode cadastrar/alterar.
- **APROVADO:** o valor bruto armazenado difere do valor enviado; o cast `encrypted` recupera o valor de teste corretamente.
- **APROVADO:** `toArray()` não expõe `pix_key`.
- **APROVADO:** chave ausente em listagens, página pública e campo do gerenciamento.
- **APROVADO:** somente o responsável pela inscrição acessa seus detalhes; a chave aparece enquanto o status é `pending` e desaparece com `confirmed`.
- **APROVADO:** inscrição pendente sem chave apresenta a mensagem adequada.
- **APROVADO:** entradas inválidas não substituem a chave armazenada; falha de validação não preserva a chave em `_old_input`.
- **NÃO TESTADO:** recebimento de pagamento real e confirmação por um fluxo externo. O status confirmado foi simulado exclusivamente na fixture de teste.

O comportamento continua sendo chave fixa por torneio. Não foram adicionados serviços de pagamento.

## 10. TESTES EXECUTADOS

Os comandos abaixo foram executados a partir da raiz do projeto. As variáveis de banco impedem o uso do PostgreSQL nos testes; o `RefreshDatabase` existente reconstrói o SQLite efêmero para preparar os cenários.

| Comando/verificação | Resultado |
| --- | --- |
| `php artisan route:list -v --no-interaction` | **APROVADO:** 56 rotas identificadas; inventário conferido novamente após os ajustes |
| `php artisan route:list --json --no-interaction` | **APROVADO:** inventário estruturado para conferência |
| `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan test --compact` — baseline | Sem falhas na suíte existente: 55 casos, 213 asserções; resumo com 54 `passed`, 1 `skipped`, 1 `risky`. Não cobria os problemas novos |
| Primeira execução de `BusinessRoutesAuditTest.php` antes das correções | **FALHOU:** 11 falhas e 1 erro em 66 cenários. Uma falha era do cookie no próprio teste; as demais revelaram os problemas descritos |
| `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= APP_URL=http://127.0.0.1:8000 php artisan test --compact tests/Feature/BusinessRoutesAuditTest.php tests/Feature/ParticipantRegistrationFlowTest.php tests/Feature/RegistrationFlowTest.php tests/Feature/Http/Controllers/TournamentControllerTest.php` | **APROVADO:** execução intermediária com 90 casos e 498 asserções, antes do último reforço dos testes |
| `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= APP_URL=http://127.0.0.1:8000 php artisan test --compact tests/Feature/BusinessRoutesAuditTest.php tests/Feature/ParticipantRegistrationFlowTest.php` | **APROVADO:** versão final dos dois arquivos, 82 casos e 483 asserções |
| `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= APP_URL=http://127.0.0.1:8000 php artisan test --compact` — final | Sem falhas: **122 casos e 605 asserções**. Resumo do executor: `passed=121`, `skipped=1`, `risky=1`; o caso vazio também entra em `passed`, portanto essa contagem não significa 121 testes com verificação efetiva |
| `vendor/bin/phpstan analyse --no-progress --error-format=table --memory-limit=512M` | **FALHOU inicialmente:** 18 diagnósticos. **APROVADO após correções:** zero erros, nível 7 configurado, sem mudar a configuração |
| `php artisan view:cache --no-interaction` | **APROVADO:** compilação Blade |
| `php artisan view:clear --no-interaction` | **APROVADO:** limpeza posterior do cache compilado |
| `vendor/bin/pint --dirty --format agent` | **FALHOU como comando:** a cópia atual não é reconhecida como repositório Git; `--dirty` indisponível |
| Pint com lista explícita dos arquivos PHP alterados | **APROVADO:** formatação aplicada; último passe dos arquivos de teste sem ajustes pendentes |
| `php -l` | **APROVADO:** 22 arquivos; inclui os três controllers, quatro models, rotas, bootstrap, factory, dois arquivos de teste e as dez migrations próprias |
| `DatabaseQuery` do Laravel Boost, via bootstrap PHP | **APROVADO:** SELECTs de contagens, vínculos, consistência e esquema; PostgreSQL sem escrita para testes |

Comando real de formatação utilizado:

```sh
vendor/bin/pint --format agent app/Http/Controllers/AthleteController.php app/Http/Controllers/RegistrationController.php app/Http/Controllers/TournamentController.php app/Models/User.php app/Models/Athlete.php app/Models/Registration.php app/Models/Tournament.php bootstrap/app.php routes/web.php database/factories/UserFactory.php tests/Feature/ParticipantRegistrationFlowTest.php tests/Feature/BusinessRoutesAuditTest.php
```

Comando real de sintaxe utilizado; os dois arquivos de teste também foram conferidos após o último ajuste:

```sh
for task_php_file in app/Http/Controllers/TournamentController.php app/Http/Controllers/RegistrationController.php app/Http/Controllers/AthleteController.php app/Models/User.php app/Models/Athlete.php app/Models/Tournament.php app/Models/Registration.php routes/web.php bootstrap/app.php database/factories/UserFactory.php tests/Feature/ParticipantRegistrationFlowTest.php tests/Feature/BusinessRoutesAuditTest.php database/migrations/*.php; do php -l "$task_php_file" || exit 1; done
```

Também houve uma tentativa de passar `--no-interaction` ao comando de testes; essa opção não é aceita pelo executor utilizado. Os testes foram executados novamente sem ela. Consultas à documentação pelo Boost falharam por resolução de rede; foram usadas a implementação instalada e as referências oficiais citadas neste relatório.

Não houve migração, rollback, exclusão ou criação de fixtures no PostgreSQL. As mudanças em dados e status feitas pelos testes ocorreram apenas no SQLite em memória.

## 11. PROBLEMAS QUE PERMANECEM

1. **NÃO TESTADO — concorrência real:** o bloqueio foi implementado e os testes funcionais passaram, mas falta comprovação com processos/requisições simultâneos num PostgreSQL separado. Isso também limita qualquer conclusão sobre corridas envolvendo exclusão de torneios.
2. **NÃO TESTADO — desafio de dois fatores:** o teste de login com esse recurso foi pulado porque o Fortify não o habilita na configuração atual. Corrigir a factory não habilita nem valida o recurso inteiro.
3. **FALHOU como evidência de cobertura — teste antigo vazio:** `two factor authentication disabled when confirmation abandoned between requests`, em [SecurityTest.php](tests/Feature/Settings/SecurityTest.php), não tem asserções e permanece `risky`. Não foi preenchido com uma verificação artificial de um fluxo fora desta auditoria.
4. **NÃO TESTADO integralmente — rotas auxiliares e navegador:** não houve auditoria individual de todos os assets, uploads, armazenamento e endpoints de infraestrutura. Os fluxos destrutivos foram testados por HTTP isolado, sem repeti-los no banco real pelo navegador.
5. **Comportamento de ambiente documentado:** console ainda gera a base `localhost`; usar `127.0.0.1` desde o login mantém a consistência do uso manual. Uma alteração permanente de `.env` não foi feita.

Não restaram falhas de asserção nos cenários de negócio executados. Os limites de cobertura acima impedem uma afirmação de que todo o sistema foi comprovado em todas as condições.

## 12. RESUMO DO QUE EVOLUÍMOS HOJE

Ficou claro quando uma pessoa está apenas cadastrada e quando está inscrita num torneio. O novo participante volta selecionado, e a lista do organizador mostra somente quem realmente se inscreveu. Remover uma inscrição mantém a pessoa cadastrada; o torneio só pode ser excluído depois que não tem inscrições.

Corrigimos erros de URLs inválidas, valores incompatíveis de taxa, retenção do Pix na sessão e gerenciamento de inscrições antigas sem atleta. O limite de quatro recebeu proteção por transação e bloqueio. Os testes comprovaram os controles de acesso e a rejeição de campos falsificados, e a análise estática ficou sem erros.

Os dados existentes foram preservados durante a auditoria: continuam uma conta, um torneio, 18 participantes e três inscrições. As pendências de testes antigos e de concorrência foram registradas explicitamente.

## 13. PRÓXIMO PASSO RECOMENDADO

**Validar o limite de quatro com requisições simultâneas em um PostgreSQL isolado.** Preparar uma conta com três inscrições nesse ambiente separado, disparar tentativas concorrentes e comprovar que apenas uma nova inscrição entra. Esse teste deve usar credenciais e banco próprios de teste, preservando o PostgreSQL atual.
