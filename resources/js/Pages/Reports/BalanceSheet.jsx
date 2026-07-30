import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import { Scale, CheckCircle2, AlertCircle, Printer, FileText, FileSpreadsheet } from 'lucide-react';

export default function BalanceSheet({ periods, selectedPeriodId, reportData }) {
    const [periodId, setPeriodId] = useState(selectedPeriodId || '');

    const handlePeriodChange = (val) => {
        setPeriodId(val);
        router.get('/app/balance-sheet', { fiscal_period_id: val }, { preserveState: true, replace: true });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AppLayout title="Laporan Neraca (Balance Sheet)">
            <Head title="Laporan Neraca - SIA Shoe Workshop" />

            <div className="space-y-6 max-w-5xl mx-auto">
                {/* Header & Controls Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Laporan Neraca (Balance Sheet)
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Posisi posisi keuangan perusahaan (Aset = Kewajiban + Ekuitas) kumulatif.
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


                        <a
                            href={`/app/balance-sheet/export?format=pdf&fiscal_period_id=${periodId}`}
                            target="_blank"
                            className="px-4 py-2.5 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                        >
                            <FileText className="w-4 h-4" />
                            <span className="hidden sm:inline">PDF</span>
                        </a>
                        
                        <a
                            href={`/app/balance-sheet/export?format=excel&fiscal_period_id=${periodId}`}
                            className="px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                        >
                            <FileSpreadsheet className="w-4 h-4" />
                            <span className="hidden sm:inline">Excel</span>
                        </a>
                    </div>
                </div>

                {reportData && (
                    <>
                        {/* Balance Status Banner */}
                        <div className={`p-4 rounded-2xl border flex items-center justify-between ${
                            reportData.is_balanced
                                ? 'bg-emerald-50/60 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-100'
                                : 'bg-rose-50/60 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-100'
                        }`}>
                            <div className="flex items-center gap-3">
                                {reportData.is_balanced ? (
                                    <CheckCircle2 className="w-6 h-6 text-emerald-500" />
                                ) : (
                                    <AlertCircle className="w-6 h-6 text-rose-500" />
                                )}
                                <div>
                                    <span className="text-sm font-bold block">
                                        {reportData.is_balanced ? 'Persamaan Akuntansi Seimbang (Balance)' : 'Neraca Tidak Seimbang!'}
                                    </span>
                                    <span className="text-xs opacity-75">
                                        Total Aset ({formatRupiah(reportData.total_assets)}) = Total Kewajiban + Ekuitas ({formatRupiah(reportData.total_liabilities_and_equity)})
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Two Columns Grid Layout (Assets Left, Liabilities & Equity Right) */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Left Column: ASET */}
                            <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 space-y-6">
                                <h3 className="text-base font-extrabold text-emerald-600 dark:text-emerald-400 border-b pb-2">
                                    AKTIVA / ASET
                                </h3>

                                <div className="space-y-4">
                                    <div>
                                        <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Aset Lancar</h4>
                                        <div className="space-y-1.5">
                                            {reportData.current_assets?.map((row, idx) => (
                                                <div key={idx} className="flex justify-between text-xs">
                                                    <span className="text-gray-700 dark:text-gray-300">
                                                        <span className="font-mono text-gray-400 mr-2">{row.code}</span>
                                                        {row.name}
                                                    </span>
                                                    <span className="font-mono font-medium text-gray-900 dark:text-white">{formatRupiah(row.balance)}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div>
                                        <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Aset Tetap</h4>
                                        <div className="space-y-1.5">
                                            {reportData.fixed_assets?.map((row, idx) => (
                                                <div key={idx} className="flex justify-between text-xs">
                                                    <span className="text-gray-700 dark:text-gray-300">
                                                        <span className="font-mono text-gray-400 mr-2">{row.code}</span>
                                                        {row.name}
                                                    </span>
                                                    <span className="font-mono font-medium text-gray-900 dark:text-white">{formatRupiah(row.balance)}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                <div className="pt-4 border-t border-gray-200 dark:border-gray-800 flex justify-between font-extrabold text-sm text-emerald-600 dark:text-emerald-400">
                                    <span>TOTAL ASET</span>
                                    <span className="font-mono">{formatRupiah(reportData.total_assets)}</span>
                                </div>
                            </div>

                            {/* Right Column: KEWAJIBAN & EKUITAS */}
                            <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 space-y-6">
                                <h3 className="text-base font-extrabold text-emerald-600 dark:text-emerald-400 border-b pb-2">
                                    PASIVA (KEWAJIBAN & EKUITAS)
                                </h3>

                                <div className="space-y-4">
                                    <div>
                                        <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Kewajiban</h4>
                                        <div className="space-y-1.5">
                                            {reportData.current_liabilities?.map((row, idx) => (
                                                <div key={idx} className="flex justify-between text-xs">
                                                    <span className="text-gray-700 dark:text-gray-300">
                                                        <span className="font-mono text-gray-400 mr-2">{row.code}</span>
                                                        {row.name}
                                                    </span>
                                                    <span className="font-mono font-medium text-gray-900 dark:text-white">{formatRupiah(row.balance)}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div>
                                        <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Ekuitas / Modal</h4>
                                        <div className="space-y-1.5">
                                            {reportData.equity?.map((row, idx) => (
                                                <div key={idx} className="flex justify-between text-xs">
                                                    <span className="text-gray-700 dark:text-gray-300">
                                                        <span className="font-mono text-gray-400 mr-2">{row.code}</span>
                                                        {row.name}
                                                    </span>
                                                    <span className="font-mono font-medium text-gray-900 dark:text-white">{formatRupiah(row.balance)}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                <div className="pt-4 border-t border-gray-200 dark:border-gray-800 flex justify-between font-extrabold text-sm text-emerald-600 dark:text-emerald-400">
                                    <span>TOTAL KEWAJIBAN & EKUITAS</span>
                                    <span className="font-mono">{formatRupiah(reportData.total_liabilities_and_equity)}</span>
                                </div>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
