# Plano de correção incremental

## Regras de execução

1. Cada lote começa com teste de caracterização e termina com lint, testes relevantes e revisão de escopo tenant.
2. Controllers não acessarão Model, Query Builder, SQL, `db_connect()` ou transações.
3. Services recebem dependências explícitas e coordenam casos de uso; Models/Repositories concentram persistência e consultas.
4. Nenhuma migration, alteração de Shield ou mudança de cálculo monetário será aplicada sem teste e decisão técnica documentada.

## Etapa 0 — concluída parcialmente

- Leitura integral de `AGENTS.md` e `docs/RELATORIO_AUDITORIA_CI4_CODEX.md`.
- Inventário, baseline e matriz de achados registrados.
- Pendência: criar ambiente MySQL isolado e fixtures A/B antes de mudanças P0.

## Etapa 1 — P0 Financeiro e tenancy

1. Criar testes de caracterização para `PaymentsController`: saldo, excesso, pagamento parcial/integral, estorno e reenvio.
2. Criar Models/Repositories `FinancialEntry`, `Payment` e `PaymentAllocation`, todos com métodos tenant-scoped.
3. Criar `PaymentService` sem Query Builder; mover upload para adapter de storage.
4. Reproduzir e corrigir concorrência/idempotência somente com MySQL isolado.
5. Adicionar testes A/B para recursos financeiros.

## Etapa 2 — P1 Pré-vendas e Garagem

1. Caracterizar criação, edição, sinal, chegada, cancelamento, parcelas e totais.
2. Extrair `PreorderService`, `GarageService`, repositories de escrita e read repositories.
3. Remover sincronização financeira de GET após teste de compatibilidade.
4. Validar referências únicas e transações completas.

## Etapa 3 — P1 Entregas e Coleção

1. Caracterizar recebimento parcial, envio, repetição, transferência à coleção e rateio.
2. Extrair repositories de shipment, delivery e collection.
3. Centralizar storage e autorização de fotos/anexos.

## Etapa 4 — P1/P2 Demais módulos

- Finance read/report, Administração, Tenancy, Dashboard, Explore, History, Catalog, Sellers, Purchases, Imports, Attachments e Profile.
- Criar Models ausentes por tabela própria antes de mover suas queries.

## Etapa 5 — P2 Qualidade e prevenção

1. Avaliar e aprovar ferramenta PSR-12 compatível antes de adicioná-la.
2. Configurar checks de lint, estilo, análise estática e testes em CI.
3. Documentar convenções de repository, tenant context, dinheiro e storage.
