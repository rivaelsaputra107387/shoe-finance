import React, { useState, useEffect, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import ConfirmationModal from '@/Components/ConfirmationModal';
import Pagination from '@/Components/Pagination';
import CustomSelect from '@/Components/CustomSelect';
import MonthYearPicker from '@/Components/MonthYearPicker';
import DayPicker from '@/Components/DayPicker';
import { Search, CheckCircle2, Clock, FileEdit, Check, Trash2, Calendar, RotateCcw, Lock, Eye, ChevronDown } from 'lucide-react';

function MobileJournalCard({ item, isSelected, canSelect, toggleSelectOne, formatRupiah, isOwnerOrFinance, promptApprove }) {
    const [isOpen, setIsOpen] = React.useState(false);

    const totalDebit = item.lines?.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0) || 0;

    return (
        <div className={`transition-colors ${isSelected ? 'bg-emerald-50/40 dark:bg-emerald-950/30' : 'bg-white dark:bg-transparent'}`}>
            <div 
                className="p-3.5 flex items-start gap-3 cursor-pointer select-none"
                onClick={() => setIsOpen(!isOpen)}
            >
                {canSelect && (
                    <div className="pt-0.5" onClick={(e) => e.stopPropagation()}>
                        <input
                            type="checkbox"
                            checked={isSelected}
                            onChange={() => toggleSelectOne(item.id)}
                            className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer w-4 h-4"
                        />
                    </div>
                )}
                
                <div className="flex-1 min-w-0">
                    <div className="flex justify-between items-start mb-1.5">
                        <span className="font-mono font-bold text-gray-900 dark:text-white text-sm">{item.reference || '-'}</span>
                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold ${
                            item.status === 'posted'
                                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                                : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800'
                        }`}>
                            {item.status === 'posted' && <CheckCircle2 className="w-3 h-3" />}
                            {item.status === 'unapproved' && <Clock className="w-3 h-3" />}
                            <span className="capitalize">{item.status}</span>
                        </span>
                    </div>
                    
                    <div className="text-xs text-gray-500 dark:text-gray-400 truncate pr-4">
                        {item.description}
                    </div>
                    
                    <div className="flex justify-between items-center mt-2">
                        <span className="font-mono font-bold text-gray-900 dark:text-white text-xs">
                            {formatRupiah(totalDebit)}
                        </span>
                        <div className={`p-1 rounded-full ${isOpen ? 'bg-gray-100 dark:bg-gray-800' : ''}`}>
                            <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
                        </div>
                    </div>
                </div>
            </div>

            {isOpen && (
                <div className="px-3 pb-3 pt-2 border-t border-gray-100 dark:border-gray-800/60 space-y-3 bg-gray-50/50 dark:bg-gray-800/30">
                    {/* Rincian Akun */}
                    <div>
                        <div className="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Rincian Akun</div>
                        <div className="space-y-2">
                            {item.lines?.map((line) => (
                                <div key={line.id} className="flex justify-between items-start text-xs">
                                    <div className={`flex-1 ${line.credit > 0 ? 'pl-4 text-gray-500' : 'font-medium text-gray-900 dark:text-gray-200'}`}>
                                        <span className="font-mono text-gray-400 dark:text-gray-500 mr-1.5">{line.account?.code}</span>
                                        {line.account?.name}
                                    </div>
                                    <div className="font-mono font-medium text-right min-w-[80px]">
                                        {line.debit > 0 && <span className="text-emerald-600 dark:text-emerald-400">{formatRupiah(line.debit)}</span>}
                                        {line.credit > 0 && <span className="text-rose-600 dark:text-rose-400">{formatRupiah(line.credit)}</span>}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Aksi */}
                    <div className="flex justify-end gap-2 pt-3 border-t border-gray-200/50 dark:border-gray-700/50">
                        {item.status === 'unapproved' ? (
                            <>
                                <Link
                                    href={`/app/journal-entries/${item.id}/edit`}
                                    className="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition-colors"
                                >
                                    <FileEdit className="w-3.5 h-3.5" />
                                    <span>Edit</span>
                                </Link>

                                {isOwnerOrFinance && (
                                    <button
                                        onClick={() => promptApprove(item)}
                                        className="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-500 rounded-lg text-xs font-semibold transition-colors inline-flex items-center gap-1.5 shadow-sm"
                                    >
                                        <Check className="w-3.5 h-3.5" />
                                        <span>Approve</span>
                                    </button>
                                )}
                            </>
                        ) : (
                            <Link
                                href={`/app/journal-entries/${item.id}`}
                                className="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition-colors"
                            >
                                <Eye className="w-3.5 h-3.5" />
                                <span>Show Details</span>
                            </Link>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

export default function JournalList({ entries, filters }) {
    const { auth } = usePage().props;
    const userRoles = auth?.user?.roles || [];
    const isOwnerOrFinance = userRoles.some(r => ['owner', 'finance'].includes(r));

    const [search, setSearch] = useState(filters?.search || '');
    const [monthYear, setMonthYear] = useState(filters?.month_year || '2026-07');
    const [day, setDay] = useState(filters?.day || '');
    const [status, setStatus] = useState(filters?.status || '');
    const [selectedIds, setSelectedIds] = useState([]);

    const [confirmConfig, setConfirmConfig] = useState({
        isOpen: false,
        title: '',
        message: '',
        variant: 'primary',
        confirmText: 'Ya, Lanjutkan',
        onConfirm: () => {},
    });

    // Keep state synced with props
    useEffect(() => {
        if (filters?.month_year !== undefined) setMonthYear(filters.month_year);
        if (filters?.day !== undefined) setDay(filters.day);
        if (filters?.status !== undefined) setStatus(filters.status);
    }, [filters]);

    // Handle instant filter execution
    const executeFilter = (
        newMonthYear = monthYear,
        newDay = day,
        newStatus = status,
        newSearch = search
    ) => {
        router.get('/app/journal-entries', {
            month_year: newMonthYear,
            day: newDay,
            status: newStatus,
            search: newSearch,
        }, { replace: true });
    };

    const handleMonthYearChange = (val) => {
        setMonthYear(val);
        setDay(''); // Reset day when month-year changes
        executeFilter(val, '', status, search);
    };

    const handleDayChange = (val) => {
        setDay(val);
        executeFilter(monthYear, val, status, search);
    };

    const handleStatusChange = (val) => {
        setStatus(val);
        executeFilter(monthYear, day, val, search);
    };

    const handleReset = () => {
        setSearch('');
        setMonthYear('2026-07');
        setDay('');
        setStatus('');
        router.get('/app/journal-entries', { month_year: '2026-07' });
    };

    // Calculate days count in selected monthYear (28..31)
    const daysInSelectedMonth = useMemo(() => {
        if (!monthYear) return 31;
        const [y, m] = monthYear.split('-');
        if (!y || !m) return 31;
        return new Date(parseInt(y), parseInt(m), 0).getDate();
    }, [monthYear]);

    // Helper date formatting
    const formatDateHeader = (dateStr) => {
        if (!dateStr) return '-';
        try {
            const cleanStr = dateStr.split('T')[0];
            const [y, m, d] = cleanStr.split('-');
            const dateObj = new Date(parseInt(y), parseInt(m) - 1, parseInt(d));
            const dayName = dateObj.toLocaleDateString('id-ID', { weekday: 'long' });
            const formatted = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            return `${dayName.toUpperCase()}, ${formatted.toUpperCase()}`;
        } catch (e) {
            return dateStr;
        }
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
    };

    // Group entries by date
    const groupedEntries = useMemo(() => {
        if (!entries?.data) return [];
        const groups = {};
        entries.data.forEach((item) => {
            const dateKey = (item.entry_date || '').split('T')[0];
            if (!groups[dateKey]) {
                groups[dateKey] = [];
            }
            groups[dateKey].push(item);
        });

        return Object.keys(groups).map((dateKey) => {
            const items = groups[dateKey];
            const groupTotal = items.reduce((acc, item) => {
                const debitSum = item.lines?.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0) || 0;
                return acc + debitSum;
            }, 0);

            return {
                date: dateKey,
                items,
                count: items.length,
                totalAmount: groupTotal,
            };
        });
    }, [entries]);

    // Checkbox bulk actions - strictly for unapproved entries (posted entries cannot be deleted or modified)
    const toggleSelectAllGroup = (groupItems) => {
        const selectableItems = groupItems.filter(i => i.status !== 'posted');
        const selectableIds = selectableItems.map(i => i.id);
        if (selectableIds.length === 0) return;

        const allGroupSelected = selectableIds.every(id => selectedIds.includes(id));

        if (allGroupSelected) {
            setSelectedIds(prev => prev.filter(id => !selectableIds.includes(id)));
        } else {
            setSelectedIds(prev => Array.from(new Set([...prev, ...selectableIds])));
        }
    };

    const toggleSelectOne = (id) => {
        setSelectedIds(prev =>
            prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
        );
    };

    // Confirmation Prompts
    const promptApprove = (item) => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Approve Jurnal',
            message: `Apakah Anda yakin ingin menyetujui (Approve) jurnal ref ${item.reference || ''}? Jurnal ini akan langsung di-POSTED ke Buku Besar.`,
            variant: 'success',
            confirmText: 'Ya, Approve & Post',
            onConfirm: () => {
                router.post(`/app/journal-entries/${item.id}/approve`, {}, {
                    onFinish: () => setConfirmConfig(prev => ({ ...prev, isOpen: false }))
                });
            }
        });
    };

    const promptBulkApprove = () => {
        setConfirmConfig({
            isOpen: true,
            title: 'Konfirmasi Bulk Approve Jurnal',
            message: `Apakah Anda yakin ingin menyetujui dan memposting ${selectedIds.length} jurnal terpilih sekaligus?`,
            variant: 'success',
            confirmText: `Ya, Approve (${selectedIds.length}) Jurnal`,
            onConfirm: () => {
                router.post('/app/journal-entries/bulk-approve', { ids: selectedIds }, {
                    onFinish: () => {
                        setSelectedIds([]);
                        setConfirmConfig(prev => ({ ...prev, isOpen: false }));
                    }
                });
            }
        });
    };

    const hasActiveFilters = search || status || day || monthYear !== '2026-07';

    return (
        <AppLayout title="Daftar Jurnal Umum">
            <Head title="Daftar Jurnal Umum - SIA Shoe Workshop" />

            <div className="space-y-6">
                {/* Top Title Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Daftar Jurnal Umum (Posted & Unapproved)
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Daftar catatan transaksi keuangan final dan persetujuan jurnal harian.
                        </p>
                    </div>
                </div>

                {/* Bulk Action Toolbar if Items Selected */}
                {selectedIds.length > 0 && (
                    <div className="p-4 bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 rounded-2xl flex items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2">
                        <div className="flex items-center gap-3">
                            <span className="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-xs font-bold font-mono">
                                {selectedIds.length} Terpilih
                            </span>
                            <p className="text-xs font-semibold text-emerald-900 dark:text-emerald-200">
                                Pilih aksi massal untuk jurnal yang dicentang:
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            {isOwnerOrFinance && (
                                <button
                                    onClick={promptBulkApprove}
                                    className="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm inline-flex items-center gap-1.5"
                                >
                                    <Check className="w-3.5 h-3.5" />
                                    <span>Bulk Approve ({selectedIds.length}) Jurnal</span>
                                </button>
                            )}
                            <button
                                onClick={() => setSelectedIds([])}
                                className="px-2 py-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                )}

                {/* Custom Form Filter Bar */}
                <div className="p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
                    <div className="flex flex-col lg:flex-row items-center gap-3">
                        {/* Search Input */}
                        <div className="relative flex-1 w-full">
                            <Search className="w-4 h-4 absolute left-3.5 top-2.5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Cari nomor referensi atau keterangan jurnal..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && executeFilter()}
                                className="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                            />
                        </div>

                        {/* Custom Form Popover Controls */}
                        <div className="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                            {/* FORM 1: Custom Month & Year Popover Picker */}
                            <MonthYearPicker
                                value={monthYear}
                                onChange={handleMonthYearChange}
                            />

                            {/* FORM 2: Custom Day Grid Popover Picker (Calendar Grid 1..31 Tanpa Dropdown Native) */}
                            <DayPicker
                                value={day}
                                onChange={handleDayChange}
                                maxDays={daysInSelectedMonth}
                            />

                            {/* Status Filter */}
                            <CustomSelect
                                value={status}
                                onChange={(e) => handleStatusChange(e.target.value)}
                            >
                                <option value="">Semua Status (Posted & Unapproved)</option>
                                <option value="posted">Posted (Final)</option>
                                <option value="unapproved">Unapproved (Perlu Persetujuan)</option>
                            </CustomSelect>
                        </div>
                    </div>

                    {/* Reset Button Row if Active */}
                    {hasActiveFilters && (
                        <div className="flex justify-end pt-1 border-t border-gray-100 dark:border-gray-800">
                            <button
                                onClick={handleReset}
                                className="px-3 py-1 text-xs font-semibold text-gray-500 hover:text-rose-600 dark:hover:text-rose-400 inline-flex items-center gap-1 transition-colors"
                            >
                                <RotateCcw className="w-3 h-3" />
                                <span>Reset Filter</span>
                            </button>
                        </div>
                    )}
                </div>

                {/* Date-Grouped Timeline Journal Table */}
                <div className="space-y-4">
                    {groupedEntries.length > 0 ? (
                        groupedEntries.map((group) => {
                            const selectableGroupItems = group.items.filter(i => i.status !== 'posted');
                            const selectableIds = selectableGroupItems.map(i => i.id);
                            const hasSelectableInGroup = selectableIds.length > 0;
                            const isGroupAllSelected = hasSelectableInGroup && selectableIds.every(id => selectedIds.includes(id));
                            const isPeriodClosed = group.items.some(i => i.fiscal_period?.status === 'closed');

                            return (
                                <div key={group.date} className={`bg-white dark:bg-gray-900 rounded-2xl border shadow-sm overflow-hidden transition-all ${
                                    isPeriodClosed 
                                        ? 'border-amber-200 dark:border-amber-900/40 bg-amber-50/10 dark:bg-amber-950/5' 
                                        : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'
                                }`}>
                                    {/* Date Group Header Banner */}
                                    <div className={`px-4 py-3 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 ${
                                        isPeriodClosed 
                                            ? 'bg-amber-50/70 dark:bg-amber-950/30 border-amber-200/80 dark:border-amber-900/40' 
                                            : 'bg-gray-50/80 dark:bg-gray-800/80 border-gray-200 dark:border-gray-800'
                                    }`}>
                                        <div className="flex items-center gap-3">
                                            {hasSelectableInGroup ? (
                                                <input
                                                    type="checkbox"
                                                    checked={isGroupAllSelected}
                                                    onChange={() => toggleSelectAllGroup(group.items)}
                                                    className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                                    title="Pilih semua transaksi unapproved pada hari ini"
                                                />
                                            ) : (
                                                <span className="w-4 h-4 inline-block"></span>
                                            )}
                                            <div className="flex items-center gap-2">
                                                <Calendar className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                                <span className="text-xs font-extrabold tracking-wider text-gray-900 dark:text-white font-mono">
                                                    {formatDateHeader(group.date)}
                                                </span>

                                                {/* Closed Period Badge */}
                                                {isPeriodClosed && (
                                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800 shadow-2xs">
                                                        <Lock className="w-3 h-3 text-amber-600 dark:text-amber-400" />
                                                        <span>PERIODE DITUTUP</span>
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-3 self-end sm:self-auto text-xs">
                                            <span className="px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-200/60 dark:border-emerald-800/60">
                                                {group.count} Transaksi
                                            </span>
                                            <span className="font-mono font-bold text-gray-800 dark:text-gray-200">
                                                Total: {formatRupiah(group.totalAmount)}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Desktop View: Group Transactions Table */}
                                    <div className="hidden md:block">
                                        <table className="w-full text-left border-collapse table-fixed">
                                            <thead>
                                                <tr className="border-b border-gray-100 dark:border-gray-800 text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider bg-gray-50/30 dark:bg-gray-900/30">
                                                    <th className="py-2.5 px-2 w-8"></th>
                                                    <th className="py-2.5 px-2 w-[110px]">Referensi</th>
                                                    <th className="py-2.5 px-2 w-[150px]">Keterangan</th>
                                                    <th className="py-2.5 px-2">Rincian Akun</th>
                                                    <th className="py-2.5 px-2 w-[105px] text-right text-emerald-600 dark:text-emerald-400">Debet</th>
                                                    <th className="py-2.5 px-2 w-[105px] text-right text-rose-600 dark:text-rose-400">Kredit</th>
                                                    <th className="py-2.5 px-2 w-[90px]">Status</th>
                                                    <th className="py-2.5 px-2 w-[150px] text-right">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                                                {group.items.map((item) => {
                                                    const isSelected = selectedIds.includes(item.id);
                                                    const canSelect = item.status !== 'posted';
                                                        return (
                                                        <tr key={item.id} className={`transition-colors ${isSelected ? 'bg-emerald-50/40 dark:bg-emerald-950/30' : 'hover:bg-gray-50/60 dark:hover:bg-gray-800/40'}`}>
                                                            <td className="py-2.5 px-2">
                                                                {canSelect && (
                                                                    <input
                                                                        type="checkbox"
                                                                        checked={isSelected}
                                                                        onChange={() => toggleSelectOne(item.id)}
                                                                        className="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                                                    />
                                                                )}
                                                            </td>
                                                            <td className="py-2.5 px-2 font-mono font-bold text-gray-900 dark:text-white text-[11px] truncate">
                                                                {item.reference || '-'}
                                                            </td>
                                                            <td className="py-2.5 px-2 text-gray-700 dark:text-gray-300 truncate" title={item.description}>
                                                                {item.description}
                                                            </td>
                                                            <td className="py-2.5 px-2">
                                                                <div className="space-y-1">
                                                                    {item.lines?.map((line) => (
                                                                        <div key={line.id} className={`text-[11px] truncate ${line.credit > 0 ? 'pl-3 text-gray-500' : 'font-medium text-gray-900 dark:text-gray-200'}`}>
                                                                            <span className="font-mono text-gray-400 mr-1.5">{line.account?.code}</span>
                                                                            {line.account?.name}
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            </td>
                                                            <td className="py-2.5 px-2 text-right">
                                                                <div className="space-y-1 font-mono font-semibold tabular-nums text-[11px] text-emerald-600 dark:text-emerald-400">
                                                                    {item.lines?.map((line) => (
                                                                        <div key={line.id}>
                                                                            {line.debit > 0 ? formatRupiah(line.debit) : <span className="text-gray-300 dark:text-gray-700 opacity-40">-</span>}
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            </td>
                                                            <td className="py-2.5 px-2 text-right">
                                                                <div className="space-y-1 font-mono font-semibold tabular-nums text-[11px] text-rose-600 dark:text-rose-400">
                                                                    {item.lines?.map((line) => (
                                                                        <div key={line.id}>
                                                                            {line.credit > 0 ? formatRupiah(line.credit) : <span className="text-gray-300 dark:text-gray-700 opacity-40">-</span>}
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            </td>
                                                            <td className="py-2.5 px-2">
                                                                <span className={`inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold ${
                                                                    item.status === 'posted'
                                                                        ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                                                                        : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800'
                                                                }`}>
                                                                    {item.status === 'posted' && <CheckCircle2 className="w-3 h-3" />}
                                                                    {item.status === 'unapproved' && <Clock className="w-3 h-3" />}
                                                                    <span className="capitalize">{item.status}</span>
                                                                </span>
                                                            </td>
                                                            <td className="py-2.5 px-2 text-right">
                                                                <div className="flex items-center justify-end gap-1">
                                                                {item.status === 'unapproved' ? (
                                                                    <>
                                                                        <Link
                                                                            href={`/app/journal-entries/${item.id}/edit`}
                                                                            className="px-2 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-gray-700 dark:text-gray-300 rounded-lg text-[11px] font-semibold inline-flex items-center gap-1 transition-colors"
                                                                            title="Edit Jurnal"
                                                                        >
                                                                            <FileEdit className="w-3 h-3" />
                                                                            <span>Edit</span>
                                                                        </Link>

                                                                        {isOwnerOrFinance && (
                                                                            <button
                                                                                onClick={() => promptApprove(item)}
                                                                                className="px-2 py-1 bg-emerald-600 text-white hover:bg-emerald-500 rounded-lg text-[11px] font-semibold transition-colors inline-flex items-center gap-1 shadow-sm"
                                                                            >
                                                                                <Check className="w-3 h-3" />
                                                                                <span>Approve</span>
                                                                            </button>
                                                                        )}
                                                                    </>
                                                                ) : (
                                                                    <Link
                                                                        href={`/app/journal-entries/${item.id}`}
                                                                        className="px-2 py-1 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 rounded-lg text-[11px] font-semibold inline-flex items-center gap-1 transition-colors"
                                                                        title="Lihat Jurnal"
                                                                    >
                                                                        <Eye className="w-3 h-3" />
                                                                        <span>Show</span>
                                                                    </Link>
                                                                )}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Mobile View: Accordion List */}
                                    <div className="md:hidden flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
                                        {group.items.map((item) => {
                                            const isSelected = selectedIds.includes(item.id);
                                            const canSelect = item.status !== 'posted';
                                            return (
                                                <MobileJournalCard 
                                                    key={item.id}
                                                    item={item}
                                                    isSelected={isSelected}
                                                    canSelect={canSelect}
                                                    toggleSelectOne={toggleSelectOne}
                                                    formatRupiah={formatRupiah}
                                                    isOwnerOrFinance={isOwnerOrFinance}
                                                    promptApprove={promptApprove}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                            );
                        })
                    ) : (
                        <div className="p-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 text-center text-gray-500 text-xs shadow-sm">
                            Tidak ada data jurnal umum ditemukan untuk periode atau tanggal terpilih.
                        </div>
                    )}
                </div>

                {/* Pagination */}
                <Pagination links={entries?.links} meta={entries} />
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
