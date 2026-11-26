import React, { useState, useRef, useEffect, useMemo } from 'react';
import { Calendar, ChevronDown, X, CalendarDays, Check, ArrowRight } from 'lucide-react';

export default function LedgerDateFilter({
    period,
    selectedDay,
    selectedStartDate,
    selectedEndDate,
    onFilterChange,
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [activeTab, setActiveTab] = useState('day'); // 'day' | 'range'
    const popoverRef = useRef(null);

    // Calculate maximum days in the period's month
    const maxDays = useMemo(() => {
        if (!period?.start_date) return 31;
        const d = new Date(period.start_date);
        return new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    }, [period]);

    const periodYearMonth = useMemo(() => {
        if (!period?.start_date) return '';
        const d = new Date(period.start_date);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        return `${y}-${m}`;
    }, [period]);

    const periodMin = period?.start_date ? String(period.start_date).slice(0, 10) : '';
    const periodMax = period?.end_date ? String(period.end_date).slice(0, 10) : '';

    // Local state for custom range inputs
    const [rangeStart, setRangeStart] = useState(selectedStartDate || periodMin);
    const [rangeEnd, setRangeEnd] = useState(selectedEndDate || periodMax);

    useEffect(() => {
        if (selectedStartDate) setRangeStart(selectedStartDate);
        else if (periodMin) setRangeStart(periodMin);

        if (selectedEndDate) setRangeEnd(selectedEndDate);
        else if (periodMax) setRangeEnd(periodMax);
    }, [selectedStartDate, selectedEndDate, periodMin, periodMax]);

    // Close on click outside
    useEffect(() => {
        function handleClickOutside(event) {
            if (popoverRef.current && !popoverRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Check if any filter is active
    const isFiltered = Boolean(selectedDay || (selectedStartDate && selectedEndDate));

    // Display label
    const displayLabel = useMemo(() => {
        if (selectedDay) {
            return `Tanggal ${selectedDay < 10 ? `0${selectedDay}` : selectedDay}`;
        }
        if (selectedStartDate && selectedEndDate) {
            if (selectedStartDate === selectedEndDate) {
                return `Tanggal ${selectedStartDate.slice(8, 10)}`;
            }
            const s = selectedStartDate.split('-');
            const e = selectedEndDate.split('-');
            return `${s[2]}/${s[1]} - ${e[2]}/${e[1]}`;
        }
        return 'Semua Tanggal';
    }, [selectedDay, selectedStartDate, selectedEndDate]);

    const handleSelectAll = (e) => {
        e?.stopPropagation();
        onFilterChange({ day: null, startDate: null, endDate: null });
        setIsOpen(false);
    };

    const handleSelectDay = (d) => {
        onFilterChange({ day: d, startDate: null, endDate: null });
        setIsOpen(false);
    };

    const handleApplyRange = (e) => {
        e.preventDefault();
        if (!rangeStart || !rangeEnd) return;

        // If range start and end are identical, treat as single day
        if (rangeStart === rangeEnd) {
            const dayNum = parseInt(rangeStart.slice(8, 10), 10);
            onFilterChange({ day: dayNum, startDate: rangeStart, endDate: rangeEnd });
        } else {
            onFilterChange({ day: null, startDate: rangeStart, endDate: rangeEnd });
        }
        setIsOpen(false);
    };

    const handleQuickRange = (startDay, endDay) => {
        if (!periodYearMonth) return;
        const s = `${periodYearMonth}-${String(startDay).padStart(2, '0')}`;
        const e = `${periodYearMonth}-${String(Math.min(endDay, maxDays)).padStart(2, '0')}`;
        setRangeStart(s);
        setRangeEnd(e);
        onFilterChange({ day: null, startDate: s, endDate: e });
        setIsOpen(false);
    };

    return (
        <div className="relative inline-block text-left" ref={popoverRef}>
            {/* Trigger Button */}
            <div className="inline-flex items-center">
                <button
                    type="button"
                    onClick={() => setIsOpen(!isOpen)}
                    className={`px-3.5 py-2.5 rounded-xl text-xs font-semibold inline-flex items-center gap-2 transition-all shadow-2xs border cursor-pointer ${
                        isFiltered
                            ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20'
                            : 'bg-gray-50/90 dark:bg-gray-800/90 border-gray-200/90 dark:border-gray-700/90 text-gray-800 dark:text-gray-200 hover:border-emerald-300'
                    }`}
                >
                    <Calendar className={`w-4 h-4 ${isFiltered ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400'}`} />
                    <span>{displayLabel}</span>
                    <ChevronDown className={`w-3.5 h-3.5 text-gray-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} />
                </button>

                {isFiltered && (
                    <button
                        type="button"
                        onClick={handleSelectAll}
                        title="Reset ke Semua Tanggal"
                        className="ml-1.5 p-1 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                    >
                        <X className="w-4 h-4" />
                    </button>
                )}
            </div>

            {/* Popover Card */}
            {isOpen && (
                <div className="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-2xl z-50 p-4 space-y-3 animate-in fade-in zoom-in-95">
                    {/* Header: Reset to Full Month Option */}
                    <button
                        type="button"
                        onClick={handleSelectAll}
                        className={`w-full py-2 px-3 text-xs font-bold rounded-xl transition-all border flex items-center justify-between ${
                            !isFiltered
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/25'
                                : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700'
                        }`}
                    >
                        <div className="flex items-center gap-2">
                            <CalendarDays className="w-4 h-4" />
                            <span>Semua Tanggal (1 Bulan Penuh)</span>
                        </div>
                        {!isFiltered && <Check className="w-4 h-4" />}
                    </button>

                    {/* Mode Segmented Tabs */}
                    <div className="flex p-0.5 bg-gray-100 dark:bg-gray-800 rounded-xl text-xs font-semibold">
                        <button
                            type="button"
                            onClick={() => setActiveTab('day')}
                            className={`flex-1 py-1.5 rounded-lg transition-all ${
                                activeTab === 'day'
                                    ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs font-bold'
                                    : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
                            }`}
                        >
                            Per Tanggal (1..{maxDays})
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('range')}
                            className={`flex-1 py-1.5 rounded-lg transition-all ${
                                activeTab === 'range'
                                    ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-2xs font-bold'
                                    : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
                            }`}
                        >
                            Rentang Tanggal
                        </button>
                    </div>

                    {/* TAB 1: Specific Day Grid */}
                    {activeTab === 'day' && (
                        <div className="space-y-2">
                            <p className="text-[10px] uppercase font-bold tracking-wider text-gray-400">
                                Pilih Hari Spesifik:
                            </p>
                            <div className="grid grid-cols-7 gap-1.5">
                                {Array.from({ length: maxDays }, (_, i) => i + 1).map((d) => {
                                    const isDayActive = selectedDay === d;
                                    return (
                                        <button
                                            key={d}
                                            type="button"
                                            onClick={() => handleSelectDay(d)}
                                            className={`h-8 rounded-lg text-xs font-mono font-bold transition-all flex items-center justify-center ${
                                                isDayActive
                                                    ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 scale-105'
                                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/60 hover:text-emerald-600 border border-transparent hover:border-emerald-200'
                                            }`}
                                        >
                                            {d < 10 ? `0${d}` : d}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    {/* TAB 2: Custom Date Range */}
                    {activeTab === 'range' && (
                        <form onSubmit={handleApplyRange} className="space-y-3">
                            <div className="space-y-2">
                                <div>
                                    <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                        Dari Tanggal
                                    </label>
                                    <input
                                        type="date"
                                        value={rangeStart}
                                        min={periodMin}
                                        max={periodMax}
                                        onChange={(e) => setRangeStart(e.target.value)}
                                        className="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-1.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                                        Sampai Tanggal
                                    </label>
                                    <input
                                        type="date"
                                        value={rangeEnd}
                                        min={rangeStart || periodMin}
                                        max={periodMax}
                                        onChange={(e) => setRangeEnd(e.target.value)}
                                        className="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-1.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                        required
                                    />
                                </div>
                            </div>

                            {/* Quick Shortcut Buttons */}
                            <div className="pt-1">
                                <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">
                                    Pilihan Cepat:
                                </p>
                                <div className="grid grid-cols-3 gap-1.5 text-[11px]">
                                    <button
                                        type="button"
                                        onClick={() => handleQuickRange(1, 10)}
                                        className="py-1 px-1.5 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 font-semibold"
                                    >
                                        Tgl 1 - 10
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handleQuickRange(11, 20)}
                                        className="py-1 px-1.5 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 font-semibold"
                                    >
                                        Tgl 11 - 20
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handleQuickRange(21, maxDays)}
                                        className="py-1 px-1.5 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 font-semibold"
                                    >
                                        Tgl 21 - Akhir
                                    </button>
                                </div>
                            </div>

                            <button
                                type="submit"
                                className="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-emerald-600/20 inline-flex items-center justify-center gap-1.5"
                            >
                                <span>Terapkan Rentang</span>
                                <ArrowRight className="w-3.5 h-3.5" />
                            </button>
                        </form>
                    )}
                </div>
            )}
        </div>
    );
}
