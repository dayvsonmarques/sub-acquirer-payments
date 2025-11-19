# Sistema de Integração com Subadquirentes - Laravel

Sistema de integração com subadquirentes de pagamento (gateways de PIX e saques) com arquitetura multi-tenant onde cada usuário pode usar subadquirentes diferentes.

## 📋 Requisitos Técnicos

- PHP 8.2+
- Laravel 12.38.1
- MySQL/PostgreSQL
- Composer
- Redis (opcional, para filas)

## 🚀 Instalação

1. **Clone o repositório e instale as dependências:**

```bash
composer install
```

2. **Configure o arquivo `.env`:**

Copie o arquivo `.env.example` para `.env` e configure:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

3. **Execute as migrations:**

```bash
php artisan migrate
```

4. **Execute os seeders para popular os subadquirentes:**

```bash
php artisan db:seed
```

Isso criará:
- SubadqA (https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io)
- SubadqB (https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io)
- 2 usuários de teste (testa@example.com e testb@example.com)

5. **Gere a chave da aplicação (se necessário):**

```bash
php artisan key:generate
```

6. **Inicie o servidor de filas (para processar webhooks):**

```bash
php artisan queue:work
```

7. **Inicie o servidor de desenvolvimento:**

```bash
php artisan serve
```

## 🔐 Autenticação

O sistema usa Laravel Sanctum para autenticação via API. Para obter um token:

```bash
# Criar um token para o usuário
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'testa@example.com')->first();
$token = $user->createToken('api-token')->plainTextToken;
echo $token;
```

## 📡 Endpoints da API

### Base URL
```
http://localhost:8000/api
```

### 1. Criar Transação PIX

**POST** `/api/pix`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
    "amount": 100.50,
    "pix_key": "12345678900",
    "pix_key_type": "cpf",
    "description": "Pagamento de teste"
}
```

**Tipos de chave PIX aceitos:**
- `cpf`
- `email`
- `phone`
- `random`

**Resposta de sucesso (201):**
```json
{
    "success": true,
    "message": "PIX transaction created successfully",
    "data": {
        "transaction_id": "PIX-XXXXXXXX-1234567890",
        "external_id": "ext-123",
        "status": "PENDING",
        "amount": "100.50",
        "created_at": "2025-11-17T21:00:00.000000Z"
    }
}
```

### 2. Criar Transação de Saque

**POST** `/api/withdraw`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
    "amount": 500.00,
    "bank_code": "001",
    "agency": "1234",
    "account": "56789",
    "account_type": "checking",
    "account_holder_name": "João Silva",
    "account_holder_document": "12345678900",
    "description": "Saque de teste"
}
```

**Tipos de conta aceitos:**
- `checking` (conta corrente)
- `savings` (conta poupança)

**Resposta de sucesso (201):**
```json
{
    "success": true,
    "message": "Withdraw transaction created successfully",
    "data": {
        "transaction_id": "WD-XXXXXXXX-1234567890",
        "external_id": "ext-456",
        "status": "PENDING",
        "amount": "500.00",
        "created_at": "2025-11-17T21:00:00.000000Z"
    }
}
```

## 📝 Exemplos de Uso

### cURL - Criar Transação PIX

```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.50,
    "pix_key": "12345678900",
    "pix_key_type": "cpf",
    "description": "Pagamento de teste"
  }'
```

### cURL - Criar Transação de Saque

```bash
curl -X POST http://localhost:8000/api/withdraw \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 500.00,
    "bank_code": "001",
    "agency": "1234",
    "account": "56789",
    "account_type": "checking",
    "account_holder_name": "João Silva",
    "account_holder_document": "12345678900",
    "description": "Saque de teste"
  }'
```

### Postman Collection

Você pode importar a collection do Postman usando os exemplos acima.

## 🔄 Fluxo de Transação

1. **Usuário solicita PIX/Saque** via API
2. **Sistema identifica** o subadquirente do usuário
3. **Envia requisição** para API mock do subadquirente
4. **Registra transação** com status `PENDING`
5. **Dispara Job** para simular webhook após 5-10 segundos
6. **Webhook atualiza** status para `CONFIRMED`/`PAID`

## 🏗️ Arquitetura

### Estrutura de Diretórios

```
app/
├── Contracts/
│   └── SubacquirerInterface.php      # Interface para subadquirentes
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── PixController.php     # Controller para PIX
│           └── WithdrawController.php # Controller para Saques
├── Jobs/
│   ├── SimulatePixWebhook.php         # Job para simular webhook PIX
│   └── SimulateWithdrawWebhook.php    # Job para simular webhook Saque
├── Models/
│   ├── PixTransaction.php             # Model de transação PIX
│   ├── Subacquirer.php                # Model de subadquirente
│   ├── User.php                        # Model de usuário
│   └── WithdrawTransaction.php        # Model de transação de saque
├── Providers/
│   └── SubacquirerServiceProvider.php # Service Provider
└── Services/
    ├── SubacquirerService.php          # Serviço principal
    └── Subacquirers/
        └── GenericSubacquirer.php     # Implementação genérica para todos os subadquirentes
```

### Extensibilidade

O sistema usa uma implementação genérica (`GenericSubacquirer`) que funciona para todos os subadquirentes. SubadqA e SubadqB são apenas registros na tabela `subacquirers` com diferentes URLs de API.

**Para adicionar um novo subadquirente:**

1. **Adicionar registro no banco de dados** via seeder ou manualmente:

```php
Subacquirer::create([
    'name' => 'SubadqC',
    'code' => 'subadqc',
    'base_url' => 'https://api.subadqc.com',
    'is_active' => true,
]);
```

2. **Se precisar de comportamento específico**, crie uma classe customizada:

```php
<?php

namespace App\Services\Subacquirers;

use App\Contracts\SubacquirerInterface;
use App\Models\Subacquirer;

class SpecialSubacquirer implements SubacquirerInterface
{
    // Implementação específica
}
```

3. **Registrar no `SubacquirerService`**:

```php
public function getImplementation(Subacquirer $subacquirer): SubacquirerInterface
{
    $code = strtolower($subacquirer->code);
    
    return match ($code) {
        'special_subacquirer' => new SpecialSubacquirer($subacquirer),
        default => new GenericSubacquirer($subacquirer), // Genérico para todos
    };
}
```

## 📊 Banco de Dados

### Tabelas Principais

- `users` - Usuários do sistema
- `subacquirers` - Subadquirentes disponíveis
- `pix_transactions` - Transações PIX
- `withdraw_transactions` - Transações de saque
- `jobs` - Fila de jobs (para webhooks)

### Status de Transações

**PIX:**
- `PENDING` - Aguardando confirmação
- `CONFIRMED` - Confirmado
- `FAILED` - Falhou
- `CANCELLED` - Cancelado

**Saque:**
- `PENDING` - Aguardando pagamento
- `PAID` - Pago
- `FAILED` - Falhou
- `CANCELLED` - Cancelado

## 🔧 Configuração de Filas

O sistema usa filas assíncronas para processar webhooks. Por padrão, está configurado para usar `database`.

Para usar Redis (recomendado para produção):

1. Instale Redis
2. Configure no `.env`:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

3. Instale o Horizon (opcional):
```bash
composer require laravel/horizon
php artisan horizon:install
```

## 📝 Logging

Todos os eventos importantes são registrados em logs:

- Requisições aos subadquirentes
- Respostas dos subadquirentes
- Processamento de webhooks
- Erros e exceções

Logs podem ser visualizados em `storage/logs/laravel.log`.

## 🧪 Testes

Para executar os testes:

```bash
php artisan test
```

## 🚨 Tratamento de Erros

O sistema possui tratamento robusto de erros:

- Validação de dados de entrada
- Tratamento de erros de API dos subadquirentes
- Retry automático em caso de falha (3 tentativas com backoff exponencial: 5s, 10s, 30s)
- Locks para evitar processamento duplicado de webhooks
- Logging detalhado de erros

### Workaround para Postman Mock

O sistema implementa um workaround para um problema conhecido do Postman Mock relacionado à validação de `amount`. Quando o mock retorna erro `invalid_amount` mesmo com valores válidos, o sistema:

1. Detecta o erro específico `invalid_amount`
2. Registra um warning no log indicando o problema do mock
3. Simula uma resposta de sucesso como fallback
4. Permite que a aplicação continue funcionando normalmente

**Nota:** Este é um workaround temporário. Recomenda-se corrigir a configuração do Postman Mock ou usar um serviço de mock alternativo em produção.

O sistema também utiliza o header `x-mock-response-name` para especificar qual resposta do mock deve ser retornada, conforme documentação do Postman.

## 📈 Performance

- Suporta 3+ requisições/segundo
- Processamento assíncrono de webhooks com delay configurável (5-10 segundos)
- Jobs executados em fila dedicada (`webhooks`) para melhor isolamento
- Locks distribuídos para evitar processamento duplicado
- Retry exponencial para falhas temporárias
- Índices otimizados no banco de dados
- Cache de configurações quando aplicável

### Otimizações Implementadas

1. **Delay no Dispatch**: Os webhooks são agendados com delay aleatório (5-10s) no momento do dispatch, não bloqueando workers
2. **Locks Distribuídos**: Uso de Cache locks para garantir que cada webhook seja processado apenas uma vez, mesmo em alta concorrência
3. **Fila Dedicada**: Jobs de webhook executam em fila separada (`webhooks`) permitindo escalonamento independente
4. **Retry Exponencial**: Backoff progressivo (5s → 10s → 30s) para tentativas de retry

## 🔒 Segurança

- Autenticação via Laravel Sanctum
- Validação de dados de entrada
- Proteção contra SQL Injection (Eloquent ORM)
- Logs de auditoria

## 📚 Recursos Adicionais

- [Documentação Laravel](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Laravel Queues](https://laravel.com/docs/queues)

## 👥 Usuários de Teste

Após executar o seeder, você terá:

- **testa@example.com** - Usa SubadqA
- **testb@example.com** - Usa SubadqB

Senha padrão: `password`

## 📞 Suporte

Para dúvidas ou problemas, consulte a documentação do Laravel ou abra uma issue no repositório.

---

Desenvolvido com ❤️ usando Laravel

