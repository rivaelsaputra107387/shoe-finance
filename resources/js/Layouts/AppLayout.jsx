import React, { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import ConfirmationModal from '@/Components/ConfirmationModal';
import {
    LayoutDashboard,
    FileText,
    PlusCircle,
    Building2,
    BookOpen,
    Sheet,
    TrendingUp,
    Scale,
    PieChart,
    Receipt,
    ListTree,
    Calendar,
    Lock,
    ShieldCheck,
    LogOut,
    Sun,
    Moon,
    User,
    CheckCircle2,
    AlertCircle,
    X,
    Archive,
    ChevronDown,
    Menu,
} from 'lucide-react';

export default function AppLayout({ children, title }) {
    const { auth, flash, url } = usePage().props;
    const [darkMode, setDarkMode] = useState(() => {
        return localStorage.getItem('theme') === 'dark' ||
            (!('theme' in localStorage) && typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    });
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [logoutModalOpen, setLogoutModalOpen] = useState(false);
    const [topbarUserMenuOpen, setTopbarUserMenuOpen] = useState(false);

    useEffect(() => {
        if (darkMode) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    }, [darkMode]);

    const userRoles = auth?.user?.roles || [];
    const isOwnerOrFinance = userRoles.includes('owner') || userRoles.includes('finance');
    const isOwner = userRoles.includes('owner');

    const navigation = [
        {
            group: 'Utama',
            items: [
                { name: 'Dashboard', href: '/app/dashboard', icon: LayoutDashboard, show: true },
            ].filter(i => i.show),
        },
        {
            group: 'Transaksi',
            items: [
                { name: 'Transaksi', href: '/app/bank-mutations', icon: Building2, show: true },
                { name: 'Draft Jurnal', href: '/app/draft-journals', icon: BookOpen, show: true },
                { name: 'Daftar Jurnal', href: '/app/journal-entries', icon: FileText, show: true },
                { name: 'Arsip Transaksi', href: '/app/transaction-archive', icon: Archive, show: true },
            ].filter(i => i.show),
        },
        {
            group: 'Laporan Keuangan',
            items: [
                { name: 'Buku Besar', href: '/app/general-ledger', icon: BookOpen, show: isOwnerOrFinance },
                { name: 'Neraca Lajur', href: '/app/trial-balance', icon: Sheet, show: isOwnerOrFinance },
                { name: 'Laba Rugi', href: '/app/income-statement', icon: TrendingUp, show: isOwnerOrFinance },
                { name: 'Neraca', href: '/app/balance-sheet', icon: Scale, show: isOwnerOrFinance },
                { name: 'Perubahan Ekuitas', href: '/app/equity-statement', icon: PieChart, show: isOwnerOrFinance },
                { name: 'Arus Kas', href: '/app/cash-flow-statement', icon: Receipt, show: isOwnerOrFinance },
            ].filter(i => i.show),
        },
        {
            group: 'Master & Pengaturan',
            items: [
                { name: 'Chart of Accounts', href: '/app/accounts', icon: ListTree, show: isOwnerOrFinance },
                { name: 'Periode Akuntansi', href: '/app/fiscal-periods', icon: Calendar, show: isOwnerOrFinance },
                { name: 'Penutupan Periode', href: '/app/period-closing', icon: Lock, show: isOwnerOrFinance },
                { name: 'Manajemen Akun', href: '/app/users', icon: User, show: isOwnerOrFinance },
                { name: 'Audit Trail', href: '/app/audit-trail', icon: ShieldCheck, show: isOwnerOrFinance },
            ].filter(i => i.show),
        },
    ];

    const currentUrl = usePage().url;

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 font-sans antialiased flex flex-col">
            {/* Flash Notifications */}
            {flash?.success && (
                <div className="fixed top-4 right-4 z-[100] max-w-md flex items-start p-4 text-emerald-800 rounded-xl bg-emerald-50 dark:bg-emerald-950/90 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-xl animate-in slide-in-from-top-2">
                    <CheckCircle2 className="w-5 h-5 mr-3 mt-0.5 flex-shrink-0 text-emerald-600 dark:text-emerald-400" />
                    <div className="text-sm font-medium leading-relaxed">{flash.success}</div>
                </div>
            )}

            {flash?.error && (
                <div className="fixed top-4 right-4 z-[100] max-w-md flex items-start p-4 text-rose-800 rounded-xl bg-rose-50 dark:bg-rose-950/90 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shadow-xl animate-in slide-in-from-top-2">
                    <AlertCircle className="w-5 h-5 mr-3 mt-0.5 flex-shrink-0 text-rose-600 dark:text-rose-400" />
                    <div className="text-sm font-medium leading-relaxed">{flash.error}</div>
                </div>
            )}

            {/* Layout Wrapper */}
            <div className="flex flex-1">
                {/* Mobile Backdrop */}
                {sidebarOpen && (
                    <div
                        onClick={() => setSidebarOpen(false)}
                        className="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden"
                    />
                )}

                {/* Sidebar */}
                <aside className={`
                    fixed lg:sticky lg:top-0 lg:h-screen inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col transition-transform duration-300 ease-in-out
                    ${sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
                `}>
                    {/* Brand / Logo Header */}
                    <div className="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-800 justify-between">
                        <Link href="/app/dashboard" className="flex items-center gap-2.5">
                            <img src="/logo.png" alt="Shoe Workshop Logo" className="h-8 w-auto object-contain" />
                            <div className="flex flex-col">
                                <span className="font-extrabold tracking-tight text-gray-900 dark:text-white leading-none text-sm">
                                    Shoe Workshop
                                </span>
                                <span className="text-[10px] font-medium text-gray-500 dark:text-gray-400 mt-0.5">
                                    SIA Finance
                                </span>
                            </div>
                        </Link>
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="lg:hidden text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>

                    {/* Navigation Menu */}
                    <nav className="flex-1 px-4 py-6 overflow-y-auto space-y-6">
                        {navigation.map((group, groupIdx) => {
                            if (group.items.length === 0) return null;
                            return (
                                <div key={groupIdx}>
                                <h3 className="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                    {group.group}
                                </h3>
                                <div className="space-y-1">
                                    {group.items.map((item) => {
                                        let isActive = currentUrl.startsWith(item.href);
                                        
                                        if (currentUrl.includes('/edit') && usePage().props?.entry?.status === 'draft') {
                                            if (item.name === 'Draft Jurnal') isActive = true;
                                            if (item.name === 'Daftar Jurnal') isActive = false;
                                        }

                                        const Icon = item.icon;
                                        return (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className={`
                                                    flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                                                    ${isActive
                                                        ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 font-semibold shadow-sm'
                                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800/60 hover:text-gray-900 dark:hover:text-white'
                                                    }
                                                `}
                                            >
                                                <Icon className={`w-4 h-4 ${isActive ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'}`} />
                                                <span>{item.name}</span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                    </nav>

                    {/* Sidebar Footer */}
                    <div className="px-4 py-3 border-t border-gray-100 dark:border-gray-800/70">
                        <div className="flex items-center gap-2.5">
                            <img src="/logo.png" alt="Logo" className="w-6 h-6 object-contain opacity-70 flex-shrink-0" />
                            <div className="flex flex-col min-w-0">
                                <span className="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest truncate">
                                    SIA Finance
                                </span>
                                <span className="text-[9px] text-gray-400 dark:text-gray-600 truncate">
                                    © {new Date().getFullYear()} Shoe Workshop · v1.0
                                </span>
                            </div>
                        </div>
                    </div>

                </aside>

                {/* Main Content Area */}
                <div className="flex-1 flex flex-col min-w-0">
                    {/* Top Header Navbar */}
                    <header className="h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-4 lg:px-8">
                        <div className="flex items-center gap-4">
                            <button
                                onClick={() => setSidebarOpen(true)}
                                className="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 lg:hidden"
                            >
                                <Menu className="w-5 h-5" />
                            </button>
                            <h1 className="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                                {title || 'Dashboard'}
                            </h1>
                        </div>

                        {/* Topbar Actions */}
                        <div className="flex items-center gap-3">
                            <button
                                onClick={() => setDarkMode(!darkMode)}
                                title={darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'}
                                className="p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            >
                                {darkMode ? <Sun className="w-5 h-5 text-amber-400" /> : <Moon className="w-5 h-5" />}
                            </button>

                            <div className="relative hidden sm:block">
                                <button 
                                    onClick={() => setTopbarUserMenuOpen(!topbarUserMenuOpen)}
                                    className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-xs font-semibold text-gray-600 dark:text-gray-300 transition-colors group"
                                >
                                    <img src={auth?.user?.profile_photo_url} alt={auth?.user?.name} className="w-5 h-5 rounded-full object-cover" />
                                    <span>{auth?.user?.name}</span>
                                    <ChevronDown className={`w-3.5 h-3.5 text-gray-400 transition-transform duration-200 ${topbarUserMenuOpen ? 'rotate-180' : ''}`} />
                                </button>

                                {topbarUserMenuOpen && (
                                    <>
                                        <div className="fixed inset-0 z-40" onClick={() => setTopbarUserMenuOpen(false)}></div>
                                        <div className="absolute right-0 mt-2 w-48 z-50 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl overflow-hidden py-1 animate-in fade-in zoom-in-95 origin-top-right">
                                            <Link 
                                                href="/app/profile" 
                                                className="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors w-full"
                                                onClick={() => setTopbarUserMenuOpen(false)}
                                            >
                                                <User className="w-4 h-4 text-gray-400" />
                                                <span className="font-medium">Edit Profil</span>
                                            </Link>
                                            <button 
                                                onClick={() => {
                                                    setTopbarUserMenuOpen(false);
                                                    setLogoutModalOpen(true);
                                                }} 
                                                className="flex items-center gap-2 px-4 py-2.5 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors w-full text-left"
                                            >
                                                <LogOut className="w-4 h-4" />
                                                <span className="font-medium">Logout</span>
                                            </button>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </header>

                    {/* Page Content */}
                    <main className="flex-1 p-4 lg:p-8 max-w-7xl w-full mx-auto">
                        {children}
                    </main>
                </div>
            </div>

            {/* Logout Confirmation Modal */}
            <ConfirmationModal
                isOpen={logoutModalOpen}
                title="Konfirmasi Logout"
                message="Apakah Anda yakin ingin keluar dari aplikasi SIA Finance?"
                variant="danger"
                confirmText="Ya, Logout"
                cancelText="Batal"
                onConfirm={() => router.post('/logout')}
                onClose={() => setLogoutModalOpen(false)}
            />
        </div>
    );
}
