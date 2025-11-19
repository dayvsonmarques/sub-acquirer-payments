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
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden w-full">
                <div class="px-3 sm:px-6 py-3 sm:py-4 bg-green-50 border-b border-green-200">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Transações PIX</h3>
                </div>
                <div class="overflow-x-auto w-full">
                    <table class="w-full min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-center text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap leading-loose">ID</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-center text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap leading-loose">Valor</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-center text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap leading-loose">Status</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-center text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap leading-loose">Data</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($pixTransactions as $transaction)
                                <tr class="hover:bg-blue-50 transition-colors cursor-pointer active:bg-blue-100" onclick="window.location.href='{{ route('client-area.pix.show', $transaction->id) }}'">
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-center text-sm sm:text-base text-gray-900 break-all leading-loose">{{ strlen($transaction->transaction_id) > 15 ? substr($transaction->transaction_id, 0, 15) . '...' : $transaction->transaction_id }}</td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-center text-sm sm:text-base text-gray-900 whitespace-nowrap font-medium leading-loose">R$ {{ number_format($transaction->amount, 2, ',', '.') }}</td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-center text-sm sm:text-base leading-loose">
                                        <span class="px-3 py-1 inline-flex items-center justify-center text-xs sm:text-sm leading-7 font-semibold rounded-full whitespace-nowrap
                                            {{ $transaction->status === 'CONFIRMED' ? 'bg-green-100 text-green-800' : 
                                               ($transaction->status === 'PROCESSING' ? 'bg-blue-100 text-blue-800' :
                                               ($transaction->status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' : 
                                                'bg-red-100 text-red-800')) }}">
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-center text-sm sm:text-base text-gray-600 whitespace-nowrap leading-loose">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-xs sm:text-sm text-gray-500">Nenhuma transação PIX encontrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($pixTransactions->hasPages())
                    <div class="px-2 sm:px-4 py-2 sm:py-3 border-t border-gray-200">
                        {{ $pixTransactions->appends(['withdrawPage' => request('withdrawPage')])->links() }}
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden w-full">
                <div class="px-3 sm:px-6 py-3 sm:py-4 bg-blue-50 border-b border-blue-200">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Transações de Saque</h3>
                </div>
                <div class="overflow-x-auto w-full">
                    <table class="w-full min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-center text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap leading-loose">ID</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-center text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap leading-loose">Valor</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-center text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap leading-loose">Status</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 text-center text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap leading-loose">Data</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($withdrawTransactions as $transaction)
                                <tr class="hover:bg-blue-50 transition-colors cursor-pointer active:bg-blue-100" onclick="window.location.href='{{ route('client-area.withdraw.show', $transaction->id) }}'">
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-center text-sm sm:text-base text-gray-900 break-all leading-relaxed">{{ strlen($transaction->transaction_id) > 15 ? substr($transaction->transaction_id, 0, 15) . '...' : $transaction->transaction_id }}</td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-center text-sm sm:text-base text-gray-900 whitespace-nowrap font-medium leading-relaxed">R$ {{ number_format($transaction->amount, 2, ',', '.') }}</td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-center text-sm sm:text-base leading-relaxed">
                                        <span class="px-3 py-1 inline-flex items-center justify-center text-xs sm:text-sm leading-6 font-semibold rounded-full whitespace-nowrap
                                            {{ $transaction->status === 'PAID' ? 'bg-green-100 text-green-800' : 
                                               ($transaction->status === 'PROCESSING' ? 'bg-blue-100 text-blue-800' :
                                               ($transaction->status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' : 
                                                'bg-red-100 text-red-800')) }}">
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-3 sm:py-4 text-center text-sm sm:text-base text-gray-600 whitespace-nowrap leading-relaxed">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-xs sm:text-sm text-gray-500">Nenhuma transação de Saque encontrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($withdrawTransactions->hasPages())
                    <div class="px-2 sm:px-4 py-2 sm:py-3 border-t border-gray-200">
                        {{ $withdrawTransactions->appends(['pixPage' => request('pixPage')])->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
