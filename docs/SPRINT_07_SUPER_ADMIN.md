# Sprint 07 — Super Admin

## Escopo entregue

- Painel administrativo com totais de usuários, contas ativas, suspensas e cadastros do dia.
- Busca de conta, responsável e e-mail.
- Ativação e suspensão de tenants e usuários.
- Papéis globais do Shield e papéis locais do tenant: administrador da conta, membro e visualizador.
- Planos sem cobrança: Gratuito e Colecionador, atribuíveis por tenant.
- Auditoria de todas as alterações administrativas.

## Isolamento

As rotas de administração exigem sessão e grupo global superadmin, mas não usam o
filtro de tenant. O painel consulta apenas metadados de contas, usuários, planos e
auditoria. Não há rota administrativa para pré-vendas, pagamentos, fotos, produtos
ou outros registros privados.

A suspensão de um tenant é efetiva porque o resolvedor do tenant também exige que a
conta esteja ativa. Um Super Admin não ganha associação em tenant_users e, portanto,
não acessa dados privados somente por ter esse grupo.

## Como promover o primeiro Super Admin

Promova um usuário já existente somente em ambiente controlado:

    php spark shield:user addgroup -n nome-do-usuario -g superadmin

Depois, entre novamente na aplicação. O menu Administração ficará disponível.

## Validação

    php spark migrate --all
    php spark routes
    vendor/bin/phpunit
