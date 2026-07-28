import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import ConfirmationModal from '@/Components/ConfirmationModal';
import Pagination from '@/Components/Pagination';
import { Clock, ArrowRight, AlertTriangle, Pencil, Trash2 } from 'lucide-react';

export default function DraftJournals({ entries }) {
    const [selectedIds, setSelectedIds] = useState([]);
    const [confirmConfig, setConfirmConfig] = useState({
        isOpen: false,
        title: '',
        message: '',
        variant: 'primary',
        confirmText: 'Ya, Submit Jurnal',
        onConfirm: () => {},
    });

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        try {
            const cleanStr = dateStr.split('T')[0];
            const [year, month, day] = cleanStr.split('-');
            if (!year || !month || !day) return dateStr;
            const dateObj = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
            return dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        } catch (e) {
            return dateStr;
        }
    };

    const toggleSelectAll = () => {
        if (!entries?.data?.length) return;
        if (selectedIds.length === entries.data.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(entries.data.map(item => item.id));
        }
    };

    const toggleSelectOne = (id) => {
        setSelectedIds(prev =>
            prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
        );
    };

    const promptBulkSubmit = () => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Bulk Submit Draft Jurnal',
            message: `Apakah Anda yakin ingin mengirimkan ${selectedIds.length} draft jurnal terpilih untuk disetujui?`,
            variant: 'primary',
            confirmText: `Ya, Submit (${selectedIds.length}) Jurnal`,
            onConfirm: () => {
                router.post('/app/journal-entries/bulk-submit', { ids: selectedIds }, {
                    onFinish: () => {
                        setSelectedIds([]);
                        setConfirmConfig(prev => ({ ...prev, isOpen: false }));
                    }
                });
            }
        });
    };

    const promptBulkDelete = () => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Bulk Delete Draft Jurnal',
            message: `Apakah Anda yakin ingin menghapus ${selectedIds.length} draft jurnal terpilih? Data yang sudah dihapus tidak dapat dikembalikan.`,
            variant: 'danger',
            confirmText: `Ya, Hapus (${selectedIds.length}) Jurnal`,
            onConfirm: () => {
                router.post('/app/journal-entries/bulk-delete', { ids: selectedIds }, {
                    onFinish: () => {
                        setSelectedIds([]);
                        setConfirmConfig(prev => ({ ...prev, isOpen: false }));
                    }
                });
            }
        });
    };

    const promptSubmit = (item) => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Submit Draft Jurnal',
            message: `Apakah Anda yakin ingin mengirimkan jurnal '${item.reference || item.description}' ini?`,
            variant: 'primary',
            confirmText: 'Ya, Submit Jurnal',
            onConfirm: () => {
                router.post(`/app/journal-entries/${item.id}/submit`, {}, {
                    onFinish: () => setConfirmConfig(prev => ({ ...prev, isOpen: false }))
                });
            }
        });
    };

    const allSelected = entries?.data?.length > 0 && selectedIds.length === entries.data.length;

    return (
        <AppLayout title="Draft Jurnal">
            <Head title="Draft Jurnal - SIA Shoe Workshop" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Draft Jurnal Transaksi
                    </h2>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Daftar jurnal sementara (hasil impor mutasi bank atau penginputan staff) yang perlu dilengkapi akun COA-nya sebelum di-submit.
                    </p>
                </div>

                {/* Bulk Action Bar */}
                {selectedIds.length > 0 && (
                    <div className="p-4 bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 rounded-2xl flex items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2">
                        <div className="flex items-center gap-3">
                            <span className="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-xs font-bold font-mono">
                                {selectedIds.length} Terpilih
                            </span>
                            <p className="text-xs font-semibold text-emerald-900 dark:text-emerald-200">
                                Kirimkan atau hapus draft jurnal yang dicentang secara massal:
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                onClick={promptBulkSubmit}
                                className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm inline-flex items-center gap-1.5"
                            >
                                <span>Bulk Submit ({selectedIds.length})</span>
                                <ArrowRight className="w-3.5 h-3.5" />
                            </button>
                            <button
                                onClick={promptBulkDelete}
                                className="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm inline-flex items-center gap-1.5"
                            >
                                <Trash2 className="w-3.5 h-3.5" />
                                <span>Bulk Delete ({selectedIds.length})</span>
                            </button>
                            <button
                                onClick={() => setSelectedIds([])}
                                className="px-2 py-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                )}

                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-2.5 px-2.5 w-8">
                                        <input
                                            type="checkbox"
                                            checked={allSelected}
                                            onChange={toggleSelectAll}
                                            className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                        />
                                    </th>
                                    <th className="py-2.5 px-2.5 w-24">Tanggal</th>
                                    <th className="py-2.5 px-2.5 w-28">Referensi</th>
                                    <th className="py-2.5 px-2.5 max-w-[160px]">Keterangan</th>
                                    <th className="py-2.5 px-3 min-w-[180px]">Rincian Akun</th>
                                    <th className="py-2.5 px-3 w-28 text-right text-emerald-600 dark:text-emerald-400">Debet</th>
                                    <th className="py-2.5 px-3 w-28 text-right text-rose-600 dark:text-rose-400">Kredit</th>
                                    <th className="py-2.5 px-2.5 w-24">Status Akun</th>
                                    <th className="py-2.5 px-3 w-28 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-xs">
                                {entries?.data?.length > 0 ? (
                                    entries.data.map((item) => {
                                        const hasSuspense = item.lines?.some(l => l.account?.code === '9999');
                                        const isSelected = selectedIds.includes(item.id);
                                        return (
                                            <tr key={item.id} className={`transition-colors ${isSelected ? 'bg-emerald-50/40 dark:bg-emerald-950/30' : 'hover:bg-gray-50/60 dark:hover:bg-gray-800/40'}`}>
                                                <td className="py-2.5 px-2.5 whitespace-nowrap">
                                                    <input
                                                        type="checkbox"
                                                        checked={isSelected}
                                                        onChange={() => toggleSelectOne(item.id)}
                                                        className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                                    />
                                                </td>
                                                <td className="py-2.5 px-2.5 whitespace-nowrap font-mono font-medium text-gray-600 dark:text-gray-400">
                                                    {formatDate(item.entry_date)}
                                                </td>
                                                <td className="py-2.5 px-2.5 whitespace-nowrap font-mono font-bold text-gray-900 dark:text-white">
                                                    {item.reference || '-'}
                                                </td>
                                                <td className="py-2.5 px-2.5 text-gray-800 dark:text-gray-200 font-medium max-w-[160px] truncate" title={item.description}>
                                                    {item.description}
                                                </td>
                                                <td className="py-2.5 px-3">
                                                    <div className="space-y-1">
                                                        {item.lines?.map((line) => (
                                                            <div key={line.id} className={`text-[11px] truncate ${line.credit > 0 ? 'pl-3 text-gray-500' : 'font-medium text-gray-900 dark:text-gray-200'}`}>
                                                                <span className={`font-mono mr-1.5 ${line.account?.code === '9999' ? 'text-amber-500' : 'text-gray-400'}`}>{line.account?.code}</span>
                                                                <span className={line.account?.code === '9999' ? 'text-amber-600 font-bold' : ''}>{line.account?.name}</span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="py-2.5 px-3 text-right whitespace-nowrap">
                                                    <div className="space-y-1 font-mono font-semibold tabular-nums text-[11px] text-emerald-600 dark:text-emerald-400">
                                                        {item.lines?.map((line) => (
                                                            <div key={line.id}>
                                                                {line.debit > 0 ? formatRupiah(line.debit) : <span className="text-gray-300 dark:text-gray-700 opacity-40">-</span>}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="py-2.5 px-3 text-right whitespace-nowrap">
                                                    <div className="space-y-1 font-mono font-semibold tabular-nums text-[11px] text-rose-600 dark:text-rose-400">
                                                        {item.lines?.map((line) => (
                                                            <div key={line.id}>
                                                                {line.credit > 0 ? formatRupiah(line.credit) : <span className="text-gray-300 dark:text-gray-700 opacity-40">-</span>}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="py-2.5 px-2.5 whitespace-nowrap">
                                                    {hasSuspense ? (
                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                            <AlertTriangle className="w-3 h-3" />
                                                            <span>Akun 9999</span>
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                            <span>Akun Lengkap</span>
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-2.5 px-3 whitespace-nowrap text-right space-x-1.5">
                                                    <Link
                                                        href={`/app/journal-entries/${item.id}/edit`}
                                                        className="px-3 py-1.5 bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 hover:bg-amber-100 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition-colors"
                                                    >
                                                        <Pencil className="w-3.5 h-3.5" />
                                                        <span>Edit</span>
                                                    </Link>

                                                    {!hasSuspense && (
                                                        <button
                                                            onClick={() => promptSubmit(item)}
                                                            className="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-500 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition-colors shadow-sm"
                                                        >
                                                            <span>Submit</span>
                                                            <ArrowRight className="w-3 h-3" />
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td colSpan="7" className="py-8 text-center text-gray-500 text-xs">
                                            Tidak ada draft jurnal. Semua pekerjaan selesai!
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    {/* Pagination */}
                    <Pagination links={entries?.links} meta={entries} />
                </div>
            </div>

            {/* Confirmation Modal */}
            <ConfirmationModal
                isOpen={confirmConfig.isOpen}
                title={confirmConfig.title}
                message={confirmConfig.message}
                variant={confirmConfig.variant}
                confirmText={confirmConfig.confirmText}
                onConfirm={confirmConfig.onConfirm}
                onClose={() => setConfirmConfig(prev => ({ ...prev, isOpen: false }))}
            />
        </AppLayout>
    );
}
