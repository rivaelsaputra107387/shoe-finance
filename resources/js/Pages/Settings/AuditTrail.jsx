import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import { ShieldCheck, Eye, Filter, Database, User, Clock, ChevronLeft, ChevronRight } from 'lucide-react';

export default function AuditTrail({ auditTrails, selectedTable, selectedAction }) {
    const [tableFilter, setTableFilter] = useState(selectedTable || '');
    const [actionFilter, setActionFilter] = useState(selectedAction || '');
    const [activeRecord, setActiveRecord] = useState(null);

    const handleFilter = () => {
        router.get('/app/audit-trail', {
            table_name: tableFilter,
            action: actionFilter,
        }, { preserveState: true, replace: true });
    };

    const getActionBadge = (action) => {
        switch (action) {
            case 'create':
                return <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">CREATE</span>;
            case 'update':
                return <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300">UPDATE</span>;
            case 'delete':
                return <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">DELETE</span>;
            case 'close_period':
                return <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">CLOSE PERIOD</span>;
            default:
                return <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">{action?.toUpperCase()}</span>;
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
                        Riwayat lengkap pencatatan perubahan data (pembuatan, pengubahan, penghapusan) oleh pengguna.
                    </p>
                </div>

                {/* Filter Selector */}
                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row items-center gap-4">
                    <div className="flex-1 w-full">
                        <label className="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                            Tabel Database
                        </label>
                        <CustomSelect
                            value={tableFilter}
                            onChange={(e) => setTableFilter(e.target.value)}
                            className="w-full"
                        >
                            <option value="">Semua Tabel</option>
                            <option value="journal_entries">Journal Entries</option>
                            <option value="journal_entry_lines">Journal Entry Lines</option>
                            <option value="accounts">Accounts</option>
                            <option value="fiscal_periods">Fiscal Periods</option>
                            <option value="bank_mutations">Bank Mutations</option>
                        </CustomSelect>
                    </div>

                    <div className="w-full md:w-64">
                        <label className="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                            Jenis Aksi
                        </label>
                        <CustomSelect
                            value={actionFilter}
                            onChange={(e) => setActionFilter(e.target.value)}
                            className="w-full"
                        >
                            <option value="">Semua Aksi</option>
                            <option value="create">Create</option>
                            <option value="update">Update</option>
                            <option value="delete">Delete</option>
                            <option value="close_period">Close Period</option>
                        </CustomSelect>
                    </div>

                    <div className="pt-5 w-full md:w-auto">
                        <button
                            onClick={handleFilter}
                            className="w-full md:w-auto px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl shadow-md transition-all"
                        >
                            Filter
                        </button>
                    </div>
                </div>

                {/* Audit Table */}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden space-y-4">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3 px-5">Waktu</th>
                                    <th className="py-3 px-5">Pengguna</th>
                                    <th className="py-3 px-5">Tabel</th>
                                    <th className="py-3 px-5">Record ID</th>
                                    <th className="py-3 px-5 text-center">Aksi</th>
                                    <th className="py-3 px-5 text-right">Detail</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm font-mono">
                                {auditTrails?.data?.length > 0 ? (
                                    auditTrails.data.map((row) => (
                                        <tr key={row.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                            <td className="py-3 px-5 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                                {new Date(row.created_at).toLocaleString('id-ID')}
                                            </td>
                                            <td className="py-3 px-5 text-xs font-sans font-semibold text-gray-900 dark:text-white">
                                                {row.user?.name || 'System'}
                                            </td>
                                            <td className="py-3 px-5 text-xs text-emerald-600 dark:text-emerald-400 font-bold">
                                                {row.table_name}
                                            </td>
                                            <td className="py-3 px-5 text-xs text-gray-500">#{row.record_id}</td>
                                            <td className="py-3 px-5 text-center">
                                                {getActionBadge(row.action)}
                                            </td>
                                            <td className="py-3 px-5 text-right">
                                                <button
                                                    onClick={() => setActiveRecord(row)}
                                                    className="p-1.5 rounded-lg text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950 transition-colors"
                                                    title="Lihat Perubahan"
                                                >
                                                    <Eye className="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="py-8 text-center text-gray-500 font-sans text-xs">
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
                    <div className="bg-white dark:bg-gray-900 rounded-3xl max-w-2xl w-full p-6 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-5">
                        <div className="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-4">
                            <div>
                                <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                    Detail Perubahan Data (Log #{activeRecord.id})
                                </h3>
                                <p className="text-xs text-gray-500">
                                    Tabel: <strong className="text-emerald-600">{activeRecord.table_name}</strong> | ID: #{activeRecord.record_id} | Oleh: {activeRecord.user?.name || 'System'}
                                </p>
                            </div>
                            {getActionBadge(activeRecord.action)}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Old Values */}
                            <div className="space-y-1">
                                <span className="text-xs font-bold text-rose-600 block">Data Lama (Old Values):</span>
                                <pre className="p-3 bg-gray-900 text-rose-300 rounded-xl text-[11px] font-mono overflow-x-auto max-h-60 border border-gray-800">
                                    {activeRecord.old_data ? JSON.stringify(activeRecord.old_data, null, 2) : 'null'}
                                </pre>
                            </div>

                            {/* New Values */}
                            <div className="space-y-1">
                                <span className="text-xs font-bold text-emerald-600 block">Data Baru (New Values):</span>
                                <pre className="p-3 bg-gray-900 text-emerald-300 rounded-xl text-[11px] font-mono overflow-x-auto max-h-60 border border-gray-800">
                                    {activeRecord.new_data ? JSON.stringify(activeRecord.new_data, null, 2) : 'null'}
                                </pre>
                            </div>
                        </div>

                        <div className="flex justify-end pt-2">
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
