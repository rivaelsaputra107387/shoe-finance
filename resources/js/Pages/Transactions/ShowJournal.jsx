import React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Clock, History, Pencil, Printer } from 'lucide-react';

function CorrectionLines({ title, lines, formatRupiah }) {
    return (
        <div className="min-w-0">
            <p className="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">{title}</p>
            <div className="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table className="w-full text-xs">
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                        {lines.map((line, index) => (
                            <tr key={index}>
                                <td className="py-2 px-3 text-gray-700 dark:text-gray-300">
                                    <span className="font-mono">{line.code}</span> {line.name}
                                </td>
                                <td className="py-2 px-3 text-right font-mono whitespace-nowrap text-emerald-600 dark:text-emerald-400">
                                    {line.debit > 0 ? formatRupiah(line.debit) : '-'}
                                </td>
                                <td className="py-2 px-3 text-right font-mono whitespace-nowrap text-rose-600 dark:text-rose-400">
                                    {line.credit > 0 ? formatRupiah(line.credit) : '-'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export default function ShowJournal({ auth, entry, canCorrect = false, corrections = [] }) {
    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(angka);
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        try {
            const cleanStr = dateStr.split('T')[0];
            const [y, m, d] = cleanStr.split('-');
            const dateObj = new Date(parseInt(y), parseInt(m) - 1, parseInt(d));
            return dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        } catch (e) {
            return dateStr;
        }
    };

    const totalDebit = entry.lines?.reduce((sum, line) => sum + Number(line.debit), 0) || 0;
    const totalCredit = entry.lines?.reduce((sum, line) => sum + Number(line.credit), 0) || 0;

    return (
        <AppLayout
            user={auth.user}
            header={<h2 className="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Jurnal Umum</h2>}
        >
            <Head title={`Detail Jurnal ${entry.reference || 'Umum'}`} />

            <div className="max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <Link
                        href="/app/journal-entries"
                        className="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        <span>Kembali ke Daftar</span>
                    </Link>
                    
                    <div className="flex items-center gap-2 print:hidden">
                        {canCorrect && (
                            <Link
                                href={`/app/journal-entries/${entry.id}/correction`}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 rounded-lg text-sm font-semibold transition-colors shadow-sm text-white"
                            >
                                <Pencil className="w-4 h-4" />
                                <span>Koreksi Jurnal</span>
                            </Link>
                        )}
                        <button onClick={() => window.print()} className="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm text-gray-700 dark:text-gray-300">
                            <Printer className="w-4 h-4" />
                            <span>Cetak</span>
                        </button>
                    </div>
                </div>

                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden print:shadow-none print:border-none">
                    
                    {/* Header Section */}
                    <div className="p-6 md:p-8 border-b border-gray-200 dark:border-gray-800">
                        <div className="flex flex-col md:flex-row justify-between gap-6">
                            <div>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-1">Jurnal Umum</h3>
                                <p className="text-gray-500 dark:text-gray-400 text-sm">{entry.description || 'Tanpa keterangan'}</p>
                            </div>
                            
                            <div className="flex flex-col items-start md:items-end gap-2">
                                <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold ${
                                    entry.status === 'posted'
                                        ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                                        : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800'
                                }`}>
                                    {entry.status === 'posted' ? <CheckCircle2 className="w-3.5 h-3.5" /> : <Clock className="w-3.5 h-3.5" />}
                                    <span className="uppercase tracking-wider">{entry.status}</span>
                                </span>
                                <div className="text-right">
                                    <p className="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Referensi</p>
                                    <p className="text-lg font-mono font-bold text-gray-900 dark:text-white">{entry.reference || '-'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8">
                            <div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Tanggal</p>
                                <p className="font-semibold text-gray-900 dark:text-white text-sm">{formatDate(entry.entry_date)}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Periode</p>
                                <p className="font-semibold text-gray-900 dark:text-white text-sm">{entry.fiscal_period?.name || '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Dibuat Oleh</p>
                                <p className="font-semibold text-gray-900 dark:text-white text-sm">{entry.creator?.name || '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Disetujui Oleh</p>
                                <p className="font-semibold text-gray-900 dark:text-white text-sm">
                                    {entry.posted_by ? entry.posted_by.name : '-'}
                                    {entry.posted_at && <span className="block text-xs text-gray-500 font-normal mt-0.5">{new Date(entry.posted_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Table Section */}
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3 px-6 w-32">Akun</th>
                                    <th className="py-3 px-6">Nama Akun</th>
                                    <th className="py-3 px-6">Keterangan</th>
                                    <th className="py-3 px-6 text-right w-40 text-emerald-600 dark:text-emerald-400">Debet</th>
                                    <th className="py-3 px-6 text-right w-40 text-rose-600 dark:text-rose-400">Kredit</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800/60 text-sm">
                                {entry.lines?.map((line, index) => (
                                    <tr key={index} className="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                        <td className="py-3 px-6 font-mono text-gray-500 dark:text-gray-400">{line.account?.code}</td>
                                        <td className={`py-3 px-6 font-medium ${line.credit > 0 ? 'text-gray-600 dark:text-gray-300 pl-10' : 'text-gray-900 dark:text-white'}`}>
                                            {line.account?.name}
                                        </td>
                                        <td className="py-3 px-6 text-gray-600 dark:text-gray-400 text-xs">{line.description || '-'}</td>
                                        <td className="py-3 px-6 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                            {line.debit > 0 ? formatRupiah(line.debit) : '-'}
                                        </td>
                                        <td className="py-3 px-6 text-right font-mono font-semibold text-rose-600 dark:text-rose-400">
                                            {line.credit > 0 ? formatRupiah(line.credit) : '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 border-gray-200 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-800/30">
                                    <td colSpan="3" className="py-4 px-6 text-right font-bold text-gray-900 dark:text-white">
                                        TOTAL
                                    </td>
                                    <td className="py-4 px-6 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {formatRupiah(totalDebit)}
                                    </td>
                                    <td className="py-4 px-6 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                        {formatRupiah(totalCredit)}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                </div>

                {corrections.length > 0 && (
                    <div className="mt-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 space-y-5 print:hidden">
                        <div className="flex items-center gap-2">
                            <History className="w-4 h-4 text-amber-500" />
                            <h3 className="text-sm font-bold text-gray-900 dark:text-white">Riwayat Koreksi ({corrections.length})</h3>
                        </div>

                        {corrections.map((correction) => (
                            <div key={correction.id} className="space-y-3 border-t border-gray-100 dark:border-gray-800 pt-4 first:border-t-0 first:pt-0">
                                <div className="text-xs text-gray-500 dark:text-gray-400">
                                    <span className="font-semibold text-gray-800 dark:text-gray-200">{correction.user}</span>
                                    {' · '}
                                    {new Date(correction.created_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                </div>
                                <p className="text-sm text-gray-700 dark:text-gray-300">{correction.reason}</p>
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <CorrectionLines title="Sebelum" lines={correction.before} formatRupiah={formatRupiah} />
                                    <CorrectionLines title="Sesudah" lines={correction.after} formatRupiah={formatRupiah} />
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
