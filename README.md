# Sistema de Integração com Subadquirentes - Laravel

Sistema de integração com subadquirentes de pagamento (gateways de PIX e saques) com arquitetura multi-tenant onde cada usuário pode usar subadquirentes diferentes.

## 📋 Requisitos Técnicos

- PHP 8.2+
- Laravel 12.38.1
- MySQL/PostgreSQL
- Composer
- Redis (recomendado para produção, para filas e cache)

## 🚀 Instalação

1. Clone o repositório e instale as dependências: `composer install`

2. Configure o arquivo `.env` com as credenciais do banco de dados:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel_test
   DB_USERNAME=root
   DB_PASSWORD=
   
   # Redis (recomendado para produção)
   QUEUE_CONNECTION=redis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   REDIS_PASSWORD=null
   REDIS_DB=0
   ```

3. Execute as migrations: `php artisan migrate`

4. Execute os seeders: `php artisan db:seed`

   Isso criará:
   - SubadqA e SubadqB (subadquirentes)
   - 3 usuários clientes (clientea@example.com, clienteb@example.com, clientec@example.com)
   - 1 usuário admin (admin@super.com / Admin@123)

5. Gere a chave da aplicação: `php artisan key:generate`

6. **Inicie o Laravel Horizon** (gerenciador de filas com auto-scaling):
   ```bash
   php artisan horizon
   ```
   
   **Nota:** O Horizon gerencia automaticamente os workers. Acesse o dashboard em `http://localhost:8000/horizon`

7. Inicie o servidor: `php artisan serve`

## 🔐 Autenticação

O sistema usa Laravel Sanctum para autenticação via API. 

**Endpoint:** `POST /api/login`

**Usuários de teste:**
- `clientea@example.com` / `password` (SubadqA)
- `clienteb@example.com` / `password` (SubadqB)
- `clientec@example.com` / `password` (SubadqA)
- `admin@super.com` / `Admin@123` (Admin)

### Como usar o token:

1. **Via Swagger UI:**
   - Acesse `http://localhost:8000/api/documentation`
   - Execute o endpoint `/api/login` com suas credenciais
   - Copie o token retornado no campo `data.token`
   - Clique no botão **"Authorize"** no topo da página
   - Cole o token no campo (sem o prefixo "Bearer")
   - Agora você pode testar os endpoints protegidos

2. **Via cURL/Postman:**
   - Execute `POST /api/login` e copie o token da resposta
   - Use no header: `Authorization: Bearer {token}`

**Importante:** O token é dinâmico e deve ser obtido através do endpoint `/api/login`. Não use tokens de exemplo.

## 📡 API Endpoints

**Base URL:** `http://localhost:8000/api`

### Documentação Swagger/OpenAPI

Acesse a documentação interativa em: `http://localhost:8000/api/documentation`

A documentação inclui todos os endpoints, exemplos de requisições/respostas, validações e permite testar diretamente no navegador.

**Como usar:**
1. Primeiro, execute o endpoint `/api/login` para obter um token
2. Clique no botão **"Authorize"** no topo da página Swagger
3. Cole o token obtido do login (sem o prefixo "Bearer")
4. Agora você pode testar os endpoints protegidos (`/api/pix`, `/api/withdraw`)

Para regenerar após alterações: `php artisan l5-swagger:generate`

### Endpoints Disponíveis

- **POST** `/api/login` - Autenticação e obtenção de token
- **POST** `/api/logout` - Revogar token atual
- **POST** `/api/pix` - Criar transação PIX
- **POST** `/api/withdraw` - Criar transação de saque

Todos os endpoints (exceto login) requerem autenticação via Bearer Token.

## 🔄 Fluxo de Transação

1. Usuário solicita PIX/Saque via API
2. Sistema identifica o subadquirente do usuário
3. Envia requisição para API mock do subadquirente
4. Registra transação com status `PENDING`
5. Dispara Job para simular webhook após 5-10 segundos
6. Webhook atualiza status para `CONFIRMED`/`PAID`

## 🏗️ Arquitetura

### Estrutura Principal

- **Contracts/SubacquirerInterface.php** - Interface para subadquirentes
- **Services/SubacquirerService.php** - Serviço principal de gerenciamento
- **Services/Subacquirers/GenericSubacquirer.php** - Implementação genérica para todos os subadquirentes
- **Jobs/** - Processamento assíncrono de webhooks
- **Models/** - Models de transações, usuários e subadquirentes

### Extensibilidade

O sistema usa uma implementação genérica (`GenericSubacquirer`) que funciona para todos os subadquirentes. SubadqA e SubadqB são apenas registros na tabela `subacquirers` com diferentes URLs de API.

Para adicionar um novo subadquirente, basta adicionar um registro na tabela `subacquirers`. Se precisar de comportamento específico, crie uma classe customizada implementando `SubacquirerInterface` e registre no `SubacquirerService`.

## 📊 Banco de Dados

### Tabelas Principais

- `users` - Usuários do sistema
- `subacquirers` - Subadquirentes disponíveis
- `pix_transactions` - Transações PIX
- `withdraw_transactions` - Transações de saque
- `jobs` - Fila de jobs (para webhooks)

### Status de Transações

**PIX:** `PENDING`, `CONFIRMED`, `FAILED`, `CANCELLED`

**Saque:** `PENDING`, `PAID`, `FAILED`, `CANCELLED`

## 🔧 Configuração

### Filas e Redis

O sistema utiliza **Laravel Horizon** para gerenciamento dinâmico de workers com auto-scaling.

**Configuração do Redis:**

O sistema usa **Predis** (biblioteca PHP pura) por padrão, não requer extensão PHP Redis.

**1. Instale o servidor Redis:**

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

**macOS (via Homebrew):**
```bash
brew install redis
brew services start redis
```

**2. Configure no `.env`:**
```env
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0
```

**3. Verifique se o Redis está rodando:**
```bash
redis-cli ping
# Deve retornar: PONG
```

**Nota:** Se preferir usar a extensão `phpredis` (mais rápida), instale a extensão PHP e configure `REDIS_CLIENT=phpredis`.

**Configuração do Horizon:**

O Horizon está configurado para:
- **Filas processadas:** `transactions` (prioridade) e `webhooks`
- **Auto-scaling:** 3-10 workers em produção, 2-5 em desenvolvimento
- **Balanceamento:** Automático baseado em tempo de espera
- **Retry:** 3 tentativas com backoff exponencial (5s, 10s, 30s)

**Iniciar o Horizon:**

```bash
php artisan horizon
```

**Acessar o Dashboard:**

Após iniciar o Horizon, acesse: `http://localhost:8000/horizon`

**Nota:** Para produção, configure o Horizon como serviço usando Supervisor ou systemd para garantir que ele sempre esteja rodando.

### Logging

Todos os eventos importantes são registrados em `storage/logs/laravel.log`:
- Requisições aos subadquirentes
- Respostas dos subadquirentes
- Processamento de webhooks
- Erros e exceções

## 🚨 Tratamento de Erros

- Validação de dados de entrada
- Tratamento de erros de API dos subadquirentes
- Retry automático (3 tentativas com backoff exponencial: 5s, 10s, 30s)
- Locks para evitar processamento duplicado de webhooks
- Logging detalhado

### Workaround para Postman Mock

O sistema implementa um fallback para problemas conhecidos do Postman Mock (`invalid_amount` e `mockRequestNotFoundError`). Quando esses erros ocorrem, o sistema simula uma resposta de sucesso e registra um warning no log, permitindo que a aplicação continue funcionando.

## 📈 Performance

- Suporta 3+ requisições/segundo
- **Laravel Horizon** com auto-scaling dinâmico de workers (3-10 workers)
- **Processamento assíncrono** de transações (PIX e Withdraw processados em background)
- Processamento assíncrono de webhooks com delay configurável (5-10 segundos)
- Jobs executados em filas dedicadas (`transactions` e `webhooks`) via Redis
- **Rate limiting** configurado (200 requisições/minuto por usuário autenticado)
- Locks distribuídos para evitar processamento duplicado
- Retry exponencial para falhas temporárias (3 tentativas: 5s, 10s, 30s)
- Índices otimizados no banco de dados
- Dashboard Horizon para monitoramento em tempo real

## 🔒 Segurança

- Autenticação via Laravel Sanctum
- Validação de dados de entrada
- Proteção contra SQL Injection (Eloquent ORM)
- Logs de auditoria

## 📚 Recursos Adicionais

- [Documentação Laravel](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Laravel Queues](https://laravel.com/docs/queues)

---

Desenvolvido com ❤️ usando Laravel
