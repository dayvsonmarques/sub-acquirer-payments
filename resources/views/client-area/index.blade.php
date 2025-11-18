<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Área do Cliente - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0">
                    <a href="{{ route('home') }}" class="text-xl font-semibold text-gray-800">Pix / Saques Payload</a>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('client-area.index') }}" class="text-blue-600 hover:text-blue-900 px-3 py-2 rounded-md text-sm font-medium">
                        Área do Cliente
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        Admin
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Simular Transação</h2>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                    @if(session('transaction_data'))
                        <div class="mt-2 text-sm">
                            <strong>ID da Transação:</strong> {{ session('transaction_data.transaction_id') }}<br>
                            <strong>Valor:</strong> R$ {{ number_format(session('transaction_data.amount'), 2, ',', '.') }}<br>
                            <strong>Status:</strong> {{ session('transaction_data.status') }}
                        </div>
                    @endif
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('client-area.process') }}" method="POST" id="transactionForm">
                @csrf

                <div class="mb-4">
                    <label for="subacquirer_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Subadquirente *
                    </label>
                    <select name="subacquirer_id" id="subacquirer_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione um subadquirente</option>
                        @foreach($subacquirers as $subacquirer)
                            <option value="{{ $subacquirer->id }}">{{ $subacquirer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="transaction_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Transação *
                    </label>
                    <select name="transaction_type" id="transaction_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione o tipo</option>
                        <option value="pix">PIX</option>
                        <option value="withdraw">Saque</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Valor (R$) *
                    </label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0.00">
                </div>

                <div id="pixFields" style="display: none;">
                    <div class="mb-4">
                        <label for="pix_key" class="block text-sm font-medium text-gray-700 mb-2">
                            Chave PIX *
                        </label>
                        <input type="text" name="pix_key" id="pix_key"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ex: 12345678900 ou email@exemplo.com">
                    </div>

                    <div class="mb-4">
                        <label for="pix_key_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Chave PIX *
                        </label>
                        <select name="pix_key_type" id="pix_key_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="cpf">CPF</option>
                            <option value="email">Email</option>
                            <option value="phone">Telefone</option>
                            <option value="random">Chave Aleatória</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Descrição
                        </label>
                        <textarea name="description" id="description" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Descrição opcional da transação"></textarea>
                    </div>
                </div>

                <div id="withdrawFields" style="display: none;">
                    <div class="mb-4">
                        <label for="bank_code" class="block text-sm font-medium text-gray-700 mb-2">
                            Código do Banco *
                        </label>
                        <input type="text" name="bank_code" id="bank_code" maxlength="10"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ex: 001">
                    </div>

                    <div class="mb-4">
                        <label for="agency" class="block text-sm font-medium text-gray-700 mb-2">
                            Agência *
                        </label>
                        <input type="text" name="agency" id="agency" maxlength="20"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ex: 1234">
                    </div>

                    <div class="mb-4">
                        <label for="account" class="block text-sm font-medium text-gray-700 mb-2">
                            Conta *
                        </label>
                        <input type="text" name="account" id="account" maxlength="20"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ex: 56789-0">
                    </div>

                    <div class="mb-4">
                        <label for="account_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Conta *
                        </label>
                        <select name="account_type" id="account_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="checking">Conta Corrente</option>
                            <option value="savings">Conta Poupança</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="account_holder_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome do Titular *
                        </label>
                        <input type="text" name="account_holder_name" id="account_holder_name" maxlength="255"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ex: João Silva">
                    </div>

                    <div class="mb-4">
                        <label for="account_holder_document" class="block text-sm font-medium text-gray-700 mb-2">
                            CPF/CNPJ do Titular *
                        </label>
                        <input type="text" name="account_holder_document" id="account_holder_document" maxlength="20"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ex: 12345678900">
                    </div>

                    <div class="mb-4">
                        <label for="withdraw_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Descrição
                        </label>
                        <textarea name="description" id="withdraw_description" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Descrição opcional da transação"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('home') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Processar Transação
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('transaction_type').addEventListener('change', function() {
            const pixFields = document.getElementById('pixFields');
            const withdrawFields = document.getElementById('withdrawFields');
            
            if (this.value === 'pix') {
                pixFields.style.display = 'block';
                withdrawFields.style.display = 'none';
                document.getElementById('pix_key').required = true;
                document.getElementById('pix_key_type').required = true;
                document.getElementById('bank_code').required = false;
                document.getElementById('agency').required = false;
                document.getElementById('account').required = false;
                document.getElementById('account_type').required = false;
                document.getElementById('account_holder_name').required = false;
                document.getElementById('account_holder_document').required = false;
            } else if (this.value === 'withdraw') {
                pixFields.style.display = 'none';
                withdrawFields.style.display = 'block';
                document.getElementById('pix_key').required = false;
                document.getElementById('pix_key_type').required = false;
                document.getElementById('bank_code').required = true;
                document.getElementById('agency').required = true;
                document.getElementById('account').required = true;
                document.getElementById('account_type').required = true;
                document.getElementById('account_holder_name').required = true;
                document.getElementById('account_holder_document').required = true;
            } else {
                pixFields.style.display = 'none';
                withdrawFields.style.display = 'none';
            }
        });
    </script>
</body>
</html>

