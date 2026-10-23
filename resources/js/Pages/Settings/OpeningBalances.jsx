import React, { useState, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import {
    Coins,
    CheckCircle2,
    AlertCircle,
    Calendar,
    Save,
    Search,
    RotateCcw,
    Lock,
    ArrowRight,
    Scale,
    FileCheck2,
    Info,
} from 'lucide-react';

export default function OpeningBalances({
    periods = [],
    selectedPeriod,
    accounts = [],
    openingJournal,
    canEdit = true,
}) {
    // Initialise balances state from accounts prop
    const [balances, setBalances] = useState(() => {
        const initial = {};
        accounts.forEach((acc) => {
            initial[acc.id] = acc.amount || 0;
        });
        return initial;
    });

    const [entryDate, setEntryDate] = useState(() => {
        if (openingJournal?.entry_date) return openingJournal.entry_date;
        if (selectedPeriod?.start_date) {
            return selectedPeriod.start_date.substring(0, 10);
        }
        return new Date().toISOString().substring(0, 10);
    });

    const [search, setSearch] = useState('');
    const [selectedType, setSelectedType] = useState('Semua');
    const [processing, setProcessing] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    // Calculate live totals
    const { totalDebit, totalCredit, difference, isBalanced, filledCount } = useMemo(() => {
        let deb = 0;
        let cre = 0;
        let count = 0;

        accounts.forEach((acc) => {
            const val = parseFloat(balances[acc.id]) || 0;
            if (val > 0) {
                count++;
                if (acc.normal_balance === 'Debet') {
                    deb += val;
                } else {
                    cre += val;
                }
            }
        });

        const diff = Math.abs(deb - cre);
        const balanced = diff < 0.01 && deb > 0;

        return {
            totalDebit: deb,
            totalCredit: cre,
            difference: diff,
            isBalanced: balanced,
            filledCount: count,
        };
    }, [balances, accounts]);

    // Filter accounts by search & type tab
    const filteredAccounts = useMemo(() => {
        return accounts.filter((acc) => {
            const matchSearch =
                acc.code.toLowerCase().includes(search.toLowerCase()) ||
                acc.name.toLowerCase().includes(search.toLowerCase());

            const matchType = selectedType === 'Semua' || acc.type === selectedType;

            return matchSearch && matchType;
        });
    }, [accounts, search, selectedType]);

    const handleAmountChange = (accountId, val) => {
        if (!canEdit) return;
        // Strip non-digits except comma/period
        const cleanVal = val.replace(/[^0-9]/g, '');
        const num = cleanVal === '' ? 0 : parseFloat(cleanVal);
        setBalances((prev) => ({
            ...prev,
            [accountId]: num,
        }));
    };

    const handlePeriodChange = (e) => {
        const periodId = e.target.value;
        router.get(
            '/app/opening-balances',
            { fiscal_period_id: periodId },
            { preserveState: false }
        );
    };

    const handleReset = () => {
        if (!canEdit) return;
        if (confirm('Kembalikan semua input ke saldo sebelumnya?')) {
            const resetObj = {};
            accounts.forEach((acc) => {
                resetObj[acc.id] = acc.amount || 0;
            });
            setBalances(resetObj);
            setErrorMessage('');
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!canEdit || !isBalanced || processing) return;

        setProcessing(true);
        setErrorMessage('');

        const payloadBalances = Object.keys(balances).map((accId) => ({
            account_id: parseInt(accId),
            amount: balances[accId] || 0,
        }));

        router.post(
            '/app/opening-balances',
            {
                fiscal_period_id: selectedPeriod.id,
                entry_date: entryDate,
                balances: payloadBalances,
            },
            {
                onError: (errors) => {
                    setErrorMessage(errors.balances || errors.fiscal_period_id || 'Gagal menyimpan saldo awal.');
                    setProcessing(false);
                },
                onFinish: () => {
                    setProcessing(false);
                },
            }
        );
    };

    return (
        <AppLayout title="Saldo Awal Neraca">
            <Head title="Saldo Awal Neraca - SIA Shoe Workshop" />

            <div className="space-y-6 pb-24">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-xl">
                                <Coins className="w-5 h-5" />
                            </span>
                            <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                                Saldo Awal Neraca (Opening Balance)
                            </h2>
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-2xl">
                            Inisialisasi posisi saldo awal seluruh akun Neraca (Aset, Kewajiban, dan Ekuitas) saat migrasi ke sistem Finlog.
                            Sistem mewajibkan <strong>Total Debit = Total Kredit</strong> agar pembukuan dijamin seimbang dari hari pertama.
                        </p>
                    </div>

                    {/* Period Selector & Entry Date */}
                    <div className="flex flex-wrap items-center gap-3">
                        <div>
                            <label className="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1">
                                Periode Akuntansi
                            </label>
                            <div className="relative">
                                <select
                                    value={selectedPeriod?.id || ''}
                                    onChange={handlePeriodChange}
                                    className="text-xs font-semibold pl-3 pr-8 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                >
                                    {periods.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} {p.status === 'closed' ? '(Tertutup)' : '(Aktif)'}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1">
                                Tanggal Cut-Off Jurnal
                            </label>
                            <input
                                type="date"
                                value={entryDate}
                                onChange={(e) => setEntryDate(e.target.value)}
                                disabled={!canEdit}
                                className="text-xs font-semibold px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none disabled:opacity-50"
                            />
                        </div>
                    </div>
                </div>

                {/* Status Notice if Closed or Saved */}
                {selectedPeriod?.status === 'closed' && (
                    <div className="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex items-center gap-3 text-amber-800 dark:text-amber-300 text-xs">
                        <Lock className="w-5 h-5 flex-shrink-0" />
                        <div>
                            <strong>Periode ini sudah ditutup.</strong> Saldo Awal terkunci secara permanen dan tidak dapat diedit untuk menjaga integritas laporan historis.
                        </div>
                    </div>
                )}

                {openingJournal && (
                    <div className="p-4 rounded-2xl bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-between text-xs text-emerald-800 dark:text-emerald-300">
                        <div className="flex items-center gap-2.5">
                            <FileCheck2 className="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400" />
                            <div>
                                <span>Saldo awal periode ini telah aktif dengan referensi jurnal </span>
                                <strong className="font-mono bg-emerald-100 dark:bg-emerald-900/60 px-1.5 py-0.5 rounded">
                                    {openingJournal.reference}
                                </strong>
                                <span className="text-emerald-600 dark:text-emerald-400 ml-1">
                                    (Terakhir diperbarui: {openingJournal.updated_at})
                                </span>
                            </div>
                        </div>
                        <span className="inline-flex items-center gap-1 font-semibold px-2.5 py-1 bg-emerald-600 text-white rounded-lg text-[11px]">
                            Status: Posted
                        </span>
                    </div>
                )}

                {errorMessage && (
                    <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 flex items-center gap-3 text-rose-700 dark:text-rose-300 text-xs">
                        <AlertCircle className="w-5 h-5 flex-shrink-0" />
                        <div>{errorMessage}</div>
                    </div>
                )}

                {/* 4 Metrics & Balance Status Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Card 1: Total Debit */}
                    <div className="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm space-y-1">
                        <span className="text-[11px] font-semibold text-gray-400 uppercase tracking-wider block">
                            Total Saldo Debet (Aset)
                        </span>
                        <div className="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">
                            {formatRupiah(totalDebit)}
                        </div>
                        <p className="text-[11px] text-gray-500 dark:text-gray-400">
                            Harta lancar, kas, bank & aset tetap
                        </p>
                    </div>

                    {/* Card 2: Total Kredit */}
                    <div className="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm space-y-1">
                        <span className="text-[11px] font-semibold text-gray-400 uppercase tracking-wider block">
                            Total Saldo Kredit (Utang + Modal)
                        </span>
                        <div className="text-2xl font-extrabold text-blue-600 dark:text-blue-400 font-mono">
                            {formatRupiah(totalCredit)}
                        </div>
                        <p className="text-[11px] text-gray-500 dark:text-gray-400">
                            Kewajiban, modal disetor & laba ditahan
                        </p>
                    </div>

                    {/* Card 3: Selisih / Difference */}
                    <div className="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm space-y-1">
                        <span className="text-[11px] font-semibold text-gray-400 uppercase tracking-wider block">
                            Selisih (Unbalance)
                        </span>
                        <div
                            className={`text-2xl font-extrabold font-mono ${
                                difference < 0.01
                                    ? 'text-gray-400 dark:text-gray-500'
                                    : 'text-rose-600 dark:text-rose-400'
                            }`}
                        >
                            {formatRupiah(difference)}
                        </div>
                        <p className="text-[11px] text-gray-500 dark:text-gray-400">
                            Wajib Rp 0 sebelum dapat disimpan
                        </p>
                    </div>

                    {/* Card 4: Status Keseimbangan */}
                    <div
                        className={`p-5 rounded-2xl border shadow-sm space-y-1 transition-all ${
                            isBalanced
                                ? 'bg-emerald-50/60 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200'
                                : totalDebit === 0
                                ? 'bg-gray-50 dark:bg-gray-800/40 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400'
                                : 'bg-rose-50/60 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800 text-rose-900 dark:text-rose-200'
                        }`}
                    >
                        <span className="text-[11px] font-semibold uppercase tracking-wider block opacity-75">
                            Status Keseimbangan
                        </span>
                        <div className="flex items-center gap-2">
                            {isBalanced ? (
                                <>
                                    <CheckCircle2 className="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                                    <span className="text-xl font-extrabold text-emerald-600 dark:text-emerald-400">
                                        100% BALANCE
                                    </span>
                                </>
                            ) : totalDebit === 0 ? (
                                <>
                                    <Info className="w-5 h-5 text-gray-400" />
                                    <span className="text-base font-bold text-gray-500">
                                        Menunggu Input
                                    </span>
                                </>
                            ) : (
                                <>
                                    <AlertCircle className="w-6 h-6 text-rose-600 dark:text-rose-400 animate-pulse" />
                                    <span className="text-base font-bold text-rose-600 dark:text-rose-400">
                                        BELUM BALANCE
                                    </span>
                                </>
                            )}
                        </div>
                        <p className="text-[11px] opacity-80">
                            {isBalanced
                                ? `${filledCount} akun terisi dengan seimbang`
                                : totalDebit === 0
                                ? 'Ketik saldo di tabel bawah'
                                : `Koreksi selisih ${formatRupiah(difference)}`}
                        </p>
                    </div>
                </div>

                {/* Filter and Search Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                    {/* Category Tabs */}
                    <div className="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0">
                        {['Semua', 'Aset', 'Kewajiban', 'Ekuitas'].map((t) => (
                            <button
                                key={t}
                                type="button"
                                onClick={() => setSelectedType(t)}
                                className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors ${
                                    selectedType === t
                                        ? 'bg-emerald-600 text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
                                }`}
                            >
                                {t}
                            </button>
                        ))}
                    </div>

                    {/* Search Field */}
                    <div className="relative w-full sm:w-72">
                        <Search className="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            placeholder="Cari kode atau nama akun..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full pl-9 pr-3 py-1.5 text-xs bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        />
                    </div>
                </div>

                {/* Table Section */}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr className="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider text-[11px]">
                                    <th className="py-3 px-4 w-28">Kode Akun</th>
                                    <th className="py-3 px-4">Nama Akun</th>
                                    <th className="py-3 px-4 w-32">Kategori</th>
                                    <th className="py-3 px-4 w-28 text-center">Saldo Normal</th>
                                    <th className="py-3 px-4 w-60 text-right">Input Saldo Awal (Rp)</th>
                                    <th className="py-3 px-4 w-40 text-right">Posisi Debet</th>
                                    <th className="py-3 px-4 w-40 text-right">Posisi Kredit</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800/70">
                                {filteredAccounts.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="py-8 text-center text-gray-400">
                                            Tidak ada akun yang sesuai dengan pencarian.
                                        </td>
                                    </tr>
                                ) : (
                                    filteredAccounts.map((acc) => {
                                        const amount = balances[acc.id] || 0;
                                        const isDebit = acc.normal_balance === 'Debet';

                                        return (
                                            <tr
                                                key={acc.id}
                                                className={`hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors ${
                                                    amount > 0 ? 'bg-emerald-50/30 dark:bg-emerald-950/10' : ''
                                                }`}
                                            >
                                                {/* Kode Akun */}
                                                <td className="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                                    {acc.code}
                                                </td>

                                                {/* Nama Akun */}
                                                <td className="py-3 px-4 font-medium text-gray-800 dark:text-gray-200">
                                                    {acc.name}
                                                </td>

                                                {/* Tipe Akun */}
                                                <td className="py-3 px-4">
                                                    <span
                                                        className={`inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                                                            acc.type === 'Aset'
                                                                ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50'
                                                                : acc.type === 'Kewajiban'
                                                                ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50'
                                                                : 'bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800/50'
                                                        }`}
                                                    >
                                                        {acc.type}
                                                    </span>
                                                </td>

                                                {/* Saldo Normal */}
                                                <td className="py-3 px-4 text-center">
                                                    <span
                                                        className={`inline-block px-2 py-0.5 rounded text-[10px] font-semibold font-mono ${
                                                            isDebit
                                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                                                        }`}
                                                    >
                                                        {acc.normal_balance}
                                                    </span>
                                                </td>

                                                {/* Input Saldo Awal */}
                                                <td className="py-2.5 px-4 text-right">
                                                    <div className="relative inline-block w-full max-w-[220px]">
                                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-semibold text-xs pointer-events-none">
                                                            Rp
                                                        </span>
                                                        <input
                                                            type="text"
                                                            value={
                                                                amount > 0
                                                                    ? new Intl.NumberFormat('id-ID').format(amount)
                                                                    : ''
                                                            }
                                                            placeholder="0"
                                                            onChange={(e) =>
                                                                handleAmountChange(acc.id, e.target.value)
                                                            }
                                                            disabled={!canEdit}
                                                            className={`w-full text-right font-mono font-bold text-xs pl-9 pr-3 py-1.5 rounded-xl border focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all ${
                                                                amount > 0
                                                                    ? 'bg-white dark:bg-gray-800 border-emerald-400 dark:border-emerald-600 text-gray-900 dark:text-white shadow-sm'
                                                                    : 'bg-gray-50/70 dark:bg-gray-800/40 border-gray-200 dark:border-gray-700 text-gray-400'
                                                            } disabled:opacity-50`}
                                                        />
                                                    </div>
                                                </td>

                                                {/* Posisi Debet Preview */}
                                                <td className="py-3 px-4 text-right font-mono font-semibold">
                                                    {isDebit && amount > 0 ? (
                                                        <span className="text-emerald-600 dark:text-emerald-400">
                                                            {formatRupiah(amount)}
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-300 dark:text-gray-700">—</span>
                                                    )}
                                                </td>

                                                {/* Posisi Kredit Preview */}
                                                <td className="py-3 px-4 text-right font-mono font-semibold">
                                                    {!isDebit && amount > 0 ? (
                                                        <span className="text-blue-600 dark:text-blue-400">
                                                            {formatRupiah(amount)}
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-300 dark:text-gray-700">—</span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Sticky Bottom Action Bar */}
                <div className="fixed bottom-0 left-0 lg:left-64 right-0 z-30 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-t border-gray-200 dark:border-gray-800 py-3.5 px-4 lg:px-8 shadow-2xl">
                    <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                        {/* Live Quick Counter */}
                        <div className="flex items-center gap-6 text-xs">
                            <div className="flex items-center gap-2">
                                <span className="text-gray-400">Debit:</span>
                                <strong className="font-mono text-emerald-600 dark:text-emerald-400 text-sm">
                                    {formatRupiah(totalDebit)}
                                </strong>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-400">Kredit:</span>
                                <strong className="font-mono text-blue-600 dark:text-blue-400 text-sm">
                                    {formatRupiah(totalCredit)}
                                </strong>
                            </div>
                            <div className="flex items-center gap-2 border-l border-gray-200 dark:border-gray-700 pl-4">
                                <span className="text-gray-400">Selisih:</span>
                                <strong
                                    className={`font-mono text-sm ${
                                        difference < 0.01
                                            ? 'text-gray-400'
                                            : 'text-rose-600 dark:text-rose-400'
                                    }`}
                                >
                                    {formatRupiah(difference)}
                                </strong>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center gap-3 w-full sm:w-auto justify-end">
                            <button
                                type="button"
                                onClick={handleReset}
                                disabled={!canEdit || processing}
                                className="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors flex items-center gap-1.5 disabled:opacity-40"
                            >
                                <RotateCcw className="w-3.5 h-3.5" />
                                <span>Reset</span>
                            </button>

                            <button
                                type="button"
                                onClick={handleSubmit}
                                disabled={!canEdit || !isBalanced || processing}
                                className={`px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg transition-all flex items-center justify-center gap-2 ${
                                    canEdit && isBalanced && !processing
                                        ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20 transform hover:-translate-y-0.5 cursor-pointer'
                                        : 'bg-gray-200 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                }`}
                                title={
                                    !canEdit
                                        ? 'Periode terkunci'
                                        : !isBalanced
                                        ? 'Total Debit dan Kredit wajib seimbang (selisih Rp 0)'
                                        : 'Simpan Saldo Awal'
                                }
                            >
                                <Save className="w-4 h-4" />
                                <span>{processing ? 'Menyimpan...' : 'Simpan Saldo Awal'}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
