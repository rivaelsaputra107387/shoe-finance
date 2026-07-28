import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { BookOpen, Calendar, Filter } from 'lucide-react';

export default function GeneralLedger({ accounts, periods, selectedAccountId, selectedPeriodId, ledgerData }) {
    const [accountId, setAccountId] = useState(selectedAccountId || '');
    const [periodId, setPeriodId] = useState(selectedPeriodId || '');

    const handleFilter = () => {
        router.get('/app/general-ledger', {
            account_id: accountId,
            fiscal_period_id: periodId,
        }, { preserveState: true, replace: true });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
    };

    return (
        <AppLayout title="Buku Besar (General Ledger)">
            <Head title="Buku Besar - SIA Shoe Workshop" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Buku Besar Per Akun
                    </h2>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Rincian mutasi debit, kredit, dan saldo berjalan per akun COA untuk periode terpilih.
                    </p>
                </div>

                {/* Filter Selector */}
                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row items-center gap-4">
                    <div className="flex-1 w-full">
                        <label className="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            Pilih Akun COA
                        </label>
                        <select
                            value={accountId}
                            onChange={(e) => setAccountId(e.target.value)}
                            className="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            {accounts?.map((acc) => (
                                <option key={acc.id} value={acc.id}>
                                    {acc.code} - {acc.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="w-full md:w-64">
                        <label className="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            Periode Akuntansi
                        </label>
                        <select
                            value={periodId}
                            onChange={(e) => setPeriodId(e.target.value)}
                            className="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            {periods?.map((p) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                    </div>

                    <div className="pt-5 w-full md:w-auto">
                        <button
                            onClick={handleFilter}
                            className="w-full md:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs rounded-xl shadow-md transition-all"
                        >
                            Tampilkan
                        </button>
                    </div>
                </div>

                {/* Ledger Content */}
                {ledgerData ? (
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden space-y-4">
                        <div className="p-6 border-b border-gray-200 dark:border-gray-800 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/40 dark:bg-gray-800/30">
                            <div>
                                <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                    {ledgerData.account?.code} - {ledgerData.account?.name}
                                </h3>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Tipe: {ledgerData.account?.type} | Normal Balance: {ledgerData.account?.normal_balance}
                                </p>
                            </div>

                            <div className="text-right">
                                <span className="text-xs text-gray-500 block">Saldo Awal Periode:</span>
                                <span className="text-base font-mono font-bold text-gray-900 dark:text-white">
                                    {formatRupiah(ledgerData.beginning_balance)}
                                </span>
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <th className="py-3.5 px-4">Tanggal</th>
                                        <th className="py-3.5 px-4">No. Ref</th>
                                        <th className="py-3.5 px-4">Keterangan</th>
                                        <th className="py-3.5 px-4 text-right">Debit</th>
                                        <th className="py-3.5 px-4 text-right">Kredit</th>
                                        <th className="py-3.5 px-4 text-right">Saldo Berjalan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm font-mono">
                                    {ledgerData.transactions?.length > 0 ? (
                                        ledgerData.transactions.map((row, idx) => (
                                            <tr key={idx} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                                <td className="py-3.5 px-4 text-xs whitespace-nowrap text-gray-600 dark:text-gray-400">{row.date}</td>
                                                <td className="py-3.5 px-4 text-xs whitespace-nowrap font-semibold text-gray-900 dark:text-white">{row.reference}</td>
                                                <td className="py-3.5 px-4 text-xs font-sans text-gray-700 dark:text-gray-300 max-w-xs truncate">{row.description}</td>
                                                <td className="py-3.5 px-4 text-right text-xs text-gray-900 dark:text-white">{row.debit > 0 ? formatRupiah(row.debit) : '-'}</td>
                                                <td className="py-3.5 px-4 text-right text-xs text-gray-900 dark:text-white">{row.credit > 0 ? formatRupiah(row.credit) : '-'}</td>
                                                <td className="py-3.5 px-4 text-right text-xs font-bold text-indigo-600 dark:text-indigo-400">{formatRupiah(row.running_balance)}</td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="6" className="py-8 text-center text-gray-500 font-sans text-xs">
                                                Tidak ada transaksi mutasi untuk akun ini pada periode terpilih.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ) : (
                    <div className="p-12 text-center bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 text-gray-500 text-xs">
                        Pilih akun dan periode di atas untuk memuat buku besar.
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
