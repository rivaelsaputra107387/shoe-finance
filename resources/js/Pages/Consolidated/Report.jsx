import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, ChevronRight, FileSpreadsheet, FileText, Scale } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import ConsolidationStatusPanel from '@/Components/ConsolidationStatusPanel';

const money = (value) => {
    const number = Number(value || 0);

    if (Math.abs(number) < 0.005) return '-';

    const text = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Math.abs(number));

    return number < 0 ? `(${text})` : text;
};

const ROW_STYLES = {
    row: '',
    subtotal: 'font-semibold bg-gray-50/70 dark:bg-gray-800/40',
    total: 'font-bold bg-gray-100 dark:bg-gray-800 border-t-2 border-gray-300 dark:border-gray-600',
};

function ReportRow({ row, units }) {
    const [open, setOpen] = useState(false);
    const expandable = row.details?.length > 0;

    return (
        <>
            <tr className={`border-b border-gray-100 dark:border-gray-800 ${ROW_STYLES[row.type] || ''}`}>
                <td className="py-2 px-4 min-w-56">
                    {expandable ? (
                        <button type="button" onClick={() => setOpen(!open)} className="inline-flex items-center gap-1 text-left hover:text-emerald-700 dark:hover:text-emerald-400">
                            <ChevronRight className={`w-3.5 h-3.5 shrink-0 transition-transform ${open ? 'rotate-90' : ''}`} />
                            <span className="font-mono text-xs text-gray-400">{row.code}</span>
                            <span>{row.label}</span>
                        </button>
                    ) : (
                        <span className={row.type === 'row' ? 'pl-5 inline-block' : ''}>
                            {row.code && <span className="font-mono text-xs text-gray-400 mr-1">{row.code}</span>}
                            {row.label}
                        </span>
                    )}
                </td>
                {units.map((unit) => (
                    <td key={unit.code} className="py-2 px-3 text-right font-mono text-xs whitespace-nowrap">{money(row.amounts[unit.code])}</td>
                ))}
                <td className="py-2 px-3 text-right font-mono text-xs whitespace-nowrap text-amber-700 dark:text-amber-400">{money(row.elimination)}</td>
                <td className="py-2 px-3 text-right font-mono text-xs whitespace-nowrap font-semibold">{money(row.combined)}</td>
            </tr>
            {open && row.details.map((detail) => (
                <tr key={detail.code} className="border-b border-gray-50 dark:border-gray-900 text-xs text-gray-500 dark:text-gray-400">
                    <td className="py-1.5 px-4 pl-12">
                        <span className="font-mono mr-1">{detail.code}</span>
                        {detail.label}
                    </td>
                    {units.map((unit) => (
                        <td key={unit.code} className="py-1.5 px-4 text-right font-mono whitespace-nowrap">{money(detail.amounts[unit.code])}</td>
                    ))}
                    <td colSpan="2" />
                </tr>
            ))}
        </>
    );
}

function CheckBadges({ checks }) {
    const items = [];

    if ('is_balanced' in checks) {
        items.push({ ok: checks.is_balanced, text: checks.is_balanced ? 'Setiap kolom seimbang' : 'Ada kolom yang tidak seimbang' });
    }

    if ('matches_balance_sheet' in checks) {
        items.push({ ok: checks.matches_balance_sheet, text: checks.matches_balance_sheet ? 'Cocok dengan Neraca Gabungan' : 'Tidak cocok dengan Neraca Gabungan' });
    }

    if ('units_valid' in checks) {
        items.push({ ok: checks.units_valid, text: checks.units_valid ? 'Kas akhir tiap unit cocok dengan buku besar' : 'Kas akhir ada unit yang tidak cocok dengan buku besar' });
    }

    return items.map((item) => (
        <span key={item.text} className={`inline-flex items-center gap-1.5 text-xs font-semibold ${item.ok ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600'}`}>
            {item.ok ? <CheckCircle2 className="w-4 h-4" /> : <AlertTriangle className="w-4 h-4" />}
            {item.text}
        </span>
    ));
}

export default function Report({ reportKey, report, periods, selectedPeriod }) {
    const changePeriod = (period) => router.get(`/app/consolidated/${reportKey}`, { period }, { preserveScroll: true });
    const exportUrl = (format) => `/app/consolidated/${reportKey}/export?period=${selectedPeriod}&format=${format}`;

    return (
        <AppLayout title="Laporan Gabungan">
            <Head title={report?.title || 'Laporan Gabungan'} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-5">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <Scale className="w-5 h-5 text-emerald-600" />
                            {report?.title || 'Laporan Gabungan'}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Laporan kombinasi internal, bukan laporan pajak atau audit.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <CustomSelect value={selectedPeriod || ''} onChange={(e) => changePeriod(e.target.value)}>
                            {periods.map((period) => (
                                <option key={period.start_date} value={period.start_date}>{period.name}</option>
                            ))}
                        </CustomSelect>
                        {report && (
                            <>
                                <a href={exportUrl('pdf')} className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <FileText className="w-4 h-4" /> PDF
                                </a>
                                <a href={exportUrl('excel')} className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <FileSpreadsheet className="w-4 h-4" /> Excel
                                </a>
                            </>
                        )}
                    </div>
                </div>

                {!report ? (
                    <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-10 text-center text-sm text-gray-500">
                        Belum ada periode di kalender.
                    </div>
                ) : (
                    <>
                        <ConsolidationStatusPanel status={report.status} />

                        <div className="flex flex-wrap gap-4">
                            <CheckBadges checks={report.checks} />
                        </div>

                        <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto">
                            <table className="w-full text-sm text-gray-800 dark:text-gray-200">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 text-xs font-bold uppercase tracking-wider text-gray-500">
                                        <th className="py-3 px-4 text-left">Pos</th>
                                        {report.units.map((unit) => (
                                            <th key={unit.code} className="py-3 px-3 text-right whitespace-nowrap">{unit.name}</th>
                                        ))}
                                        <th className="py-3 px-3 text-right">Eliminasi</th>
                                        <th className="py-3 px-3 text-right">Gabungan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {report.rows.map((row, index) => (
                                        row.type === 'heading' ? (
                                            <tr key={index} className="bg-gray-50 dark:bg-gray-800/30">
                                                <td colSpan={report.units.length + 3} className="py-2 px-4 text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">{row.label}</td>
                                            </tr>
                                        ) : (
                                            <ReportRow key={`${row.code || row.label}-${index}`} row={row} units={report.units} />
                                        )
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
