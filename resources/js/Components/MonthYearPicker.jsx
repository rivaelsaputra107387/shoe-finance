import React, { useState, useRef, useEffect } from 'react';
import { Calendar, ChevronDown, ChevronLeft, ChevronRight } from 'lucide-react';

const MONTH_NAMES = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const SHORT_MONTHS = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
];

export default function MonthYearPicker({ value, onChange, label = 'Bulan & Tahun' }) {
    const [isOpen, setIsOpen] = useState(false);
    const popoverRef = useRef(null);

    // Extract current selected year and month (format: YYYY-MM)
    const [year, month] = useMemo(() => {
        if (!value) return [new Date().getFullYear(), new Date().getMonth() + 1];
        const parts = value.split('-');
        return [parseInt(parts[0]) || new Date().getFullYear(), parseInt(parts[1]) || (new Date().getMonth() + 1)];
    }, [value]);

    const [pickerYear, setPickerYear] = useState(year);

    useEffect(() => {
        setPickerYear(year);
    }, [year]);

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

    const handleSelectMonth = (monthIndex) => {
        const formattedMonth = (monthIndex + 1).toString().padStart(2, '0');
        const newValue = `${pickerYear}-${formattedMonth}`;
        onChange(newValue);
        setIsOpen(false);
    };

    const handlePrevYear = () => setPickerYear(prev => prev - 1);
    const handleNextYear = () => setPickerYear(prev => prev + 1);

    const currentMonthName = MONTH_NAMES[month - 1] || 'Juli';

    return (
        <div className="relative inline-block text-left" ref={popoverRef}>
            {/* Trigger Button (No typing, pure click popover) */}
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="px-3.5 py-2 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 inline-flex items-center gap-2 transition-all shadow-sm cursor-pointer"
            >
                <Calendar className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                <span>{currentMonthName} {year}</span>
                <ChevronDown className={`w-3.5 h-3.5 text-gray-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} />
            </button>

            {/* Custom Month & Year Picker Popover Card */}
            {isOpen && (
                <div className="absolute right-0 lg:left-0 mt-2 w-64 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-xl z-50 p-3 space-y-3 animate-in fade-in zoom-in-95">
                    {/* Header Year Controls */}
                    <div className="flex items-center justify-between px-1 pb-2 border-b border-gray-100 dark:border-gray-800">
                        <button
                            type="button"
                            onClick={handlePrevYear}
                            className="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors"
                            title="Tahun Sebelumnya"
                        >
                            <ChevronLeft className="w-4 h-4" />
                        </button>

                        <div className="flex items-center gap-1.5">
                            <select
                                value={pickerYear}
                                onChange={(e) => setPickerYear(parseInt(e.target.value))}
                                className="bg-transparent text-sm font-bold text-gray-900 dark:text-white border-none p-0 focus:ring-0 cursor-pointer"
                            >
                                {Array.from({ length: 15 }, (_, i) => new Date().getFullYear() - 7 + i).map(y => (
                                    <option key={y} value={y} className="bg-white dark:bg-gray-900">{y}</option>
                                ))}
                            </select>
                        </div>

                        <button
                            type="button"
                            onClick={handleNextYear}
                            className="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors"
                            title="Tahun Berikutnya"
                        >
                            <ChevronRight className="w-4 h-4" />
                        </button>
                    </div>

                    {/* 4x3 Month Chips Grid */}
                    <div className="grid grid-cols-3 gap-1.5 pt-1">
                        {SHORT_MONTHS.map((mName, idx) => {
                            const isSelected = pickerYear === year && (idx + 1) === month;
                            return (
                                <button
                                    key={mName}
                                    type="button"
                                    onClick={() => handleSelectMonth(idx)}
                                    className={`py-2 text-xs font-semibold rounded-xl transition-all ${
                                        isSelected
                                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30'
                                            : 'hover:bg-emerald-50 dark:hover:bg-emerald-950/50 text-gray-700 dark:text-gray-300'
                                    }`}
                                >
                                    {mName}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}

// React useMemo import check
import { useMemo } from 'react';
