# RELATÓRIO DE FECHAMENTO DO MVP

> Registro histórico desta etapa. O estado consolidado e as correções posteriores estão no [README](README.md#status-atual).

Data: 04/10/2026. Projeto: `torneio` (raiz do projeto).

**Resultado funcional automatizado: APROVADO. Fechamento para uso real: FALHOU, com pendência bloqueadora de envio de e-mail.**

Os critérios funcionais do torneio passaram nos testes. O ambiente atual usa `mail.default = log`: novas contas recebem a notificação no fluxo do Fortify, mas o transporte atual não entrega a mensagem na caixa de e-mail. Como o dashboard exige verificação, o MVP ainda não deve ser declarado encerrado para usuários reais. `.env` e `APP_KEY` foram preservados.

## 1. Funcionalidades finalizadas

- Criação de torneio vinculada à conta autenticada; formulário com erros e preservação dos campos, esporte selecionado, data, local e valor.
- Listagem pública ordenada por data, incluindo torneios de hoje e futuros, sem Pix nem controles de gerenciamento.
- Página pública com dados do torneio, login/cadastro e escolha apenas dos participantes da própria conta quando autenticado.
- Cadastro de participante com retorno ao torneio correto e seleção do participante recém-criado; a inscrição continua sendo uma ação explícita posterior.
- Inscrição com limite de quatro participantes por conta e torneio; inscrição existente encontrada antes do limite; unicidade de `tournament_id + athlete_id` preservada.
- Página **Minhas inscrições** com participante, torneio, esporte, status e link para os detalhes, limitada à conta atual.
- Gerenciamento por proprietário, carregando `registrations.athlete`, exibindo apenas inscritos, status, data, remoção e confirmação de pagamento.
- Remoção apenas da inscrição, preservando o participante cadastrado.
- Exclusão de torneio bloqueada enquanto houver inscrições; permitida depois de removida a última.
- Confirmação manual de pagamento com data de confirmação e sem reabrir instruções de pagamento após confirmar.
- Navegação com Criar torneio primeiro entre as ações, Torneios disponíveis, Meus torneios e Minhas inscrições no dashboard, menus de desktop/mobile e páginas do fluxo.
- Exclusão de conta com transação, análise dos vínculos e logout somente após a exclusão bem-sucedida.

Arquitetura `User → Athlete / Tournament / Registration` mantida. Não foram criados novos modelos de domínio, dependências ou migrations.

## 2. Bugs corrigidos e decisões

- **Inscrição gratuita:** novas inscrições em torneios com valor zero recebem automaticamente `payment_status = confirmed` e `payment_confirmed_at = now()`. As telas exibem **Inscrição gratuita**, sem Pix ou espera por pagamento. Inscrições gratuitas antigas ainda pendentes também são apresentadas como gratuitas, sem atualização em massa dos dados existentes.
- **Precisão do valor:** validação com `numeric`, `min:0`, `max:99999999.99` e `decimal:0,2`. Valores negativos, além do máximo, com mais de duas casas ou em notação científica são rejeitados. O formulário inclui `min`, `max` e `step` e mantém `old()`.
- **E-mail:** `User` agora implementa `MustVerifyEmail`, alinhado à funcionalidade e às rotas já existentes do Fortify. O middleware `verified` do dashboard agora efetivamente bloqueia contas não verificadas. Não foi criado um fluxo paralelo; as demais exigências de middleware foram preservadas.
- **Exclusão da conta:** a existência de inscrições próprias, de inscrições vinculadas aos seus participantes ou de inscritos em seus torneios bloqueia a operação com mensagem clara, sem logout. Sem esses vínculos, a transação exclui participantes sem inscrição, torneios vazios e depois a conta. Dados de outras contas são preservados. Falha inesperada de FK reverte a limpeza e mantém a autenticação.
- **Concorrência:** o bloqueio de conta já existente foi mantido. Inscrição e exclusão passam a bloquear também o torneio dentro de transação, com até três tentativas em conflitos de transação. A restrição única no banco permanece. Não houve mudança estrutural.
- **Pix em sessão:** `dontFlash(['pix_key'])` já estava corretamente configurado em `bootstrap/app.php`; foi preservado e seu teste passou. A criptografia e a ocultação na serialização do `Tournament` também foram mantidas.
- **Qualidade dos testes:** datas da listagem pública foram fixadas nos testes existentes para evitar falhas com o passar do tempo. Uma expectativa antiga foi alinhada ao texto legível de pagamento. Um teste vazio de 2FA foi marcado explicitamente como ignorado, sem exclusão de testes e sem simular aprovação.

## 3. Arquivos alterados ou criados

Caminhos relativos à raiz do projeto:

| Área | Arquivos |
| --- | --- |
| Controllers | `app/Http/Controllers/RegistrationController.php`, `app/Http/Controllers/TournamentController.php` |
| Models | `app/Models/Registration.php`, `app/Models/User.php` |
| Rotas | `routes/web.php` |
| Participantes | `resources/views/athletes/create.blade.php` |
| Dashboard e menus | `resources/views/dashboard.blade.php`, `resources/views/layouts/app/header.blade.php`, `resources/views/layouts/app/sidebar.blade.php` |
| Exclusão da conta | `resources/views/pages/settings/⚡delete-user-modal.blade.php` |
| Navegação compartilhada, novo | `resources/views/partials/tournament-navigation.blade.php` |
| Inscrições | `resources/views/registrations/show.blade.php`, `resources/views/registrations/index.blade.php` (novo) |
| Torneios | `resources/views/tournaments/create.blade.php`, `available.blade.php`, `index.blade.php`, `manage.blade.php`, `show.blade.php` |
| Isolamento dos testes | `phpunit.xml` |
| Testes | `tests/Feature/MvpClosureTest.php` (novo), `BusinessRoutesAuditTest.php`, `DashboardTest.php`, `ParticipantRegistrationFlowTest.php`, `RegistrationFlowTest.php`, `Settings/SecurityTest.php` |
| Relatório, novo | `RELATORIO_FECHAMENTO_MVP.md` |

Na etapa deste relatório, antes da primeira publicação no GitHub, o diretório ainda não continha repositório Git. A revisão usou uma cópia temporária do código anterior em `/tmp/torneio-mvp-before`.

## 4. Rotas criadas ou alteradas

| Método | Caminho | Nome | Alteração/proteção |
| --- | --- | --- | --- |
| GET | `/registrations` | `registrations.index` | Nova; exige login; somente inscrições da conta |
| PATCH | `/tournaments/{tournament}/registrations/{registration}/confirm-payment` | `tournaments.registrations.confirm-payment` | Nova; login, proprietário e vínculo com torneio; bloqueia confirmação própria |
| GET | `/tournaments/available` | `tournaments.available` | Mantida pública; filtra datas anteriores a hoje |
| POST | `/tournaments` | `tournaments.store` | Caminho mantido; validação de precisão do valor |
| POST | `/registrations` | `registrations.store` | Caminho mantido; gratuidade e reforço transacional |
| DELETE | `/tournaments/{tournament}` | `tournaments.destroy` | Caminho mantido; proteção de inscritos dentro de transação |

`GET /tournaments/{tournament}/manage` e `DELETE /tournaments/{tournament}/registrations/{registration}` permanecem com seus nomes e proteção por proprietário. A exclusão da conta continua no componente Livewire existente, sem nova rota.

## 5. Fluxo final do participante

1. Abrir Torneios disponíveis ou o link público recebido.
2. Criar conta ou entrar. O Fortify mantém o fluxo de verificação para acessar o dashboard; a entrega real de e-mail precisa ser resolvida conforme a pendência abaixo.
3. Escolher um participante da própria conta ou cadastrar outro.
4. Após cadastrar, voltar ao torneio correto com o participante selecionado e clicar em **Inscrever-se**.
5. Abrir Minha inscrição para conferir o status. O cadastro do participante, sozinho, não gera inscrição.
6. Encontrar novamente os detalhes por **Minhas inscrições**.

Até quatro participantes por conta e torneio. A quinta inscrição é bloqueada; repetir uma inscrição existente abre os detalhes sem criar duplicata, inclusive quando a conta já alcançou quatro.

## 6. Fluxo final do organizador

1. Criar torneio com data, esporte, local e valor.
2. Abrir Meus torneios e Gerenciar torneio.
3. Cadastrar a chave Pix fixa para torneios pagos e compartilhar o link público.
4. Consultar apenas os participantes inscritos e suas datas/status.
5. Confirmar os pagamentos recebidos ou remover inscrições.
6. Excluir o torneio somente depois de não haver inscrições.

A conta do organizador também pode cadastrar participantes. Conforme a proibição de confirmar o próprio pagamento, uma inscrição paga sob a mesma conta do proprietário não mostra o botão e sua confirmação pelo endpoint retorna 403. Não foi criado um administrador global ou mecanismo de exceção.

## 7. Fluxo final de pagamento manual

1. Torneio pago cria inscrição pendente.
2. Apenas a conta responsável pela inscrição acessa sua chave Pix e instruções de pagamento nos detalhes. Se a chave ainda não foi cadastrada, a tela informa essa situação.
3. O participante paga fora da aplicação; o organizador verifica o recebimento.
4. O proprietário confirma pela página de gerenciamento: status `confirmed`, data atual e mensagem de sucesso.
5. Minha inscrição passa a mostrar pagamento confirmado e oculta a chave/instruções. Repetir a confirmação conserva a data original.
6. Inscrição gratuita dispensa esse fluxo e é confirmada automaticamente.

## 8. Segurança validada

- Login exigido nas rotas privadas, inclusive nas duas rotas novas; visitantes são redirecionados ao login.
- Acesso administrativo limitado ao dono do torneio; outra conta recebe 404.
- Detalhes e listagem de inscrições limitados à conta responsável; tentativa de IDOR bloqueada.
- Inscrição aceita somente participante da conta autenticada; `user_id` e atributos de pagamento enviados pelo cliente não alteram a propriedade ou confirmação.
- Remoção e confirmação recusam inscrições pertencentes a outro torneio.
- Participante não pode confirmar o próprio pagamento, inclusive quando também é dono do torneio.
- CSRF exercitado nos endpoints de mutação, incluindo confirmação de pagamento; saída dos nomes escapada nas listagens e detalhes.
- Pix criptografado no banco, oculto em `toArray()`, ausente das páginas públicas e de Minhas inscrições, e excluído de `_old_input` na falha de validação.
- A inspeção do código da aplicação não encontrou chamadas de log que imprimam a chave. Nenhuma chave real foi exibida ou usada como fixture de teste.
- Relações, FKs e unicidade preservadas. Nenhuma migration destrutiva ou limpeza do banco real foi executada.
- Os hashes dos arquivos `.env*` permaneceram idênticos; `APP_KEY` não foi alterada.

## 9. Testes e verificações executados

Banco dos testes: **SQLite em memória**, com `APP_ENV`, `DB_CONNECTION`, `DB_DATABASE` e `DB_URL` forçados no `phpunit.xml` para impedir o uso acidental da conexão real. Mailer e sessão em memória nos testes. PostgreSQL real consultado somente para o estado das migrations.

| Verificação | Resultado |
| --- | --- |
| `php artisan test --compact --display-skipped --fail-on-risky` | **APROVADO**: 156 casos, 154 aprovados, 2 ignorados, 796 assertions, zero falhas e zero testes sem assertions |
| `tests/Feature/MvpClosureTest.php` | **APROVADO**: 28 casos novos, incluindo rollback por falha inesperada de FK |
| Login/cadastro, criação/validação de torneio e cadastro de participante | **APROVADO** |
| Inscrição, unicidade, quinta inscrição, repetição após o limite e isolamento por conta/torneio | **APROVADO** |
| Própria inscrição, IDOR, Minhas inscrições e gerenciamento somente de inscritos | **APROVADO** |
| Remoção preservando Athlete e exclusão bloqueada/liberada conforme inscritos | **APROVADO** |
| Pagamento manual, autorização, confirmação própria bloqueada e repetição da confirmação | **APROVADO** |
| Pix privado/criptografado, proteção de old input, gratuidade, CSRF e escape HTML | **APROVADO** |
| Dashboard de conta verificada/não verificada, envio de notificação e link assinado do Fortify | **APROVADO** em ambiente isolado |
| Exclusão de conta livre, bloqueio por vínculos, preservação de terceiros e rollback | **APROVADO** |
| `php artisan route:list -v --no-interaction` | **APROVADO**: 58 rotas, incluindo middleware das rotas privadas |
| `php -l` nos quatro Controllers e quatro Models próprios e no componente de exclusão | **APROVADO** |
| `php artisan view:cache --no-interaction` | **APROVADO** |
| `php artisan view:clear --no-interaction` | **APROVADO** |
| `php artisan migrate:status --no-interaction` | **APROVADO**: todas as dez migrations aplicadas no PostgreSQL local |
| `vendor/bin/phpstan analyse --no-progress --error-format=table --memory-limit=512M` | **APROVADO**: zero erros no nível 7 configurado |
| Laravel Pint nos arquivos PHP alterados | **APROVADO** |
| Teste simultâneo com vários processos no PostgreSQL | **NÃO TESTADO** |
| Entrega de e-mail na caixa de entrada real | **NÃO TESTADO**; ambiente usa `log` |
| Interação visual completa em navegador e dispositivos móveis | **NÃO TESTADO**; views compiladas e respostas HTML exercitadas pelos testes |

Observações de execução: PHPStan precisou de permissão para abrir seu socket local; a execução final solicitada passou. `pint --dirty --format agent` não funciona sem Git, então foi usado `pint --format agent` com a lista explícita dos arquivos PHP alterados. Os dois testes ignorados são de 2FA desabilitada, fora do MVP; um deles era um esqueleto vazio e deixou de ser contado como executado.

## 10. Resultado

- **APROVADO:** todos os critérios funcionais automatizados da definição de pronto, sintaxe, rotas, views, migrations e análise estática.
- **FALHOU:** encerramento para disponibilização a usuários reais enquanto o transporte de e-mail continuar em `log` e o dashboard exigir verificação.
- **NÃO TESTADO:** entrega real de e-mail, concorrência com múltiplos processos e interação visual em navegador/mobile.

## 11. Pendências restantes

**Bloqueadora para uso real:** configurar um transporte que entregue as mensagens do Fortify e validar cadastro → recebimento do e-mail → clique no link → dashboard. O diagnóstico confirmou `mail.default = log`. A configuração não foi alterada porque o pedido proíbe modificar `.env`; não há aprovação funcional de entrega real de e-mail.

Pendências sem expansão do escopo:

- Executar teste de concorrência em PostgreSQL isolado para comprovar o comportamento dos bloqueios com múltiplas requisições. SQLite em memória comprova o limite normal e a unicidade, mas não exercita os bloqueios de linha do PostgreSQL. O reforço pequeno já foi aplicado, sem novos serviços ou tabelas.
- Fazer uma conferência visual em navegador/mobile antes da apresentação pública. Não houve redesign, e os fluxos HTML e a compilação foram testados.

Não foi identificada outra falha bloqueadora de código nos critérios exercitados.

## 12. Itens propositalmente fora do MVP

Cartão, gateways, Pix dinâmico, QR Code, integração bancária, tabela payments, reservas, associados, rankings, chaves de campeonato, painel administrativo global, notificações complexas, 2FA, redesign completo, mudança de arquitetura e limpeza/migração em massa de dados reais.

## 13. Conclusão objetiva

**O código do MVP é funcional e passou nos testes essenciais. O MVP ainda não pode ser considerado encerrado para uso real**, pois novas contas precisam de verificação para acessar o dashboard e o ambiente atual apenas registra os e-mails em log. A liberação depende de resolver e testar essa entrega, preservando o fluxo já existente do Fortify. Não foi declarada uma validação real de e-mail nem de concorrência que não tenha sido executada.
