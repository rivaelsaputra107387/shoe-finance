import React, { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import ConfirmationModal from '@/Components/ConfirmationModal';
import Pagination from '@/Components/Pagination';
import CustomSelect from '@/Components/CustomSelect';
import { Upload, Sparkles, Search, ArrowUpRight, ArrowDownLeft, X, Trash2, Filter, RotateCcw, Calendar, ChevronDown, Plus, Pencil } from 'lucide-react';

export default function BankMutations({ mutations, filters }) {
    const [importModalOpen, setImportModalOpen] = useState(false);
    const [addModalOpen, setAddModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [editItem, setEditItem] = useState(null);
    const [search, setSearch] = useState(filters?.search || '');
    const [bankSource, setBankSource] = useState(filters?.bank_source || '');
    const [mutationType, setMutationType] = useState(filters?.mutation_type || '');
    const [status, setStatus] = useState(filters?.status || '');
    const [datePreset, setDatePreset] = useState(filters?.date_preset || 'all');
    const [startDate, setStartDate] = useState(filters?.start_date || '');
    const [endDate, setEndDate] = useState(filters?.end_date || '');
    const [selectedIds, setSelectedIds] = useState([]);

    const [confirmConfig, setConfirmConfig] = useState({
        isOpen: false,
        title: '',
        message: '',
        variant: 'primary',
        confirmText: 'Ya, Lanjutkan',
        onConfirm: () => {},
    });

    const { data, setData, post, processing, errors, reset } = useForm({
        file: null,
        bank_source: 'AUTO',
    });

    const addForm = useForm({
        date: '',
        bank_source: '',
        mutation_type: 'IN',
        amount: '',
        description: '',
    });

    const editForm = useForm({
        date: '',
        bank_source: '',
        mutation_type: 'IN',
        amount: '',
        description: '',
    });

    // Preset helper calculation
    const handlePresetChange = (preset) => {
        setDatePreset(preset);
        const today = new Date();
        const formatDateStr = (d) => d.toISOString().split('T')[0];

        if (preset === 'today') {
            const dateStr = formatDateStr(today);
            setStartDate(dateStr);
            setEndDate(dateStr);
        } else if (preset === 'last_7_days') {
            const past7 = new Date();
            past7.setDate(today.getDate() - 7);
            setStartDate(formatDateStr(past7));
            setEndDate(formatDateStr(today));
        } else if (preset === 'this_month') {
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            setStartDate(formatDateStr(firstDay));
            setEndDate(formatDateStr(lastDay));
        } else if (preset === 'last_month') {
            const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
            setStartDate(formatDateStr(firstDay));
            setEndDate(formatDateStr(lastDay));
        } else if (preset === 'all') {
            setStartDate('');
            setEndDate('');
        }
    };

    const handleFilter = () => {
        router.get('/app/bank-mutations', {
            search,
            bank_source: bankSource,
            mutation_type: mutationType,
            status,
            date_preset: datePreset,
            start_date: datePreset === 'all' ? '' : startDate,
            end_date: datePreset === 'all' ? '' : endDate,
        }, { preserveState: true, replace: true });
    };

    const handleReset = () => {
        setSearch('');
        setBankSource('');
        setMutationType('');
        setStatus('');
        setDatePreset('all');
        setStartDate('');
        setEndDate('');
        router.get('/app/bank-mutations');
    };

    const handleImportSubmit = (e) => {
        e.preventDefault();
        post('/app/bank-mutations/import', {
            onSuccess: () => {
                setImportModalOpen(false);
                reset();
            },
        });
    };

    const handleAddSubmit = (e) => {
        e.preventDefault();
        addForm.post('/app/bank-mutations', {
            onSuccess: () => {
                setAddModalOpen(false);
                addForm.reset();
            },
        });
    };

    const handleEditOpen = (item) => {
        setEditItem(item);
        editForm.setData({
            date: item.date,
            bank_source: item.bank_source,
            mutation_type: item.mutation_type,
            amount: item.amount,
            description: item.description,
        });
        setEditModalOpen(true);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        editForm.put(`/app/bank-mutations/${editItem.id}`, {
            onSuccess: () => {
                setEditModalOpen(false);
                setEditItem(null);
                editForm.reset();
            },
        });
    };

    const toggleSelectAll = () => {
        if (!mutations?.data?.length) return;
        if (selectedIds.length === mutations.data.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(mutations.data.map(item => item.id));
        }
    };

    const toggleSelectOne = (id) => {
        setSelectedIds(prev =>
            prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
        );
    };

    const promptBulkGenerateDraft = () => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Bulk Generate Draft Jurnal',
            message: `Apakah Anda yakin ingin membuat draft jurnal otomatis untuk ${selectedIds.length} data mutasi terpilih?`,
            variant: 'primary',
            confirmText: `Ya, Generate (${selectedIds.length}) Draft`,
            onConfirm: () => {
                router.post('/app/bank-mutations/bulk-generate-draft', { ids: selectedIds }, {
                    onFinish: () => {
                        setSelectedIds([]);
                        setConfirmConfig(prev => ({ ...prev, isOpen: false }));
                    }
                });
            }
        });
    };

    const promptBulkMatchApi = () => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Bulk Match API',
            message: `Apakah Anda yakin ingin mencocokkan ${selectedIds.length} data mutasi terpilih secara otomatis dengan invoice internal?`,
            variant: 'success',
            confirmText: `Ya, Match API (${selectedIds.length}) Data`,
            onConfirm: () => {
                router.post('/app/bank-mutations/bulk-match-api', { ids: selectedIds }, {
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
            title: 'Konfirmasi Bulk Delete Mutasi Bank',
            message: `Apakah Anda yakin ingin menghapus ${selectedIds.length} data mutasi bank terpilih? Data yang sudah dihapus tidak dapat dikembalikan.`,
            variant: 'danger',
            confirmText: `Ya, Hapus (${selectedIds.length}) Mutasi`,
            onConfirm: () => {
                router.post('/app/bank-mutations/bulk-delete', { ids: selectedIds }, {
                    onFinish: () => {
                        setSelectedIds([]);
                        setConfirmConfig(prev => ({ ...prev, isOpen: false }));
                    }
                });
            }
        });
    };

    const promptGenerateDraft = (item) => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Generate Draft Jurnal',
            message: `Apakah Anda yakin ingin membuat draft jurnal otomatis untuk mutasi bank nominal ${formatRupiah(item.amount)} ini?`,
            variant: 'primary',
            confirmText: 'Ya, Generate Draft',
            onConfirm: () => {
                router.post(`/app/bank-mutations/${item.id}/generate-draft`, {}, {
                    onFinish: () => setConfirmConfig(prev => ({ ...prev, isOpen: false }))
                });
            }
        });
    };

    const promptMatchApi = (item) => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Match API',
            message: `Apakah Anda yakin ingin mencocokkan mutasi bank nominal ${formatRupiah(item.amount)} ini secara otomatis dengan sistem invoice?`,
            variant: 'success',
            confirmText: 'Ya, Match API',
            onConfirm: () => {
                router.post(`/app/bank-mutations/${item.id}/match-api`, {}, {
                    onFinish: () => setConfirmConfig(prev => ({ ...prev, isOpen: false }))
                });
            }
        });
    };

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

    const formatNumberInput = (val) => {
        if (!val && val !== 0) return '';
        const cleanVal = val.toString().replace(/\D/g, '');
        return cleanVal ? new Intl.NumberFormat('id-ID').format(cleanVal) : '';
    };

    const parseNumberInput = (val) => {
        const cleanVal = val.toString().replace(/\D/g, '');
        return cleanVal ? parseInt(cleanVal, 10) : 0;
    };

    const hasActiveFilters = search || bankSource || mutationType || status || (datePreset !== 'all');
    const allSelected = mutations?.data?.length > 0 && selectedIds.length === mutations.data.length;

    return (
        <AppLayout title="Transaksi">
            <Head title="Transaksi - SIA Shoe Workshop" />

            <div className="space-y-6">
                {/* Top Action Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Transaksi
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Kelola data transaksi bank/kas dan buat draft jurnal otomatis.
                        </p>
                    </div>

                    <div className="flex flex-col sm:flex-row items-center gap-2">
                        <button
                            onClick={() => setAddModalOpen(true)}
                            className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-sm font-semibold rounded-xl shadow-sm transition-all"
                        >
                            <Plus className="w-4 h-4 text-emerald-600" />
                            <span>Input Manual</span>
                        </button>
                        <button
                            onClick={() => setImportModalOpen(true)}
                            className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition-all"
                        >
                            <Upload className="w-4 h-4" />
                            <span>Import CSV (BCA/Mandiri)</span>
                        </button>
                    </div>
                </div>

                {/* Bulk Action Bar if Selected */}
                {selectedIds.length > 0 && (
                    <div className="p-4 bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 rounded-2xl flex items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2">
                        <div className="flex items-center gap-3">
                            <span className="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-xs font-bold font-mono">
                                {selectedIds.length} Terpilih
                            </span>
                            <p className="text-xs font-semibold text-emerald-900 dark:text-emerald-200">
                                Pilih aksi massal untuk item mutasi yang dicentang:
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                disabled={true}
                                className="px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-400 dark:text-gray-600 text-xs font-semibold rounded-xl cursor-not-allowed opacity-60 select-none"
                                title="Fitur Match API Segera Hadir / Non-Aktif"
                            >
                                Bulk Match API ({selectedIds.length})
                            </button>
                            <button
                                onClick={promptBulkGenerateDraft}
                                className="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm inline-flex items-center gap-1.5"
                            >
                                <Sparkles className="w-3.5 h-3.5" />
                                <span>Bulk Generate Draft ({selectedIds.length})</span>
                            </button>
                            <button
                                onClick={promptBulkDelete}
                                className="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm inline-flex items-center gap-1.5"
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

                {/* Ultra-Minimalist Finance Filter Bar */}
                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
                    <div className="flex flex-col lg:flex-row items-center gap-3">
                        {/* Search Input */}
                        <div className="relative flex-1 w-full">
                            <Search className="w-4 h-4 absolute left-3.5 top-2.5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Cari deskripsi mutasi / nama pengirim..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                className="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                            />
                        </div>

                        {/* Filter Dropdowns */}
                        <div className="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                            {/* Bank Dropdown */}
                            <CustomSelect
                                value={bankSource}
                                onChange={(e) => setBankSource(e.target.value)}
                            >
                                <option value="">Semua Bank</option>
                                <option value="BCA">BCA</option>
                                <option value="MANDIRI">Mandiri</option>
                            </CustomSelect>

                            {/* Tipe Dropdown */}
                            <CustomSelect
                                value={mutationType}
                                onChange={(e) => setMutationType(e.target.value)}
                            >
                                <option value="">Semua Tipe</option>
                                <option value="IN">Uang Masuk (IN)</option>
                                <option value="OUT">Uang Keluar (OUT)</option>
                            </CustomSelect>

                            {/* Status Dropdown */}
                            <CustomSelect
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                            >
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="drafted">Drafted</option>
                                <option value="matched">Matched</option>
                            </CustomSelect>

                            {/* Finance Preset Periode Dropdown */}
                            <CustomSelect
                                value={datePreset}
                                onChange={(e) => handlePresetChange(e.target.value)}
                            >
                                <option value="all">📅 Semua Waktu</option>
                                <option value="today">Hari Ini</option>
                                <option value="last_7_days">7 Hari Terakhir</option>
                                <option value="this_month">Bulan Ini</option>
                                <option value="last_month">Bulan Lalu</option>
                                <option value="custom">Kustom Tanggal...</option>
                            </CustomSelect>
                        </div>
                    </div>

                    {/* Expandable Custom Date Row (Only if 'custom' selected or active) */}
                    {(datePreset === 'custom' || startDate || endDate) && datePreset !== 'all' && (
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2.5 border-t border-gray-100 dark:border-gray-800 text-xs animate-in fade-in">
                            <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <Calendar className="w-3.5 h-3.5 text-indigo-500" />
                                <span className="font-semibold text-gray-800 dark:text-gray-200">Rentang Tanggal Kustom:</span>
                                <input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => { setStartDate(e.target.value); setDatePreset('custom'); }}
                                    className="py-1 px-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-900 dark:text-white"
                                />
                                <span>s/d</span>
                                <input
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => { setEndDate(e.target.value); setDatePreset('custom'); }}
                                    className="py-1 px-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-900 dark:text-white"
                                />
                            </div>
                        </div>
                    )}

                    {/* Filter Actions */}
                    <div className="flex items-center justify-end gap-2 pt-1">
                        {hasActiveFilters && (
                            <button
                                onClick={handleReset}
                                className="px-3 py-1.5 text-xs font-semibold text-gray-500 hover:text-rose-600 dark:hover:text-rose-400 inline-flex items-center gap-1 transition-colors"
                            >
                                <RotateCcw className="w-3 h-3" />
                                <span>Reset Filter</span>
                            </button>
                        )}

                        <button
                            onClick={handleFilter}
                            className="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold inline-flex items-center gap-1.5 transition-colors shadow-sm shadow-emerald-600/20"
                        >
                            <Filter className="w-3.5 h-3.5" />
                            <span>Terapkan Filter</span>
                        </button>
                    </div>
                </div>

                {/* Table */}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-100 dark:border-gray-800 text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider bg-gray-50/50 dark:bg-gray-800/50">
                                    <th className="py-2.5 px-2.5 w-8">
                                        <input
                                            type="checkbox"
                                            checked={allSelected}
                                            onChange={toggleSelectAll}
                                            className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                        />
                                    </th>
                                    <th className="py-2.5 px-2.5 w-28">Tanggal</th>
                                    <th className="py-2.5 px-2.5 w-20">Bank</th>
                                    <th className="py-2.5 px-2.5 max-w-[260px]">Keterangan Mutasi</th>
                                    <th className="py-2.5 px-2.5 w-28">Tipe</th>
                                    <th className="py-2.5 px-3 w-32 text-right">Nominal</th>
                                    <th className="py-2.5 px-2.5 w-20">Status</th>
                                    <th className="py-2.5 px-4 w-48 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                                {mutations?.data?.length > 0 ? (
                                    mutations.data.map((item) => {
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
                                                <td className="py-2.5 px-2.5 whitespace-nowrap font-mono font-medium text-gray-700 dark:text-gray-300">
                                                    {formatDate(item.date)}
                                                </td>
                                                <td className="py-2.5 px-2.5 whitespace-nowrap">
                                                    <div className="flex flex-col gap-1">
                                                        <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 w-max">
                                                            {item.bank_source}
                                                        </span>
                                                        <span className={`inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold w-max ${
                                                            item.source_type === 'manual' 
                                                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400' 
                                                                : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400'
                                                        }`}>
                                                            {item.source_type === 'manual' ? 'MANUAL' : 'EXCEL'}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="py-2.5 px-2.5 text-gray-800 dark:text-gray-200 font-medium max-w-[260px] truncate" title={item.description}>
                                                    {item.description}
                                                </td>
                                                <td className="py-2.5 px-2.5 whitespace-nowrap">
                                                    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold ${
                                                        item.mutation_type === 'IN'
                                                            ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400'
                                                            : 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400'
                                                    }`}>
                                                        {item.mutation_type === 'IN' ? <ArrowDownLeft className="w-3 h-3" /> : <ArrowUpRight className="w-3 h-3" />}
                                                        <span>{item.mutation_type === 'IN' ? 'Uang Masuk' : 'Uang Keluar'}</span>
                                                    </span>
                                                </td>
                                                <td className="py-2.5 px-3 whitespace-nowrap text-right font-mono font-semibold text-gray-900 dark:text-white">
                                                    {formatRupiah(item.amount)}
                                                </td>
                                                <td className="py-2.5 px-2.5 whitespace-nowrap">
                                                    <span className="capitalize text-[11px] font-semibold text-gray-500">
                                                        {item.status}
                                                    </span>
                                                </td>
                                                <td className="py-4 px-4 whitespace-nowrap text-right space-x-2">
                                                    {item.status === 'pending' && (
                                                        <>
                                                            {item.source_type === 'manual' && (
                                                                <button
                                                                    onClick={() => handleEditOpen(item)}
                                                                    className="px-2.5 py-1.5 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-900/60 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition-colors shadow-sm"
                                                                >
                                                                    <Pencil className="w-3.5 h-3.5" />
                                                                    <span>Edit</span>
                                                                </button>
                                                            )}
                                                            <button
                                                                disabled={true}
                                                                className="px-3 py-1.5 bg-gray-100 dark:bg-gray-800/60 text-gray-400 dark:text-gray-600 rounded-lg text-xs font-semibold inline-flex items-center gap-1 cursor-not-allowed opacity-60 select-none"
                                                                title="Fitur Match API Segera Hadir / Non-Aktif"
                                                            >
                                                                <span>Match API</span>
                                                            </button>

                                                            <button
                                                                onClick={() => promptGenerateDraft(item)}
                                                                className="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-500 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition-colors shadow-sm"
                                                            >
                                                                <Sparkles className="w-3.5 h-3.5" />
                                                                <span>Generate Draft</span>
                                                            </button>
                                                        </>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td colSpan="8" className="py-8 text-center text-gray-500 text-xs">
                                            Tidak ada data transaksi ditemukan.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    {/* Pagination */}
                    <Pagination links={mutations?.links} meta={mutations} />
                </div>
            </div>

            {/* Import CSV Modal */}
            {importModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-in fade-in">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 max-w-md w-full shadow-2xl space-y-6">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <Upload className="w-5 h-5 text-emerald-500" />
                                <span>Import CSV Mutasi Bank</span>
                            </h3>
                            <button onClick={() => setImportModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleImportSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    Pilih Format Bank
                                </label>
                                <select
                                    value={data.bank_source}
                                    onChange={(e) => setData('bank_source', e.target.value)}
                                    className="w-full py-2.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white"
                                >
                                    <option value="AUTO">Otomatis Deteksi (Rekomendasi - BCA & Mandiri)</option>
                                    <option value="BCA">BCA (Terdapat kolom mutasi gabungan)</option>
                                    <option value="MANDIRI">Mandiri (Terdapat kolom Debit dan Kredit terpisah)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    File CSV / Excel Export
                                </label>
                                <input
                                    type="file"
                                    onChange={(e) => setData('file', e.target.files[0])}
                                    accept=".csv,.txt,.xls,.xlsx"
                                    required
                                    className="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-400 cursor-pointer"
                                />
                                <p className="text-[11px] text-gray-400 mt-1">
                                    File mentah BCA/Mandiri dapat langsung diunggah tanpa membuang baris judul.
                                </p>
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    onClick={() => setImportModalOpen(false)}
                                    className="px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-md disabled:opacity-50"
                                >
                                    {processing ? 'Memproses...' : 'Mulai Import'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Add Manual Modal */}
            {addModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-200">
                        <div className="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white">Input Mutasi Manual</h3>
                            <button
                                onClick={() => {
                                    setAddModalOpen(false);
                                    addForm.reset();
                                }}
                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <form onSubmit={handleAddSubmit} className="p-6">
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Tanggal Transaksi
                                    </label>
                                    <input
                                        type="date"
                                        required
                                        value={addForm.data.date}
                                        onChange={e => addForm.setData('date', e.target.value)}
                                        className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-emerald-500 focus:border-emerald-500 text-sm px-3 py-2.5 transition-colors"
                                    />
                                    {addForm.errors.date && <p className="mt-1 text-xs text-rose-500">{addForm.errors.date}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Sumber (Bank/Cash)
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Contoh: BCA, MANDIRI, CASH"
                                        value={addForm.data.bank_source}
                                        onChange={e => addForm.setData('bank_source', e.target.value.toUpperCase())}
                                        className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-emerald-500 focus:border-emerald-500 text-sm px-3 py-2.5 transition-colors uppercase"
                                    />
                                    {addForm.errors.bank_source && <p className="mt-1 text-xs text-rose-500">{addForm.errors.bank_source}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Tipe Mutasi
                                    </label>
                                    <div className="flex gap-4">
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input 
                                                type="radio" 
                                                name="mutation_type" 
                                                value="IN"
                                                checked={addForm.data.mutation_type === 'IN'}
                                                onChange={() => addForm.setData('mutation_type', 'IN')}
                                                className="text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Uang Masuk (IN)</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input 
                                                type="radio" 
                                                name="mutation_type" 
                                                value="OUT"
                                                checked={addForm.data.mutation_type === 'OUT'}
                                                onChange={() => addForm.setData('mutation_type', 'OUT')}
                                                className="text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Uang Keluar (OUT)</span>
                                        </label>
                                    </div>
                                    {addForm.errors.mutation_type && <p className="mt-1 text-xs text-rose-500">{addForm.errors.mutation_type}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Nominal (Rp)
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        required
                                        placeholder="0"
                                        value={formatNumberInput(addForm.data.amount)}
                                        onChange={e => addForm.setData('amount', parseNumberInput(e.target.value))}
                                        className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-emerald-500 focus:border-emerald-500 text-sm px-3 py-2.5 transition-colors"
                                    />
                                    {addForm.errors.amount && <p className="mt-1 text-xs text-rose-500">{addForm.errors.amount}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Keterangan
                                    </label>
                                    <textarea
                                        required
                                        rows={3}
                                        placeholder="Keterangan transaksi..."
                                        value={addForm.data.description}
                                        onChange={e => addForm.setData('description', e.target.value)}
                                        className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-emerald-500 focus:border-emerald-500 text-sm px-3 py-2.5 transition-colors resize-none"
                                    ></textarea>
                                    {addForm.errors.description && <p className="mt-1 text-xs text-rose-500">{addForm.errors.description}</p>}
                                </div>
                            </div>
                            <div className="mt-6 flex justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setAddModalOpen(false);
                                        addForm.reset();
                                    }}
                                    className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={addForm.processing}
                                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2"
                                >
                                    {addForm.processing ? 'Menyimpan...' : 'Simpan Transaksi'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Edit Manual Modal */}
            {editModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-200">
                        <div className="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white">Edit Mutasi Manual</h3>
                            <button
                                onClick={() => {
                                    setEditModalOpen(false);
                                    setEditItem(null);
                                    editForm.reset();
                                }}
                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <form onSubmit={handleEditSubmit} className="p-6">
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Tanggal Transaksi
                                    </label>
                                    <input
                                        type="date"
                                        required
                                        value={editForm.data.date}
                                        onChange={e => editForm.setData('date', e.target.value)}
                                        className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-emerald-500 focus:border-emerald-500 text-sm px-3 py-2.5 transition-colors"
                                    />
                                    {editForm.errors.date && <p className="mt-1 text-xs text-rose-500">{editForm.errors.date}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Sumber (Bank/Cash)
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Contoh: BCA, MANDIRI, CASH"
                                        value={editForm.data.bank_source}
                                        onChange={e => editForm.setData('bank_source', e.target.value.toUpperCase())}
                                        className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-emerald-500 focus:border-emerald-500 text-sm px-3 py-2.5 transition-colors uppercase"
                                    />
                                    {editForm.errors.bank_source && <p className="mt-1 text-xs text-rose-500">{editForm.errors.bank_source}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Tipe Mutasi
                                    </label>
                                    <div className="flex gap-4">
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input 
                                                type="radio" 
                                                name="mutation_type" 
                                                value="IN"
                                                checked={editForm.data.mutation_type === 'IN'}
                                                onChange={() => editForm.setData('mutation_type', 'IN')}
                                                className="text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Uang Masuk (IN)</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input 
                                                type="radio" 
                                                name="mutation_type" 
                                                value="OUT"
                                                checked={editForm.data.mutation_type === 'OUT'}
                                                onChange={() => editForm.setData('mutation_type', 'OUT')}
                                                className="text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Uang Keluar (OUT)</span>
                                        </label>
                                    </div>
                                    {editForm.errors.mutation_type && <p className="mt-1 text-xs text-rose-500">{editForm.errors.mutation_type}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Nominal (Rp)
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        required
                                        placeholder="0"
                                        value={formatNumberInput(editForm.data.amount)}
                                        onChange={e => editForm.setData('amount', parseNumberInput(e.target.value))}
                                        className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-emerald-500 focus:border-emerald-500 text-sm px-3 py-2.5 transition-colors"
                                    />
                                    {editForm.errors.amount && <p className="mt-1 text-xs text-rose-500">{editForm.errors.amount}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Keterangan
                                    </label>
                                    <textarea
                                        required
                                        rows={3}
                                        placeholder="Keterangan transaksi..."
                                        value={editForm.data.description}
                                        onChange={e => editForm.setData('description', e.target.value)}
                                        className="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-emerald-500 focus:border-emerald-500 text-sm px-3 py-2.5 transition-colors resize-none"
                                    ></textarea>
                                    {editForm.errors.description && <p className="mt-1 text-xs text-rose-500">{editForm.errors.description}</p>}
                                </div>
                            </div>
                            <div className="mt-6 flex justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setEditModalOpen(false);
                                        setEditItem(null);
                                        editForm.reset();
                                    }}
                                    className="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={editForm.processing}
                                    className="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2"
                                >
                                    {editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
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
