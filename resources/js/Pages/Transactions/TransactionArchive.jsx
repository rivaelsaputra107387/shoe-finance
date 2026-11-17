import React, { useState, useEffect, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import {
    Archive,
    Search,
    ArrowUpRight,
    ArrowDownLeft,
    X,
    RotateCcw,
    Calendar,
    Eye,
    ShieldCheck,
} from 'lucide-react';

const formatRupiah = (amount) =>
    'Rp ' + parseFloat(amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 0 });

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

export default function TransactionArchive({ mutations, filters = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [bankSource, setBankSource] = useState(filters.bank_source || '');
    const [mutationType, setMutationType] = useState(filters.mutation_type || '');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');
    const [datePreset, setDatePreset] = useState(
        filters.start_date || filters.end_date ? 'custom' : 'all'
    );

    const isInitialMount = useRef(true);

    useEffect(() => {
        if (isInitialMount.current) {
            isInitialMount.current = false;
            return;
        }

        const timer = setTimeout(() => {
            router.get('/app/transaction-archive', {
                search,
                bank_source: bankSource,
                mutation_type: mutationType,
                start_date: startDate,
                end_date: endDate,
            }, { preserveState: true, replace: true });
        }, 300);

        return () => clearTimeout(timer);
    }, [search, bankSource, mutationType, startDate, endDate]);

    const handlePresetChange = (preset) => {
        setDatePreset(preset);
        const today = new Date();
        const fmt = (d) => d.toISOString().split('T')[0];

        if (preset === 'all') {
            setStartDate('');
            setEndDate('');
        } else if (preset === 'today') {
            setStartDate(fmt(today));
            setEndDate(fmt(today));
        } else if (preset === 'last_7_days') {
            const past = new Date(today);
            past.setDate(past.getDate() - 7);
            setStartDate(fmt(past));
            setEndDate(fmt(today));
        } else if (preset === 'this_month') {
            const first = new Date(today.getFullYear(), today.getMonth(), 1);
            setStartDate(fmt(first));
            setEndDate(fmt(today));
        } else if (preset === 'last_month') {
            const first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const last = new Date(today.getFullYear(), today.getMonth(), 0);
            setStartDate(fmt(first));
            setEndDate(fmt(last));
        }
    };

    const handleReset = () => {
        setSearch('');
        setBankSource('');
        setMutationType('');
        setStartDate('');
        setEndDate('');
        setDatePreset('all');
        router.get('/app/transaction-archive', {}, { preserveState: true, replace: true });
    };

    const hasActiveFilters = search || bankSource || mutationType || startDate || endDate || datePreset !== 'all';

    // unique bank sources from current page for filter dropdown (dynamic)
    const uniqueBanks = [...new Set((mutations?.data || []).map(m => m.bank_source).filter(Boolean))];

    return (
        <AppLayout title="Arsip Transaksi">
            <Head title="Arsip Transaksi - SIA Shoe Workshop" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                            <Archive className="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                            Arsip Transaksi
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Riwayat transaksi yang telah disetujui dan masuk ke Jurnal Final. Data tidak dapat dihapus.
                        </p>
                    </div>
                    <div className="flex items-center gap-2 px-3 py-2 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs text-emerald-700 dark:text-emerald-400 font-semibold">
                        <ShieldCheck className="w-4 h-4" />
                        <span>Data Terkunci — Hanya Baca</span>
                    </div>
                </div>

                {/* Real-time Filter Bar */}
                <div className="p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                    <div className="flex items-center justify-between">
                        <h3 className="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Filter Otomatis (Real-time)
                        </h3>
                        {hasActiveFilters && (
                            <button
                                onClick={handleReset}
                                className="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-700 font-semibold"
                            >
                                <RotateCcw className="w-3.5 h-3.5" />
                                Reset Filter
                            </button>
                        )}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        {/* Search */}
                        <div className="relative lg:col-span-1">
                            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                Cari Deskripsi
                            </label>
                            <div className="relative">
                                <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari deskripsi transaksi..."
                                    className="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl pl-9 pr-8 py-2 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                {search && (
                                    <button onClick={() => setSearch('')} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <X className="w-3.5 h-3.5" />
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Bank Source */}
                        <div>
                            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                Sumber Bank
                            </label>
                            <CustomSelect
                                value={bankSource}
                                onChange={(e) => setBankSource(e.target.value)}
                                className="w-full text-xs"
                            >
                                <option value="">Semua Bank</option>
                                <option value="BCA">BCA</option>
                                <option value="MANDIRI">Mandiri</option>
                                <option value="BRI">BRI</option>
                                <option value="BNI">BNI</option>
                                <option value="CASH">Kas</option>
                            </CustomSelect>
                        </div>

                        {/* Mutation Type */}
                        <div>
                            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                Tipe Transaksi
                            </label>
                            <CustomSelect
                                value={mutationType}
                                onChange={(e) => setMutationType(e.target.value)}
                                className="w-full text-xs"
                            >
                                <option value="">Masuk & Keluar</option>
                                <option value="IN">Masuk (IN)</option>
                                <option value="OUT">Keluar (OUT)</option>
                            </CustomSelect>
                        </div>

                        {/* Date Preset */}
                        <div>
                            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                Rentang Tanggal
                            </label>
                            <CustomSelect
                                value={datePreset}
                                onChange={(e) => handlePresetChange(e.target.value)}
                                className="w-full text-xs"
                            >
                                <option value="all">📅 Semua Waktu</option>
                                <option value="today">Hari Ini</option>
                                <option value="last_7_days">7 Hari Terakhir</option>
                                <option value="this_month">Bulan Ini</option>
                                <option value="last_month">Bulan Lalu</option>
                                <option value="custom">Kustom Tanggal...</option>
                            </CustomSelect>
                        </div>
                    </div>

                    {/* Expandable Custom Date */}
                    {datePreset === 'custom' && (
                        <div className="pt-3 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row items-center gap-3 text-xs animate-in fade-in">
                            <div className="flex items-center gap-2 text-gray-500 font-semibold">
                                <Calendar className="w-4 h-4 text-indigo-600" />
                                <span>Pilih Rentang Tanggal Kustom:</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                    className="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-1.5 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                <span className="text-gray-400">s/d</span>
                                <input
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                    className="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-1.5 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                />
                            </div>
                        </div>
                    )}
                </div>

                {/* Table */}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3.5 px-4">Tanggal</th>
                                    <th className="py-3.5 px-4">Deskripsi</th>
                                    <th className="py-3.5 px-4">Bank</th>
                                    <th className="py-3.5 px-4 text-center">Tipe</th>
                                    <th className="py-3.5 px-4 text-right">Nominal</th>
                                    <th className="py-3.5 px-4">No. Jurnal Final</th>
                                    <th className="py-3.5 px-4">Diarsipkan Oleh</th>
                                    <th className="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {mutations?.data?.length > 0 ? (
                                    mutations.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-indigo-50/30 dark:hover:bg-indigo-950/10 transition-colors">
                                            <td className="py-3 px-4 text-xs font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                {formatDate(item.date)}
                                            </td>
                                            <td className="py-3 px-4 text-xs text-gray-700 dark:text-gray-300 max-w-[280px] truncate">
                                                {item.description}
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800">
                                                    {item.bank_source}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold ${
                                                    item.mutation_type === 'IN'
                                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800'
                                                        : 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800'
                                                }`}>
                                                    {item.mutation_type === 'IN'
                                                        ? <><ArrowDownLeft className="w-3 h-3" /><span>Masuk</span></>
                                                        : <><ArrowUpRight className="w-3 h-3" /><span>Keluar</span></>
                                                    }
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-right text-xs font-bold font-mono text-gray-900 dark:text-white whitespace-nowrap">
                                                {formatRupiah(item.amount)}
                                            </td>
                                            <td className="py-3 px-4 text-xs">
                                                {item.journal_entry ? (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800 font-mono">
                                                        {item.journal_entry.reference}
                                                    </span>
                                                ) : (
                                                    <span className="text-gray-400 text-[11px]">—</span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4 text-xs text-gray-600 dark:text-gray-400">
                                                {item.poster?.name || item.uploader?.name || '—'}
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                {item.journal_entry && (
                                                    <a
                                                        href={`/app/journal-entries/${item.journal_entry.id}`}
                                                        className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 transition-colors border border-indigo-200 dark:border-indigo-800/60"
                                                        title="Lihat Jurnal Final"
                                                    >
                                                        <Eye className="w-3.5 h-3.5" />
                                                        <span>Lihat Jurnal</span>
                                                    </a>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8" className="py-12 text-center">
                                            <div className="flex flex-col items-center gap-3 text-gray-400 dark:text-gray-600">
                                                <Archive className="w-10 h-10 opacity-30" />
                                                <div>
                                                    <p className="text-sm font-semibold">Belum Ada Arsip Transaksi</p>
                                                    <p className="text-xs mt-1">Transaksi yang sudah disetujui jurnal finalnya akan muncul di sini.</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {mutations?.links?.length > 3 && (
                        <div className="p-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500">
                            <div>
                                Menampilkan {mutations.from}–{mutations.to} dari {mutations.total} transaksi terarsip
                            </div>
                            <div className="flex items-center gap-1">
                                {mutations.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        onClick={() => link.url && router.get(link.url)}
                                        disabled={!link.url}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1 rounded-lg text-xs font-medium transition-colors ${
                                            link.active
                                                ? 'bg-indigo-600 text-white font-bold'
                                                : link.url
                                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                                : 'text-gray-400 cursor-not-allowed'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
