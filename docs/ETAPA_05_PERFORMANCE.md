# Etapa 5 — Performance, consultas e escalabilidade

## Auditoria inicial

O levantamento foi realizado sem alteração de dados, de rotas, de regras de negócio ou de schema. Os planos foram coletados no banco isolado `prevenda_test`; como ele ainda não possui volume representativo, os resultados comprovam a escolha de índice, mas não permitem declarar tempos de resposta ou ganho percentual.

## Gargalos confirmados

### Listagem de pré-vendas

`PreordersController::index()` pagina pré-vendas, mas executa consultas adicionais por registro para obter item/imagem e total alocado. Este é um padrão N+1 confirmado pelo código e deve ser substituído por carregamento em lote, preservando exatamente a composição atual dos cards.

### Correção aplicada

`PreordersController::index()` passou a carregar, para a página atual, os itens/imagens e as alocações financeiras em duas consultas em lote. Os mapas são indexados por `preorder_id` antes de compor os mesmos campos dos cards. A ação deixou de executar duas consultas adicionais por pré-venda; para uma página de 15 cards, o padrão passou de até 31 consultas dessa etapa para 3. Não há métrica de latência comparável porque o banco isolado não contém volume representativo.

### Financeiro

O agregado de `financial_entries` com `payment_allocations` usa o índice de tenant em `financial_entries`, mas o plano MySQL mostra `block-nl-join` e varredura de `payment_allocations` para o join por `financial_entry_id`.

### Agregado de pré-vendas

O agregado entre `preorders` e `preorder_items` também usa índice para o tenant da tabela principal, mas realiza `block-nl-join` em `preorder_items` pelo join em `preorder_id`.

## EXPLAIN executados

```sql
EXPLAIN FORMAT=JSON
SELECT fe.id, COALESCE(SUM(pa.amount), 0)
FROM financial_entries fe
LEFT JOIN payment_allocations pa ON pa.financial_entry_id = fe.id
WHERE fe.tenant_id = 1
GROUP BY fe.id;
```

```sql
EXPLAIN FORMAT=JSON
SELECT p.id, COALESCE(SUM(pi.quantity * pi.unit_price), 0)
FROM preorders p
LEFT JOIN preorder_items pi ON pi.preorder_id = p.id
WHERE p.tenant_id = 1
GROUP BY p.id;
```

Ambos os planos usam `block-nl-join` na tabela de itens/alocações.

## Índices existentes relevantes

- `financial_entries`: `(tenant_id, due_date)`.
- `payment_allocations`: `(tenant_id, payment_id)` e FK simples em `financial_entry_id`.
- `preorders`: `(tenant_id, seller_id)` e `(tenant_id, status)`.
- `preorder_items`: `(tenant_id, preorder_id)` e FK simples em `preorder_id`.

## Índices candidatos — não criados

1. `payment_allocations (financial_entry_id, tenant_id)` para a agregação financeira por lançamento e tenant.
2. `preorder_items (preorder_id, tenant_id)` para joins da listagem/agregados de pré-vendas.

Durante a validação foi prototipada uma migration aditiva, mas o rollback repetido do CI4 revelou dependência da FK de `payment_allocations` no índice novo. A migration foi removida, pois não seria seguro entregá-la sem um rollback robusto. Antes de criar índices, é preciso validar a estratégia em uma migration independente com massa representativa e plano de reversão MySQL testado.

## Cache

Nenhum cache foi introduzido. Dashboard e financeiro possuem dados sensíveis e potencialmente desatualizados; a prioridade é corrigir consultas e N+1 antes de considerar cache com chave por tenant.

## Próximas ações propostas

1. Criar teste de equivalência HTTP para os cards de pré-venda com múltiplos itens e pagamentos.
2. Criar migration incremental com os dois índices somente após validar um rollback MySQL robusto e comparar `EXPLAIN` antes/depois.
3. Executar regressão financeira, multi-tenant e listagens após cada lote.
