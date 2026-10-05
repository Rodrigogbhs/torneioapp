# Relatório da reformulação visual

> Registro histórico desta etapa. O estado consolidado e as correções posteriores estão no [README](README.md#status-atual).

Data: 04/10/2026. Projeto: `torneio` (raiz do projeto).

## 1. Conceito visual

Uma identidade esportiva discreta, com títulos fortes, espaço livre e verde como único destaque principal. A marca `torneio.` recebe um símbolo geométrico próprio e favicon correspondente. A ilustração de quadra é um SVG decorativo leve, usado no dashboard, na entrada pública e na autenticação.

O dashboard apresenta uma saudação, a ação Criar torneio e três atalhos úteis. Não foram adicionadas métricas fictícias. As listas administrativas usam linhas organizadas; os cards ficam concentrados na descoberta de torneios e nos resumos que precisam de agrupamento.

## 2. Paleta escolhida

As cores são centralizadas em `resources/css/app.css` e possuem versões claras e escuras.

| Papel | Tema claro | Tema escuro |
| --- | --- | --- |
| Fundo | `#F7F8F5` | `#111713` |
| Superfície | `#FFFFFF` | `#19211B` |
| Superfície suave | `#EFF2ED` | `#202A22` |
| Texto principal | `#17251D` | `#EDF1E9` |
| Texto secundário | `#606F64` | `#A5B0A5` |
| Borda | `#DFE5DD` | `#303C32` |
| Destaque esportivo | `#276749` | `#B4D88B` |
| Confirmado | `#236844` | `#A8D3AF` |
| Pendente | `#855719` | `#E1BF82` |
| Erro | `#A63B38` | `#EEAAA5` |

Os status combinam texto, rótulo e cor. Os pares de texto principal, secundário e botões principais foram conferidos por cálculo de contraste: o menor valor desses pares é 4,70:1. Isso não substitui uma auditoria completa de acessibilidade.

O botão de sol/lua no cabeçalho utiliza a preferência já existente do Flux. As configurações continuam oferecendo Claro, Escuro e Sistema, com persistência entre páginas e inicialização do tema no cabeçalho.

## 3. Tipografia

Foi mantida a Instrument Sans já instalada e servida localmente. Nenhuma fonte ou dependência nova foi adicionada.

- Títulos principais: aproximadamente 42–68 px no desktop, conforme a página.
- Títulos de seção: 21–26 px.
- Texto de leitura: 14–15 px, com entrelinha confortável.
- Rótulos e informações de apoio: 11–13 px.
- Campos de texto no celular: 16 px, evitando o zoom automático comum no iOS.

## 4. Componentes reutilizáveis

Os componentes anônimos ficam em `resources/views/components/ui/`:

| Componente | Uso |
| --- | --- |
| `layout` | Cabeçalho, conteúdo, rodapé, navegação e conta |
| `button` | Botões primários, secundários e discretos, sobre o Flux existente |
| `field` | Rótulo, orientação e erro junto ao campo |
| `feedback` | Mensagens de sucesso, erro e validação |
| `payment-status` | Confirmado, pendente e inscrição gratuita |
| `event-card` | Apresentação de torneios disponíveis |
| `date` | Datas de torneios em português |
| `theme-toggle` | Alternância entre os temas |
| `court` | Elemento esportivo decorativo |

Os layouts de aplicação e autenticação existentes reutilizam essa base, preservando os aliases empregados pelas páginas Livewire. Os componentes de marca e os cabeçalhos de autenticação também foram atualizados.

## 5. Views alteradas

| View | Resultado |
| --- | --- |
| `dashboard` | Saudação, CTA evidente e atalhos reais |
| `tournaments.available` | Nome, esporte, data, local, preço e inscrição em cards leves |
| `tournaments.index` | Lista do organizador, total real de inscritos e ações organizadas |
| `tournaments.create` | Formulário com hierarquia clara, ajuda e erros por campo |
| `tournaments.show` | Apresentação pública do encontro e inscrição como ação principal |
| `tournaments.manage` | Participantes, pagamento, Pix protegido e compartilhamento |
| `athletes.create` | Cadastro do participante com explicação do próximo passo |
| `registrations.index` | Participante, torneio, pagamento e acesso aos detalhes |
| `registrations.show` | Informações da inscrição e estados de pagamento separados |
| `pages/auth/login` | Login integrado à identidade visual |
| `pages/auth/register` | Cadastro integrado à identidade visual |
| `welcome` | Entrada pública com a mesma identidade |

Também foram atualizados os layouts compartilhados, a navegação, o título das páginas e o favicon. As páginas de configuração permanecem nos componentes originais do starter, dentro do novo layout.

## 6. Melhorias de UX

- Criar torneio fica visível no cabeçalho autenticado e no dashboard.
- O destino atual recebe destaque na navegação.
- Campos mantêm valores anteriores, rótulos associados e erros próximos.
- O cadastro de participante continua explicando que a inscrição precisa ser concluída separadamente.
- Torneios disponíveis e páginas públicas orientam visitantes a entrar ou criar conta.
- Pagamentos gratuitos, pendentes e confirmados recebem apresentações distintas.
- A chave Pix existente permanece oculta no formulário do organizador; o participante autorizado vê a chave somente no estado aplicável.
- Compartilhar oferece campo de link, seleção ao focar e botão de cópia. Se o navegador bloquear a cópia automática, aparece orientação para copiar manualmente.
- Ações de exclusão continuam com a confirmação existente; excluir torneio fica no menu de ações da linha.
- Foram incluídos acesso direto ao conteúdo, foco visível, indicação de erros e respeito à preferência de movimento reduzido.

## 7. Ajustes para mobile

A navegação autenticada passa para uma barra inferior compacta; Criar torneio continua no cabeçalho. Listas de inscrições e participantes reorganizam seus campos em linhas, com ações abaixo. Formulários e resumos usam uma coluna; os cards de torneio ocupam a largura disponível.

As ações principais e os botões de gestão recebem pelo menos 44 px de altura no celular. Chaves Pix e textos extensos podem quebrar linha; o campo de compartilhamento não expande a página.

Foram realizadas 55 conferências de páginas e estados no navegador, utilizando viewports de 1440×1000, 768×1024, 390×844 e 360×800, nos temas claro e escuro. Nenhuma dessas conferências detectou largura de documento maior que a área disponível ou elementos de conteúdo/cabeçalho ultrapassando suas bordas. As medidas consideram o espaço ocupado pela barra de rolagem vertical.

Evidência: [medidas e estados conferidos](VERIFICACOES_REDESIGN.json).

## 8. Funcionalidades preservadas

Rotas, middleware, autenticação, permissões, models, validações do servidor, migrations e configurações permanecem iguais. Os formulários mantêm seus destinos, nomes de campos, CSRF, métodos HTTP e uso de `old()`.

Continuam preservados: propriedade dos participantes, limite por conta, proteção contra duplicidade, inscrição explícita, confirmação automática de inscrição gratuita, confirmação manual de pagamento pelo organizador e proteção contra confirmação do próprio pagamento.

O único ajuste de PHP foi acrescentar `withCount('registrations')` à consulta de Meus torneios. Ele fornece a contagem real exibida na lista, sem modificar regras ou gravações.

Foi feita comparação de arquivos com o estado anterior: `routes`, `tests`, `config` e `database` não têm alterações; em `app`, apenas a consulta citada mudou. Os hashes de `.env` e `.env.example` permanecem idênticos. A APP_KEY não foi alterada.

A verificação pela interface usou exclusivamente um SQLite temporário em `/tmp/torneio-redesign-preview.sqlite`, com contas e eventos fictícios. Nenhuma migration desta tarefa foi aplicada ao banco real. A inscrição criada pela interface pertence apenas a essa massa de teste.

## 9. Testes executados

| Verificação | Resultado |
| --- | --- |
| `npm run build` | Compilação concluída com sucesso |
| `php artisan test --compact --fail-on-risky` | 154 passaram, 2 ignorados, 801 assertions; nenhuma falha |
| Testes dirigidos de torneios, inscrições e autenticação após os últimos ajustes | 30 passaram, 1 ignorado, 164 assertions; nenhuma falha |
| Pint no controller alterado | Aprovado |
| PHPStan, com `--debug --no-progress --memory-limit=512M` | Aprovado, zero erros |
| `php artisan view:cache` | Templates compilados com sucesso |
| `php artisan view:clear` | Cache de views removido ao concluir |
| Navegador | Páginas principais, temas, configurações, menu, logout e estados de pagamento conferidos |

Pela interface foram verificados login da conta fictícia, erro junto ao e-mail no login, alternância de temas, opções Claro/Escuro/Sistema, persistência ao navegar, inscrição gratuita confirmada sem Pix e retorno de cópia bloqueada. Os logs inspecionados não registraram erros JavaScript da aplicação.

O PHPStan foi executado sem paralelismo porque o ambiente restringe a abertura do socket usado por seus processos auxiliares. Os testes ignorados pertencem aos cenários de autenticação em duas etapas desabilitada na configuração existente.

## 10. Pendências

- **Capturas concluídas em uma etapa posterior:** a primeira tentativa falhou na ferramenta do navegador e não produziu imagens. A tarefa de documentação posterior capturou e conferiu 20 prints reais nos dois temas, incluindo o celular. As imagens estão na [galeria do aplicativo](public/screenshots/README.md), com dados fictícios em ambiente separado.
- O build informa que o pacote opcional `fontaine` não está instalado para otimizar fontes de fallback. A compilação funciona e nenhuma dependência foi adicionada.
- As telas secundárias do starter e algumas mensagens de autenticação continuam em inglês. A tradução completa não fez parte desta reformulação.
- A configuração de envio real de e-mails mantém a pendência anterior documentada em `RELATORIO_FECHAMENTO_MVP.md`; o redesign não alterou `.env` ou entrega de e-mails.
