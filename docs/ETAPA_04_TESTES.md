# Etapa 4 — Testes automatizados, segurança e regressão

## Lote 4.1 — Infraestrutura MySQL isolada e isolamento multi-tenant

- Criado o banco exclusivo `prevenda_test`; o banco padrão não foi modificado.
- O grupo `tests` de `Config\Database` usa MySQL somente sob `ENVIRONMENT=testing`.
- Host, usuário e senha são lidos do ambiente; nenhuma credencial foi incluída no repositório.
- O nome do banco de testes é fixo em `prevenda_test`, impedindo que PHPUnit aponte para o banco padrão.
- Criado `CatalogTenantIsolationIntegrationTest`, com migrations reais do CI4 e dados sintéticos.

## Cenários executados

| Cenário | Resultado |
| --- | --- |
| Tenant A acessa por ID um produto do tenant B | Aprovado: retorna `PageNotFoundException` |
| Tenant A tenta alterar por ID um produto do tenant B | Aprovado: exceção e registro do tenant B preservado |

## Comandos

```bash
vendor/bin/phpunit --no-coverage tests/integration/CatalogTenantIsolationIntegrationTest.php
```

Resultado: **2 testes, 2 asserções, aprovado**.

## Limitações conhecidas

- Cobertura não foi medida: Xdebug/PCOV não está disponível.
- Os testes financeiros reais ainda precisam ser caracterizados antes de qualquer alteração no fluxo atual de `PaymentsController`.
- Concorrência e idempotência dependem de cenários MySQL específicos e serão tratados no próximo lote financeiro.
