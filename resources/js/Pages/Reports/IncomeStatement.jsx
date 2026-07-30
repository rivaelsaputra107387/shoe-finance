import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import { TrendingUp, ArrowUpRight, ArrowDownRight, Printer, FileText, FileSpreadsheet } from 'lucide-react';

export default function IncomeStatement({ periods, selectedPeriodId, reportData }) {
    const [periodId, setPeriodId] = useState(selectedPeriodId || '');

    const handlePeriodChange = (val) => {
        setPeriodId(val);
        router.get('/app/income-statement', { fiscal_period_id: val }, { preserveState: true, replace: true });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AppLayout title="Laporan Laba Rugi">
            <Head title="Laporan Laba Rugi - SIA Shoe Workshop" />

            <div className="space-y-6 max-w-4xl mx-auto">
                {/* Header & Controls Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Laporan Laba Rugi
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Performa kinerja keuangan pendapatan dan beban operasional perusahaan.
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
                            href={`/app/income-statement/export?format=pdf&fiscal_period_id=${periodId}`}
                            target="_blank"
                            className="px-4 py-2.5 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                        >
                            <FileText className="w-4 h-4" />
                            <span className="hidden sm:inline">PDF</span>
                        </a>
                        
                        <a
                            href={`/app/income-statement/export?format=excel&fiscal_period_id=${periodId}`}
                            className="px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                        >
                            <FileSpreadsheet className="w-4 h-4" />
                            <span className="hidden sm:inline">Excel</span>
                        </a>
                    </div>
                </div>

                {reportData && (
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 lg:p-8 space-y-8">
                        {/* Net Profit Banner */}
                        <div className={`p-6 rounded-2xl border flex items-center justify-between ${
                            reportData.net_profit >= 0
                                ? 'bg-emerald-50/60 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-100'
                                : 'bg-rose-50/60 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-100'
                        }`}>
                            <div>
                                <span className="text-xs font-bold uppercase tracking-wider block opacity-75">
                                    Laba Bersih Periode Ini
                                </span>
                                <span className="text-2xl font-mono font-extrabold mt-1 block">
                                    {formatRupiah(reportData.net_profit)}
                                </span>
                            </div>
                            {reportData.net_profit >= 0 ? (
                                <ArrowUpRight className="w-10 h-10 text-emerald-500" />
                            ) : (
                                <ArrowDownRight className="w-10 h-10 text-rose-500" />
                            )}
                        </div>

                        {/* Revenue Section */}
                        <div className="space-y-3">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-400 border-b pb-2">
                                Pendapatan Usaha
                            </h3>
                            <div className="space-y-2">
                                {reportData.revenue?.map((row, idx) => (
                                    <div key={idx} className="flex justify-between text-sm">
                                        <span className="text-gray-700 dark:text-gray-300">
                                            <span className="font-mono text-gray-400 mr-2">{row.code}</span>
                                            {row.name}
                                        </span>
                                        <span className="font-mono font-medium text-gray-900 dark:text-white">
                                            {formatRupiah(row.balance)}
                                        </span>
                                    </div>
                                ))}
                                <div className="flex justify-between text-sm font-bold pt-2 border-t text-gray-900 dark:text-white">
                                    <span>Total Pendapatan Usaha</span>
                                    <span className="font-mono text-emerald-600 dark:text-emerald-400">{formatRupiah(reportData.total_revenue)}</span>
                                </div>
                            </div>
                        </div>

                        {/* HPP Section */}
                        <div className="space-y-3">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-400 border-b pb-2">
                                Beban HPP / Produksi
                            </h3>
                            <div className="space-y-2">
                                {reportData.hpp?.map((row, idx) => (
                                    <div key={idx} className="flex justify-between text-sm">
                                        <span className="text-gray-700 dark:text-gray-300">
                                            <span className="font-mono text-gray-400 mr-2">{row.code}</span>
                                            {row.name}
                                        </span>
                                        <span className="font-mono font-medium text-gray-900 dark:text-white">
                                            {formatRupiah(row.balance)}
                                        </span>
                                    </div>
                                ))}
                                <div className="flex justify-between text-sm font-bold pt-2 border-t text-gray-900 dark:text-white">
                                    <span>Total HPP / Beban Produksi</span>
                                    <span className="font-mono text-rose-600 dark:text-rose-400">{formatRupiah(reportData.total_hpp)}</span>
                                </div>
                            </div>
                        </div>

                        {/* Gross Profit Summary */}
                        <div className="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl flex justify-between font-bold text-sm text-gray-900 dark:text-white">
                            <span>Laba Kotor (Gross Profit)</span>
                            <span className="font-mono">{formatRupiah(reportData.gross_profit)}</span>
                        </div>

                        {/* Operational Expenses */}
                        <div className="space-y-3">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-400 border-b pb-2">
                                Beban Operasional
                            </h3>
                            <div className="space-y-2">
                                {reportData.operational_expenses?.map((row, idx) => (
                                    <div key={idx} className="flex justify-between text-sm">
                                        <span className="text-gray-700 dark:text-gray-300">
                                            <span className="font-mono text-gray-400 mr-2">{row.code}</span>
                                            {row.name}
                                        </span>
                                        <span className="font-mono font-medium text-gray-900 dark:text-white">
                                            {formatRupiah(row.balance)}
                                        </span>
                                    </div>
                                ))}
                                <div className="flex justify-between text-sm font-bold pt-2 border-t text-gray-900 dark:text-white">
                                    <span>Total Beban Operasional</span>
                                    <span className="font-mono text-rose-600 dark:text-rose-400">{formatRupiah(reportData.total_operational_expenses)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
