import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Receipt } from 'lucide-react';

export default function CashFlow({ periods, selectedPeriodId, reportData }) {
    const [periodId, setPeriodId] = useState(selectedPeriodId || '');

    const handlePeriodChange = (val) => {
        setPeriodId(val);
        router.get('/app/cash-flow-statement', { fiscal_period_id: val }, { preserveState: true, replace: true });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
    };

    return (
        <AppLayout title="Laporan Arus Kas">
            <Head title="Laporan Arus Kas - SIA Shoe Workshop" />

            <div className="space-y-6 max-w-4xl mx-auto">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Laporan Arus Kas (Metode Langsung)
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Aliran kas masuk dan keluar berdasarkan aktivitas Operasi, Investasi, dan Pendanaan.
                        </p>
                    </div>

                    <div className="w-full sm:w-64">
                        <select
                            value={periodId}
                            onChange={(e) => handlePeriodChange(e.target.value)}
                            className="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-900 dark:text-white shadow-sm"
                        >
                            {periods?.map((p) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                    </div>
                </div>

                {reportData && (
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 lg:p-8 space-y-6">
                        {/* Operating Activities */}
                        <div className="space-y-2">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-400 border-b pb-1">
                                Arus Kas dari Aktivitas Operasi
                            </h3>
                            <div className="flex justify-between text-sm py-1">
                                <span className="text-gray-700 dark:text-gray-300">Penerimaan Kas dari Pelanggan</span>
                                <span className="font-mono text-emerald-600 dark:text-emerald-400">+{formatRupiah(reportData.operating_inflows)}</span>
                            </div>
                            <div className="flex justify-between text-sm py-1">
                                <span className="text-gray-700 dark:text-gray-300">Pembayaran Kas untuk Beban Operasional</span>
                                <span className="font-mono text-rose-600 dark:text-rose-400">-{formatRupiah(reportData.operating_outflows)}</span>
                            </div>
                            <div className="flex justify-between text-sm font-bold pt-2 border-t text-gray-900 dark:text-white">
                                <span>Arus Kas Bersih dari Aktivitas Operasi</span>
                                <span className="font-mono">{formatRupiah(reportData.net_operating)}</span>
                            </div>
                        </div>

                        {/* Investing Activities */}
                        <div className="space-y-2">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-400 border-b pb-1">
                                Arus Kas dari Aktivitas Investasi
                            </h3>
                            <div className="flex justify-between text-sm py-1">
                                <span className="text-gray-700 dark:text-gray-300">Pembelian Aset Tetap / Peralatan</span>
                                <span className="font-mono text-rose-600 dark:text-rose-400">-{formatRupiah(reportData.investing_outflows)}</span>
                            </div>
                            <div className="flex justify-between text-sm font-bold pt-2 border-t text-gray-900 dark:text-white">
                                <span>Arus Kas Bersih dari Aktivitas Investasi</span>
                                <span className="font-mono">{formatRupiah(reportData.net_investing)}</span>
                            </div>
                        </div>

                        {/* Financing Activities */}
                        <div className="space-y-2">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-400 border-b pb-1">
                                Arus Kas dari Aktivitas Pendanaan
                            </h3>
                            <div className="flex justify-between text-sm py-1">
                                <span className="text-gray-700 dark:text-gray-300">Penerimaan Setoran Modal / Utang</span>
                                <span className="font-mono text-emerald-600 dark:text-emerald-400">+{formatRupiah(reportData.financing_inflows)}</span>
                            </div>
                            <div className="flex justify-between text-sm py-1">
                                <span className="text-gray-700 dark:text-gray-300">Pembayaran Prive / Angsuran Utang</span>
                                <span className="font-mono text-rose-600 dark:text-rose-400">-{formatRupiah(reportData.financing_outflows)}</span>
                            </div>
                            <div className="flex justify-between text-sm font-bold pt-2 border-t text-gray-900 dark:text-white">
                                <span>Arus Kas Bersih dari Aktivitas Pendanaan</span>
                                <span className="font-mono">{formatRupiah(reportData.net_financing)}</span>
                            </div>
                        </div>

                        {/* Total Summary */}
                        <div className="p-4 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl border border-indigo-200 dark:border-indigo-800 space-y-2">
                            <div className="flex justify-between text-sm font-bold text-indigo-900 dark:text-indigo-200">
                                <span>Kenaikan / (Penurunan) Bersih Kas</span>
                                <span className="font-mono">{formatRupiah(reportData.net_change_in_cash)}</span>
                            </div>
                            <div className="flex justify-between text-xs text-indigo-700 dark:text-indigo-300">
                                <span>Saldo Kas Awal Periode</span>
                                <span className="font-mono">{formatRupiah(reportData.beginning_cash)}</span>
                            </div>
                            <div className="flex justify-between text-sm font-extrabold text-indigo-900 dark:text-indigo-100 pt-2 border-t border-indigo-200 dark:border-indigo-800">
                                <span>SALDO KAS AKHIR PERIODE</span>
                                <span className="font-mono">{formatRupiah(reportData.ending_cash)}</span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
