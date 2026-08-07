import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import { FileText, FileSpreadsheet } from 'lucide-react';

export default function CashFlow({ periods, selectedPeriodId, reportData }) {
    const [periodId, setPeriodId] = useState(selectedPeriodId || '');

    const handlePeriodChange = (val) => {
        setPeriodId(val);
        router.get('/app/cash-flow-statement', { fiscal_period_id: val }, { preserveState: true, replace: true });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(val || 0);
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

                        <a
                            href={`/app/cash-flow-statement/export?format=pdf&fiscal_period_id=${periodId}`}
                            target="_blank"
                            className="px-4 py-2.5 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                        >
                            <FileText className="w-4 h-4" />
                            <span className="hidden sm:inline">PDF</span>
                        </a>

                        <a
                            href={`/app/cash-flow-statement/export?format=excel&fiscal_period_id=${periodId}`}
                            className="px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                        >
                            <FileSpreadsheet className="w-4 h-4" />
                            <span className="hidden sm:inline">Excel</span>
                        </a>
                    </div>
                </div>

                {reportData && (
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 lg:p-8 space-y-6">

                        {/* ── AKTIVITAS OPERASI ── */}
                        <CashFlowSection
                            title="Arus Kas dari Aktivitas Operasi"
                            items={reportData.operating ?? []}
                            total={reportData.total_operating ?? 0}
                            totalLabel="Arus Kas Bersih dari Aktivitas Operasi"
                            formatRupiah={formatRupiah}
                        />

                        {/* ── AKTIVITAS INVESTASI ── */}
                        <CashFlowSection
                            title="Arus Kas dari Aktivitas Investasi"
                            items={reportData.investing ?? []}
                            total={reportData.total_investing ?? 0}
                            totalLabel="Arus Kas Bersih dari Aktivitas Investasi"
                            formatRupiah={formatRupiah}
                        />

                        {/* ── AKTIVITAS PENDANAAN ── */}
                        <CashFlowSection
                            title="Arus Kas dari Aktivitas Pendanaan"
                            items={reportData.financing ?? []}
                            total={reportData.total_financing ?? 0}
                            totalLabel="Arus Kas Bersih dari Aktivitas Pendanaan"
                            formatRupiah={formatRupiah}
                        />

                        {/* ── RINGKASAN AKHIR ── */}
                        <div className="p-4 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl border border-emerald-200 dark:border-emerald-800 space-y-2">
                            <div className="flex justify-between text-sm font-bold text-emerald-900 dark:text-emerald-200">
                                <span>Kenaikan / (Penurunan) Bersih Kas</span>
                                <span className="font-mono tabular-nums">{formatRupiah(reportData.net_increase)}</span>
                            </div>
                            <div className="flex justify-between text-xs text-emerald-700 dark:text-emerald-300">
                                <span>Saldo Kas Awal Periode</span>
                                <span className="font-mono tabular-nums">{formatRupiah(reportData.beginning_cash)}</span>
                            </div>
                            <div className="flex justify-between text-sm font-extrabold text-emerald-900 dark:text-emerald-100 pt-2 border-t border-emerald-200 dark:border-emerald-800">
                                <span>SALDO KAS AKHIR PERIODE</span>
                                <span className="font-mono tabular-nums">{formatRupiah(reportData.ending_cash)}</span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

/**
 * Komponen seksi arus kas: Operasi / Investasi / Pendanaan.
 * Menampilkan detail per akun dari service response, beserta total bersih.
 */
function CashFlowSection({ title, items, total, totalLabel, formatRupiah }) {
    const hasItems = items.length > 0;

    return (
        <div className="space-y-1">
            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-1 mb-2">
                {title}
            </h3>

            {hasItems ? (
                items.map((item, i) => (
                    <div key={i} className="flex justify-between items-start text-sm py-1">
                        <span className="text-gray-700 dark:text-gray-300 flex-1 pr-4">
                            <span className="text-gray-400 dark:text-gray-500 mr-1.5 text-xs font-mono">{item.account_code}</span>
                            {item.account_name}
                        </span>
                        <span className={`font-mono tabular-nums text-right shrink-0 ${
                            item.amount >= 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-rose-600 dark:text-rose-400'
                        }`}>
                            {item.amount >= 0 ? '+' : ''}{formatRupiah(item.amount)}
                        </span>
                    </div>
                ))
            ) : (
                <p className="text-sm text-gray-400 dark:text-gray-500 italic py-1">
                    Tidak ada transaksi pada kategori ini.
                </p>
            )}

            <div className="flex justify-between text-sm font-bold pt-2 mt-1 border-t border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">
                <span>{totalLabel}</span>
                <span className={`font-mono tabular-nums ${total < 0 ? 'text-rose-600 dark:text-rose-400' : ''}`}>
                    {formatRupiah(total)}
                </span>
            </div>
        </div>
    );
}
