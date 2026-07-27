<div class="flex flex-col space-y-2 py-2 text-sm w-full min-w-[350px]">
    @foreach($getRecord()->lines->sortByDesc('debit') as $line)
        <div class="flex justify-between items-start">
            <div class="w-1/2 flex flex-col {{ $line->credit > 0 ? 'pl-6' : '' }}">
                <span class="font-mono text-xs text-gray-500">{{ $line->account->code ?? '' }}</span>
                <span class="font-medium {{ $line->credit > 0 ? 'text-gray-600 dark:text-gray-400' : 'text-gray-900 dark:text-gray-100' }}">
                    {{ $line->account->name ?? '' }}
                </span>
            </div>
            
            <div class="w-1/4 text-right tabular-nums text-gray-700 dark:text-gray-300 pr-4">
                @if($line->debit > 0)
                    Rp {{ number_format($line->debit, 2, ',', '.') }}
                @else
                    <span class="text-gray-400">-</span>
                @endif
            </div>
            
            <div class="w-1/4 text-right tabular-nums text-gray-700 dark:text-gray-300">
                @if($line->credit > 0)
                    Rp {{ number_format($line->credit, 2, ',', '.') }}
                @else
                    <span class="text-gray-400">-</span>
                @endif
            </div>
        </div>
    @endforeach
</div>
