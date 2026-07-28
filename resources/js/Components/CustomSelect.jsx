import React from 'react';
import { ChevronDown } from 'lucide-react';

export default function CustomSelect({
    value,
    onChange,
    children,
    className = '',
    icon: IconComponent = null,
    disabled = false,
    ...props
}) {
    return (
        <div className={`relative inline-flex items-center ${className}`}>
            {IconComponent && (
                <IconComponent className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-emerald-500 pointer-events-none z-10" />
            )}
            <select
                value={value}
                onChange={onChange}
                disabled={disabled}
                className={`appearance-none w-full bg-gray-50/90 dark:bg-gray-800/90 border border-gray-200/90 dark:border-gray-700/90 hover:border-emerald-300 dark:hover:border-emerald-700 text-gray-800 dark:text-gray-200 text-xs font-semibold rounded-xl ${
                    IconComponent ? 'pl-9' : 'pl-3.5'
                } pr-9 py-2.5 shadow-2xs focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed`}
                {...props}
            >
                {children}
            </select>
            <ChevronDown className="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 pointer-events-none stroke-[2.2]" />
        </div>
    );
}
