import React, { useState, useEffect, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import AccountSelect from '@/Components/AccountSelect';
import LedgerDateFilter from '@/Components/LedgerDateFilter';
import { BookOpen, Calendar, Filter, Printer, FileText, FileSpreadsheet, RotateCcw } from 'lucide-react';

export default function GeneralLedger({
    accounts,
    periods,
    selectedAccountId,
    selectedPeriodId,
    selectedDay,
    selectedStartDate,
    selectedEndDate,
    ledgerData,
}) {
    const [accountId, setAccountId] = useState(selectedAccountId || '');
    const [periodId, setPeriodId] = useState(selectedPeriodId || '');
    const [day, setDay] = useState(selectedDay || null);
    const [startDate, setStartDate] = useState(selectedStartDate || '');
    const [endDate, setEndDate] = useState(selectedEndDate || '');

    useEffect(() => {
        if (selectedAccountId) setAccountId(selectedAccountId);
        if (selectedPeriodId) setPeriodId(selectedPeriodId);
        setDay(selectedDay || null);
        setStartDate(selectedStartDate || '');
        setEndDate(selectedEndDate || '');
    }, [selectedAccountId, selectedPeriodId, selectedDay, selectedStartDate, selectedEndDate]);

    const currentPeriod = useMemo(() => {
        return periods?.find((p) => p.id === Number(periodId));
    }, [periods, periodId]);

    const executeFilter = (accId = accountId, perId = periodId, d = day, sDate = startDate, eDate = endDate) => {
        const params = {
            account_id: accId,
            fiscal_period_id: perId,
        };
        if (d) params.day = d;
        if (sDate) params.start_date = sDate;
        if (eDate) params.end_date = eDate;

        router.get('/app/general-ledger', params, { replace: true, preserveState: true });
    };

    const handleAccountChange = (val) => {
        setAccountId(val);
        executeFilter(val, periodId, day, startDate, endDate);
    };

    const handlePeriodChange = (val) => {
        setPeriodId(val);
        // Reset date filter when period changes to keep filter within bounds
        setDay(null);
        setStartDate('');
        setEndDate('');
        executeFilter(accountId, val, null, '', '');
    };

    const handleDateFilterChange = ({ day: newDay, startDate: newStart, endDate: newEnd }) => {
        setDay(newDay);
        setStartDate(newStart || '');
        setEndDate(newEnd || '');
        executeFilter(accountId, periodId, newDay, newStart || '', newEnd || '');
    };

    const handleResetDateFilter = () => {
        setDay(null);
        setStartDate('');
        setEndDate('');
        executeFilter(accountId, periodId, null, '', '');
    };

    const exportQuery = useMemo(() => {
        return new URLSearchParams(
            Object.fromEntries(
                Object.entries({
                    account_id: accountId,
                    fiscal_period_id: periodId,
                    day: day || '',
                    start_date: startDate || '',
                    end_date: endDate || '',
                }).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
            )
        ).toString();
    }, [accountId, periodId, day, startDate, endDate]);

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
    };

    const isDateFiltered = Boolean(day || (startDate && endDate));

    const activeDateLabel = useMemo(() => {
        if (day) {
            return `Tanggal ${day < 10 ? `0${day}` : day}`;
        }
        if (startDate && endDate) {
            if (startDate === endDate) {
                return `Tanggal ${startDate.slice(8, 10)}`;
            }
            const s = startDate.split('-');
            const e = endDate.split('-');
            return `${s[2]}/${s[1]}/${s[0]} s/d ${e[2]}/${e[1]}/${e[0]}`;
        }
        return '';
    }, [day, startDate, endDate]);

    return (
        <AppLayout title="Buku Besar (General Ledger)">
            <Head title="Buku Besar - SIA Shoe Workshop" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <img src="/logo.png" alt="Shoe Workshop Logo" className="h-10 w-auto object-contain" />
                        <div>
                            <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                                Buku Besar Per Akun
                            </h2>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Shoe Workshop Finance • Rincian mutasi debit, kredit, dan saldo berjalan per akun.
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 self-end sm:self-auto">
                        <a
                            href={`/app/general-ledger/export?format=pdf&${exportQuery}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="px-4 py-2.5 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                            title="Export Laporan Buku Besar ke PDF"
                        >
                            <FileText className="w-4 h-4" />
                            <span className="hidden sm:inline">PDF</span>
                        </a>
                        
                        <a
                            href={`/app/general-ledger/export?format=excel&${exportQuery}`}
                            className="px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 transition-colors inline-flex items-center gap-2 shadow-sm"
                            title="Export Laporan Buku Besar ke Excel"
                        >
                            <FileSpreadsheet className="w-4 h-4" />
                            <span className="hidden sm:inline">Excel</span>
                        </a>
                    </div>
                </div>

                {/* Filter Selector */}
                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row items-center gap-4">
                    <div className="flex-1 w-full">
                        <label className="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            Pilih Akun COA
                        </label>
                        <AccountSelect
                            accounts={accounts}
                            value={accountId}
                            onChange={handleAccountChange}
                            placeholder="Cari atau pilih akun..."
                        />
                    </div>

                    <div className="w-full md:w-56">
                        <label className="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            Periode Akuntansi
                        </label>
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

                    <div className="w-full md:w-auto">
                        <label className="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            Filter Tanggal
                        </label>
                        <LedgerDateFilter
                            period={currentPeriod}
                            selectedDay={day}
                            selectedStartDate={startDate}
                            selectedEndDate={endDate}
                            onFilterChange={handleDateFilterChange}
                        />
                    </div>
                </div>

                {/* Active Date Filter Notice Banner */}
                {isDateFiltered && (
                    <div className="px-4 py-2.5 bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/60 rounded-xl flex items-center justify-between text-xs text-emerald-800 dark:text-emerald-300 animate-in fade-in">
                        <div className="flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <span>
                                Menampilkan mutasi transaksi <strong>{activeDateLabel}</strong> ({currentPeriod?.name || 'Periode Terpilih'}).
                                Saldo Awal dihitung otomatis hingga sebelum tanggal tersebut.
                            </span>
                        </div>
                        <button
                            type="button"
                            onClick={handleResetDateFilter}
                            className="inline-flex items-center gap-1 font-semibold text-emerald-700 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-200 underline text-xs cursor-pointer ml-3 shrink-0"
                        >
                            <RotateCcw className="w-3.5 h-3.5" />
                            <span>Tampilkan 1 Bulan Penuh</span>
                        </button>
                    </div>
                )}

                {/* Header Summary Cards */}
                {ledgerData && (
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                            <p className="text-xs text-gray-500">
                                Saldo Awal {isDateFiltered && <span className="text-[10px] text-emerald-600 font-semibold">(s/d awal filter)</span>}
                            </p>
                            <p className="text-lg font-bold font-mono text-gray-900 dark:text-white mt-1">
                                {formatRupiah(ledgerData.starting_balance)}
                            </p>
                        </div>
                        <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                            <p className="text-xs text-gray-500">Total Mutasi Debet</p>
                            <p className="text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                                {formatRupiah(ledgerData.total_debit)}
                            </p>
                        </div>
                        <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                            <p className="text-xs text-gray-500">Total Mutasi Kredit</p>
                            <p className="text-lg font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">
                                {formatRupiah(ledgerData.total_credit)}
                            </p>
                        </div>
                        <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm bg-emerald-50/30 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900">
                            <p className="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                Saldo Akhir Berjalan {isDateFiltered && <span className="text-[10px] text-emerald-600 font-semibold">(s/d akhir filter)</span>}
                            </p>
                            <p className="text-lg font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-1">
                                {formatRupiah(ledgerData.ending_balance)}
                            </p>
                        </div>
                    </div>
                )}

                {/* Ledger Table */}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3.5 px-4">Tanggal</th>
                                    <th className="py-3.5 px-4">Referensi</th>
                                    <th className="py-3.5 px-4">Keterangan</th>
                                    <th className="py-3.5 px-4 text-right">Debet</th>
                                    <th className="py-3.5 px-4 text-right">Kredit</th>
                                    <th className="py-3.5 px-4 text-right">Saldo Berjalan</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {ledgerData?.rows?.length > 0 ? (
                                    ledgerData.rows.map((row, idx) => (
                                        <tr key={idx} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                            <td className="py-3.5 px-4 whitespace-nowrap text-xs font-mono text-gray-600 dark:text-gray-400">
                                                {row.date}
                                            </td>
                                            <td className="py-3.5 px-4 whitespace-nowrap font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                                {row.reference}
                                            </td>
                                            <td className="py-3.5 px-4 text-gray-800 dark:text-gray-200 text-xs">
                                                {row.description}
                                            </td>
                                            <td className="py-3.5 px-4 whitespace-nowrap text-right font-mono text-xs text-gray-900 dark:text-white">
                                                {row.debit > 0 ? formatRupiah(row.debit) : '-'}
                                            </td>
                                            <td className="py-3.5 px-4 whitespace-nowrap text-right font-mono text-xs text-gray-900 dark:text-white">
                                                {row.credit > 0 ? formatRupiah(row.credit) : '-'}
                                            </td>
                                            <td className="py-3.5 px-4 whitespace-nowrap text-right font-mono text-xs font-bold text-gray-900 dark:text-white">
                                                {formatRupiah(row.running_balance)}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="py-8 text-center text-gray-500 text-xs">
                                            {isDateFiltered
                                                ? `Tidak ada mutasi transaksi untuk akun ini pada ${activeDateLabel}.`
                                                : 'Tidak ada mutasi transaksi untuk akun ini pada periode terpilih.'}
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
