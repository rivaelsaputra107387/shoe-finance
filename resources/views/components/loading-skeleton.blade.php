{{--
    Loading Skeleton Shimmer Component
    Usage: <x-loading-skeleton target="account_id,start_date" type="table|card|report" rows="5" />
    Props:
      - target : wire:target string (comma-separated Livewire actions/properties)
      - type   : "table" | "card" | "report" (controls skeleton layout shape)
      - rows   : number of shimmer rows (default 5)
      - label  : optional label text below shimmer (default "Memuat data...")
--}}
@props([
    'target' => '',
    'type'   => 'table',
    'rows'   => 5,
    'label'  => 'Memuat data...',
])

<div
    wire:loading.delay
    {{ $target ? "wire:target={$target}" : '' }}
    class="w-full"
>
    <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl p-6">

        @if($type === 'table')
            {{-- Skeleton: Header row --}}
            <div class="animate-pulse space-y-3">
                <div class="flex gap-4 mb-5 pb-4 border-b border-gray-100 dark:border-white/5">
                    @foreach(range(1, 5) as $col)
                        <div class="h-3.5 bg-gray-200 dark:bg-gray-700 rounded flex-1"></div>
                    @endforeach
                </div>
                {{-- Skeleton: Data rows --}}
                @for($i = 0; $i < $rows; $i++)
                    <div class="flex gap-4 py-1">
                        <div class="h-3 rounded bg-gray-100 dark:bg-gray-800" style="flex: 0.6;"></div>
                        <div class="h-3 rounded bg-gray-100 dark:bg-gray-800" style="flex: 0.5;"></div>
                        <div class="h-3 rounded bg-gray-200 dark:bg-gray-700" style="flex: 1.5;"></div>
                        <div class="h-3 rounded bg-gray-100 dark:bg-gray-800" style="flex: 0.8;"></div>
                        <div class="h-3 rounded bg-gray-100 dark:bg-gray-800" style="flex: 0.8;"></div>
                        <div class="h-3 rounded bg-gray-200 dark:bg-gray-700" style="flex: 0.7;"></div>
                    </div>
                @endfor
            </div>

        @elseif($type === 'card')
            {{-- Skeleton: 2-column summary cards --}}
            <div class="animate-pulse grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                @foreach(range(1, 2) as $c)
                    <div class="rounded-xl p-5 ring-1 ring-gray-200 dark:ring-white/10">
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2 mb-3"></div>
                        <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-2/3"></div>
                    </div>
                @endforeach
            </div>
            {{-- Then table rows --}}
            <div class="animate-pulse space-y-3">
                @for($i = 0; $i < $rows; $i++)
                    <div class="flex gap-4">
                        <div class="h-3 rounded bg-gray-200 dark:bg-gray-700" style="flex:1.5;"></div>
                        <div class="h-3 rounded bg-gray-100 dark:bg-gray-800" style="flex:1;"></div>
                        <div class="h-3 rounded bg-gray-100 dark:bg-gray-800" style="flex:1;"></div>
                    </div>
                @endfor
            </div>

        @elseif($type === 'report')
            {{-- Skeleton: Report sections (Pendapatan, Beban, etc.) --}}
            <div class="animate-pulse space-y-6">
                {{-- Section header --}}
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/4"></div>
                <div class="space-y-2 pl-4">
                    @for($i = 0; $i < 3; $i++)
                        <div class="flex justify-between">
                            <div class="h-3 bg-gray-100 dark:bg-gray-800 rounded w-1/2"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                        </div>
                    @endfor
                </div>
                {{-- Divider --}}
                <div class="h-px bg-gray-200 dark:bg-gray-700 w-full"></div>
                {{-- Section 2 --}}
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/3"></div>
                <div class="space-y-2 pl-4">
                    @for($i = 0; $i < 4; $i++)
                        <div class="flex justify-between">
                            <div class="h-3 bg-gray-100 dark:bg-gray-800 rounded" style="width: {{ 40 + $i * 10 }}%"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/5"></div>
                        </div>
                    @endfor
                </div>
                {{-- Total bar --}}
                <div class="h-px bg-gray-200 dark:bg-gray-700 w-full"></div>
                <div class="flex justify-between">
                    <div class="h-5 bg-gray-300 dark:bg-gray-600 rounded w-1/4"></div>
                    <div class="h-5 bg-indigo-200 dark:bg-indigo-900 rounded w-1/5"></div>
                </div>
            </div>

        @elseif($type === 'two-column')
            {{-- Skeleton: 2-column layout (Balance Sheet) --}}
            <div class="animate-pulse grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach(['Aktiva', 'Pasiva'] as $side)
                    <div class="space-y-3">
                        <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/3 mb-4"></div>
                        @for($i = 0; $i < 5; $i++)
                            <div class="flex justify-between">
                                <div class="h-3 bg-gray-100 dark:bg-gray-800 rounded w-1/2"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                            </div>
                        @endfor
                        <div class="h-px bg-gray-200 dark:bg-gray-700 mt-2"></div>
                        <div class="flex justify-between">
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/4"></div>
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/5"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Loading label --}}
        <div class="mt-5 flex items-center justify-center gap-2 text-gray-400 dark:text-gray-500">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-xs font-medium">{{ $label }}</span>
        </div>

    </div>
</div>
