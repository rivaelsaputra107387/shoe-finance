import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import { Receipt, Printer } from 'lucide-react';

export default function CashFlow({ periods, selectedPeriodId, reportData }) {
    const [periodId, setPeriodId] = useState(selectedPeriodId || '');

    const handlePeriodChange = (val) => {
        setPeriodId(val);
        router.get('/app/cash-flow-statement', { fiscal_period_id: val }, { preserveState: true, replace: true });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AppLayout title="Laporan Arus Kas">
            <Head title="Laporan Arus Kas - SIA Shoe Workshop" />

            <div className="space-y-6 max-w-4xl mx-auto">
                {/* Header & Controls Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Laporan Arus Kas (Metode Langsung)
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Aliran kas masuk dan keluar berdasarkan aktivitas Operasi, Investasi, dan Pendanaan.
                        </p>
                    </div>

                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <div className="w-full sm:w-64">
                            <CustomSelect
                                value={periodId}
                                onChange={(e) => handlePeriodChange(e.target.value)}
                                className="w-full"
                            >
                                {periods?.map((p) => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                            </CustomSelect>
                        </div>

                        <button
                            onClick={handlePrint}
                            className="px-4 py-2.5 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-xl text-xs font-semibold hover:opacity-90 transition-opacity inline-flex items-center gap-2 shadow-sm"
                        >
                            <Printer className="w-4 h-4" />
                            <span className="hidden sm:inline">Cetak</span>
                        </button>
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
                        <div className="p-4 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl border border-emerald-200 dark:border-emerald-800 space-y-2">
                            <div className="flex justify-between text-sm font-bold text-emerald-900 dark:text-emerald-200">
                                <span>Kenaikan / (Penurunan) Bersih Kas</span>
                                <span className="font-mono">{formatRupiah(reportData.net_change_in_cash)}</span>
                            </div>
                            <div className="flex justify-between text-xs text-emerald-700 dark:text-emerald-300">
                                <span>Saldo Kas Awal Periode</span>
                                <span className="font-mono">{formatRupiah(reportData.beginning_cash)}</span>
                            </div>
                            <div className="flex justify-between text-sm font-extrabold text-emerald-900 dark:text-emerald-100 pt-2 border-t border-emerald-200 dark:border-emerald-800">
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
