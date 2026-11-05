import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, ChevronRight, GitCompare, XCircle } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import ConsolidationStatusPanel from '@/Components/ConsolidationStatusPanel';

const money = (value) => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value || 0));

function JournalLines({ title, lines }) {
    return (
        <div>
            <p className="text-xs font-bold text-gray-600 dark:text-gray-300 mb-1.5">{title}</p>
            {lines.length === 0 ? (
                <p className="text-xs text-gray-400">Tidak ada jurnal.</p>
            ) : (
                <table className="w-full text-xs">
                    <tbody>
                        {lines.map((line, index) => (
                            <tr key={index} className="border-b border-gray-100 dark:border-gray-800">
                                <td className="py-1 pr-2 whitespace-nowrap font-mono text-gray-500">{line.entry_date}</td>
                                <td className="py-1 pr-2 text-gray-700 dark:text-gray-300">{line.description}</td>
                                <td className="py-1 pr-2 whitespace-nowrap capitalize text-gray-500">{line.status}</td>
                                <td className="py-1 text-right whitespace-nowrap font-mono">{money(line.debit - line.credit)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}

function PairRow({ pair }) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <tr className="border-b border-gray-100 dark:border-gray-800 text-sm">
                <td className="py-2.5 px-4">
                    <button type="button" disabled={pair.matched} onClick={() => setOpen(!open)} className="inline-flex items-center gap-1 text-left disabled:cursor-default">
                        <ChevronRight className={`w-3.5 h-3.5 transition-transform ${pair.matched ? 'opacity-0' : ''} ${open ? 'rotate-90' : ''}`} />
                        {pair.label}
                    </button>
                </td>
                <td className="py-2.5 px-4">
                    <span className="font-medium">{pair.unit_a_name}</span>
                    <span className="block text-xs text-gray-500">{pair.name_a}</span>
                </td>
                <td className="py-2.5 px-4 text-right font-mono text-xs">{money(pair.amount_a)}</td>
                <td className="py-2.5 px-4">
                    <span className="font-medium">{pair.unit_b_name}</span>
                    <span className="block text-xs text-gray-500">
                        {pair.counterparty_included ? (pair.name_b || 'Akun lawan tidak ada') : 'Unit lawan tidak dihitung pada periode ini'}
                    </span>
                </td>
                <td className="py-2.5 px-4 text-right font-mono text-xs">{money(pair.amount_b)}</td>
                <td className={`py-2.5 px-4 text-right font-mono text-xs font-semibold ${pair.matched ? '' : 'text-rose-600'}`}>{money(pair.difference)}</td>
                <td className="py-2.5 px-4">
                    {pair.matched ? (
                        <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400"><CheckCircle2 className="w-4 h-4" />Cocok</span>
                    ) : (
                        <span className="inline-flex items-center gap-1 text-xs font-semibold text-rose-600"><XCircle className="w-4 h-4" />Selisih</span>
                    )}
                </td>
            </tr>
            {open && (
                <tr className="bg-gray-50 dark:bg-gray-800/30">
                    <td colSpan="7" className="px-8 py-3">
                        <div className="grid gap-4 md:grid-cols-2">
                            <JournalLines title={`Jurnal ${pair.unit_a_name}`} lines={pair.lines_a} />
                            <JournalLines title={`Jurnal ${pair.unit_b_name}`} lines={pair.lines_b} />
                        </div>
                    </td>
                </tr>
            )}
        </>
    );
}

export default function Reconciliation({ reconciliation, periods, selectedPeriod }) {
    return (
        <AppLayout title="Laporan Gabungan">
            <Head title="Cek Kecocokan Antar Unit" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-5">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <GitCompare className="w-5 h-5 text-emerald-600" />
                            Cek Kecocokan Antar Unit
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Hanya jurnal posted yang dihitung. Baris selisih bisa dibuka untuk melihat semua jurnalnya.</p>
                    </div>
                    <CustomSelect value={selectedPeriod || ''} onChange={(e) => router.get('/app/consolidated/reconciliation', { period: e.target.value })}>
                        {periods.map((period) => (
                            <option key={period.start_date} value={period.start_date}>{period.name}</option>
                        ))}
                    </CustomSelect>
                </div>

                {!reconciliation ? (
                    <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-10 text-center text-sm text-gray-500">Belum ada periode di kalender.</div>
                ) : (
                    <>
                        <ConsolidationStatusPanel status={reconciliation.status} />

                        <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto">
                            <table className="w-full text-gray-800 dark:text-gray-200">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 text-xs font-bold uppercase tracking-wider text-gray-500">
                                        <th className="py-3 px-4 text-left">Pasangan</th>
                                        <th className="py-3 px-4 text-left">Unit</th>
                                        <th className="py-3 px-4 text-right">Nilai</th>
                                        <th className="py-3 px-4 text-left">Unit Lawan</th>
                                        <th className="py-3 px-4 text-right">Nilai</th>
                                        <th className="py-3 px-4 text-right">Selisih</th>
                                        <th className="py-3 px-4 text-left">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reconciliation.pairs.length === 0 ? (
                                        <tr><td colSpan="7" className="py-10 text-center text-sm text-gray-500">Belum ada transaksi antar unit pada periode ini.</td></tr>
                                    ) : (
                                        reconciliation.pairs.map((pair, index) => <PairRow key={index} pair={pair} />)
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
