<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-100">
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-semibold text-gray-800">Pix / Saques Payload</h1>
                    </div>
                    <div class="flex space-x-4">
                        <a href="{{ route('client-area.index') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            Área do Cliente
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            Admin
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        <div class="flex items-center justify-center min-h-[calc(100vh-4rem)]">
            <h2 class="text-4xl font-semibold text-gray-800">Pix / Saques Payload</h2>
        </div>
    </body>
</html>
