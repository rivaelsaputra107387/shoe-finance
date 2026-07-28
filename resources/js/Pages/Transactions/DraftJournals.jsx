import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { FileEdit, CheckCircle2, Clock, ArrowRight } from 'lucide-react';

export default function DraftJournals({ entries }) {
    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
    };

    return (
        <AppLayout title="Draft Jurnal">
            <Head title="Draft Jurnal - SIA Shoe Workshop" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Draft Jurnal Transaksi
                    </h2>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Daftar jurnal sementara hasil generate mutasi bank yang perlu ditinjau sebelum di-submit.
                    </p>
                </div>

                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3.5 px-4">Tanggal</th>
                                    <th className="py-3.5 px-4">Referensi</th>
                                    <th className="py-3.5 px-4">Keterangan</th>
                                    <th className="py-3.5 px-4">Status</th>
                                    <th className="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {entries?.data?.length > 0 ? (
                                    entries.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                            <td className="py-4 px-4 whitespace-nowrap text-xs font-mono text-gray-600 dark:text-gray-400">
                                                {item.entry_date}
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                                {item.reference || '-'}
                                            </td>
                                            <td className="py-4 px-4 text-gray-800 dark:text-gray-200 text-xs font-medium max-w-sm truncate">
                                                {item.description}
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap">
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:border-amber-800">
                                                    <Clock className="w-3.5 h-3.5" />
                                                    <span>Draft</span>
                                                </span>
                                            </td>
                                            <td className="py-4 px-4 whitespace-nowrap text-right">
                                                <button
                                                    onClick={() => router.post(`/app/journal-entries/${item.id}/submit`)}
                                                    className="px-3 py-1.5 bg-indigo-600 text-white hover:bg-indigo-500 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition-colors"
                                                >
                                                    <span>Submit Jurnal</span>
                                                    <ArrowRight className="w-3.5 h-3.5" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="5" className="py-8 text-center text-gray-500 text-xs">
                                            Tidak ada draft jurnal.
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
