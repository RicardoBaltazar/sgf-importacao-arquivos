<x-filament-panels::page>
    <form wire:submit="enviar">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Enviar Arquivo
        </x-filament::button>
    </form>

    @if ($processedData)
    <div class="mt-4">
        <h2 class="text-lg font-medium">Resultado do Processamento</h2>
        <div class="mt-2 bg-gray-100 dark:bg-gray-800 rounded-lg shadow p-4 overflow-auto">
            @if (isset($processedData['error']))
            <div class="text-red-500">{{ $processedData['error'] }}</div>
            @elseif (isset($processedData['success']) && $processedData['success'])
            <div class="text-green-500">
                Importação feita com sucesso, foi importado {{ $processedData['count'] }} registro(s).
            </div>
            @else
            <pre class="text-gray-900 dark:text-gray-100">{{ json_encode($processedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
    </div>
    @endif
</x-filament-panels::page>