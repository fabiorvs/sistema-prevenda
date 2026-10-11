# Diecast Manager

Aplicação web para colecionadores de miniaturas diecast organizarem compras, pré-vendas, pagamentos, envios e a coleção pessoal em um único lugar.

Desenvolvida em PHP com CodeIgniter 4 e CodeIgniter Shield, a aplicação isola os dados de cada conta por tenant: catálogos, vendedores, finanças, anexos e coleção não são compartilhados entre usuários.

## Recursos

- Autenticação, perfil editável e alteração segura de senha.
- Vendedores, catálogo de miniaturas, marcas, escalas e fotos.
- Pré-vendas com sinal, pagamentos parciais, saldo, chegada no vendedor e cancelamento.
- Garagem para itens já reservados/guardados pelo vendedor.
- Compras presenciais com local, data, valor, foto e entrada automática na coleção.
- Envios agrupados por vendedor, frete, rateio, rastreio e previsão de entrega.
- Coleção com galeria, custo final, origem, localização e filtros.
- Financeiro unificado para Pré-vendas e Garagem.
- Dashboard com alertas de pagamentos vencidos, envios atrasados e itens aguardando envio.
- Histórico de compras e envios concluídos.
- Anexos de comprovantes e fotos de pedidos.
- Administração para usuários com grupo `superadmin`.

## Tecnologias

- PHP 8.2+
- CodeIgniter 4.7
- CodeIgniter Shield 1.4
- MySQL 8+ ou MariaDB compatível
- Bootstrap 5, Bootstrap Icons e PHPUnit 10

## Requisitos

- PHP 8.2 ou superior, com `intl`, `mbstring`, `json`, `mysqli`/`mysqlnd`, `fileinfo` e `gd`.
- Composer 2.
- MySQL 8+ ou MariaDB.

## Instalação local

```bash
git clone https://github.com/seu-usuario/diecast-manager.git
cd diecast-manager
composer install
cp env .env
```

Edite `.env` com a URL da aplicação e as credenciais do banco:

```ini
app.baseURL = 'http://127.0.0.1:8080/'

database.default.hostname = 127.0.0.1
database.default.database = diecast_manager
database.default.username = seu_usuario
database.default.password = sua_senha
database.default.DBDriver = MySQLi
database.default.port = 3306
```

Crie o banco antes de executar as migrations:

```sql
CREATE DATABASE diecast_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Depois, aplique o esquema e inicie o servidor:

```bash
php spark migrate --all
composer serve
```

Abra `http://127.0.0.1:8080/register` para criar a primeira conta. Não existem credenciais padrão versionadas no projeto. O comando `composer serve` usa quatro workers PHP em `127.0.0.1:8080`.

## Fluxo de uso

1. Cadastre vendedores e miniaturas.
2. Use **Pré-vendas** quando o item ainda chegará ao vendedor.
3. Use **Garagem** para itens já guardados pelo vendedor.
4. Confirme a chegada da pré-venda ao vendedor.
5. Organize um envio com itens pagos do mesmo vendedor, informando frete, transportadora, rastreio e previsão.
6. Ao receber o envio, os itens entram automaticamente na **Coleção**.
7. Use **Compras** para aquisições presenciais: elas entram direto na coleção.

## Uploads e backup

Fotos e anexos são armazenados em `writable/uploads`, fora da raiz pública. Eles são acessados por rotas autenticadas e validadas pelo tenant ativo.

Faça backup periódico do banco de dados e de `writable/uploads`.

## Testes e verificações

```bash
vendor/bin/phpunit
php spark migrate:status --all
php spark routes
```

## Estrutura

```text
app/
├── Modules/
│   ├── Attachments/     # Comprovantes e fotos anexadas
│   ├── Catalog/         # Catálogo e imagens
│   ├── Collection/      # Coleção e galeria
│   ├── Deliveries/      # Chegadas e envios agrupados
│   ├── Finance/         # Contas e pagamentos
│   ├── Garage/          # Itens guardados pelo vendedor
│   ├── Preorders/       # Pré-vendas
│   ├── Profile/         # Perfil e senha
│   ├── Purchases/       # Compras presenciais
│   └── Tenancy/         # Isolamento de dados
├── Database/Migrations/ # Esquema do banco
└── Views/               # Interfaces PHP/Bootstrap
public/                  # Única raiz pública do servidor web
writable/uploads/        # Uploads locais
```

## Produção

Configure Apache ou Nginx para apontar somente para `public/`. Não exponha `app/`, `writable/`, `vendor/` ou `.env`.

Defina `CI_ENVIRONMENT = production`, use uma chave de criptografia exclusiva e credenciais de banco restritas. Recursos de e-mail do Shield exigem configuração do provedor em `app/Config/Email.php` ou variáveis de ambiente.

## Licença

Licenciado sob a [MIT License](LICENSE), exceto arquivos de terceiros sob suas respectivas licenças.
