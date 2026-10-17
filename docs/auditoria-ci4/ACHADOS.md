# Achados da auditoria CI4

Data da verificação: 17/10/2026.

## Escopo e baseline

- Repositório completo, incluindo `app/`, `tests/`, configurações, migrations, rotas e Composer.
- Estado inicial possui alterações locais não commitadas em Catálogo, Compras, Importações e Perfil. Elas são anteriores a esta auditoria e não foram descartadas nem incluídas como correção de P0.
- 17 controllers modulares, 15 services, 9 models e nenhum repository especializado foram encontrados.
- 17 controllers e 10 services possuem acesso direto a `db_connect()`, Query Builder, SQL ou transação.
- O repositório contém 20 migrations próprias e 13 testes unitários. Não há fixture/integração MySQL para concorrência, transação ou cenário tenant A/B.

## Matriz de achados

| ID | Prioridade | Status | Arquivo:evidência | Impacto | Correção planejada | Teste necessário |
| --- | --- | --- | --- | --- | --- | --- |
| A-001 | P0 | Confirmado | `Finance/Controllers/PaymentsController.php:36-101,113-159` | Regras financeiras, upload, Query Builder e transação estão no controller. | Criar `PaymentService` e repositories de pagamento, alocação e lançamento. | Caracterização de pagamento parcial, integral e excedente. |
| A-002 | P0 | Necessita investigação | `PaymentsController.php:48-98` | Saldo é lido antes da transação e a chave é aleatória a cada POST; há risco plausível de duplicação/ultrapassagem concorrente. | Confirmar com MySQL/InnoDB isolado; definir operação idempotente e lock no repository se reproduzido. | Reenvio idêntico, duas conexões e rollback. |
| A-003 | P0/P1 | Confirmado parcialmente | `PaymentsController.php:121-142` | Estorno é identificado por texto e status vira `pending` sem recálculo agregado explícito. | Caracterizar saldos; criar vínculo estruturado apenas se a migration for justificada. | Estorno total, parcial, duplo e múltiplos pagamentos. |
| A-004 | P0 | Necessita investigação | Consultas de pagamentos, pré-vendas, garagem, coleção e envios usam escopo parcial em joins/relacionamentos. | Não há exploração reproduzida; faltam testes A/B. | Repositories com `findForTenant`/`updateForTenant` e testes HTTP/DB A/B. |
| A-005 | P0/P1 | Confirmado | `PaymentsController.php:49-55,96`; `GarageController.php`; `PreordersController.php` | Valores monetários usam `float` e tolerâncias. | Caracterizar cálculo atual antes de introduzir decimal/cents. | Casos de centavos, soma, sinal, frete e estorno. |
| A-006 | P1 | Confirmado | `PreordersController.php:25-504`; GET em `163-175` chama sincronização. | Controller reúne query, regra, transação e uma leitura pode alterar estado. | `PreorderService` + repositories de leitura/escrita; retirar efeito de GET após caracterização. | Criação, edição, chegada, cancelamento e referência única. |
| A-007 | P1 | Confirmado | `GarageController.php:74-375` | Regras de pagamento e persistência no controller; pagamento e próxima parcela não têm unidade transacional explícita. | `GarageService` e repositories. | Parcial/integral, preço abaixo do pago, concorrência e cancelamento. |
| A-008 | P1 | Confirmado | `FinanceController.php:12-301` | Agregações, filtros e status em controller. | `FinanceReadRepository` e serviço de relatório. | Períodos, frete, sinais, compras, importações e estornos. |
| A-009 | P1 | Confirmado | `AdministrationController.php:14-203` | Administração altera usuários, tenants, grupos e planos diretamente. | Service/repositories administrativos e auditoria atômica. | Papéis, plano inválido e trilha de auditoria. |
| A-010 | P1 | Confirmado | `CollectionController.php:12-79` | Query, alteração e upload no controller. | Service de coleção, repositories e storage. | A/B, upload e atualização de localização. |
| A-011 | P1/P2 | Confirmado | 17 controllers modulares encontrados com persistência/acoplamento direto. | Viola o padrão Controller → Service → Model/Repository. | Refatoração incremental por módulo. | Regressão por rota/módulo. |
| A-012 | P1/P2 | Confirmado | 10 services com Query Builder, incluindo `ShipmentService`, `DeliveryService` e `FinancialEntryService`. | Persistência fora de Models/Repositories. | Extrair repositories por agregado. | Integração de entrega, coleção e financeiro. |
| A-013 | P1/P2 | Confirmado | `ActiveTenantResolver.php:12-45` e uso repetido em controllers. | Resolver nullable e infraestrutura repetida; não é vazamento comprovado. | Tenant context/factory e repository de associação. | Usuário sem tenant, tenant suspenso e sessão obsoleta. |
| A-014 | P1/P2 | Confirmado | Uploads distribuídos em controllers/services. | Falta política única de storage e compensação banco/arquivo. | Serviço de storage + repositories de metadados. | MIME, tamanho, A/B e falha intermediária. |
| A-015 | P2 | Confirmado | Controllers e migrations possuem métodos/instruções condensados. | Não atende ao padrão de legibilidade definido. | Configurar formatter após aprovação de dependência. | Dry-run de ferramenta. |

## Itens já verificados como positivos

- `app/Config/Routing.php` mantém `autoRoute = false`.
- Rotas privadas usam filtros `session` e `tenant`; administração usa `session` e `superadmin`.
- CSRF está em filtros globais antes da requisição.
- CodeIgniter Shield continua instalado; nenhuma alteração de autenticação foi feita nesta etapa.

## Limitações atuais

- Não foi acessado banco de produção nem executada migration.
- Não há ambiente MySQL de teste isolado disponível/configurado nesta etapa; por isso concorrência, locks, FKs e isolamento A/B ainda não foram reproduzidos.
- PHPCS, PHP-CS-Fixer e PHPStan não estão instalados; conformidade PSR-12 não foi medida automaticamente.
