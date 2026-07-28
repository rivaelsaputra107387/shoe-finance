import React from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function Pagination({ links, meta }) {
    if (!links || links.length <= 3) return null;

    const from = meta?.from || 0;
    const to = meta?.to || 0;
    const total = meta?.total || 0;

    return (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 py-3 px-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 text-xs">
            <div className="text-gray-500 dark:text-gray-400">
                Menampilkan <span className="font-semibold text-gray-900 dark:text-white">{from}</span> hingga <span className="font-semibold text-gray-900 dark:text-white">{to}</span> dari <span className="font-semibold text-gray-900 dark:text-white">{total}</span> data
            </div>

            <div className="flex items-center gap-1.5 flex-wrap">
                {links.map((link, key) => {
                    if (link.url === null) {
                        return (
                            <div
                                key={key}
                                className="px-3 py-1.5 rounded-lg text-gray-300 dark:text-gray-600 font-medium select-none pointer-events-none"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        );
                    }

                    const isActive = link.active;

                    return (
                        <Link
                            key={key}
                            href={link.url}
                            preserveState
                            replace
                            className={`px-3 py-1.5 rounded-lg font-medium transition-all ${
                                isActive
                                    ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20'
                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    );
                })}
            </div>
        </div>
    );
}
