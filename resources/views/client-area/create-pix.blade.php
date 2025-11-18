<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novo PIX - {{ config('app.name') }}</title>
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
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Criar Nova Transação PIX</h2>

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('client-area.pix.store') }}" method="POST">
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
                    <label for="pix_key" class="block text-sm font-medium text-gray-700 mb-2">
                        Chave PIX *
                    </label>
                    <input type="text" name="pix_key" id="pix_key" required
                        value="{{ old('pix_key') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ex: 12345678900 ou email@exemplo.com">
                    @error('pix_key')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="pix_key_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Chave PIX *
                    </label>
                    <select name="pix_key_type" id="pix_key_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="cpf" {{ old('pix_key_type') == 'cpf' ? 'selected' : '' }}>CPF</option>
                        <option value="email" {{ old('pix_key_type') == 'email' ? 'selected' : '' }}>Email</option>
                        <option value="phone" {{ old('pix_key_type') == 'phone' ? 'selected' : '' }}>Telefone</option>
                        <option value="random" {{ old('pix_key_type') == 'random' ? 'selected' : '' }}>Chave Aleatória</option>
                    </select>
                    @error('pix_key_type')
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
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        Criar PIX
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

