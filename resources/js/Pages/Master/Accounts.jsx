import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Plus, ListTree, Search, X } from 'lucide-react';

export default function Accounts({ accounts, parents, filters }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [search, setSearch] = useState(filters?.search || '');
    const [type, setType] = useState(filters?.type || '');

    const { data, setData, post, processing, errors, reset } = useForm({
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

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        post('/app/accounts', {
            onSuccess: () => {
                setCreateModalOpen(false);
                reset();
            },
        });
    };

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
                        onClick={() => setCreateModalOpen(true)}
                        className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-600/20 transition-all"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Tambah Akun Baru</span>
                    </button>
                </div>

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

                    <select
                        value={type}
                        onChange={(e) => setType(e.target.value)}
                        className="py-2 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Tipe</option>
                        <option value="Aset">Aset</option>
                        <option value="Kewajiban">Kewajiban</option>
                        <option value="Ekuitas">Ekuitas</option>
                        <option value="Pendapatan">Pendapatan</option>
                        <option value="Beban">Beban</option>
                    </select>

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
                                    <th className="py-3.5 px-4">Kode Akun</th>
                                    <th className="py-3.5 px-4">Nama Akun</th>
                                    <th className="py-3.5 px-4">Tipe</th>
                                    <th className="py-3.5 px-4">Saldo Normal</th>
                                    <th className="py-3.5 px-4">Kategori Laporan</th>
                                    <th className="py-3.5 px-4">Parent Akun</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                {accounts?.data?.map((acc) => (
                                    <tr key={acc.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
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
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Create Modal */}
            {createModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 max-w-md w-full shadow-2xl space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-base font-bold text-gray-900 dark:text-white">Tambah Akun COA Baru</h3>
                            <button onClick={() => setCreateModalOpen(false)} className="text-gray-400 hover:text-gray-600"><X className="w-5 h-5" /></button>
                        </div>

                        <form onSubmit={handleCreateSubmit} className="space-y-4 text-xs">
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
                                    <select value={data.type} onChange={(e) => setData('type', e.target.value)} className="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border rounded-xl">
                                        <option value="Aset">Aset</option>
                                        <option value="Kewajiban">Kewajiban</option>
                                        <option value="Ekuitas">Ekuitas</option>
                                        <option value="Pendapatan">Pendapatan</option>
                                        <option value="Beban">Beban</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block font-semibold mb-1">Saldo Normal</label>
                                    <select value={data.normal_balance} onChange={(e) => setData('normal_balance', e.target.value)} className="w-full p-2.5 bg-gray-50 dark:bg-gray-800 border rounded-xl">
                                        <option value="Debet">Debet</option>
                                        <option value="Kredit">Kredit</option>
                                    </select>
                                </div>
                            </div>
                            <div className="flex justify-end gap-3 pt-3">
                                <button type="button" onClick={() => setCreateModalOpen(false)} className="px-4 py-2 font-semibold text-gray-500">Batal</button>
                                <button type="submit" disabled={processing} className="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
