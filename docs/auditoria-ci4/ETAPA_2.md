# Etapa 2 — Refatoração arquitetural P1

## Módulo atual: Pré-vendas

### Lote 2.1 — base de persistência tenant-scoped

- Criado `PreorderStatusHistoryModel` para a tabela `preorder_status_history`.
- Criado `PreorderRepository` com consultas e alterações de pré-venda sempre recebendo `tenantId` explicitamente.
- Criado teste de regressão que protege os contratos `findForTenant()` e `itemsForTenant()` contra remoção do escopo de tenant.

### Compatibilidade

- Nenhuma rota, view, migration, schema ou dado foi modificado.
- `PreordersController` ainda não utiliza o repositório neste lote; a migração do controller ocorrerá quando os casos de uso de criação, edição, chegada e cancelamento estiverem cobertos.

### Validação

- Lint PHP dos novos arquivos: aprovado.
- `vendor/bin/phpunit tests/unit/PreorderRepositoryTenantScopeTest.php`: 1 teste, 3 asserções aprovadas.
- Aviso conhecido: driver de cobertura indisponível.

### Próximo lote da Etapa 2

- Caracterizar criação/edição/cancelamento de pré-venda e mover cada caso de uso para `PreorderService`, mantendo persistência em Models/Repositories.

### Lote 2.2 — cancelamento de pré-venda

- Criado `TransactionManager` de infraestrutura para delimitar transações fora de controllers.
- Criado `PreorderService::cancel()` com validação de recurso no tenant, transição de status e histórico atômicos.
- `PreordersController::cancel()` passou a adaptar somente a requisição e a resposta HTTP; não inicia transação, não atualiza Model e não insere histórico.
- Registrada factory `preorderService` em `Config\Services` para evitar construção do caso de uso no controller.

### Validação do lote 2.2

- Lint PHP dos arquivos alterados: aprovado.
- `vendor/bin/phpunit tests/unit/PreorderRepositoryTenantScopeTest.php tests/unit/PaymentBalanceCalculatorTest.php`: 4 testes, 8 asserções aprovadas.
- Aviso conhecido: driver de cobertura indisponível.
