import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import AccountSelect from '@/Components/AccountSelect';
import { AlertCircle, AlertTriangle, ArrowLeft, CheckCircle2, Plus, Save, Trash2 } from 'lucide-react';

const emptyLine = { account_id: '', debit: 0, credit: 0, description: '' };

const formatRupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
}).format(value || 0);

export default function CorrectJournal({ entry, accounts = [], requiredSourceLines = [] }) {
    const { data, setData, put, processing, errors } = useForm({
        reason: '',
        lines: entry.lines.map((line) => ({
            account_id: line.account_id,
            debit: Number(line.debit) || 0,
            credit: Number(line.credit) || 0,
            description: line.description || '',
        })),
    });

    const updateLine = (index, field, value) => {
        setData('lines', data.lines.map((line, lineIndex) => (
            lineIndex === index ? { ...line, [field]: value } : line
        )));
    };

    const totalDebit = data.lines.reduce((sum, line) => sum + (Number(line.debit) || 0), 0);
    const totalCredit = data.lines.reduce((sum, line) => sum + (Number(line.credit) || 0), 0);
    const isBalanced = totalDebit > 0 && Math.abs(totalDebit - totalCredit) < 0.01;
    const lineError = errors.lines || Object.entries(errors).find(([key]) => key.startsWith('lines.'))?.[1];

    const submit = (event) => {
        event.preventDefault();
        put(`/app/journal-entries/${entry.id}/correction`, { preserveScroll: true });
    };

    return (
        <AppLayout title="Koreksi Jurnal Posted">
            <Head title={`Koreksi ${entry.reference || 'Jurnal'}`} />

            <form onSubmit={submit} className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center justify-between gap-3">
                    <Link
                        href={`/app/journal-entries/${entry.id}`}
                        className="inline-flex items-center gap-2 text-xs font-semibold text-gray-500 hover:text-gray-900 dark:hover:text-white"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        <span>Kembali ke Detail Jurnal</span>
                    </Link>
                    <span className="text-xs text-gray-400 font-mono">Ref: {entry.reference || '-'}</span>
                </div>

                <div className="p-4 rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 flex gap-3">
                    <AlertTriangle className="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" />
                    <div className="text-xs text-amber-900 dark:text-amber-200 space-y-1">
                        <p className="font-bold">Jurnal ini sudah posted dan langsung memengaruhi laporan.</p>
                        <p>Isi sebelum dan sesudah, nama pengubah, waktu, dan alasan akan tersimpan di riwayat.</p>
                    </div>
                </div>

                <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                    <h3 className="text-base font-bold text-gray-900 dark:text-white">{entry.description}</h3>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {entry.entry_date?.substring(0, 10)} · {entry.fiscal_period?.name}
                    </p>

                    {requiredSourceLines.map((source) => (
                        <p key={`${source.account_id}-${source.side}`} className="mt-3 text-xs text-gray-600 dark:text-gray-300">
                            Sumber mutasi {source.bank_source}: <strong>{source.code} {source.name}</strong> harus tetap di sisi{' '}
                            <strong>{source.side === 'debit' ? 'debit' : 'kredit'} {formatRupiah(source.amount)}</strong>.
                        </p>
                    ))}
                </div>

                <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                    <div className="flex items-center justify-between gap-3">
                        <h3 className="text-base font-bold text-gray-900 dark:text-white">Baris Jurnal</h3>
                        <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold ${isBalanced ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400'}`}>
                            {isBalanced ? <CheckCircle2 className="w-3.5 h-3.5" /> : <AlertCircle className="w-3.5 h-3.5" />}
                            <span>{isBalanced ? 'Seimbang' : 'Belum seimbang'}</span>
                        </span>
                    </div>

                    <div className="space-y-3">
                        {data.lines.map((line, index) => (
                            <div key={index} className="p-3 bg-gray-50/50 dark:bg-gray-800/40 rounded-xl border border-gray-200/60 dark:border-gray-700/60 space-y-2">
                                <div className="flex flex-col md:flex-row gap-3">
                                    <div className="flex-1 min-w-0">
                                        <AccountSelect
                                            value={line.account_id}
                                            onChange={(value) => updateLine(index, 'account_id', value)}
                                            accounts={accounts}
                                        />
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            aria-label={`Debit baris ${index + 1}`}
                                            value={line.debit}
                                            onChange={(event) => updateLine(index, 'debit', event.target.value)}
                                            className="w-full md:w-36 py-2 px-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-mono text-right text-gray-900 dark:text-white"
                                        />
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            aria-label={`Kredit baris ${index + 1}`}
                                            value={line.credit}
                                            onChange={(event) => updateLine(index, 'credit', event.target.value)}
                                            className="w-full md:w-36 py-2 px-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-mono text-right text-gray-900 dark:text-white"
                                        />
                                        <button
                                            type="button"
                                            aria-label={`Hapus baris ${index + 1}`}
                                            onClick={() => setData('lines', data.lines.filter((_, lineIndex) => lineIndex !== index))}
                                            disabled={data.lines.length <= 2}
                                            className="p-2 text-gray-400 hover:text-rose-500 disabled:opacity-30"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                                <input
                                    type="text"
                                    maxLength={255}
                                    placeholder="Keterangan baris (opsional)"
                                    value={line.description}
                                    onChange={(event) => updateLine(index, 'description', event.target.value)}
                                    className="w-full py-1.5 px-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-700 dark:text-gray-300"
                                />
                            </div>
                        ))}
                    </div>

                    {lineError && <p className="text-xs font-medium text-rose-600 dark:text-rose-400">{lineError}</p>}

                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <button
                            type="button"
                            onClick={() => setData('lines', [...data.lines, { ...emptyLine }])}
                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            <Plus className="w-4 h-4" />
                            <span>Tambah Baris</span>
                        </button>
                        <div className="text-xs font-mono text-gray-500 space-x-4">
                            <span>Debit: <strong className="text-gray-900 dark:text-white">{formatRupiah(totalDebit)}</strong></span>
                            <span>Kredit: <strong className="text-gray-900 dark:text-white">{formatRupiah(totalCredit)}</strong></span>
                        </div>
                    </div>
                </div>

                <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                    <label htmlFor="reason" className="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Alasan koreksi
                    </label>
                    <textarea
                        id="reason"
                        rows={3}
                        maxLength={500}
                        required
                        value={data.reason}
                        onChange={(event) => setData('reason', event.target.value)}
                        placeholder="Contoh: Kasus 2, transfer BCA ke Kas tercatat dua kali, sisi BCA dialihkan ke Ayat Silang"
                        className="w-full py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white"
                    />
                    {errors.reason && <p className="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{errors.reason}</p>}
                </div>

                <div className="flex items-center justify-end gap-3">
                    <Link href={`/app/journal-entries/${entry.id}`} className="px-4 py-2.5 text-xs font-semibold text-gray-500">
                        Batal
                    </Link>
                    <button
                        type="submit"
                        disabled={processing || !isBalanced}
                        className="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl disabled:opacity-50"
                    >
                        <Save className="w-4 h-4" />
                        <span>{processing ? 'Menyimpan...' : 'Simpan Koreksi'}</span>
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}
