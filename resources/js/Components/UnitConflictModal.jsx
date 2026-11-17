import React, { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { UNIT_CONFLICT_EVENT } from '@/Utils/unitContext';

export default function UnitConflictModal() {
    const { unit } = usePage().props;
    const [activeUnit, setActiveUnit] = useState(null);

    useEffect(() => {
        const handleConflict = (event) => setActiveUnit(event.detail.activeUnit);

        window.addEventListener(UNIT_CONFLICT_EVENT, handleConflict);

        return () => window.removeEventListener(UNIT_CONFLICT_EVENT, handleConflict);
    }, []);

    if (!activeUnit) {
        return null;
    }

    const pageUnit = unit?.active;
    const pageUnitId = unit?.available?.find((item) => item.code === pageUnit?.code)?.id;

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 max-w-md w-full shadow-2xl space-y-4">
                <div className="flex items-start gap-3">
                    <div className="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/40">
                        <AlertTriangle className="w-5 h-5 text-amber-600" />
                    </div>
                    <div>
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white">Unit aktif telah berubah</h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Tab ini masih menampilkan <strong>{pageUnit?.name}</strong>, sedangkan unit aktif sekarang <strong>{activeUnit}</strong>. Perubahan belum disimpan.
                        </p>
                    </div>
                </div>
                <div className="flex justify-end gap-3 pt-2">
                    {pageUnitId && (
                        <button
                            type="button"
                            onClick={() => router.post('/app/units/select', { unit_id: pageUnitId })}
                            className="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900"
                        >
                            Kembali ke {pageUnit?.name}
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => window.location.reload()}
                        className="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-md"
                    >
                        Muat Ulang
                    </button>
                </div>
            </div>
        </div>
    );
}
