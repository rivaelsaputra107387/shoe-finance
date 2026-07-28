import React, { useState, useRef, useEffect } from 'react';
import { ChevronDown, Search, Check, X } from 'lucide-react';

export default function AccountSelect({ value, onChange, accounts = [], placeholder = '-- Pilih Akun COA --' }) {
    const [isOpen, setIsOpen] = useState(false);
    const [query, setQuery] = useState('');
    const containerRef = useRef(null);

    const selectedAccount = accounts.find((a) => String(a.id) === String(value));

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const filteredAccounts = query.trim() === ''
        ? accounts
        : accounts.filter((acc) => {
            const q = query.toLowerCase();
            return (
                acc.code.toLowerCase().includes(q) ||
                acc.name.toLowerCase().includes(q) ||
                (acc.type && acc.type.toLowerCase().includes(q))
            );
        });

    const handleSelect = (account) => {
        onChange(account.id);
        setQuery('');
        setIsOpen(false);
    };

    const handleClear = (e) => {
        e.stopPropagation();
        onChange('');
        setQuery('');
    };

    return (
        <div ref={containerRef} className="relative w-full">
            <div
                className={`relative flex items-center w-full bg-white dark:bg-gray-800 border ${
                    isOpen ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-700'
                } rounded-xl shadow-xs transition-all cursor-text`}
                onClick={() => setIsOpen(true)}
            >
                <input
                    type="text"
                    value={isOpen ? query : (selectedAccount ? `${selectedAccount.code} - ${selectedAccount.name}` : '')}
                    onChange={(e) => {
                        setQuery(e.target.value);
                        if (!isOpen) setIsOpen(true);
                    }}
                    onFocus={() => setIsOpen(true)}
                    placeholder={selectedAccount ? `${selectedAccount.code} - ${selectedAccount.name}` : placeholder}
                    className="w-full py-2 pl-3 pr-16 bg-transparent text-xs font-medium text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none"
                />

                <div className="absolute right-2 flex items-center gap-1">
                    {selectedAccount && (
                        <button
                            type="button"
                            onClick={handleClear}
                            className="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md transition-colors"
                        >
                            <X className="w-3.5 h-3.5" />
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={(e) => {
                            e.stopPropagation();
                            setIsOpen(!isOpen);
                        }}
                        className="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md transition-colors"
                    >
                        <ChevronDown className={`w-4 h-4 transition-transform duration-200 ${isOpen ? 'rotate-180 text-indigo-500' : ''}`} />
                    </button>
                </div>
            </div>

            {/* Dropdown Options */}
            {isOpen && (
                <div className="absolute z-50 left-0 right-0 mt-1.5 max-h-60 overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-2xl divide-y divide-gray-100 dark:divide-gray-800/60 animate-in fade-in zoom-in-95 duration-100">
                    {filteredAccounts.length > 0 ? (
                        filteredAccounts.map((acc) => {
                            const isSelected = String(acc.id) === String(value);
                            return (
                                <div
                                    key={acc.id}
                                    onClick={() => handleSelect(acc)}
                                    className={`flex items-center justify-between px-3 py-2.5 text-xs cursor-pointer transition-colors ${
                                        isSelected
                                            ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold'
                                            : 'hover:bg-gray-50 dark:hover:bg-gray-800/60 text-gray-800 dark:text-gray-200'
                                    }`}
                                >
                                    <div className="flex items-center gap-2 truncate">
                                        <span className="font-mono font-bold text-indigo-600 dark:text-indigo-400 min-w-[3.5rem]">
                                            {acc.code}
                                        </span>
                                        <span className="truncate">{acc.name}</span>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <span className="text-[10px] px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-500 font-medium">
                                            {acc.type}
                                        </span>
                                        {isSelected && <Check className="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />}
                                    </div>
                                </div>
                            );
                        })
                    ) : (
                        <div className="px-3 py-4 text-center text-xs text-gray-400">
                            Tidak ada akun COA cocok.
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
