import React from 'react';
import { Link } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Clock } from 'lucide-react';

export default function ConsolidationStatusPanel({ status }) {
    const isFinal = status.label === 'FINAL';

    return (
        <div className={`rounded-2xl border p-4 space-y-3 ${isFinal ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900' : 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900'}`}>
            <div className="flex flex-wrap items-center gap-3">
                <span className={`px-3 py-1 rounded-lg text-sm font-extrabold tracking-wide text-white ${isFinal ? 'bg-emerald-600' : 'bg-amber-600'}`}>{status.label}</span>
                <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                    {status.units.map((unit) => (
                        <span key={unit.code} className="inline-flex items-center gap-1.5">
                            {unit.closed ? <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" /> : <Clock className="w-3.5 h-3.5 text-amber-600" />}
                            {unit.name}: {unit.closed ? 'tutup buku' : 'masih terbuka'}
                            {unit.unposted > 0 && ` (${unit.unposted} belum posted)`}
                        </span>
                    ))}
                    {status.excluded.map((unit) => (
                        <span key={unit.code} className="inline-flex items-center gap-1.5 text-gray-500">
                            <AlertTriangle className="w-3.5 h-3.5" />
                            {unit.name}: {unit.reason}, tidak dihitung
                        </span>
                    ))}
                </div>
            </div>
            {status.differences > 0 && (
                <p className="text-xs font-semibold text-rose-600">
                    {status.differences} pasangan antar unit belum cocok.{' '}
                    <Link href="/app/consolidated/reconciliation" className="underline">Lihat rinciannya</Link>
                </p>
            )}
        </div>
    );
}
