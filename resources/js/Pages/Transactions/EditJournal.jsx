import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import AccountSelect from '@/Components/AccountSelect';
import CustomSelect from '@/Components/CustomSelect';
import { Plus, Trash2, ArrowLeft, CheckCircle2, AlertCircle, Save, Send } from 'lucide-react';

export default function EditJournal({ entry, journal, periods, accounts }) {
    entry = entry || journal || {};

    const { data, setData, put, post, processing, errors } = useForm({
        fiscal_period_id: entry?.fiscal_period_id || periods?.[0]?.id || '',
        entry_date: entry?.entry_date ? entry.entry_date.substring(0, 10) : new Date().toISOString().split('T')[0],
        description: entry?.description || '',
        reference: entry?.reference || '',
        lines: entry?.lines?.length > 0 ? entry.lines.map(line => ({
            account_id: line.account_id || '',
            debit: parseFloat(line.debit) || 0,
            credit: parseFloat(line.credit) || 0,
            description: line.description || line.memo || '',
        })) : [
            { account_id: '', debit: 0, credit: 0, description: '' },
            { account_id: '', debit: 0, credit: 0, description: '' },
        ],
    });

    const handleLineChange = (index, field, value) => {
        const newLines = [...data.lines];
        newLines[index][field] = value;
        setData('lines', newLines);
    };

    const addLine = () => {
        setData('lines', [
            ...data.lines,
            { account_id: '', debit: 0, credit: 0, memo: '' },
        ]);
    };

    const removeLine = (index) => {
        if (data.lines.length <= 2) return;
        const newLines = data.lines.filter((_, i) => i !== index);
        setData('lines', newLines);
    };

    const totalDebit = data.lines.reduce((sum, item) => sum + (parseFloat(item.debit) || 0), 0);
    const totalCredit = data.lines.reduce((sum, item) => sum + (parseFloat(item.credit) || 0), 0);
    const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01 && totalDebit > 0;

    const formatNumberInput = (val) => {
        if (!val && val !== 0) return '';
        const strVal = val.toString().split('.')[0];
        const cleanVal = strVal.replace(/\D/g, '');
        return cleanVal ? new Intl.NumberFormat('id-ID').format(cleanVal) : '';
    };

    const parseNumberInput = (val) => {
        const cleanVal = val.toString().replace(/\D/g, '');
        return cleanVal ? parseInt(cleanVal, 10) : 0;
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/app/journal-entries/${entry.id}`);
    };

    const handleSubmitAndApprove = () => {
        put(`/app/journal-entries/${entry.id}`, {
            onSuccess: () => {
                post(`/app/journal-entries/${entry.id}/submit`);
            },
        });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
    };

    return (
        <AppLayout title="Edit Jurnal">
            <Head title="Edit Jurnal - SIA Shoe Workshop" />

            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <Link
                        href={entry.status === 'draft' ? '/app/draft-journals' : '/app/journal-entries'}
                        className="inline-flex items-center gap-2 text-xs font-semibold text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        <span>Kembali ke {entry.status === 'draft' ? 'Draft Jurnal' : 'Daftar Jurnal'}</span>
                    </Link>

                    <div className="flex items-center gap-2">
                        <span className="text-xs text-gray-400 font-mono">Ref: {entry.reference}</span>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Header Info Card */}
                    <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-6">
                        <h3 className="text-base font-bold text-gray-900 dark:text-white">
                            Informasi Jurnal
                        </h3>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    Periode Akuntansi
                                </label>
                                <CustomSelect
                                    value={data.fiscal_period_id}
                                    onChange={(e) => setData('fiscal_period_id', e.target.value)}
                                    required
                                    className="w-full"
                                >
                                    <option value="">Pilih Periode</option>
                                    {periods?.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </CustomSelect>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    Tanggal Transaksi
                                </label>
                                <input
                                    type="date"
                                    value={data.entry_date}
                                    onChange={(e) => setData('entry_date', e.target.value)}
                                    required
                                    className="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    No. Referensi
                                </label>
                                <input
                                    type="text"
                                    placeholder="No. Referensi"
                                    value={data.reference}
                                    onChange={(e) => setData('reference', e.target.value)}
                                    className="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 font-mono"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                Deskripsi Keterangan Transaksi
                            </label>
                            <input
                                type="text"
                                placeholder="Deskripsi Keterangan Transaksi"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                required
                                className="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    {/* Lines Card */}
                    <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                Rincian Baris Debit / Kredit
                            </h3>

                            <div className="flex items-center gap-2">
                                <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold ${
                                    isBalanced
                                        ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                                        : 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800'
                                }`}>
                                    {isBalanced ? <CheckCircle2 className="w-3.5 h-3.5" /> : <AlertCircle className="w-3.5 h-3.5" />}
                                    <span>{isBalanced ? 'Balanced' : 'Unbalanced'}</span>
                                </span>
                            </div>
                        </div>

                        <div className="space-y-3">
                            {data.lines.map((line, idx) => (
                                <div key={idx} className="p-3 bg-gray-50/50 dark:bg-gray-800/40 rounded-xl border border-gray-200/60 dark:border-gray-700/60 space-y-2">
                                    <div className="flex flex-col md:flex-row items-start gap-3">
                                        <div className="flex-1 w-full">
                                            <AccountSelect
                                                value={line.account_id}
                                                onChange={(val) => handleLineChange(idx, 'account_id', val)}
                                                accounts={accounts}
                                            />
                                        </div>

                                        <div className="flex items-center gap-2 w-full md:w-auto">
                                            <div className="flex-1 md:w-36">
                                                <input
                                                    type="text"
                                                    inputMode="numeric"
                                                    placeholder="Debit (Rp)"
                                                    value={formatNumberInput(line.debit)}
                                                    onChange={(e) => handleLineChange(idx, 'debit', parseNumberInput(e.target.value))}
                                                    className="w-full py-2 px-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-mono text-right text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                                />
                                            </div>

                                            <div className="flex-1 md:w-36">
                                                <input
                                                    type="text"
                                                    inputMode="numeric"
                                                    placeholder="Kredit (Rp)"
                                                    value={formatNumberInput(line.credit)}
                                                    onChange={(e) => handleLineChange(idx, 'credit', parseNumberInput(e.target.value))}
                                                    className="w-full py-2 px-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-mono text-right text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                                />
                                            </div>

                                            <button
                                                type="button"
                                                onClick={() => removeLine(idx)}
                                                disabled={data.lines.length <= 2}
                                                className="p-2 text-gray-400 hover:text-rose-500 rounded-lg disabled:opacity-30 transition-colors shrink-0"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <input
                                        type="text"
                                        placeholder="Keterangan rincian baris ini (opsional)..."
                                        value={line.description || ''}
                                        onChange={(e) => handleLineChange(idx, 'description', e.target.value)}
                                        className="w-full py-1.5 px-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-700 dark:text-gray-300 placeholder-gray-400 focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="flex items-center justify-between pt-2">
                            <button
                                type="button"
                                onClick={addLine}
                                className="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 rounded-xl transition-colors"
                            >
                                <Plus className="w-4 h-4" />
                                <span>Tambah Baris</span>
                            </button>

                            <div className="text-right text-xs space-x-4 font-mono font-medium">
                                <span className="text-gray-500">Total Debit: <strong className="text-gray-900 dark:text-white">{formatRupiah(totalDebit)}</strong></span>
                                <span className="text-gray-500">Total Kredit: <strong className="text-gray-900 dark:text-white">{formatRupiah(totalCredit)}</strong></span>
                            </div>
                        </div>
                    </div>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3">
                        <Link
                            href={entry.status === 'draft' ? '/app/draft-journals' : '/app/journal-entries'}
                            className="px-4 py-2.5 text-xs font-semibold text-gray-500 hover:text-gray-900 dark:hover:text-white"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={processing || !isBalanced}
                            className="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-md shadow-emerald-600/20 disabled:opacity-50 transition-all flex items-center gap-2"
                        >
                            <Save className="w-4 h-4" />
                            <span>{processing ? 'Menyimpan...' : 'Simpan Jurnal'}</span>
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
