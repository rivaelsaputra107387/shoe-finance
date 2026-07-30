import React, { useState } from 'react';
import { useForm, Head, Link } from '@inertiajs/react';
import { Lock, Mail, ArrowRight, Eye, EyeOff } from 'lucide-react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Login - SIA Shoe Workshop" />
            <div className="min-h-screen flex font-sans bg-white">
                {/* Left Branding Side - Solid Green with Glassmorphism Cards */}
                <div className="hidden lg:flex lg:w-1/2 bg-[#1a4a38] p-12 flex-col relative overflow-hidden">
                    {/* Top Left Logo */}
                    <div className="relative z-10 flex items-center gap-3">
                        <img src="/logo.png" alt="Shoe Workshop Logo" className="h-10 w-auto object-contain" />
                        <span className="text-lg font-bold tracking-tight text-white uppercase">
                            SHOEWORKSHOP
                        </span>
                    </div>

                    {/* Center Illustration Area */}
                    <div className="flex-1 flex items-center justify-center relative w-full h-full">
                        {/* Center Circle */}
                        <div className="absolute w-64 h-64 bg-[#143d2e] rounded-full flex flex-col items-center justify-center shadow-2xl z-10 border border-emerald-800/50">
                            <img src="/logo.png" alt="Shoe Workshop Logo" className="h-20 w-auto object-contain" />
                        </div>

                        {/* Floating Card 1 */}
                        <div className="absolute top-[20%] left-[10%] w-64 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 shadow-xl z-20 transform -rotate-2">
                            <div className="flex justify-between items-start mb-2">
                                <span className="text-emerald-400 font-bold text-xs">#JU-001</span>
                                <span className="text-[10px] font-bold text-amber-500 bg-amber-500/10 px-2 py-0.5 rounded-full uppercase tracking-wide">
                                    POSTED
                                </span>
                            </div>
                            <p className="text-white text-sm font-medium leading-snug mb-4">
                                Pencatatan jurnal umum penerimaan kas periode berjalan
                            </p>
                            <div className="text-right text-[10px] text-gray-300">
                                2 jam lalu
                            </div>
                        </div>

                        {/* Floating Card 2 */}
                        <div className="absolute top-[35%] right-[5%] w-64 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 shadow-xl z-20 transform rotate-3">
                            <div className="flex justify-between items-start mb-2">
                                <span className="text-emerald-400 font-bold text-xs">#MB-084</span>
                                <span className="text-[10px] font-bold text-emerald-400 bg-emerald-400/10 px-2 py-0.5 rounded-full uppercase tracking-wide">
                                    SELESAI
                                </span>
                            </div>
                            <p className="text-white text-sm font-medium leading-snug mb-4">
                                Rekonsiliasi mutasi rekening BCA harian bulan ini
                            </p>
                            <div className="text-right text-[10px] text-gray-300">
                                5 jam lalu
                            </div>
                        </div>

                        {/* Floating Card 3 */}
                        <div className="absolute bottom-[20%] left-[15%] w-64 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 shadow-xl z-20 transform -rotate-1">
                            <div className="flex justify-between items-start mb-2">
                                <span className="text-emerald-400 font-bold text-xs">#TB-031</span>
                                <span className="text-[10px] font-bold text-blue-400 bg-blue-400/10 px-2 py-0.5 rounded-full uppercase tracking-wide">
                                    PROSES
                                </span>
                            </div>
                            <p className="text-white text-sm font-medium leading-snug mb-4">
                                Pengecekan saldo buku besar dan neraca lajur
                            </p>
                            <div className="text-right text-[10px] text-gray-300">
                                1 hari lalu
                            </div>
                        </div>
                    </div>

                    {/* Bottom Tagline */}
                    <div className="relative z-10 max-w-lg mt-auto">
                        <h2 className="text-2xl font-bold text-white tracking-tight leading-tight mb-3">
                            Satu tempat untuk setiap <span className="text-amber-500">Transaksi</span> & <span className="text-emerald-400">Laporan</span>.
                        </h2>
                        <p className="text-emerald-100/70 text-sm leading-relaxed">
                            Sistem manajemen keuangan terintegrasi, pelacakan real-time, dan pemantauan arus kas dari awal hingga selesai.
                        </p>
                    </div>
                </div>

                {/* Right Form Side - Light Mode */}
                <div className="w-full lg:w-1/2 flex items-center justify-center p-8 lg:p-16 bg-white">
                    <div className="w-full max-w-md space-y-8">
                        <div>
                            <div className="lg:hidden mb-8">
                                <img src="/logo.png" alt="Shoe Workshop Logo" className="h-12 w-auto object-contain" />
                            </div>
                            <h2 className="text-3xl font-extrabold text-gray-900 tracking-tight">
                                Selamat datang kembali
                            </h2>
                            <p className="mt-3 text-sm text-gray-500 leading-relaxed">
                                Masuk untuk mengelola alur keuangan dan melacak status jurnal akutansi.
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="mt-10 space-y-6">
                            <div className="space-y-5">
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                        Alamat Email
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                            <Mail className="w-5 h-5" />
                                        </div>
                                        <input
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            required
                                            placeholder="email@shoeworkshop.com"
                                            className="w-full pl-11 pr-4 py-3 bg-white border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all shadow-sm"
                                        />
                                    </div>
                                    {errors.email && (
                                        <p className="mt-1.5 text-xs text-rose-500 font-medium">{errors.email}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                        Kata Sandi Aman
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                            <Lock className="w-5 h-5" />
                                        </div>
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            required
                                            placeholder="••••••••"
                                            className="w-full pl-11 pr-12 py-3 bg-white border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all shadow-sm"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                                        >
                                            {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                                        </button>
                                    </div>
                                    {errors.password && (
                                        <p className="mt-1.5 text-xs text-rose-500 font-medium">{errors.password}</p>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-center justify-between pt-1">
                                <label className="flex items-center gap-2 cursor-pointer group">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                    />
                                    <span className="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">Ingat saya</span>
                                </label>
                                <a href="#" className="text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                                    Lupa Kata Sandi?
                                </a>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 transition-all disabled:opacity-50 mt-4"
                            >
                                <span>{processing ? 'Memproses...' : 'Masuk ke Portal'}</span>
                                {!processing && <ArrowRight className="w-4 h-4" />}
                            </button>
                            
                            {/* Divider & Contact Admin */}
                            <div className="pt-6 relative flex flex-col items-center">
                                <div className="absolute inset-0 flex items-center pt-6" aria-hidden="true">
                                    <div className="w-full border-t border-gray-200"></div>
                                </div>
                                <div className="relative flex justify-center">
                                    <span className="px-4 bg-white text-xs text-gray-400">atau</span>
                                </div>
                                <p className="mt-8 text-sm text-gray-500">
                                    Belum punya akun? <a href="#" className="font-semibold text-emerald-600 hover:text-emerald-700">Hubungi Admin</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
