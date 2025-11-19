<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalhes Saque - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0">
                    <a href="{{ route('home') }}" class="text-xl font-semibold text-gray-800">Pix / Saques</a>
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
            <div class="px-4 sm:px-6 py-4 bg-blue-50 border-b border-blue-200">
                <h2 class="text-2xl font-semibold text-gray-800">Detalhes da Transação de Saque</h2>
            </div>

            <div class="px-4 sm:px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Informações Básicas</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-base font-medium text-gray-500">ID da Transação</dt>
                                <dd class="mt-1 text-base text-gray-900 break-all">{{ $withdrawTransaction->transaction_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">ID Externo</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->external_id ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Valor</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">R$ {{ number_format($withdrawTransaction->amount, 2, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full
                                        {{ $withdrawTransaction->status === 'PAID' ? 'bg-green-100 text-green-800' : 
                                           ($withdrawTransaction->status === 'PROCESSING' ? 'bg-blue-100 text-blue-800' :
                                           ($withdrawTransaction->status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' : 
                                            'bg-red-100 text-red-800')) }}">
                                        {{ $withdrawTransaction->status }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Código do Banco</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->bank_code }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Agência</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->agency }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Conta</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->account }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Tipo de Conta</dt>
                                <dd class="mt-1 text-base text-gray-900 capitalize">{{ $withdrawTransaction->account_type }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Informações Adicionais</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-base font-medium text-gray-500">Titular da Conta</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->account_holder_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">CPF/CNPJ</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->account_holder_document }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Subadquirente</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->subacquirer->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Cliente</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->user->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->user->email ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-base font-medium text-gray-500">Data de Criação</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->created_at->format('d/m/Y H:i:s') }}</dd>
                            </div>
                            @if($withdrawTransaction->paid_at)
                            <div>
                                <dt class="text-base font-medium text-gray-500">Data de Pagamento</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->paid_at->format('d/m/Y H:i:s') }}</dd>
                            </div>
                            @endif
                            @if($withdrawTransaction->description)
                            <div>
                                <dt class="text-base font-medium text-gray-500">Descrição</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $withdrawTransaction->description }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                @if($withdrawTransaction->request_data)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Dados da Requisição</h3>
                    <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ json_encode($withdrawTransaction->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
                @endif

                @if($withdrawTransaction->response_data)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Resposta do Subadquirente</h3>
                    <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ json_encode($withdrawTransaction->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
                @endif

                @if($withdrawTransaction->webhook_data)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-700 uppercase mb-4">Dados do Webhook</h3>
                    <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ json_encode($withdrawTransaction->webhook_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

