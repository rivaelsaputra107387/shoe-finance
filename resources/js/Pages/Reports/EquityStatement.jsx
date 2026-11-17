import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import { PieChart, Printer, FileText, FileSpreadsheet } from 'lucide-react';

export default function EquityStatement({ periods, selectedPeriodId, reportData }) {
    const [periodId, setPeriodId] = useState(selectedPeriodId || '');

    const handlePeriodChange = (val) => {
        setPeriodId(val);
        router.get('/app/equity-statement', { fiscal_period_id: val }, { preserveState: true, replace: true });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AppLayout title="Laporan Perubahan Ekuitas">
            <Head title="Laporan Perubahan Ekuitas - SIA Shoe Workshop" />

            <div className="space-y-6 max-w-3xl mx-auto">
                {/* Header & Controls Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Laporan Perubahan Ekuitas
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Perubahan modal awal, setoran tambahan, laba bersih, dan prive selama periode berjalan.
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
                            href={`/app/equity-statement/export?format=pdf&fiscal_period_id=${periodId}`}
                            target="_blank"
                            className="px-4 py-2.5 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                        >
                            <FileText className="w-4 h-4" />
                            <span className="hidden sm:inline">PDF</span>
                        </a>
                        
                        <a
                            href={`/app/equity-statement/export?format=excel&fiscal_period_id=${periodId}`}
                            className="px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                        >
                            <FileSpreadsheet className="w-4 h-4" />
                            <span className="hidden sm:inline">Excel</span>
                        </a>
                    </div>
                </div>

                {reportData && (
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 lg:p-8 space-y-6">
                        <div className="divide-y divide-gray-200 dark:divide-gray-800 text-sm space-y-4 pt-2">
                            <div className="flex justify-between py-2 font-semibold text-gray-700 dark:text-gray-300">
                                <span>Modal Awal Periode</span>
                                <span className="font-mono text-gray-900 dark:text-white">{formatRupiah(reportData.beginning_capital)}</span>
                            </div>

                            {reportData.additional_capital > 0 && (
                                <div className="flex justify-between py-2 text-emerald-600 dark:text-emerald-400">
                                    <span>(+) Setoran Modal Tambahan ({reportData.modal_account_name})</span>
                                    <span className="font-mono font-medium">+{formatRupiah(reportData.additional_capital)}</span>
                                </div>
                            )}

                            <div className={`flex justify-between py-2 font-medium ${reportData.net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}`}>
                                <span>{reportData.net_profit >= 0 ? '(+) Laba Bersih Periode Berjalan' : '(-) Rugi Bersih Periode Berjalan'}</span>
                                <span className="font-mono">{formatRupiah(reportData.net_profit)}</span>
                            </div>

                            {reportData.prive > 0 && (
                                <div className="flex justify-between py-2 text-rose-600 dark:text-rose-400">
                                    <span>(-) Prive Pengambilan Pribadi ({reportData.prive_account_name})</span>
                                    <span className="font-mono">-{formatRupiah(reportData.prive)}</span>
                                </div>
                            )}

                            <div className="flex justify-between py-4 text-base font-extrabold text-emerald-600 dark:text-emerald-400 border-t-2">
                                <span className="uppercase">Modal Akhir Periode</span>
                                <span className="font-mono">{formatRupiah(reportData.ending_capital)}</span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
