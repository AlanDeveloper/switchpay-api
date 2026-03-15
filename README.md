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
cd switchpay-api

# 2. Copie o arquivo de ambiente
cp .env.example .env

# 3. Suba os containers
docker compose up -d --build
```

A API estará disponível em `http://localhost:8000`.

---

## Usuários Padrão

O seeder cria automaticamente um usuário para cada role:

| Role    | E-mail            | Senha    |
| ------- | ----------------- | -------- |
| Admin   | admin@admin.com   | password |
| Manager | manager@admin.com | password |
| Finance | finance@admin.com | password |
| User    | user@admin.com    | password |

---

## Executando os Testes

Atenção nesse comando, ele irá limpar o db para execução dos testes!

```bash
docker exec switchpay_app php artisan test
```

---

## Permissões por Role

| Recurso                | Admin | Manager | Finance | User  |
| ---------------------- | :---: | :-----: | :-----: | :---: |
| CRUD Usuários          |   ✅   |    ✅    |    ❌    |   ❌   |
| CRUD Produtos          |   ✅   |    ✅    |    ✅    |   ❌   |
| Visualizar Produtos    |   ✅   |    ✅    |    ✅    |   ✅   |
| Visualizar Clientes    |   ✅   |    ✅    |    ✅    |   ✅   |
| CRUD Clientes          |   ✅   |    ✅    |    ✅    |   ❌   |
| Visualizar Transações  |   ✅   |    ✅    |    ✅    |   ❌   |
| Reembolso de Transação |   ✅   |    ❌    |    ✅    |   ❌   |
| Gerenciar Gateways     |   ✅   |    ❌    |    ❌    |   ❌   |

---

## Rotas

### Públicas

| Método | Endpoint            | Descrição                      |
| ------ | ------------------- | ------------------------------ |
| POST   | /api/login          | Autenticar usuário             |
| POST   | /api/forgotPassword | Solicitar redefinição de senha |
| POST   | /api/resetPassword  | Redefinir senha                |
| POST   | /api/transaction    | Realizar uma compra            |

### Privadas (requer Bearer token)

#### Autenticação
| Método | Endpoint     | Descrição       |
| ------ | ------------ | --------------- |
| POST   | /api/logout  | Encerrar sessão |
| GET    | /api/profile | Perfil próprio  |

#### Gateways
| Método | Endpoint               | Descrição                              |
| ------ | ---------------------- | -------------------------------------- |
| GET    | /api/gateway           | Listar gateways                        |
| GET    | /api/gateway/{id}      | Detalhes do gateway                    |
| PATCH  | /api/gateway/{id}      | Ativar/desativar ou alterar prioridade |
| GET    | /api/gateway/{id}/logs | Logs do gateway                        |

#### Usuários
| Método | Endpoint       | Descrição           |
| ------ | -------------- | ------------------- |
| GET    | /api/user      | Listar usuários     |
| GET    | /api/user/{id} | Detalhes do usuário |
| POST   | /api/user      | Criar usuário       |
| PUT    | /api/user/{id} | Atualizar usuário   |
| DELETE | /api/user/{id} | Remover usuário     |

#### Produtos
| Método | Endpoint          | Descrição           |
| ------ | ----------------- | ------------------- |
| GET    | /api/product      | Listar produtos     |
| GET    | /api/product/{id} | Detalhes do produto |
| POST   | /api/product      | Criar produto       |
| PUT    | /api/product/{id} | Atualizar produto   |
| DELETE | /api/product/{id} | Remover produto     |

#### Clientes
| Método | Endpoint         | Descrição                          |
| ------ | ---------------- | ---------------------------------- |
| GET    | /api/client      | Listar clientes                    |
| GET    | /api/client/{id} | Detalhes do cliente e suas compras |
| POST   | /api/client      | Criar cliente                      |
| PUT    | /api/client/{id} | Atualizar cliente                  |
| DELETE | /api/client/{id} | Remover cliente                    |

#### Transações
| Método | Endpoint                     | Descrição             |
| ------ | ---------------------------- | --------------------- |
| GET    | /api/transaction             | Listar transações     |
| GET    | /api/transaction/{id}        | Detalhes da transação |
| POST   | /api/transaction/{id}/refund | Realizar reembolso    |

---

## Filtros e Paginação

Todas as rotas de listagem suportam paginação via query params padrão do Laravel:

| Param      | Descrição                      | Padrão |
| ---------- | ------------------------------ | ------ |
| `page`     | Número da página               | 1      |
| `per_page` | Quantidade de itens por página | 15     |

#### Filtros disponíveis

| Rota                 | Param       | Descrição                     |
| -------------------- | ----------- | ----------------------------- |
| GET /api/transaction | `client_id` | Filtra transações por cliente |
| GET /api/user        | `name`      | Filtra usuários por nome      |
| GET /api/user        | `email`     | Filtra usuários por e-mail    |
| GET /api/client      | `name`      | Filtra clientes por nome      |
| GET /api/client      | `email`     | Filtra clientes por e-mail    |
| GET /api/gateway     | `name`      | Filtra gateways por nome      |
| GET /api/product     | `name`      | Filtra produtos por nome      |

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

---

## Problemas ?

- Docker não consegue identificar os IPs: No meu caso eu tinha alguns containers e redes docker já rodando que acabaram conflitando, precisei dar um down nos containers junto a `docker system prune` e `docker network prune`
