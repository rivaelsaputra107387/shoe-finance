import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import { Sheet, CheckCircle2, AlertCircle, Printer } from 'lucide-react';

export default function TrialBalance({ periods, selectedPeriodId, reportData }) {
    const [periodId, setPeriodId] = useState(selectedPeriodId || '');

    const handlePeriodChange = (val) => {
        setPeriodId(val);
        router.get('/app/trial-balance', { fiscal_period_id: val }, { preserveState: true, replace: true });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AppLayout title="Neraca Lajur (Trial Balance)">
            <Head title="Neraca Lajur - SIA Shoe Workshop" />

            <div className="space-y-6">
                {/* Header & Controls Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Neraca Lajur (Trial Balance)
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Laporan keseimbangan saldo debet dan kredit seluruh akun COA secara kumulatif.
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
                    <>
                        {/* Balance Summary Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <span className="text-xs text-gray-500 font-medium block">Total Saldo Debet</span>
                                <span className="text-xl font-mono font-extrabold text-emerald-600 dark:text-emerald-400 mt-1 block">
                                    {formatRupiah(reportData.total_debit)}
                                </span>
                            </div>

                            <div className="p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <span className="text-xs text-gray-500 font-medium block">Total Saldo Kredit</span>
                                <span className="text-xl font-mono font-extrabold text-emerald-600 dark:text-emerald-400 mt-1 block">
                                    {formatRupiah(reportData.total_credit)}
                                </span>
                            </div>

                            <div className="p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between">
                                <div>
                                    <span className="text-xs text-gray-500 font-medium block">Status Keseimbangan</span>
                                    <span className={`text-base font-bold mt-0.5 block ${reportData.is_balanced ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}`}>
                                        {reportData.is_balanced ? '100% BALANCE' : 'TIDAK SEIMBANG'}
                                    </span>
                                </div>
                                {reportData.is_balanced ? (
                                    <CheckCircle2 className="w-8 h-8 text-emerald-500" />
                                ) : (
                                    <AlertCircle className="w-8 h-8 text-rose-500" />
                                )}
                            </div>
                        </div>

                        {/* Table */}
                        <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            <th className="py-3.5 px-4">Kode Akun</th>
                                            <th className="py-3.5 px-4">Nama Akun</th>
                                            <th className="py-3.5 px-4">Tipe</th>
                                            <th className="py-3.5 px-4 text-right">Saldo Debet</th>
                                            <th className="py-3.5 px-4 text-right">Saldo Kredit</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                        {reportData.accounts?.map((row) => (
                                            <tr key={row.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                                <td className="py-3.5 px-4 font-mono font-bold text-xs text-gray-900 dark:text-white">
                                                    {row.code}
                                                </td>
                                                <td className="py-3.5 px-4 font-medium text-gray-800 dark:text-gray-200">
                                                    {row.name}
                                                </td>
                                                <td className="py-3.5 px-4">
                                                    <span className="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                                        {row.type}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                                    {row.debit > 0 ? formatRupiah(row.debit) : '-'}
                                                </td>
                                                <td className="py-3.5 px-4 text-right font-mono font-semibold text-rose-600 dark:text-rose-400">
                                                    {row.credit > 0 ? formatRupiah(row.credit) : '-'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
