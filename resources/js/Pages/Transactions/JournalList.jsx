import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Plus, Search, Filter, CheckCircle2, Clock, FileEdit, Check, ArrowUpRight } from 'lucide-react';

export default function JournalList({ entries, periods, filters }) {
    const { auth } = usePage().props;
    const userRoles = auth?.user?.roles || [];
    const isOwnerOrFinance = userRoles.includes('owner') || userRoles.includes('finance');

    const [search, setSearch] = useState(filters?.search || '');
    const [periodId, setPeriodId] = useState(filters?.fiscal_period_id || '');
    const [status, setStatus] = useState(filters?.status || '');

    const handleFilter = () => {
        router.get('/app/journal-entries', {
            search,
            fiscal_period_id: periodId,
            status,
        }, { preserveState: true, replace: true });
    };

    const handleReset = () => {
        setSearch('');
        setPeriodId('');
        setStatus('');
        router.get('/app/journal-entries');
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
    };

    return (
        <AppLayout title="Daftar Jurnal">
            <Head title="Daftar Jurnal - SIA Shoe Workshop" />

            <div className="space-y-6">
                {/* Header Actions & Title */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Jurnal Umum
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Kelola dan tinjau seluruh catatan jurnal transaksi keuangan perusahaan.
                        </p>
                    </div>

                    <Link
                        href="/app/journal-entries/create"
                        className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-600/20 transition-all"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Input Jurnal Baru</span>
                    </Link>
                </div>

                {/* Filters Bar */}
                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row items-center gap-4">
                    <div className="relative flex-1 w-full">
                        <Search className="w-4 h-4 absolute left-3.5 top-3 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Cari deskripsi atau referensi..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                            className="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <select
                            value={periodId}
                            onChange={(e) => setPeriodId(e.target.value)}
                            className="py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Semua Periode</option>
                            {periods?.map((p) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>

                        <select
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="unapproved">Unapproved</option>
                            <option value="posted">Posted</option>
                        </select>

                        <button
                            onClick={handleFilter}
                            className="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity"
                        >
                            Filter
                        </button>

                        {(search || periodId || status) && (
                            <button
                                onClick={handleReset}
                                className="px-3 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            >
                                Reset
                            </button>
                        )}
                    </div>
                </div>

                {/* Journal Table */}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3.5 px-4">Tanggal</th>
                                    <th className="py-3.5 px-4">Referensi</th>
                                    <th className="py-3.5 px-4">Keterangan</th>
                                    <th className="py-3.5 px-4">Rincian Akun & Nominal</th>
                                    <th className="py-3.5 px-4">Status</th>
                                    <th className="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {entries?.data?.length > 0 ? (
                                    entries.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                            <td className="py-4 px-4 whitespace-nowrap text-xs font-mono font-medium text-gray-600 dark:text-gray-400">
                                                {item.entry_date}
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                                {item.reference || '-'}
                                            </td>
                                            <td className="py-4 px-4 text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                                {item.description}
                                            </td>
                                            <td className="py-4 px-4">
                                                <div className="space-y-1">
                                                    {item.lines?.map((line) => (
                                                        <div key={line.id} className={`flex items-center justify-between gap-4 text-xs ${line.credit > 0 ? 'pl-4 text-gray-500' : 'font-medium text-gray-900 dark:text-gray-200'}`}>
                                                            <span>
                                                                <span className="font-mono text-gray-400 mr-2">{line.account?.code}</span>
                                                                {line.account?.name}
                                                            </span>
                                                            <span className="font-mono tabular-nums">
                                                                {line.debit > 0 ? formatRupiah(line.debit) : formatRupiah(line.credit)}
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap">
                                                <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${
                                                    item.status === 'posted'
                                                        ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                                                        : item.status === 'unapproved'
                                                        ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800'
                                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
                                                }`}>
                                                    {item.status === 'posted' && <CheckCircle2 className="w-3.5 h-3.5" />}
                                                    {item.status === 'unapproved' && <Clock className="w-3.5 h-3.5" />}
                                                    {item.status === 'draft' && <FileEdit className="w-3.5 h-3.5" />}
                                                    <span className="capitalize">{item.status}</span>
                                                </span>
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap text-right space-x-2">
                                                {item.status === 'draft' && (
                                                    <button
                                                        onClick={() => router.post(`/app/journal-entries/${item.id}/submit`)}
                                                        className="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition-colors"
                                                    >
                                                        Submit
                                                    </button>
                                                )}

                                                {item.status === 'unapproved' && isOwnerOrFinance && (
                                                    <button
                                                        onClick={() => router.post(`/app/journal-entries/${item.id}/approve`)}
                                                        className="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-500 rounded-lg text-xs font-semibold transition-colors inline-flex items-center gap-1"
                                                    >
                                                        <Check className="w-3.5 h-3.5" />
                                                        <span>Approve</span>
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="py-8 text-center text-gray-500 text-xs">
                                            Tidak ada data jurnal ditemukan.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
