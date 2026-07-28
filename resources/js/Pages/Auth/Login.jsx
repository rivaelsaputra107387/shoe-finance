import React from 'react';
import { useForm, Head } from '@inertiajs/react';
import { Lock, Mail, ArrowRight } from 'lucide-react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Login - SIA Shoe Workshop" />
            <div className="min-h-screen flex bg-gray-900 text-gray-100 font-sans">
                {/* Left Branding Side */}
                <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-emerald-900 via-gray-900 to-slate-950 p-12 flex-col justify-between relative overflow-hidden">
                    <div className="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl" />
                    <div className="absolute -bottom-24 -right-24 w-96 h-96 bg-teal-500/10 rounded-full blur-3xl" />

                    <div className="relative z-10">
                        <div className="flex items-center gap-3">
                            <img src="/logo.png" alt="Shoe Workshop Logo" className="h-12 w-auto object-contain bg-white/90 p-1.5 rounded-xl shadow-lg" />
                            <span className="text-xl font-extrabold tracking-tight text-white">
                                SIA Shoe Workshop
                            </span>
                        </div>
                    </div>

                    <div className="relative z-10 max-w-md">
                        <h2 className="text-4xl font-extrabold text-white tracking-tight leading-tight mb-4">
                            Sistem Informasi Akuntansi & Keuangan
                        </h2>
                        <p className="text-gray-400 text-base leading-relaxed">
                            Kelola pembukuan, jurnal umum, mutasi bank, dan laporan keuangan perusahaan dengan presisi dan transparansi tinggi.
                        </p>
                    </div>

                    <div className="relative z-10 text-xs text-gray-500">
                        © 2026 SIA Shoe Workshop. Hak Cipta Dilindungi Undang-Undang.
                    </div>
                </div>

                {/* Right Form Side */}
                <div className="w-full lg:w-1/2 flex items-center justify-center p-8 bg-gray-950">
                    <div className="w-full max-w-md space-y-8">
                        <div>
                            <div className="lg:hidden mb-4">
                                <img src="/logo.png" alt="Shoe Workshop Logo" className="h-10 w-auto object-contain bg-white p-1 rounded-lg" />
                            </div>
                            <h2 className="text-3xl font-extrabold text-white tracking-tight">
                                Selamat Datang Kembali
                            </h2>
                            <p className="mt-2 text-sm text-gray-400">
                                Masukkan kredensial akun Anda untuk mengakses sistem finance.
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="mt-8 space-y-6">
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                                        Email
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-500">
                                            <Mail className="w-4 h-4" />
                                        </div>
                                        <input
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            required
                                            placeholder="nama@shoeworkshop.id"
                                            className="w-full pl-10 pr-4 py-3 bg-gray-900 border border-gray-800 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                        />
                                    </div>
                                    {errors.email && (
                                        <p className="mt-1 text-xs text-rose-400">{errors.email}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">
                                        Kata Sandi
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-500">
                                            <Lock className="w-4 h-4" />
                                        </div>
                                        <input
                                            type="password"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            required
                                            placeholder="••••••••"
                                            className="w-full pl-10 pr-4 py-3 bg-gray-900 border border-gray-800 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                                        />
                                    </div>
                                    {errors.password && (
                                        <p className="mt-1 text-xs text-rose-400">{errors.password}</p>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-center justify-between">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="w-4 h-4 rounded border-gray-800 bg-gray-900 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <span className="text-xs text-gray-400">Ingat saya</span>
                                </label>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 transition-all disabled:opacity-50"
                            >
                                <span>{processing ? 'Memproses...' : 'Masuk ke Sistem'}</span>
                                {!processing && <ArrowRight className="w-4 h-4" />}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
