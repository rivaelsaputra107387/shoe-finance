import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import {
    Lock,
    LockOpen,
    AlertCircle,
    CheckCircle2,
    Calendar,
    ArrowRight,
    TrendingUp,
    ShieldAlert,
    Clock,
    FileText,
} from 'lucide-react';

export default function PeriodClosing({ activePeriod, closedPeriods }) {
    const [showModal, setShowModal] = useState(false);
    const [processing, setProcessing] = useState(false);

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    const handleExecuteClosing = () => {
        if (!activePeriod?.id) return;
        setProcessing(true);

        router.post('/app/period-closing/execute', {
            fiscal_period_id: activePeriod.id,
        }, {
            onFinish: () => {
                setProcessing(false);
                setShowModal(false);
            },
        });
    };

    return (
        <AppLayout title="Penutupan Periode Akuntansi">
            <Head title="Penutupan Periode - SIA Shoe Workshop" />

            <div className="space-y-8">
                {/* Header Title */}
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Penutupan Periode (Period Closing)
                    </h2>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Proses khusus Owner untuk memindahkan saldo akun nominal (Pendapatan & Beban) ke Modal melalui Jurnal Penutup dan mengaktifkan periode akuntansi berikutnya.
                    </p>
                </div>

                {/* Active Period Card */}
                {activePeriod ? (
                    <div className="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-md p-6 md:p-8 space-y-6">
                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-gray-100 dark:border-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                                    <LockOpen className="w-7 h-7" />
                                </div>
                                <div>
                                    <span className="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest block">
                                        Periode Berjalan (OPEN)
                                    </span>
                                    <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-0.5">
                                        {activePeriod.name}
                                    </h3>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Rentang: {activePeriod.start_date} – {activePeriod.end_date}
                                    </p>
                                </div>
                            </div>

                            <button
                                onClick={() => setShowModal(true)}
                                disabled={!activePeriod.can_close}
                                className={`px-6 py-3 rounded-2xl font-bold text-xs shadow-lg transition-all flex items-center justify-center gap-2 ${
                                    activePeriod.can_close
                                        ? 'bg-rose-600 hover:bg-rose-500 text-white shadow-rose-600/20 transform hover:-translate-y-0.5 cursor-pointer'
                                        : 'bg-gray-200 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                }`}
                            >
                                <Lock className="w-4 h-4" />
                                <span>Tutup Periode Ini</span>
                            </button>
                        </div>

                        {/* Readiness Checks & Metrics Grid */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                            {/* Metric 1: Posted Transactions */}
                            <div className="p-5 rounded-2xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200/60 dark:border-gray-700/50 space-y-2">
                                <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>Jurnal Ter-Posting</span>
                                    <FileText className="w-4 h-4 text-indigo-500" />
                                </div>
                                <div className="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                                    {activePeriod.journal_count} <span className="text-xs font-normal text-gray-500">entri</span>
                                </div>
                                <p className="text-[11px] text-gray-500">Siap untuk diproses jurnal penutup</p>
                            </div>

                            {/* Metric 2: Estimated Net Profit */}
                            <div className="p-5 rounded-2xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200/60 dark:border-gray-700/50 space-y-2">
                                <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>Estimasi Laba Bersih</span>
                                    <TrendingUp className="w-4 h-4 text-emerald-500" />
                                </div>
                                <div className={`text-2xl font-bold font-mono ${activePeriod.estimated_net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600'}`}>
                                    {formatRupiah(activePeriod.estimated_net_profit)}
                                </div>
                                <p className="text-[11px] text-gray-500">Akan dialokasikan ke Akun Modal</p>
                            </div>

                            {/* Metric 3: Readiness Status */}
                            <div className={`p-5 rounded-2xl border space-y-2 ${
                                activePeriod.can_close
                                    ? 'bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-300'
                                    : 'bg-amber-50/50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-300'
                            }`}>
                                <div className="flex items-center justify-between text-xs font-semibold">
                                    <span>Syarat Kelayakan</span>
                                    {activePeriod.can_close ? <CheckCircle2 className="w-4 h-4 text-emerald-500" /> : <ShieldAlert className="w-4 h-4 text-amber-500" />}
                                </div>
                                <div className="text-sm font-bold">
                                    {activePeriod.can_close ? 'Siap Ditutup' : 'Belum Memenuhi Syarat'}
                                </div>
                                <p className="text-[11px] opacity-80">
                                    {activePeriod.can_close
                                        ? 'Semua jurnal berstatus Posted & Akun 9999 senilai Rp 0.'
                                        : 'Selesaikan jurnal draft/unapproved & pastikan Akun 9999 nihil.'}
                                </p>
                            </div>
                        </div>

                        {/* Warnings if not eligible */}
                        {!activePeriod.can_close && (
                            <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 space-y-2 text-xs">
                                <div className="font-bold flex items-center gap-2">
                                    <AlertCircle className="w-4 h-4 text-rose-600" />
                                    <span>Alasan Belum Bisa Menutup Periode:</span>
                                </div>
                                <ul className="list-disc list-inside space-y-1 pl-1 text-[11px]">
                                    {activePeriod.unposted_count > 0 && (
                                        <li>Terdapat <strong>{activePeriod.unposted_count}</strong> jurnal berstatus Draft / Unapproved yang belum di-approve/diposting.</li>
                                    )}
                                    {Math.abs(activePeriod.suspense_balance) >= 0.01 && (
                                        <li>Akun Sementara (9999) masih memiliki saldo gantung sebesar <strong>{formatRupiah(activePeriod.suspense_balance)}</strong>. Lengkapi akun mutasi terlebih dahulu.</li>
                                    )}
                                </ul>
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="p-8 bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 text-center space-y-3">
                        <AlertCircle className="w-8 h-8 text-amber-500 mx-auto" />
                        <h3 className="text-base font-bold text-gray-900 dark:text-white">Tidak Ada Periode Aktif</h3>
                        <p className="text-xs text-gray-500 max-w-md mx-auto">
                            Saat ini tidak ada periode akuntansi berstatus Open. Silakan buka atau tambahkan periode baru di menu Master Data Periode.
                        </p>
                    </div>
                )}

                {/* Closed Periods History Table */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden space-y-4">
                    <div className="p-6 border-b border-gray-200 dark:border-gray-800">
                        <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <Clock className="w-4 h-4 text-gray-500" />
                            Riwayat Periode Ditutup (Closed Periods)
                        </h3>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Daftar periode akuntansi yang telah selesai dan memiliki jurnal penutup resmi.
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3.5 px-6">Nama Periode</th>
                                    <th className="py-3.5 px-6">Tanggal Mulai</th>
                                    <th className="py-3.5 px-6">Tanggal Selesai</th>
                                    <th className="py-3.5 px-6 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {closedPeriods?.length > 0 ? (
                                    closedPeriods.map((p) => (
                                        <tr key={p.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                            <td className="py-3.5 px-6 font-bold text-gray-900 dark:text-white">{p.name}</td>
                                            <td className="py-3.5 px-6 text-xs text-gray-600 dark:text-gray-400">{p.start_date}</td>
                                            <td className="py-3.5 px-6 text-xs text-gray-600 dark:text-gray-400">{p.end_date}</td>
                                            <td className="py-3.5 px-6 text-center">
                                                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                                    <Lock className="w-3 h-3" />
                                                    Closed
                                                </span>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-gray-500 text-xs">
                                            Belum ada periode yang ditutup sebelumnya.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal Confirmation Dialog */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-in fade-in duration-200">
                    <div className="bg-white dark:bg-gray-900 rounded-3xl max-w-lg w-full p-6 md:p-8 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-6">
                        <div className="flex items-center gap-4">
                            <div className="p-3 rounded-2xl bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400">
                                <Lock className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                    Konfirmasi Penutupan Periode
                                </h3>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Tindakan ini tidak dapat dibatalkan secara otomatis.
                                </p>
                            </div>
                        </div>

                        <div className="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 text-xs space-y-2">
                            <p className="text-gray-700 dark:text-gray-300 font-medium">
                                Sistem akan melakukan langkah-langkah berikut untuk periode <strong>{activePeriod?.name}</strong>:
                            </p>
                            <ol className="list-decimal list-inside space-y-1 text-gray-600 dark:text-gray-400 text-[11px]">
                                <li>Menutup seluruh saldo Akun Pendapatan ke Ikhtisar Laba Rugi.</li>
                                <li>Menutup seluruh saldo Akun Beban ke Ikhtisar Laba Rugi.</li>
                                <li>Memindahkan Laba Bersih <strong>{formatRupiah(activePeriod?.estimated_net_profit)}</strong> ke Akun Modal.</li>
                                <li>Mengubah status periode dari OPEN menjadi CLOSED.</li>
                                <li>Secara otomatis membuat & membuka periode akuntansi bulan berikutnya.</li>
                            </ol>
                        </div>

                        <div className="flex items-center justify-end gap-3 pt-2">
                            <button
                                onClick={() => setShowModal(false)}
                                disabled={processing}
                                className="px-5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                onClick={handleExecuteClosing}
                                disabled={processing}
                                className="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition-all flex items-center gap-2"
                            >
                                {processing ? 'Memproses...' : 'Ya, Tutup Periode Sekarang'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
