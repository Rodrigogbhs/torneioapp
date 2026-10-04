# Guia de desenvolvimento — Torneio

**Mais jogo. Menos complicação.**

Sistema de gerenciamento de torneios esportivos: organize encontros, cadastre participantes, receba inscrições e acompanhe pagamentos Pix com confirmação manual. A interface é responsiva e oferece temas claro e escuro.

O MVP funcional está coberto por testes. Para usar com pessoas reais, configure a entrega de e-mails: o dashboard exige verificação da conta e o transporte padrão de desenvolvimento apenas registra mensagens em log.

## Recursos

- Cadastro, login, recuperação de senha e verificação de e-mail.
- Dashboard com acesso a criação de torneios, eventos disponíveis e inscrições.
- Torneios com nome, esporte, data, local e valor de inscrição.
- Listagem pública dos torneios de hoje e futuros, ordenados por data.
- Página pública para compartilhar e receber inscrições.
- Cadastro de participantes vinculados à própria conta.
- Até quatro participantes por conta em cada torneio, com proteção contra duplicidade.
- Minhas inscrições com participantes, torneios e status de pagamento.
- Gestão de inscritos, remoção de inscrições e confirmação manual de pagamentos pelo organizador.
- Chave Pix criptografada no banco e exibida nos detalhes autorizados de pagamento pendente.
- Inscrições gratuitas confirmadas automaticamente.
- Perfil, senha e preferências de aparência; exclusão de conta protegida por seus vínculos.
- Tema claro, escuro ou automático conforme o sistema, com preferência persistente.

## Tecnologias

| Área | Tecnologias |
| --- | --- |
| Backend | PHP, Laravel 13 e Eloquent |
| Autenticação | Laravel Fortify |
| Interface | Blade, Livewire 4, Flux 2 e Alpine.js |
| Estilos e assets | Tailwind CSS 4, Vite 8 e Vite+ |
| Banco | SQLite para desenvolvimento ou PostgreSQL |
| Qualidade | Pest 5, Laravel Pint e PHPStan com Larastan |

As versões exatas estão em `composer.lock` e `package-lock.json`.

## Requisitos

- PHP **8.4.1 ou superior**, compatível com o lockfile, para instalar também as ferramentas de testes. O ambiente foi validado com PHP 8.5.
- Composer 2.
- Node.js **22.18+ na linha 22**, **24.11+**, ou **20.19+ na linha 20**, conforme os requisitos do Vite+ instalado.
- npm.
- Extensões PHP exigidas pelo Composer e driver PDO do banco escolhido. Os testes precisam de `pdo_sqlite`.
- PostgreSQL disponível, caso essa seja a conexão escolhida.

## Instalação local

Execute os comandos na raiz do projeto. Em uma instalação existente, preserve o `.env`, a conexão de banco e a `APP_KEY` atuais.

Para obter uma instalação nova:

```bash
git clone https://github.com/Rodrigogbhs/torneioapp.git
cd torneioapp
```

### 1. Instalar dependências

```bash
composer install
npm ci
```

### 2. Preparar o ambiente

Copie o exemplo somente se o arquivo de configuração ainda não existir:

```bash
if [ ! -f .env ]; then
    cp .env.example .env
fi
```

Confira `APP_URL` no `.env`; o exemplo utiliza `http://localhost:8000`.

**Somente em uma instalação nova, com `APP_KEY` vazia**, gere a chave:

```bash
php artisan key:generate
```

As chaves Pix armazenadas dependem da `APP_KEY` para descriptografia. Mantenha essa chave em ambientes que já possuem dados.

### 3. Configurar o banco

**SQLite:** o `.env.example` já utiliza essa conexão. Mantenha `DB_DATABASE` sem definição para usar o arquivo padrão `database/database.sqlite`:

```dotenv
DB_CONNECTION=sqlite
```

Crie o arquivo caso ainda não exista:

```bash
touch database/database.sqlite
```

**PostgreSQL:** crie previamente o banco e ajuste a conexão no `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=torneio
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

Caso utilize `DB_URL`, confira também essa variável, pois ela pode definir os parâmetros da conexão.

Depois de configurar o banco, aplique as migrations pendentes e compile os assets:

```bash
php artisan config:clear
php artisan migrate
npm run build
```

### 4. Iniciar a aplicação

```bash
composer run dev
```

Esse comando executa os processos de desenvolvimento definidos pelo Laravel. Também é possível iniciar o servidor e o Vite em dois terminais:

```bash
# Terminal 1
php artisan serve
```

```bash
# Terminal 2
npm run dev
```

Acesse o endereço informado pelo servidor, cadastre a primeira conta pela interface e conclua a verificação de e-mail.

## E-mail e verificação de conta

O `.env.example` usa `MAIL_MAILER=log`. Nesse modo, as mensagens são registradas no log configurado; com os padrões do exemplo, ele fica em `storage/logs/laravel.log`. Em desenvolvimento, o link de verificação pode ser consultado nesse arquivo.

### Entrar quando a tela pede confirmação de e-mail

1. Faça login e clique em **Reenviar e-mail de verificação** na tela de confirmação.
2. Se o transporte estiver em `log`, consulte o último link gerado no terminal, na raiz do projeto:

```bash
php -r '
$log = html_entity_decode(file_get_contents("storage/logs/laravel.log"));
preg_match_all("~https?://[^\\s<>\"\\x27]+/email/verify/[^\\s<>\"\\x27]+~", $log, $links);
echo end($links[0]), PHP_EOL;
'
```

3. Abra o link completo no mesmo navegador, com a mesma conta que solicitou o envio. Use o mesmo host do início ao fim: `localhost` e `127.0.0.1` possuem sessões distintas.
4. Depois da confirmação, abra o dashboard. Se o link tiver expirado, solicite outro.

O comando mostra a última mensagem de verificação do log; em um ambiente compartilhado, confira se ela corresponde à conta utilizada. A URL contém uma assinatura temporária e deve permanecer privada. Para que os links sejam gerados com o host correto, confira `APP_URL` e execute `php artisan config:clear` após alterar a configuração.

O transporte `log` é uma opção de desenvolvimento. O login e a verificação estão implementados; o envio para uma caixa de e-mail depende da configuração de entrega.

### Entregar e-mails por SMTP

Para entregar mensagens em caixas de e-mail, configure um transporte válido. Exemplo de variáveis para SMTP, substituindo os valores pelos dados do serviço utilizado:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.seu-provedor.com
MAIL_PORT=587
MAIL_USERNAME=seu_usuario
MAIL_PASSWORD=sua_senha
MAIL_FROM_ADDRESS=torneio@seu-dominio.com
MAIL_FROM_NAME="${APP_NAME}"
```

Ajuste a porta e o `MAIL_SCHEME` conforme o serviço. Após alterar as configurações, execute `php artisan config:clear`. Confirme a entrega do e-mail e o acesso ao dashboard antes de disponibilizar o sistema para usuários reais.

## Como usar

### Organizador

1. Entre na conta e escolha **Criar torneio**.
2. Informe nome, esporte, data, local e valor; use zero para inscrição gratuita.
3. Abra **Meus torneios → Gerenciar torneio**.
4. Configure a chave Pix para torneios pagos e compartilhe o link público.
5. Acompanhe os inscritos e confirme os pagamentos recebidos.

### Participante

1. Abra **Torneios disponíveis** ou o link público do evento.
2. Entre na conta e selecione um participante, ou cadastre outro.
3. Clique em **Inscrever-se** para concluir a inscrição.
4. Acompanhe os detalhes em **Minhas inscrições**.

Cadastrar um participante apenas o adiciona à conta. A inscrição no torneio acontece ao clicar em **Inscrever-se**.

### Pagamento

Em torneios pagos, a inscrição começa como **Pendente**. A conta responsável consulta a chave Pix nos detalhes, realiza o pagamento fora da aplicação e aguarda a confirmação manual do organizador. Após a confirmação, a tela mostra o pagamento confirmado e deixa de exibir a chave e as instruções.

Em torneios gratuitos, a inscrição é confirmada automaticamente e nenhum pagamento é solicitado.

## Regras de acesso e gestão

- Cada conta inscreve somente participantes que cadastrou.
- O limite é de quatro participantes por conta e torneio.
- Repetir uma inscrição existente abre seus detalhes, sem criar outra inscrição.
- Somente o proprietário gerencia o torneio, sua chave Pix e seus inscritos.
- Somente a conta responsável acessa os detalhes de suas inscrições.
- A conta responsável pela inscrição não pode confirmar o próprio pagamento, mesmo quando também é proprietária do torneio.
- Remover uma inscrição preserva o participante cadastrado.
- Um torneio só pode ser excluído quando não possui inscrições.
- A exclusão de conta é bloqueada enquanto houver inscrições próprias, de seus participantes ou em seus torneios.

## Aparência e organização do código

O seletor de sol/lua no cabeçalho alterna entre claro e escuro. Em **Configurações da conta → Appearance**, também é possível acompanhar o tema do sistema.

| Caminho | Responsabilidade |
| --- | --- |
| `app/Models/` | Contas, participantes, torneios e inscrições |
| `app/Http/Controllers/` | Fluxos de torneios, participantes, inscrições e pagamentos |
| `routes/web.php` | Rotas do domínio |
| `routes/settings.php` | Configurações da conta |
| `resources/views/` | Páginas Blade e componentes Livewire |
| `resources/views/components/ui/` | Layout, botões, campos, feedback, status e temas |
| `resources/css/app.css` | Paleta dos dois temas, tipografia e estilos responsivos |
| `database/migrations/` | Estrutura e restrições do banco |
| `tests/` | Testes automatizados |

## Testes e qualidade

Execute a suíte funcional:

```bash
php artisan test --compact --fail-on-risky
```

O `phpunit.xml` força SQLite em memória, sessão e mailer em memória, isolando os testes da conexão real. Os testes de autenticação em duas etapas são ignorados quando essa funcionalidade está desabilitada no Fortify.

Para conferir formatação, análise estática e testes em sequência:

```bash
composer test
```

Comandos individuais:

```bash
composer lint:check
composer types:check
npm run build
```

Se o ambiente restringir o socket usado pelo paralelismo do PHPStan, execute a análise sem paralelismo:

```bash
vendor/bin/phpstan analyse --debug --no-progress --memory-limit=512M
```

Na validação registrada em **04/10/2026**, a suíte completa teve **154 testes aprovados, 2 ignorados e 801 assertions**, com build e análise estática aprovados. Consulte o relatório de redesign para os detalhes dessa execução.

## Problemas comuns

| Situação | Verificação |
| --- | --- |
| Assets ausentes ou aparência desatualizada | Execute `npm run build` ou mantenha `npm run dev` ativo |
| Views antigas após alterações | Execute `php artisan view:clear` |
| Configuração do `.env` sem efeito | Execute `php artisan config:clear` |
| E-mail de verificação não chega | Confira `MAIL_MAILER`; o driver `log` registra a mensagem sem entregá-la |
| Pagamento ainda pendente | A confirmação é manual, feita pelo proprietário após conferir o recebimento |
| Torneio não pode ser excluído | Confira se ainda existem inscrições |

## Documentação do projeto

- [História do projeto, decisões e mapa completo dos caminhos](README.md).
- [Fechamento funcional do MVP e pendências de operação](RELATORIO_FECHAMENTO_MVP.md).
- [Identidade visual, temas e verificações do redesign](RELATORIO_REDESIGN.md).
- [Histórico de desenvolvimento de 01/10/2026](RELATORIO_DESENVOLVIMENTO_2026-10-01.md).
