# SwitchPay API

API RESTful de gerenciamento de pagamentos multi-gateway desenvolvida com Laravel. Suporta múltiplos gateways de pagamento com fallback automático por ordem de prioridade.

---

## Requisitos

- Docker
- Docker Compose

---

## Instalação

```bash
# 1. Clone o repositório
git clone git@github.com:AlanDeveloper/switchpay-api.git
cd switchpay

# 2. Copie o arquivo de ambiente
cp .env.example .env

# 3. Suba os containers
docker compose up -d --build

# 4. Instale as dependências
docker exec switchpay_app composer install

# 5. Gere a chave da aplicação
docker exec switchpay_app php artisan key:generate

# 6. Execute as migrations
docker exec switchpay_app php artisan migrate

# 7. Execute os seeders
docker exec switchpay_app php artisan db:seed
```

A API estará disponível em `http://localhost:5000`.

---

## Usuários Padrão

O seeder cria automaticamente um usuário para cada role:

| Role    | E-mail               | Senha    |
|---------|----------------------|----------|
| Admin   | admin@admin.com      | password |
| Manager | manager@admin.com    | password |
| Finance | finance@admin.com    | password |
| User    | user@admin.com       | password |

---

## Variáveis de Ambiente

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:5000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

# PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=db
DB_DATABASE=switchpay
DB_USERNAME=switchpay
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

GATEWAY1_API_URL=http://gateway_mock:3001
GATEWAY1_API_EMAIL=dev@betalent.tech
GATEWAY1_API_TOKEN=FEC9BB078BF338F464F96B48089EB498

GATEWAY2_API_URL=http://gateway_mock:3002
GATEWAY2_API_SECRET=3d15e8ed6131446ea7e3456728b1211f
GATEWAY2_API_TOKEN=tk_f2198cc671b5289fa856
```

---

## Executando os Testes

Atenção nesse comando, ele irá limpar o db para execução dos testes!

```bash
docker exec switchpay_app php artisan test
```

---

## Permissões por Role

| Recurso                      | Admin | Manager | Finance | User |
|------------------------------|:-----:|:-------:|:-------:|:----:|
| CRUD Usuários                | ✅    | ✅      | ❌      | ❌   |
| CRUD Produtos                | ✅    | ✅      | ✅      | ❌   |
| Visualizar Produtos          | ✅    | ✅      | ✅      | ✅   |
| Visualizar Clientes          | ✅    | ✅      | ✅      | ✅   |
| CRUD Clientes                | ✅    | ✅      | ✅      | ❌   |
| Visualizar Transações        | ✅    | ✅      | ✅      | ❌   |
| Reembolso de Transação       | ✅    | ❌      | ✅      | ❌   |
| Gerenciar Gateways           | ✅    | ❌      | ❌      | ❌   |

---

## Rotas

### Públicas

| Método | Endpoint               | Descrição                      |
|--------|------------------------|--------------------------------|
| POST   | /api/login             | Autenticar usuário             |
| POST   | /api/forgotPassword    | Solicitar redefinição de senha |
| POST   | /api/resetPassword     | Redefinir senha                |
| POST   | /api/transaction       | Realizar uma compra            |

### Privadas (requer Bearer token)

#### Autenticação
| Método | Endpoint      | Descrição       |
|--------|---------------|-----------------|
| POST   | /api/logout   | Encerrar sessão |
| GET    | /api/profile  | Perfil próprio  |

#### Gateways
| Método | Endpoint                    | Descrição                              |
|--------|-----------------------------|----------------------------------------|
| GET    | /api/gateway                | Listar gateways                        |
| GET    | /api/gateway/{id}           | Detalhes do gateway                    |
| PATCH  | /api/gateway/{id}           | Ativar/desativar ou alterar prioridade |
| GET    | /api/gateway/{id}/logs      | Logs do gateway                        |

#### Usuários
| Método | Endpoint          | Descrição           |
|--------|-------------------|---------------------|
| GET    | /api/user         | Listar usuários     |
| GET    | /api/user/{id}    | Detalhes do usuário |
| POST   | /api/user         | Criar usuário       |
| PUT    | /api/user/{id}    | Atualizar usuário   |
| DELETE | /api/user/{id}    | Remover usuário     |

#### Produtos
| Método | Endpoint            | Descrição           |
|--------|---------------------|---------------------|
| GET    | /api/product        | Listar produtos     |
| GET    | /api/product/{id}   | Detalhes do produto |
| POST   | /api/product        | Criar produto       |
| PUT    | /api/product/{id}   | Atualizar produto   |
| DELETE | /api/product/{id}   | Remover produto     |

#### Clientes
| Método | Endpoint           | Descrição                          |
|--------|--------------------|------------------------------------|
| GET    | /api/client        | Listar clientes                    |
| GET    | /api/client/{id}   | Detalhes do cliente e suas compras |
| POST   | /api/client        | Criar cliente                      |
| PUT    | /api/client/{id}   | Atualizar cliente                  |
| DELETE | /api/client/{id}   | Remover cliente                    |

#### Transações
| Método | Endpoint                     | Descrição             |
|--------|------------------------------|-----------------------|
| GET    | /api/transaction             | Listar transações     |
| GET    | /api/transaction/{id}        | Detalhes da transação |
| POST   | /api/transaction/{id}/refund | Realizar reembolso    |

---

## Filtros e Paginação

Todas as rotas de listagem suportam paginação via query params padrão do Laravel:

| Param      | Descrição                        | Padrão |
|------------|----------------------------------|--------|
| `page`     | Número da página                 | 1      |
| `per_page` | Quantidade de itens por página   | 15     |

#### Filtros disponíveis

| Rota                  | Param        | Descrição                          |
|-----------------------|--------------|------------------------------------|
| GET /api/transaction  | `client_id`  | Filtra transações por cliente      |
| GET /api/user         | `name`       | Filtra usuários por nome           |
| GET /api/user         | `email`      | Filtra usuários por e-mail         |
| GET /api/client       | `name`       | Filtra clientes por nome           |
| GET /api/client       | `email`      | Filtra clientes por e-mail         |
| GET /api/gateway      | `name`       | Filtra gateways por nome           |
| GET /api/product      | `name`       | Filtra produtos por nome           |

## Comportamento dos Gateways

Os pagamentos são processados pelos gateways em ordem de prioridade. Se o primeiro falhar, o próximo é tentado automaticamente. A resposta só indica falha se **todos** os gateways falharem ou se não houver nenhum disponível.

Cada tentativa é registrada e pode ser consultada via `/api/gateway/{id}/logs`.

---

## Decisões Técnicas

- **Roles** gerenciadas via `spatie/laravel-permission`
- **Produtos** possuem o campo price invés de amount e também possuem available_amount indicando a quantidade disponível
- **Novos gateways** podem ser adicionados seguindo os passos abaixo:
  1. Adicionar as credenciais no `.env` e em `config/services.php` na chave `gateways`
  2. Declarar o novo gateway no Enum `App\Enums\Gateway` com sua chave e mapeamento para a classe de serviço
  3. Criar a classe de serviço em `App\Services` implementando os métodos `processPayment` e `refundPayment`
  4. Adicionar o gateway no banco via seeder
- **Emails** tanto na criação de usuário quanto na recuperação de senha será enviado um email ao usuário, como não estou utilizando nenhum provider é possível ver o email em storage/logs/laravel.log o qual irá fornecer ou a senha ou o token para recuperação
- **Paginação** gerenciada automaticamente pelo `paginate()` do Eloquent, que fornece metadados como `total`, `last_page`, `current_page` e `next_page_url` sem implementação adicional
- **Tratamento de erros** centralizado no `bootstrap/app.php` com respostas JSON padronizadas para todas as exceções, eliminando a necessidade de blocos try/catch individuais nos controllers
