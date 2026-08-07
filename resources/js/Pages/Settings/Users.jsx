import React, { useState } from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import CustomSelect from '@/Components/CustomSelect';
import { Search, Plus, Pencil, Trash2, X, Shield, Phone, Mail, User } from 'lucide-react';
import { formatPhone } from '@/Utils/format';

export default function Users({ users, roles, filters }) {
    const { auth } = usePage().props;
    const userRoles = auth?.user?.roles || [];
    const isFinanceOnly = userRoles.includes('finance') && !userRoles.includes('owner');

    const [search, setSearch] = useState(filters?.search || '');
    const [role, setRole] = useState(filters?.role || '');

    const [modalOpen, setModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        name: '',
        email: '',
        phone: '',
        role: '',
        password: '',
        password_confirmation: '',
    });

    const executeFilter = (newSearch = search, newRole = role) => {
        router.get('/app/users', { search: newSearch, role: newRole }, { preserveState: true, replace: true });
    };

    const handleAdd = () => {
        setEditMode(false);
        reset();
        if (isFinanceOnly) {
            setData('role', 'staff');
        }
        clearErrors();
        setModalOpen(true);
    };

    const handleEdit = (user) => {
        setEditMode(true);
        setSelectedUser(user);
        setData({
            name: user.name,
            email: user.email,
            phone: user.phone || '',
            role: user.roles[0]?.name || '',
            password: '',
            password_confirmation: '',
        });
        clearErrors();
        setModalOpen(true);
    };

    const handleDeleteClick = (user) => {
        setSelectedUser(user);
        setDeleteModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editMode) {
            put(`/app/users/${selectedUser.id}`, {
                onSuccess: () => {
                    setModalOpen(false);
                    reset();
                },
            });
        } else {
            post('/app/users', {
                onSuccess: () => {
                    setModalOpen(false);
                    reset();
                },
            });
        }
    };

    const confirmDelete = () => {
        router.delete(`/app/users/${selectedUser.id}`, {
            onSuccess: () => setDeleteModalOpen(false)
        });
    };

    return (
        <AppLayout title="Manajemen Akun">
            <Head title="Manajemen Akun - SIA Shoe Workshop" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Manajemen Akun
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Kelola pengguna aplikasi, peran (roles), dan hak akses.
                        </p>
                    </div>

                    <div>
                        <button
                            onClick={handleAdd}
                            className="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition-all w-full sm:w-auto"
                        >
                            <Plus className="w-4 h-4" />
                            <span>Tambah User</span>
                        </button>
                    </div>
                </div>

                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
                    <div className="flex flex-col lg:flex-row items-center gap-3">
                        <div className="relative flex-1 w-full">
                            <Search className="w-4 h-4 absolute left-3.5 top-2.5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Cari nama atau email..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && executeFilter(e.target.value, role)}
                                className="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                            />
                        </div>

                        <div className="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                            <CustomSelect
                                value={role}
                                onChange={(e) => {
                                    setRole(e.target.value);
                                    executeFilter(search, e.target.value);
                                }}
                            >
                                <option value="">Semua Role</option>
                                {roles.map(r => (
                                    <option key={r} value={r} className="capitalize">{r}</option>
                                ))}
                            </CustomSelect>
                        </div>
                    </div>
                </div>

                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-100 dark:border-gray-800 text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider bg-gray-50/50 dark:bg-gray-800/50">
                                    <th className="py-3 px-4">User</th>
                                    <th className="py-3 px-4">Kontak</th>
                                    <th className="py-3 px-4">Role</th>
                                    <th className="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                                {users.data.length > 0 ? (
                                    users.data.map((user) => (
                                        <tr key={user.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                            <td className="py-3 px-4">
                                                <div className="flex items-center gap-3">
                                                    <img src={user.profile_photo_url} alt={user.name} className="w-8 h-8 rounded-full border border-gray-200 dark:border-gray-700 object-cover" />
                                                    <div>
                                                        <div className="font-semibold text-gray-900 dark:text-white text-sm">{user.name}</div>
                                                        <div className="text-[10px] text-gray-500">Terdaftar: {new Date(user.created_at).toLocaleDateString('id-ID')}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="flex flex-col gap-1">
                                                    <div className="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                                        <Mail className="w-3 h-3" />
                                                        <span>{user.email}</span>
                                                    </div>
                                                    <div className="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                                        <Phone className="w-3 h-3" />
                                                        <span>{user.phone || '-'}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 capitalize border border-emerald-200 dark:border-emerald-800/60">
                                                    <Shield className="w-3 h-3" />
                                                    {user.roles[0]?.name || 'No Role'}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                {user.id !== auth.user.id ? (
                                                    (!isFinanceOnly || user.roles.some(r => r.name === 'staff')) ? (
                                                        <div className="flex justify-end gap-2">
                                                            <button
                                                                onClick={() => handleEdit(user)}
                                                                className="p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors"
                                                                title="Edit"
                                                            >
                                                                <Pencil className="w-4 h-4" />
                                                            </button>
                                                            <button
                                                                onClick={() => handleDeleteClick(user)}
                                                                className="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors"
                                                                title="Hapus"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        <div className="flex justify-end">
                                                            <span className="text-[10px] text-gray-400 dark:text-gray-500 font-medium italic">Akses Terbatas</span>
                                                        </div>
                                                    )
                                                ) : (
                                                    <div className="flex justify-end">
                                                        <span className="text-[10px] text-gray-400 dark:text-gray-500 font-medium italic">Anda (Diri Sendiri)</span>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-gray-500 text-xs">
                                            Tidak ada data user.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={users.links} meta={users} />
                </div>
            </div>

            {/* Modal Form */}
            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                {editMode ? 'Edit User' : 'Tambah User Baru'}
                            </h3>
                            <button onClick={() => setModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                                <input
                                    type="text"
                                    required
                                    value={data.name}
                                    onChange={e => setData('name', e.target.value)}
                                    className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-sm focus:ring-emerald-500"
                                />
                                {errors.name && <p className="text-rose-500 text-xs mt-1">{errors.name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <input
                                    type="email"
                                    required
                                    value={data.email}
                                    onChange={e => setData('email', e.target.value)}
                                    className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-sm focus:ring-emerald-500"
                                />
                                {errors.email && <p className="text-rose-500 text-xs mt-1">{errors.email}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">No HP</label>
                                <input
                                    type="text"
                                    value={data.phone}
                                    onChange={e => setData('phone', formatPhone(e.target.value))}
                                    placeholder="+62-8xx-xxxx-xxxx"
                                    className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-sm focus:ring-emerald-500"
                                />
                                {errors.phone && <p className="text-rose-500 text-xs mt-1">{errors.phone}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Role</label>
                                <select
                                    required
                                    disabled={isFinanceOnly}
                                    value={isFinanceOnly ? 'staff' : data.role}
                                    onChange={e => setData('role', e.target.value)}
                                    className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-sm focus:ring-emerald-500 capitalize disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    {isFinanceOnly ? (
                                        <option value="staff">staff</option>
                                    ) : (
                                        <>
                                            <option value="">Pilih Role...</option>
                                            {roles.filter(r => r.toLowerCase() !== 'owner').map(r => (
                                                <option key={r} value={r}>{r}</option>
                                            ))}
                                        </>
                                    )}
                                </select>
                                {isFinanceOnly && (
                                    <p className="text-[10px] text-gray-400 mt-1 italic">
                                        * User Finance hanya diperbolehkan mengelola akun dengan role Staff.
                                    </p>
                                )}
                                {errors.role && <p className="text-rose-500 text-xs mt-1">{errors.role}</p>}
                            </div>
                            <div className="pt-2">
                                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Password {editMode && <span className="text-xs font-normal text-gray-500">(Kosongkan jika tidak ingin diubah)</span>}</label>
                                <input
                                    type="password"
                                    required={!editMode}
                                    value={data.password}
                                    onChange={e => setData('password', e.target.value)}
                                    className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-sm focus:ring-emerald-500"
                                />
                                {errors.password && <p className="text-rose-500 text-xs mt-1">{errors.password}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password</label>
                                <input
                                    type="password"
                                    required={!editMode && data.password.length > 0}
                                    value={data.password_confirmation}
                                    onChange={e => setData('password_confirmation', e.target.value)}
                                    className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-sm focus:ring-emerald-500"
                                />
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <button type="button" onClick={() => setModalOpen(false)} className="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 font-semibold">Batal</button>
                                <button type="submit" disabled={processing} className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold shadow-md">
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Delete */}
            {deleteModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
                        <div className="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mx-auto mb-4">
                            <Trash2 className="w-6 h-6 text-rose-600 dark:text-rose-500" />
                        </div>
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">Hapus Pengguna</h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            Apakah Anda yakin ingin menghapus akun <strong>{selectedUser?.name}</strong>? Tindakan ini tidak dapat dibatalkan.
                        </p>
                        <div className="flex justify-center gap-3">
                            <button onClick={() => setDeleteModalOpen(false)} className="px-4 py-2 text-sm font-semibold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button onClick={confirmDelete} className="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-500 rounded-xl shadow-md transition-colors">
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
