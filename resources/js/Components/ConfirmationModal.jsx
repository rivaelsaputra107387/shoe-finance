import React from 'react';
import { AlertCircle, CheckCircle2, HelpCircle, AlertTriangle, X } from 'lucide-react';

export default function ConfirmationModal({
    isOpen,
    title = 'Konfirmasi Tindakan',
    message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    confirmText = 'Ya, Lanjutkan',
    cancelText = 'Batal',
    variant = 'primary', // 'primary' | 'success' | 'danger' | 'warning'
    onConfirm,
    onClose,
    processing = false,
}) {
    if (!isOpen) return null;

    const variantStyles = {
        primary: {
            icon: HelpCircle,
            iconBg: 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
            button: 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20',
        },
        success: {
            icon: CheckCircle2,
            iconBg: 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
            button: 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20',
        },
        danger: {
            icon: AlertTriangle,
            iconBg: 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800',
            button: 'bg-rose-600 hover:bg-rose-500 text-white shadow-rose-600/20',
        },
        warning: {
            icon: AlertCircle,
            iconBg: 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
            button: 'bg-amber-600 hover:bg-amber-500 text-white shadow-amber-600/20',
        },
    };

    const style = variantStyles[variant] || variantStyles.primary;
    const IconComponent = style.icon;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-in fade-in duration-150">
            <div className="bg-white dark:bg-gray-900 rounded-3xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-800 shadow-2xl space-y-6">
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                        <div className={`p-2.5 rounded-2xl ${style.iconBg}`}>
                            <IconComponent className="w-6 h-6" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                {title}
                            </h3>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Konfirmasi sebelum memproses.
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        disabled={processing}
                        className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <div className="p-4 bg-gray-50 dark:bg-gray-800/60 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 text-xs leading-relaxed text-gray-700 dark:text-gray-300">
                    {message}
                </div>

                <div className="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={processing}
                        className="px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    >
                        {cancelText}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                        className={`px-5 py-2.5 rounded-xl text-xs font-semibold shadow-md transition-all disabled:opacity-50 ${style.button}`}
                    >
                        {processing ? 'Memproses...' : confirmText}
                    </button>
                </div>
            </div>
        </div>
    );
}
