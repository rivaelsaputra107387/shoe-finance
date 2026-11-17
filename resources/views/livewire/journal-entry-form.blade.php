<div>
    {{-- CSS styling block using pure CSS Grid to guarantee clean layout and high contrast --}}
    <style>
        /* Container Cards */
        .custom-card {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            padding: 24px !important;
            margin-bottom: 24px !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .dark .custom-card {
            background-color: #18181b !important; /* zinc-900 */
            border-color: #27272a !important; /* zinc-800 */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3) !important;
        }

        /* Headings */
        .custom-heading {
            font-size: 18px !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            margin-bottom: 16px !important;
        }
        .dark .custom-heading {
            color: #f8fafc !important;
        }

        /* Labels */
        .field-label {
            display: block !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #475569 !important; /* Slate 600 */
            margin-bottom: 6px !important;
        }
        .dark .field-label {
            color: #cbd5e1 !important; /* Slate 300 */
        }
        .field-label span {
            color: #ef4444 !important;
        }

        /* Inputs and Selects styling */
        .custom-input-field {
            width: 100% !important;
            padding: 10px 14px !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            line-height: 1.5 !important;
            box-sizing: border-box !important;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
        }
        .custom-input-field:focus {
            outline: none !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
        }
        .custom-input-field::placeholder {
            color: #94a3b8 !important;
        }

        /* Custom Dropdown Arrow styling for selects */
        select.custom-input-field {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 12px center !important;
            background-repeat: no-repeat !important;
            background-size: 20px !important;
            padding-right: 40px !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
        }

        /* Dark mode inputs (Extreme High Contrast) */
        .dark .custom-input-field {
            background-color: #09090b !important; /* zinc-950 (deep dark) */
            color: #f8fafc !important; /* slate 50 (white) */
            border: 1px solid #27272a !important; /* zinc-800 */
        }
        .dark .custom-input-field:focus {
            border-color: #818cf8 !important;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.3) !important;
        }
        .dark .custom-input-field::placeholder {
            color: #71717a !important; /* zinc-400 */
        }
        .dark .custom-input-field option {
            background-color: #09090b !important;
            color: #f8fafc !important;
        }
        .dark select.custom-input-field {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23cbd5e1' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-color: #09090b !important;
        }

        /* Responsive Layout Grids */
        .header-layout-grid {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 16px !important;
        }
        @media (min-width: 768px) {
            .header-layout-grid {
                grid-template-columns: 1fr 1fr 1fr !important;
            }
        }

        /* Transaction Line Rows */
        .row-container {
            border: 1px solid #e2e8f0 !important;
            background-color: #f8fafc !important;
            border-radius: 8px !important;
            padding: 16px !important;
            margin-bottom: 12px !important;
        }
        .dark .row-container {
            border-color: #27272a !important;
            background-color: #09090b !important; /* solid deep dark nested bg */
        }

        .line-layout-grid {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 16px !important;
            align-items: end !important;
        }
        @media (min-width: 768px) {
            .line-layout-grid {
                grid-template-columns: 2fr 2fr 1.25fr 1.25fr auto !important; /* Akun, Keterangan, Debit, Kredit, Hapus button */
                gap: 12px !important;
            }
        }

        /* Delete Button wrapper */
        .delete-btn-wrapper {
            display: flex !important;
            justify-content: flex-end !important;
            padding-bottom: 6px !important;
        }
        @media (min-width: 768px) {
            .delete-btn-wrapper {
                justify-content: center !important;
                padding-bottom: 8px !important;
            }
        }

        /* Totals Card */
        .totals-card {
            border-radius: 12px !important;
            border: 2px solid #e2e8f0 !important;
            background-color: #ffffff !important;
            padding: 24px !important;
        }
        .dark .totals-card {
            border-color: #27272a !important;
            background-color: #18181b !important;
        }
        .totals-grid {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 16px !important;
            margin-bottom: 20px !important;
        }
        @media (min-width: 768px) {
            .totals-grid {
                grid-template-columns: 1fr 1fr 1fr !important;
            }
        }

        /* Responsive Alignment Modifications for Desktop */
        @media (min-width: 768px) {
            .desktop-header-row {
                display: grid !important;
                grid-template-columns: 2fr 2fr 1.25fr 1.25fr auto !important;
                gap: 12px !important;
                padding: 0 0 8px 0 !important;
                border-bottom: 2px solid #cbd5e1 !important;
                margin-bottom: 12px !important;
            }
            .dark .desktop-header-row {
                border-bottom-color: #27272a !important;
            }
            .hide-desktop {
                display: none !important;
            }
            .row-container {
                border: none !important;
                background-color: transparent !important;
                padding: 0 !important;
                margin-bottom: 8px !important;
            }
            .dark .row-container {
                border: none !important;
                background-color: transparent !important;
            }
        }
        @media (max-width: 767px) {
            .desktop-header-row {
                display: none !important;
            }
        }
    </style>

    {{-- No Active Period Warning --}}
    @if(!$activePeriod || !$activePeriod->is_open)
        <div class="p-4 mb-6 rounded-lg bg-danger-50 dark:bg-danger-950 border border-danger-300 dark:border-danger-700">
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-500" />
                <p class="text-sm font-medium text-danger-800 dark:text-danger-200">
                    Tidak ada periode fiskal yang aktif. Hubungi Owner untuk membuka periode baru.
                </p>
            </div>
        </div>
    @else
        {{-- Active Period Info Banner --}}
        <div class="p-4 mb-6 rounded-lg flex items-center gap-3 bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500 dark:text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-medium text-indigo-800 dark:text-indigo-200">
                <span class="font-bold text-indigo-900 dark:text-indigo-100">Periode Aktif:</span> {{ $activePeriod->name }} 
                <span class="font-normal text-indigo-600 dark:text-indigo-400">({{ $activePeriod->start_date->format('d M Y') }} — {{ $activePeriod->end_date->format('d M Y') }})</span>
            </p>
        </div>

        <form wire:submit="save">
            {{-- Header Section --}}
            <div class="custom-card">
                <h3 class="custom-heading">Informasi Transaksi</h3>
                <div class="header-layout-grid">
                    <div>
                        <label class="field-label">
                            Tanggal Transaksi <span>*</span>
                        </label>
                        <input type="date"
                               wire:model.live="entry_date"
                               min="{{ $activePeriod->start_date->format('Y-m-d') }}"
                               max="{{ $activePeriod->end_date->format('Y-m-d') }}"
                               class="custom-input-field"
                               required>
                    </div>
                    <div>
                        <label class="field-label">Referensi</label>
                        <input type="text"
                               wire:model="reference"
                               placeholder="Nomor referensi (opsional)"
                               class="custom-input-field">
                    </div>
                    <div>
                        <label class="field-label">
                            Keterangan <span>*</span>
                        </label>
                        <input type="text"
                               wire:model.live="description"
                               placeholder="Deskripsi transaksi"
                               class="custom-input-field"
                               required>
                    </div>
                </div>
            </div>

            {{-- Lines Section --}}
            <div class="custom-card">
                <div class="flex items-center justify-between" style="margin-bottom: 24px !important;">
                    <h3 class="custom-heading" style="margin-bottom: 0 !important;">Detail Transaksi</h3>
                    <button type="button"
                            wire:click="addLine"
                            class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        Tambah Baris
                    </button>
                </div>

                {{-- Lines list --}}
                <div class="space-y-4">
                    {{-- Desktop Header Row (Hidden on mobile, visible on desktop) --}}
                    <div class="desktop-header-row">
                        <div class="font-semibold text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                            Pilih Akun <span class="text-red-500">*</span>
                        </div>
                        <div class="font-semibold text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                            Keterangan Baris
                        </div>
                        <div class="font-semibold text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider text-right">
                            Debit (Rp)
                        </div>
                        <div class="font-semibold text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider text-right">
                            Kredit (Rp)
                        </div>
                        <div class="font-semibold text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider text-center" style="width: 40px;">
                            Aksi
                        </div>
                    </div>

                    @foreach($lines as $index => $line)
                        <div class="row-container" wire:key="line-{{ $index }}">
                            <div class="line-layout-grid">
                                {{-- Account Select --}}
                                <div>
                                    <label class="field-label hide-desktop">Pilih Akun <span>*</span></label>
                                    <select wire:model.live="lines.{{ $index }}.account_id"
                                            class="custom-input-field" required>
                                        <option value="">-- Pilih Akun --</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account['id'] }}">{{ $account['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label class="field-label hide-desktop">Keterangan Baris</label>
                                    <input type="text"
                                           wire:model="lines.{{ $index }}.description"
                                           placeholder="Keterangan opsional"
                                           class="custom-input-field">
                                </div>

                                {{-- Debit --}}
                                <div>
                                    <label class="field-label hide-desktop">Debit (Rp)</label>
                                    <input type="number"
                                           wire:model.live.debounce.300ms="lines.{{ $index }}.debit"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           class="custom-input-field"
                                           style="text-align: right !important;">
                                </div>

                                {{-- Credit --}}
                                <div>
                                    <label class="field-label hide-desktop">Kredit (Rp)</label>
                                    <input type="number"
                                           wire:model.live.debounce.300ms="lines.{{ $index }}.credit"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           class="custom-input-field"
                                           style="text-align: right !important;">
                                </div>

                                {{-- Remove Button --}}
                                <div class="delete-btn-wrapper" style="width: 40px; justify-content: center; align-self: center;">
                                    <button type="button"
                                            wire:click="removeLine({{ $index }})"
                                            class="p-2 text-gray-400 hover:text-danger-500 rounded-lg hover:bg-danger-50 dark:hover:bg-danger-950 transition-colors"
                                            title="Hapus baris">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Totals & Submit Section --}}
            <div class="totals-card {{ abs($difference) > 0.01 ? 'border-danger-500' : 'border-success-500' }}">
                <div class="totals-grid">
                    {{-- Total Debit --}}
                    <div class="text-center p-4 rounded-lg" style="background-color: rgba(59, 130, 246, 0.1);">
                        <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase mb-1">Total Debit</p>
                        <p class="text-2xl font-bold text-blue-800 dark:text-blue-200">
                            Rp {{ number_format($totalDebit, 2, ',', '.') }}
                        </p>
                    </div>

                    {{-- Total Credit --}}
                    <div class="text-center p-4 rounded-lg" style="background-color: rgba(16, 185, 129, 0.1);">
                        <p class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase mb-1">Total Kredit</p>
                        <p class="text-2xl font-bold text-green-800 dark:text-green-200">
                            Rp {{ number_format($totalCredit, 2, ',', '.') }}
                        </p>
                    </div>

                    {{-- Difference --}}
                    <div class="text-center p-4 rounded-lg {{ abs($difference) < 0.01 ? 'bg-success-50 dark:bg-success-950/20' : 'bg-danger-50 dark:bg-danger-950/20' }}"
                         style="background-color: {{ abs($difference) < 0.01 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' }};">
                        <p class="text-xs font-semibold uppercase mb-1 {{ abs($difference) < 0.01 ? 'text-green-600 dark:text-green-400' : 'text-danger-600 dark:text-danger-400' }}">
                            Selisih
                        </p>
                        <p class="text-2xl font-bold {{ abs($difference) < 0.01 ? 'text-green-800 dark:text-green-200' : 'text-danger-800 dark:text-danger-200' }}">
                            @if(abs($difference) < 0.01)
                                ✓ Seimbang
                            @else
                                Rp {{ number_format(abs($difference), 2, ',', '.') }}
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('filament.admin.resources.journal-entries.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        Batal
                    </a>
                    <button type="submit"
                            @if(!$this->can_save) disabled @endif
                            class="inline-flex items-center justify-center px-6 py-2 text-sm font-semibold text-white rounded-lg transition-colors shadow-sm
                                   {{ $this->can_save ? 'bg-primary-600 hover:bg-primary-500 cursor-pointer' : 'bg-gray-400 dark:bg-gray-700 text-gray-200 cursor-not-allowed opacity-50' }}">
                        <span wire:loading.remove wire:target="save">
                            💾 Simpan Jurnal
                        </span>
                        <span wire:loading wire:target="save">
                            ⏳ Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>
