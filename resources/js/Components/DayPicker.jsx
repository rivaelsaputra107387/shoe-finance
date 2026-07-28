import React, { useState, useRef, useEffect, useMemo } from 'react';
import { Calendar, ChevronDown } from 'lucide-react';

export default function DayPicker({ value, onChange, maxDays = 31 }) {
    const [isOpen, setIsOpen] = useState(false);
    const popoverRef = useRef(null);

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

    const handleSelectDay = (dayVal) => {
        onChange(dayVal);
        setIsOpen(false);
    };

    const daysList = useMemo(() => {
        const list = [];
        for (let i = 1; i <= maxDays; i++) {
            list.push(i);
        }
        return list;
    }, [maxDays]);

    const displayLabel = value ? `Tanggal ${value < 10 ? `0${value}` : value}` : 'Semua Tanggal';

    return (
        <div className="relative inline-block text-left" ref={popoverRef}>
            {/* Trigger Button */}
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className={`px-3.5 py-2 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold inline-flex items-center gap-2 transition-all shadow-sm cursor-pointer ${
                    value ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-gray-800 dark:text-gray-200'
                }`}
            >
                <Calendar className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                <span>{displayLabel}</span>
                <ChevronDown className={`w-3.5 h-3.5 text-gray-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} />
            </button>

            {/* Custom Day Grid Popover Card */}
            {isOpen && (
                <div className="absolute right-0 lg:left-0 mt-2 w-64 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-xl z-50 p-3 space-y-2.5 animate-in fade-in zoom-in-95">
                    {/* Header Option: Semua Tanggal */}
                    <button
                        type="button"
                        onClick={() => handleSelectDay('')}
                        className={`w-full py-2 text-xs font-bold rounded-xl transition-all border ${
                            !value
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/30'
                                : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/50'
                        }`}
                    >
                        📆 Semua Tanggal
                    </button>

                    <div className="text-[10px] font-semibold text-gray-400 uppercase tracking-wider text-center">
                        Pilih Tanggal Spesifik (1 s/d {maxDays})
                    </div>

                    {/* 7 Columns Visual Day Grid (Calendar Grid Style) */}
                    <div className="grid grid-cols-7 gap-1">
                        {daysList.map((d) => {
                            const isSelected = String(value) === String(d);
                            return (
                                <button
                                    key={d}
                                    type="button"
                                    onClick={() => handleSelectDay(d)}
                                    className={`h-8 text-xs font-bold rounded-lg transition-all flex items-center justify-center ${
                                        isSelected
                                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30'
                                            : 'hover:bg-emerald-50 dark:hover:bg-emerald-950/50 text-gray-700 dark:text-gray-300 bg-gray-50/50 dark:bg-gray-800/50'
                                    }`}
                                >
                                    {d}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
