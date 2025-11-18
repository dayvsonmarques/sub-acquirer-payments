<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novo Saque - {{ config('app.name') }}</title>
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
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Criar Nova Transação de Saque</h2>

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('client-area.withdraw.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="subacquirer_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Subadquirente *
                    </label>
                    <select name="subacquirer_id" id="subacquirer_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione um subadquirente</option>
                        @foreach($subacquirers as $subacquirer)
                            <option value="{{ $subacquirer->id }}" {{ old('subacquirer_id') == $subacquirer->id ? 'selected' : '' }}>
                                {{ $subacquirer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('subacquirer_id')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Valor (R$) *
                    </label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                        value="{{ old('amount') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0.00">
                    @error('amount')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="bank_code" class="block text-sm font-medium text-gray-700 mb-2">
                        Código do Banco *
                    </label>
                    <input type="text" name="bank_code" id="bank_code" maxlength="10" required
                        value="{{ old('bank_code') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ex: 001">
                    @error('bank_code')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="agency" class="block text-sm font-medium text-gray-700 mb-2">
                        Agência *
                    </label>
                    <input type="text" name="agency" id="agency" maxlength="20" required
                        value="{{ old('agency') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ex: 1234">
                    @error('agency')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="account" class="block text-sm font-medium text-gray-700 mb-2">
                        Conta *
                    </label>
                    <input type="text" name="account" id="account" maxlength="20" required
                        value="{{ old('account') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ex: 56789-0">
                    @error('account')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="account_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Conta *
                    </label>
                    <select name="account_type" id="account_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="checking" {{ old('account_type') == 'checking' ? 'selected' : '' }}>Conta Corrente</option>
                        <option value="savings" {{ old('account_type') == 'savings' ? 'selected' : '' }}>Conta Poupança</option>
                    </select>
                    @error('account_type')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="account_holder_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome do Titular *
                    </label>
                    <input type="text" name="account_holder_name" id="account_holder_name" maxlength="255" required
                        value="{{ old('account_holder_name') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ex: João Silva">
                    @error('account_holder_name')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="account_holder_document" class="block text-sm font-medium text-gray-700 mb-2">
                        CPF/CNPJ do Titular *
                    </label>
                    <input type="text" name="account_holder_document" id="account_holder_document" maxlength="20" required
                        value="{{ old('account_holder_document') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ex: 12345678900">
                    @error('account_holder_document')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição
                    </label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Descrição opcional da transação">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('client-area.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Criar Saque
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

