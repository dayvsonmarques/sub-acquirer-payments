<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalhes PIX - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8">
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

    <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4">
            <a href="{{ route('client-area.index') }}" class="text-blue-600 hover:text-blue-900 text-base font-medium">
                ← Voltar para transações
            </a>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-4 sm:px-6 py-4 bg-green-50 border-b border-green-200">
                <h2 class="text-2xl font-semibold text-gray-800">Detalhes da Transação PIX</h2>
            </div>

            <div class="px-4 sm:px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Informações Básicas</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-base font-medium text-gray-500">ID da Transação</dt>
                                <dd class="mt-1 text-base text-gray-900 break-all">{{ $pixTransaction->transaction_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">ID Externo</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $pixTransaction->external_id ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Valor</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">R$ {{ number_format($pixTransaction->amount, 2, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full
                                        {{ $pixTransaction->status === 'CONFIRMED' ? 'bg-green-100 text-green-800' : 
                                           ($pixTransaction->status === 'PROCESSING' ? 'bg-blue-100 text-blue-800' :
                                           ($pixTransaction->status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' : 
                                            'bg-red-100 text-red-800')) }}">
                                        {{ $pixTransaction->status }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Chave PIX</dt>
                                <dd class="mt-1 text-base text-gray-900 break-all">{{ $pixTransaction->pix_key }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Tipo de Chave</dt>
                                <dd class="mt-1 text-base text-gray-900 uppercase">{{ $pixTransaction->pix_key_type }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Informações Adicionais</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-base font-medium text-gray-500">Subadquirente</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $pixTransaction->subacquirer->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Cliente</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $pixTransaction->user->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $pixTransaction->user->email ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Data de Criação</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $pixTransaction->created_at->format('d/m/Y H:i:s') }}</dd>
                            </div>
                            @if($pixTransaction->confirmed_at)
                            <div>
                                <dt class="text-base font-medium text-gray-500">Data de Confirmação</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $pixTransaction->confirmed_at->format('d/m/Y H:i:s') }}</dd>
                            </div>
                            @endif
                            @if($pixTransaction->description)
                            <div>
                                <dt class="text-base font-medium text-gray-500">Descrição</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $pixTransaction->description }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                @if($pixTransaction->request_data)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Dados da Requisição</h3>
                    <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ json_encode($pixTransaction->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
                @endif

                @if($pixTransaction->response_data)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Resposta do Subadquirente</h3>
                    <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ json_encode($pixTransaction->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
                @endif

                @if($pixTransaction->webhook_data)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Dados do Webhook</h3>
                    <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ json_encode($pixTransaction->webhook_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

