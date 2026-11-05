import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronDown } from 'lucide-react';
import ConfirmationModal from '@/Components/ConfirmationModal';

export default function UnitSwitcher() {
    const { unit } = usePage().props;
    const [open, setOpen] = useState(false);
    const [target, setTarget] = useState(null);

    const active = unit?.active;
    const available = unit?.available || [];

    if (!active) {
        return (
            <a href="/app/units" className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-xs font-semibold text-amber-700 dark:text-amber-400 hover:bg-amber-100">
                <Building2 className="w-3.5 h-3.5" />
                <span>Mode Gabungan · Buka Pembukuan</span>
            </a>
        );
    }

    if (available.length <= 1) {
        return (
            <div className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                <Building2 className="w-3.5 h-3.5" />
                <span>{active.name}</span>
            </div>
        );
    }

    return (
        <div className="relative">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-xs font-semibold text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-950"
            >
                <Building2 className="w-3.5 h-3.5" />
                <span className="max-w-32 truncate">{active.name}</span>
                <ChevronDown className={`w-3.5 h-3.5 transition-transform ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <>
                    <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
                    <div className="absolute right-0 mt-2 w-56 z-50 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl p-1.5">
                        <p className="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400">Pindah Pembukuan</p>
                        {available.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                disabled={item.code === active.code}
                                onClick={() => {
                                    setOpen(false);
                                    setTarget(item);
                                }}
                                className="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:font-semibold disabled:text-emerald-700 dark:disabled:text-emerald-400 disabled:hover:bg-transparent"
                            >
                                <span className="truncate">{item.name}</span>
                                {item.code === active.code && <Check className="w-4 h-4 shrink-0" />}
                            </button>
                        ))}
                    </div>
                </>
            )}

            <ConfirmationModal
                isOpen={target !== null}
                title="Pindah Pembukuan"
                message={`Pindah ke ${target?.name}? Isian yang belum disimpan di halaman ini akan hilang, dan tab lain yang masih terbuka tidak bisa menyimpan sampai dimuat ulang.`}
                variant="primary"
                confirmText="Ya, Pindah"
                cancelText="Batal"
                onConfirm={() => router.post('/app/units/select', { unit_id: target.id })}
                onClose={() => setTarget(null)}
            />
        </div>
    );
}
