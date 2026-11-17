import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import ConfirmationModal from '@/Components/ConfirmationModal';
import Pagination from '@/Components/Pagination';
import CustomSelect from '@/Components/CustomSelect';
import { Plus, Search, X, Pencil, Trash2 } from 'lucide-react';

export default function Accounts({ accounts, parents, filters }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editingAccount, setEditingAccount] = useState(null);
    const [search, setSearch] = useState(filters?.search || '');
    const [type, setType] = useState(filters?.type || '');
    const [selectedIds, setSelectedIds] = useState([]);

    const [confirmConfig, setConfirmConfig] = useState({
        isOpen: false,
        title: '',
        message: '',
        variant: 'danger',
        confirmText: 'Ya, Hapus',
        onConfirm: () => {},
    });

    const { data, setData, post, put, processing, errors, reset } = useForm({
        code: '',
        name: '',
        type: 'Aset',
        normal_balance: 'Debet',
        report_category: 'Neraca',
        cash_flow_category: '',
        parent_id: '',
    });

    const handleFilter = () => {
        router.get('/app/accounts', { search, type }, { preserveState: true, replace: true });
    };

    const toggleSelectAll = () => {
        if (!accounts?.data?.length) return;
        if (selectedIds.length === accounts.data.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(accounts.data.map(acc => acc.id));
        }
    };

    const toggleSelectOne = (id) => {
        setSelectedIds(prev =>
            prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
        );
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        post('/app/accounts', {
            onSuccess: () => {
                setCreateModalOpen(false);
                reset();
            },
        });
    };

    const handleOpenEdit = (acc) => {
        setEditingAccount(acc);
        setData({
            code: acc.code,
            name: acc.name,
            type: acc.type,
            normal_balance: acc.normal_balance,
            report_category: acc.report_category,
            cash_flow_category: acc.cash_flow_category || '',
            parent_id: acc.parent_id || '',
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        if (!editingAccount) return;

        put(`/app/accounts/${editingAccount.id}`, {
            onSuccess: () => {
                setEditingAccount(null);
                reset();
            },
        });
    };

    const handleDelete = (acc) => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Hapus Akun COA',
            message: `Apakah Anda yakin ingin menghapus akun '${acc.code} - ${acc.name}'? Pastikan akun ini belum digunakan dalam jurnal transaksi.`,
            variant: 'danger',
            confirmText: 'Ya, Hapus Akun',
            onConfirm: () => {
                router.delete(`/app/accounts/${acc.id}`, {
                    onFinish: () => setConfirmConfig(prev => ({ ...prev, isOpen: false }))
                });
            }
        });
    };

    const promptBulkDelete = () => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Bulk Delete Akun COA',
            message: `Apakah Anda yakin ingin menghapus ${selectedIds.length} akun COA terpilih? (Catatan: Akun yang sudah memiliki transaksi jurnal tidak dapat dihapus).`,
            variant: 'danger',
            confirmText: `Ya, Hapus (${selectedIds.length}) Akun`,
            onConfirm: () => {
                router.post('/app/accounts/bulk-delete', { ids: selectedIds }, {
                    onFinish: () => {
                        setSelectedIds([]);
                        setConfirmConfig(prev => ({ ...prev, isOpen: false }));
                    }
                });
            }
        });
    };

    const allSelected = accounts?.data?.length > 0 && selectedIds.length === accounts.data.length;

    return (
        <AppLayout title="Chart of Accounts (COA)">
            <Head title="Chart of Accounts - SIA Shoe Workshop" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Chart of Accounts (Daftar Akun)
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Kelola master bagan akun untuk pencatatan transaksi keuangan.
                        </p>
                    </div>

                    <button
                        onClick={() => { reset(); setCreateModalOpen(true); }}
                        className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition-all"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Tambah Akun Baru</span>
                    </button>
                </div>

                {/* Bulk Action Bar */}
                {selectedIds.length > 0 && (
                    <div className="p-4 bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 rounded-2xl flex items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2">
                        <div className="flex items-center gap-3">
                            <span className="px-2.5 py-1 rounded-lg bg-rose-600 text-white text-xs font-bold font-mono">
                                {selectedIds.length} Terpilih
                            </span>
                            <p className="text-xs font-semibold text-rose-900 dark:text-rose-200">
                                Hapus akun COA terpilih secara massal:
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
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

                {/* Filters */}
                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row items-center gap-4">
                    <div className="relative flex-1 w-full">
                        <Search className="w-4 h-4 absolute left-3.5 top-3 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Cari kode atau nama akun..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                            className="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <CustomSelect
                        value={type}
                        onChange={(e) => setType(e.target.value)}
                    >
                        <option value="">Semua Tipe</option>
                        <option value="Aset">Aset</option>
                        <option value="Kewajiban">Kewajiban</option>
                        <option value="Ekuitas">Ekuitas</option>
                        <option value="Pendapatan">Pendapatan</option>
                        <option value="Beban">Beban</option>
                    </CustomSelect>

                    <button
                        onClick={handleFilter}
                        className="px-4 py-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity"
                    >
                        Filter
                    </button>
                </div>

                {/* Table */}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3.5 px-4 w-10">
                                        <input
                                            type="checkbox"
                                            checked={allSelected}
                                            onChange={toggleSelectAll}
                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                        />
                                    </th>
                                    <th className="py-3.5 px-4">Kode Akun</th>
                                    <th className="py-3.5 px-4">Nama Akun</th>
                                    <th className="py-3.5 px-4">Tipe</th>
                                    <th className="py-3.5 px-4">Saldo Normal</th>
                                    <th className="py-3.5 px-4">Kategori Laporan</th>
                                    <th className="py-3.5 px-4">Parent Akun</th>
                                    <th className="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {accounts?.data?.map((acc) => {
                                    const isSelected = selectedIds.includes(acc.id);
                                    return (
                                        <tr key={acc.id} className={`transition-colors ${isSelected ? 'bg-indigo-50/40 dark:bg-indigo-950/30' : 'hover:bg-gray-50/60 dark:hover:bg-gray-800/40'}`}>
                                            <td className="py-3.5 px-4 whitespace-nowrap">
                                                <input
                                                    type="checkbox"
                                                    checked={isSelected}
                                                    onChange={() => toggleSelectOne(acc.id)}
                                                    className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                />
                                            </td>
                                            <td className="py-3.5 px-4 font-mono font-bold text-xs text-gray-900 dark:text-white">{acc.code}</td>
                                            <td className="py-3.5 px-4 font-medium text-gray-800 dark:text-gray-200">{acc.name}</td>
                                            <td className="py-3.5 px-4">
                                                <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                                    {acc.type}
                                                </span>
                                            </td>
                                            <td className="py-3.5 px-4 text-xs font-semibold text-gray-600 dark:text-gray-400">{acc.normal_balance}</td>
                                            <td className="py-3.5 px-4 text-xs text-gray-500">{acc.report_category}</td>
                                            <td className="py-3.5 px-4 text-xs font-mono text-gray-400">{acc.parent ? `${acc.parent.code} - ${acc.parent.name}` : '-'}</td>
                                            <td className="py-3.5 px-4 text-right space-x-1">
                                                <button
                                                    onClick={() => handleOpenEdit(acc)}
                                                    className="p-1.5 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950 transition-colors"
                                                    title="Edit Akun"
                                                >
                                                    <Pencil className="w-4 h-4" />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(acc)}
                                                    className="p-1.5 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950 transition-colors"
                                                    title="Hapus Akun"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    {/* Pagination */}
                    <Pagination links={accounts?.links} meta={accounts} />
                </div>
            </div>

            {/* Create / Edit Modal */}
            {(createModalOpen || editingAccount) && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 max-w-md w-full shadow-2xl space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                {editingAccount ? 'Edit Akun COA' : 'Tambah Akun COA Baru'}
                            </h3>
                            <button onClick={() => { setCreateModalOpen(false); setEditingAccount(null); }} className="text-gray-400 hover:text-gray-600"><X className="w-5 h-5" /></button>
                        </div>

                        <form onSubmit={editingAccount ? handleEditSubmit : handleCreateSubmit} className="space-y-4 text-xs">
                            <div>
                                <label className="block font-semibold mb-1">Kode Akun</label>
                                <input type="text" value={data.code} onChange={(e) => setData('code', e.target.value)} required placeholder="Misal: 1110" className="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border rounded-xl" />
                            </div>
                            <div>
                                <label className="block font-semibold mb-1">Nama Akun</label>
                                <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} required placeholder="Kas Toko" className="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border rounded-xl" />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block font-semibold mb-1">Tipe</label>
                                    <CustomSelect value={data.type} onChange={(e) => setData('type', e.target.value)} className="w-full">
                                        <option value="Aset">Aset</option>
                                        <option value="Kewajiban">Kewajiban</option>
                                        <option value="Ekuitas">Ekuitas</option>
                                        <option value="Pendapatan">Pendapatan</option>
                                        <option value="Beban">Beban</option>
                                    </CustomSelect>
                                </div>
                                <div>
                                    <label className="block font-semibold mb-1">Saldo Normal</label>
                                    <CustomSelect value={data.normal_balance} onChange={(e) => setData('normal_balance', e.target.value)} className="w-full">
                                        <option value="Debet">Debet</option>
                                        <option value="Kredit">Kredit</option>
                                    </CustomSelect>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block font-semibold mb-1">Kategori Laporan</label>
                                    <CustomSelect value={data.report_category} onChange={(e) => setData('report_category', e.target.value)} className="w-full">
                                        <option value="Neraca">Neraca</option>
                                        <option value="Laba Rugi">Laba Rugi</option>
                                    </CustomSelect>
                                </div>
                                <div>
                                    <label className="block font-semibold mb-1">
                                        Parent Akun
                                        <span className="ml-1 font-normal text-gray-400">(Opsional)</span>
                                    </label>
                                    <CustomSelect
                                        value={data.parent_id}
                                        onChange={(e) => setData('parent_id', e.target.value)}
                                        className="w-full"
                                    >
                                        <option value="">— Tidak ada (Akun Induk) —</option>
                                        {parents?.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.code} – {p.name}
                                            </option>
                                        ))}
                                    </CustomSelect>
                                </div>
                            </div>
                            <div className="flex justify-end gap-3 pt-3">
                                <button type="button" onClick={() => { setCreateModalOpen(false); setEditingAccount(null); }} className="px-4 py-2 font-semibold text-gray-500">Batal</button>
                                <button type="submit" disabled={processing} className="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl">
                                    {editingAccount ? 'Perbarui' : 'Simpan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

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
