import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CustomSelect from '@/Components/CustomSelect';
import { Plus, Calendar, CheckCircle2, Lock, X, Pencil } from 'lucide-react';

export default function FiscalPeriods({ periods }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editingPeriod, setEditingPeriod] = useState(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        start_date: '',
        end_date: '',
        status: 'open',
    });

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        post('/app/fiscal-periods', {
            onSuccess: () => {
                setCreateModalOpen(false);
                reset();
            },
        });
    };

    const handleOpenEdit = (p) => {
        setEditingPeriod(p);
        setData({
            name: p.name,
            start_date: p.start_date ? p.start_date.substring(0, 10) : '',
            end_date: p.end_date ? p.end_date.substring(0, 10) : '',
            status: p.status || 'open',
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        if (!editingPeriod) return;

        put(`/app/fiscal-periods/${editingPeriod.id}`, {
            onSuccess: () => {
                setEditingPeriod(null);
                reset();
            },
        });
    };

    return (
        <AppLayout title="Periode Akuntansi">
            <Head title="Periode Akuntansi - SIA Shoe Workshop" />

            <div className="space-y-6 max-w-4xl mx-auto">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Periode Akuntansi (Fiscal Periods)
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Kelola periode siklus pembukuan bulanan/tahunan bisnis.
                        </p>
                    </div>

                    <button
                        onClick={() => { reset(); setCreateModalOpen(true); }}
                        className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition-all"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Tambah Periode Baru</span>
                    </button>
                </div>

                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3.5 px-4">Nama Periode</th>
                                    <th className="py-3.5 px-4">Tanggal Mulai</th>
                                    <th className="py-3.5 px-4">Tanggal Selesai</th>
                                    <th className="py-3.5 px-4">Status</th>
                                    <th className="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {periods?.map((p) => (
                                    <tr key={p.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                        <td className="py-3.5 px-4 font-bold text-xs text-gray-900 dark:text-white">{p.name}</td>
                                        <td className="py-3.5 px-4 font-mono text-xs text-gray-600 dark:text-gray-400">{p.start_date}</td>
                                        <td className="py-3.5 px-4 font-mono text-xs text-gray-600 dark:text-gray-400">{p.end_date}</td>
                                        <td className="py-3.5 px-4">
                                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold ${
                                                p.status === 'open'
                                                    ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-500'
                                            }`}>
                                                {p.status === 'open' ? <CheckCircle2 className="w-3.5 h-3.5" /> : <Lock className="w-3.5 h-3.5" />}
                                                <span className="capitalize">{p.status}</span>
                                            </span>
                                        </td>
                                        <td className="py-3.5 px-4 text-right">
                                            <button
                                                onClick={() => handleOpenEdit(p)}
                                                className="p-1.5 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950 transition-colors"
                                                title="Edit Periode"
                                            >
                                                <Pencil className="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Create / Edit Modal */}
            {(createModalOpen || editingPeriod) && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 max-w-md w-full shadow-2xl space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                {editingPeriod ? 'Edit Periode Akuntansi' : 'Tambah Periode Akuntansi'}
                            </h3>
                            <button onClick={() => { setCreateModalOpen(false); setEditingPeriod(null); }} className="text-gray-400 hover:text-gray-600"><X className="w-5 h-5" /></button>
                        </div>

                        <form onSubmit={editingPeriod ? handleEditSubmit : handleCreateSubmit} className="space-y-4 text-xs">
                            <div>
                                <label className="block font-semibold mb-1">Nama Periode</label>
                                <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} required placeholder="Misal: Juli 2026" className="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border rounded-xl" />
                            </div>
                            <div>
                                <label className="block font-semibold mb-1">Tanggal Mulai</label>
                                <input type="date" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} required className="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border rounded-xl" />
                            </div>
                            <div>
                                <label className="block font-semibold mb-1">Tanggal Selesai</label>
                                <input type="date" value={data.end_date} onChange={(e) => setData('end_date', e.target.value)} required className="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border rounded-xl" />
                            </div>
                            {editingPeriod && (
                                <div>
                                    <label className="block font-semibold mb-1 text-gray-700 dark:text-gray-300">Status Periode</label>
                                    <CustomSelect value={data.status} onChange={(e) => setData('status', e.target.value)} disabled={true} className="w-full">
                                        <option value="open">Open</option>
                                        <option value="closed">Closed</option>
                                    </CustomSelect>
                                    <p className="mt-1 text-[10px] text-gray-500 dark:text-gray-400 italic">
                                        * Status periode dikunci. Penutupan periode hanya dapat dilakukan melalui menu <strong>Penutupan Periode</strong>.
                                    </p>
                                </div>
                            )}
                            <div className="flex justify-end gap-3 pt-3">
                                <button type="button" onClick={() => { setCreateModalOpen(false); setEditingPeriod(null); }} className="px-4 py-2 font-semibold text-gray-500">Batal</button>
                                <button type="submit" disabled={processing} className="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl">
                                    {editingPeriod ? 'Perbarui Periode' : 'Simpan Periode'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
