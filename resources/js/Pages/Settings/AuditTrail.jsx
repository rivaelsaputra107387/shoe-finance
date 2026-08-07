import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import { ShieldCheck, Eye, Filter, Database, User, Clock, ChevronLeft, ChevronRight, ArrowRight, FileText, Code } from 'lucide-react';

export default function AuditTrail({ auditTrails, selectedTable, selectedAction }) {
    const [tableFilter, setTableFilter] = useState(selectedTable || '');
    const [actionFilter, setActionFilter] = useState(selectedAction || '');
    const [activeRecord, setActiveRecord] = useState(null);
    const [showRawJson, setShowRawJson] = useState(false);

    const handleFilter = () => {
        router.get('/app/audit-trail', {
            table_name: tableFilter,
            action: actionFilter,
        }, { preserveState: true, replace: true });
    };

    const getActionBadge = (action) => {
        switch (action) {
            case 'create':
                return <span className="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Tambah Baru</span>;
            case 'update':
                return <span className="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Perubahan</span>;
            case 'delete':
                return <span className="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">Hapus Data</span>;
            case 'close_period':
                return <span className="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800">Tutup Buku</span>;
            default:
                return <span className="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-gray-50 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300">{action?.toUpperCase()}</span>;
        }
    };

    return (
        <AppLayout title="Audit Trail Keamanan">
            <Head title="Audit Trail - SIA Shoe Workshop" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                        <ShieldCheck className="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                        Log Audit Trail Sistem
                    </h2>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Riwayat aktivitas, pembuatan, pengubahan, dan penghapusan data oleh pengguna dalam bahasa yang mudah dipahami.
                    </p>
                </div>

                {/* Filter Selector */}
                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row items-center gap-4">
                    <div className="flex-1 w-full">
                        <label className="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                            Kategori / Fitur Sistem
                        </label>
                        <CustomSelect
                            value={tableFilter}
                            onChange={(e) => setTableFilter(e.target.value)}
                            className="w-full"
                        >
                            <option value="">Semua Kategori</option>
                            <option value="journal_entries">Jurnal Transaksi</option>
                            <option value="journal_entry_lines">Rincian Jurnal</option>
                            <option value="bank_mutations">Mutasi Bank</option>
                            <option value="fiscal_periods">Periode Akuntansi</option>
                            <option value="users">Pengguna Aplikasi</option>
                            <option value="accounts">Chart of Accounts (COA)</option>
                        </CustomSelect>
                    </div>

                    <div className="w-full md:w-64">
                        <label className="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                            Jenis Aktivitas
                        </label>
                        <CustomSelect
                            value={actionFilter}
                            onChange={(e) => setActionFilter(e.target.value)}
                            className="w-full"
                        >
                            <option value="">Semua Aktivitas</option>
                            <option value="create">Tambah Baru</option>
                            <option value="update">Perubahan Data</option>
                            <option value="delete">Hapus Data</option>
                            <option value="close_period">Tutup Buku</option>
                        </CustomSelect>
                    </div>

                    <div className="pt-5 w-full md:w-auto">
                        <button
                            onClick={handleFilter}
                            className="w-full md:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl shadow-sm transition-all"
                        >
                            Terapkan Filter
                        </button>
                    </div>
                </div>

                {/* Audit Table */}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden space-y-4">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3.5 px-5">Waktu</th>
                                    <th className="py-3.5 px-5">Pengguna</th>
                                    <th className="py-3.5 px-5">Deskripsi Aktivitas</th>
                                    <th className="py-3.5 px-5">Kategori</th>
                                    <th className="py-3.5 px-5 text-center">Aksi</th>
                                    <th className="py-3.5 px-5 text-right">Detail</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {auditTrails?.data?.length > 0 ? (
                                    auditTrails.data.map((row) => (
                                        <tr key={row.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                            <td className="py-3.5 px-5 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                                {new Date(row.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}
                                            </td>
                                            <td className="py-3.5 px-5 text-xs font-semibold text-gray-900 dark:text-white">
                                                <div className="flex items-center gap-1.5">
                                                    <User className="w-3.5 h-3.5 text-gray-400" />
                                                    <span>{row.user?.name || 'Sistem'}</span>
                                                </div>
                                            </td>
                                            <td className="py-3.5 px-5 text-xs font-medium text-gray-800 dark:text-gray-200">
                                                {row.narrative || `${row.user?.name || 'Sistem'} mengelola ${row.table_label || row.table_name}`}
                                            </td>
                                            <td className="py-3.5 px-5 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                                {row.table_label || row.table_name}
                                            </td>
                                            <td className="py-3.5 px-5 text-center">
                                                {getActionBadge(row.action)}
                                            </td>
                                            <td className="py-3.5 px-5 text-right">
                                                <button
                                                    onClick={() => { setActiveRecord(row); setShowRawJson(false); }}
                                                    className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/60 transition-colors border border-emerald-200 dark:border-emerald-800/60"
                                                    title="Lihat Rincian Perubahan"
                                                >
                                                    <Eye className="w-3.5 h-3.5" />
                                                    <span>Rincian</span>
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="py-8 text-center text-gray-500 text-xs">
                                            Belum ada catatan log audit trail.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {auditTrails?.links?.length > 3 && (
                        <div className="p-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500">
                            <div>
                                Menampilkan {auditTrails.from} - {auditTrails.to} dari {auditTrails.total} log
                            </div>
                            <div className="flex items-center gap-1">
                                {auditTrails.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        onClick={() => link.url && router.get(link.url)}
                                        disabled={!link.url}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1 rounded-lg text-xs font-medium transition-colors ${
                                            link.active
                                                ? 'bg-emerald-600 text-white font-bold'
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

            {/* Modal Detail Inspector */}
            {activeRecord && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs">
                    <div className="bg-white dark:bg-gray-900 rounded-3xl max-w-3xl w-full p-6 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-5">
                        <div className="flex items-start justify-between border-b border-gray-200 dark:border-gray-800 pb-4">
                            <div>
                                <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <FileText className="w-5 h-5 text-emerald-600" />
                                    Rincian Perubahan Data
                                </h3>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {activeRecord.narrative}
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                {getActionBadge(activeRecord.action)}
                            </div>
                        </div>

                        {/* Summary Header Cards */}
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-2xl text-xs">
                            <div>
                                <span className="text-[10px] font-semibold text-gray-400 uppercase">Pengguna</span>
                                <p className="font-bold text-gray-800 dark:text-gray-200 mt-0.5">{activeRecord.user?.name || 'Sistem'}</p>
                            </div>
                            <div>
                                <span className="text-[10px] font-semibold text-gray-400 uppercase">Kategori</span>
                                <p className="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">{activeRecord.table_label || activeRecord.table_name}</p>
                            </div>
                            <div>
                                <span className="text-[10px] font-semibold text-gray-400 uppercase">Waktu</span>
                                <p className="font-bold text-gray-800 dark:text-gray-200 mt-0.5">{new Date(activeRecord.created_at).toLocaleString('id-ID')}</p>
                            </div>
                        </div>

                        {/* Structured Diffs Table / JSON View */}
                        {!showRawJson ? (
                            <div className="space-y-2">
                                <h4 className="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    Perbandingan Field yang Berubah:
                                </h4>
                                {activeRecord.formatted_diff && activeRecord.formatted_diff.length > 0 ? (
                                    <div className="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                                        <table className="w-full text-left text-xs">
                                            <thead className="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-semibold border-b border-gray-200 dark:border-gray-800">
                                                <tr>
                                                    <th className="py-2.5 px-4 w-1/3">Parameter</th>
                                                    <th className="py-2.5 px-4 w-1/3 text-rose-600 dark:text-rose-400">Sebelumnya (Old)</th>
                                                    <th className="py-2.5 px-4 w-1/3 text-emerald-600 dark:text-emerald-400">Menjadi (New)</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                                                {activeRecord.formatted_diff.map((item, idx) => (
                                                    <tr key={idx} className="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                                        <td className="py-2.5 px-4 font-semibold text-gray-800 dark:text-gray-200">
                                                            {item.field}
                                                        </td>
                                                        <td className="py-2.5 px-4 font-mono text-rose-600 dark:text-rose-400 bg-rose-50/30 dark:bg-rose-950/20">
                                                            {item.old_value}
                                                        </td>
                                                        <td className="py-2.5 px-4 font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/20 font-semibold">
                                                            {item.new_value}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl text-center text-xs text-gray-500">
                                        Tidak ada perubahan spesifik antar-kolom yang dicatat.
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <span className="text-xs font-bold text-rose-600 block">Data Lama (Old Values):</span>
                                    <pre className="p-3 bg-gray-900 text-rose-300 rounded-xl text-[11px] font-mono overflow-x-auto max-h-60 border border-gray-800">
                                        {activeRecord.old_data ? JSON.stringify(activeRecord.old_data, null, 2) : 'null'}
                                    </pre>
                                </div>
                                <div className="space-y-1">
                                    <span className="text-xs font-bold text-emerald-600 block">Data Baru (New Values):</span>
                                    <pre className="p-3 bg-gray-900 text-emerald-300 rounded-xl text-[11px] font-mono overflow-x-auto max-h-60 border border-gray-800">
                                        {activeRecord.new_data ? JSON.stringify(activeRecord.new_data, null, 2) : 'null'}
                                    </pre>
                                </div>
                            </div>
                        )}

                        {/* Modal Footer Controls */}
                        <div className="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
                            <button
                                onClick={() => setShowRawJson(!showRawJson)}
                                className="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium"
                            >
                                <Code className="w-3.5 h-3.5" />
                                <span>{showRawJson ? 'Tampilkan Mode Tabel Rapi' : 'Tampilkan Teks JSON Mentah (Developer)'}</span>
                            </button>

                            <button
                                onClick={() => setActiveRecord(null)}
                                className="px-5 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
