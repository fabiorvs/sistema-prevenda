# Diecast Manager

Aplicação web para organizar uma coleção de miniaturas diecast: catálogo, vendedores, pré-vendas, itens guardados, pagamentos, envios, importações e coleção recebida.

O sistema foi feito para o fluxo real de compra do colecionador: adquirir uma miniatura, acompanhar pagamentos parciais e a chegada ao vendedor, agrupar itens em um único frete e, quando recebidos, incorporá-los automaticamente à coleção com o custo final registrado.

> Os dados são isolados por conta (tenant). Catálogo, vendedores, finanças, anexos e coleção não são compartilhados entre usuários.

## Destaques

- Painel inicial com investimento, valores a pagar, alertas, próximos compromissos, coleção e importações.
- Busca global para encontrar miniaturas, vendedores, compras, pré-vendas e itens da garagem.
- Calendário mensal visual para pagamentos, entregas, chegadas no vendedor e importações.
- Fotos de miniaturas, vendedores, comprovantes e pedidos, armazenadas fora da raiz pública.
- Interface responsiva em português, com filtros e retornos para as páginas de origem.

## Módulos e fluxos

### Catálogo e vendedores

- Cadastro de marcas, modelos, escalas e fotos de miniaturas.
- Seleção de marca já cadastrada ou criação de uma nova diretamente nos formulários.
- Cadastro de vendedores e lojas, com imagem de referência.

### Pré-vendas

- Cadastro de itens que ainda serão disponibilizados pelo vendedor.
- Criação de uma miniatura nova durante o cadastro, se ela ainda não existir no catálogo.
- Valor total, sinal, forma de pagamento, saldo calculado e pagamentos parciais posteriores.
- Anexos, observações, edição, cancelamento e confirmação de chegada completa ao vendedor.
- Visualização detalhada com resumo financeiro, histórico e preparo de envio.

### Garagem

- Controle de miniaturas já reservadas ou guardadas com um vendedor.
- Pagamentos parciais, data da compra, previsão da próxima parcela, anexos, edição e cancelamento.
- Os itens ficam elegíveis para envio quando quitados e disponíveis.

### Compras presenciais

- Registro de compras em lojas, eventos ou particulares.
- Cadastro da miniatura e foto no mesmo fluxo, se necessário.
- Inclusão imediata na coleção, com local, data, preço e origem identificados.

### Importações

- Fluxo separado de compras presenciais para acompanhar itens internacionais.
- Moeda, cotação, valor do item, frete, taxas, fornecedor, transportadora, rastreio e previsão de chegada.
- Status de compra confirmada, em trânsito ou fiscalização.
- Ao marcar como recebida, a miniatura entra automaticamente na coleção com o custo convertido em reais.

### Entregas e frete

- Painel de chegadas por pré-venda com foto, modelo, marca, escala e quantidade pendente.
- Organização de envios agrupados por vendedor, evitando criar um envio por miniatura.
- Frete, transportadora, código de rastreio, previsão de entrega e status do pacote.
- Rateio do frete entre os itens do envio para registrar o custo real na coleção.
- Ao receber o pacote, os itens entram automaticamente na coleção.

### Financeiro

- Visão unificada de pendências e pagamentos de Pré-vendas e Garagem.
- Destaques para valores a pagar agora, próximos 30 dias, investimento mensal e saldo futuro.
- Registro de pagamentos parciais, formas de pagamento e estornos quando aplicável.

### Coleção e histórico

- Galeria com fotos, filtros por marca, escala, vendedor e localização.
- Origem identificada para cada item: pré-venda, garagem, compra presencial ou importação.
- Custo final, vendedor/loja, localização e observações por miniatura.
- Inclusão de novas fotos na galeria da coleção.
- Histórico separado para compras e envios concluídos.

### Perfil e administração

- Perfil editável e alteração segura de senha.
- Área de administração exclusiva para o grupo `superadmin`.
- Gestão de contas, usuários, papéis globais e da conta, planos, suspensões e auditoria.
- Indicadores de utilização por conta, planos em uso e filtros administrativos.

## Tecnologias

- PHP 8.2+
- CodeIgniter 4.7
- CodeIgniter Shield 1.4
- MySQL 8+ ou MariaDB compatível
- Bootstrap 5 e Bootstrap Icons
- Chart.js
- PHPUnit 10

## Requisitos

- PHP 8.2 ou superior, com extensões `intl`, `mbstring`, `json`, `mysqli`/`mysqlnd`, `fileinfo` e `gd`.
- Composer 2.
- MySQL 8+ ou MariaDB.

## Instalação local

```bash
git clone https://github.com/seu-usuario/diecast-manager.git
cd diecast-manager
composer install
cp env .env
```

Crie o banco de dados:

```sql
CREATE DATABASE diecast_manager
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Edite `.env` com a URL da aplicação e as credenciais do banco:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://127.0.0.1:8080/'

database.default.hostname = 127.0.0.1
database.default.database = diecast_manager
database.default.username = seu_usuario
database.default.password = sua_senha
database.default.DBDriver = MySQLi
database.default.port = 3306
```

Execute as migrations e inicie a aplicação:

```bash
php spark migrate --all
composer serve
```

O script `composer serve` inicia o servidor em `http://127.0.0.1:8080` com quatro workers PHP. Acesse `/register` para criar a primeira conta; nenhuma credencial padrão é versionada no projeto.

## Primeiro uso

1. Crie a conta e complete os dados do perfil.
2. Cadastre vendedores e miniaturas no catálogo.
3. Escolha o fluxo adequado:
   - **Pré-venda**: a miniatura ainda chegará ao vendedor.
   - **Garagem**: a miniatura já está guardada pelo vendedor.
   - **Compra**: aquisição presencial que entra direto na coleção.
   - **Importação**: compra internacional que ainda está em deslocamento.
4. Registre sinal e demais pagamentos conforme forem realizados.
5. Marque a chegada ao vendedor quando aplicável.
6. Em **Entregas**, selecione itens quitados do mesmo vendedor e organize um pacote.
7. Ao receber o pacote ou a importação, consulte a miniatura em **Coleção**.

## Uploads e backup

Fotos e anexos ficam em `writable/uploads`, fora da raiz pública. Eles são servidos apenas por rotas autenticadas e validadas para o tenant ativo.

Faça backup periódico de:

- Banco de dados MySQL/MariaDB.
- Diretório `writable/uploads`.
- Arquivo `.env` (mantido fora do repositório).

## Testes e verificações

```bash
vendor/bin/phpunit
php spark migrate:status --all
php spark routes
```

## Estrutura do projeto

```text
app/
├── Modules/
│   ├── Administration/  # Gestão de contas, planos e auditoria
│   ├── Attachments/     # Comprovantes e anexos
│   ├── Catalog/         # Catálogo, marcas e imagens
│   ├── Collection/      # Coleção e galeria
│   ├── Dashboard/       # Indicadores e alertas
│   ├── Deliveries/      # Chegadas, frete e envios agrupados
│   ├── Explore/         # Busca global e calendário
│   ├── Finance/         # Contas e pagamentos
│   ├── Garage/          # Itens guardados pelo vendedor
│   ├── History/         # Histórico de compras e envios
│   ├── Imports/         # Compras internacionais
│   ├── Preorders/       # Pré-vendas
│   ├── Profile/         # Perfil e senha
│   ├── Purchases/       # Compras presenciais
│   └── Tenancy/         # Isolamento dos dados por conta
├── Database/Migrations/ # Esquema do banco de dados
└── Views/               # Interfaces PHP/Bootstrap
public/                  # Única raiz pública do servidor web
writable/uploads/        # Uploads locais
```

## Produção

Configure Apache ou Nginx para apontar somente para `public/`. Nunca exponha `app/`, `writable/`, `vendor/` ou `.env` pela web.

Use `CI_ENVIRONMENT = production`, uma chave de criptografia exclusiva e um usuário de banco com privilégios mínimos. Para os recursos de e-mail do Shield, configure o provedor em `app/Config/Email.php` ou por variáveis de ambiente.

## Licença

Licenciado sob a [MIT License](LICENSE), exceto arquivos de terceiros sob suas respectivas licenças.
