import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Upload, FileUp, Sparkles, Building2, Search, ArrowUpRight, ArrowDownLeft, CheckCircle2, Clock, X } from 'lucide-react';

export default function BankMutations({ mutations, filters }) {
    const [importModalOpen, setImportModalOpen] = useState(false);
    const [search, setSearch] = useState(filters?.search || '');
    const [bankSource, setBankSource] = useState(filters?.bank_source || '');

    const { data, setData, post, processing, errors, reset } = useForm({
        file: null,
        bank_source: 'AUTO',
    });

    const handleFilter = () => {
        router.get('/app/bank-mutations', {
            search,
            bank_source: bankSource,
        }, { preserveState: true, replace: true });
    };

    const handleImportSubmit = (e) => {
        e.preventDefault();
        post('/app/bank-mutations/import', {
            onSuccess: () => {
                setImportModalOpen(false);
                reset();
            },
        });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
    };

    return (
        <AppLayout title="Mutasi Bank">
            <Head title="Mutasi Bank - SIA Shoe Workshop" />

            <div className="space-y-6">
                {/* Top Action Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Mutasi Bank (BCA & Mandiri)
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Kelola data mutasi rekening bank dan buat draft jurnal otomatis.
                        </p>
                    </div>

                    <button
                        onClick={() => setImportModalOpen(true)}
                        className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition-all"
                    >
                        <Upload className="w-4 h-4" />
                        <span>Import CSV (BCA/Mandiri)</span>
                    </button>
                </div>

                {/* Filters */}
                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row items-center gap-4">
                    <div className="relative flex-1 w-full">
                        <Search className="w-4 h-4 absolute left-3.5 top-3 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Cari deskripsi mutasi..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                            className="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <select
                        value={bankSource}
                        onChange={(e) => setBankSource(e.target.value)}
                        className="py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Bank</option>
                        <option value="BCA">BCA</option>
                        <option value="MANDIRI">Mandiri</option>
                    </select>

                    <button
                        onClick={handleFilter}
                        className="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity"
                    >
                        Filter
                    </button>
                </div>

                {/* Table */}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3.5 px-4">Tanggal</th>
                                    <th className="py-3.5 px-4">Bank</th>
                                    <th className="py-3.5 px-4">Keterangan Mutasi</th>
                                    <th className="py-3.5 px-4">Tipe</th>
                                    <th className="py-3.5 px-4 text-right">Nominal</th>
                                    <th className="py-3.5 px-4">Status</th>
                                    <th className="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {mutations?.data?.length > 0 ? (
                                    mutations.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                            <td className="py-4 px-4 whitespace-nowrap text-xs font-mono text-gray-600 dark:text-gray-400">
                                                {item.date}
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap">
                                                <span className="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                                    {item.bank_source}
                                                </span>
                                            </td>
                                            <td className="py-4 px-4 text-gray-800 dark:text-gray-200 text-xs font-medium max-w-sm truncate">
                                                {item.description}
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap">
                                                <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold ${
                                                    item.mutation_type === 'IN'
                                                        ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400'
                                                        : 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400'
                                                }`}>
                                                    {item.mutation_type === 'IN' ? <ArrowDownLeft className="w-3.5 h-3.5" /> : <ArrowUpRight className="w-3.5 h-3.5" />}
                                                    <span>{item.mutation_type === 'IN' ? 'Uang Masuk' : 'Uang Keluar'}</span>
                                                </span>
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap text-right font-mono font-semibold text-gray-900 dark:text-white">
                                                {formatRupiah(item.amount)}
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap">
                                                <span className="capitalize text-xs font-semibold text-gray-500">
                                                    {item.status}
                                                </span>
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap text-right">
                                                {item.status === 'pending' && (
                                                    <button
                                                        onClick={() => router.post(`/app/bank-mutations/${item.id}/generate-draft`)}
                                                        className="px-3 py-1.5 bg-indigo-600 text-white hover:bg-indigo-500 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition-colors shadow-sm"
                                                    >
                                                        <Sparkles className="w-3.5 h-3.5" />
                                                        <span>Generate Draft</span>
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="7" className="py-8 text-center text-gray-500 text-xs">
                                            Tidak ada data mutasi bank.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Import CSV Modal */}
            {importModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-in fade-in">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 max-w-md w-full shadow-2xl space-y-6">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <Upload className="w-5 h-5 text-emerald-500" />
                                <span>Import CSV Mutasi Bank</span>
                            </h3>
                            <button onClick={() => setImportModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleImportSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    Pilih Format Bank
                                </label>
                                <select
                                    value={data.bank_source}
                                    onChange={(e) => setData('bank_source', e.target.value)}
                                    className="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white"
                                >
                                    <option value="AUTO">Otomatis Deteksi (Rekomendasi - BCA & Mandiri)</option>
                                    <option value="BCA">BCA (Terdapat kolom mutasi gabungan)</option>
                                    <option value="MANDIRI">Mandiri (Terdapat kolom Debit dan Kredit terpisah)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    File CSV / Excel Export
                                </label>
                                <input
                                    type="file"
                                    onChange={(e) => setData('file', e.target.files[0])}
                                    accept=".csv,.txt,.xls,.xlsx"
                                    required
                                    className="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-400 cursor-pointer"
                                />
                                <p className="text-[11px] text-gray-400 mt-1">
                                    File mentah BCA/Mandiri dapat langsung diunggah tanpa membuang baris judul.
                                </p>
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    onClick={() => setImportModalOpen(false)}
                                    className="px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-md disabled:opacity-50"
                                >
                                    {processing ? 'Memproses...' : 'Mulai Import'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
