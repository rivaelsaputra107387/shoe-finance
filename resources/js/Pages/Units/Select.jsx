import React from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Building2, Clock, LogOut, Scale } from 'lucide-react';

const STATUS_LABELS = {
    persiapan: 'Persiapan',
    nonaktif: 'Nonaktif, hanya baca',
};

export default function Select({ units, activeUnitId, canViewConsolidated }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-950 flex flex-col items-center px-4 py-12">
            <Head title="Pilih Pembukuan" />

            <div className="w-full max-w-4xl space-y-8">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <img src="/logo.png" alt="Shoe Workshop Logo" className="h-10 w-auto object-contain" />
                        <div>
                            <h1 className="text-xl font-bold text-gray-900 dark:text-white">Selamat datang, {auth?.user?.name}</h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400">Pilih pembukuan yang ingin dibuka</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={() => router.post('/logout')}
                        className="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        <LogOut className="w-4 h-4" />
                        Keluar
                    </button>
                </div>

                {units.length === 0 ? (
                    <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-10 text-center">
                        <Building2 className="w-8 h-8 mx-auto text-gray-400" />
                        <h2 className="mt-3 font-semibold text-gray-900 dark:text-white">Belum ada akses unit</h2>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Hubungi Owner atau Finance untuk mendapatkan akses.</p>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {units.map((unit) => (
                            <button
                                key={unit.id}
                                type="button"
                                onClick={() => router.post('/app/units/select', { unit_id: unit.id })}
                                className={`flex flex-col items-stretch justify-start text-left bg-white dark:bg-gray-900 border rounded-2xl p-5 shadow-sm hover:border-emerald-400 hover:shadow-md transition ${
                                    unit.id === activeUnitId ? 'border-emerald-400' : 'border-gray-200 dark:border-gray-800'
                                }`}
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <Building2 className="w-5 h-5 text-emerald-600" />
                                    {STATUS_LABELS[unit.status] && (
                                        <span className="px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950/40 text-[10px] font-semibold text-amber-700 dark:text-amber-400">
                                            {STATUS_LABELS[unit.status]}
                                        </span>
                                    )}
                                </div>
                                <h2 className="mt-3 font-bold text-gray-900 dark:text-white">{unit.name}</h2>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{unit.active_period || 'Tidak ada periode aktif'}</p>
                                {unit.pending_journals > 0 && (
                                    <p className="mt-3 flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-400">
                                        <Clock className="w-3.5 h-3.5" />
                                        {unit.pending_journals} jurnal menunggu approval
                                    </p>
                                )}
                            </button>
                        ))}
                    </div>
                )}

                {canViewConsolidated && (
                    <a href="/app/consolidated/income-statement" className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-sm">
                        <Scale className="w-4 h-4" />
                        Laporan Gabungan
                    </a>
                )}
            </div>
        </div>
    );
}
