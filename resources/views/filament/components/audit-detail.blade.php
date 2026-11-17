<div class="space-y-4">
    @if($record->old_data)
        <div>
            <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Data Lama:</h3>
            <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto">
                <pre class="text-sm text-gray-800 dark:text-gray-200"><code>{{ json_encode($record->old_data, JSON_PRETTY_PRINT) }}</code></pre>
            </div>
        </div>
    @endif

    @if($record->new_data)
        <div>
            <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Data Baru:</h3>
            <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto">
                <pre class="text-sm text-gray-800 dark:text-gray-200"><code>{{ json_encode($record->new_data, JSON_PRETTY_PRINT) }}</code></pre>
            </div>
        </div>
    @endif

    @if(!$record->old_data && !$record->new_data)
        <p class="text-gray-500 italic">Tidak ada detail data yang tersedia untuk aksi ini.</p>
    @endif
</div>
