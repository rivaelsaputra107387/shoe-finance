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
    ChevronDown,
    Menu,
} from 'lucide-react';

export default function AppLayout({ children, title }) {
    const { auth, flash, url } = usePage().props;
    const [darkMode, setDarkMode] = useState(() => {
        return localStorage.getItem('theme') === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    });
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [logoutModalOpen, setLogoutModalOpen] = useState(false);

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
                { name: 'Transaksi', href: '/app/bank-mutations', icon: Building2, show: isOwnerOrFinance },
                { name: 'Draft Jurnal', href: '/app/draft-journals', icon: BookOpen, show: true },
                { name: 'Daftar Jurnal', href: '/app/journal-entries', icon: FileText, show: true },
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
                { name: 'Penutupan Periode', href: '/app/period-closing', icon: Lock, show: isOwner },
                { name: 'Audit Trail', href: '/app/audit-trail', icon: ShieldCheck, show: isOwner },
            ].filter(i => i.show),
        },
    ];

    const currentUrl = usePage().url;

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 font-sans antialiased flex flex-col">
            {/* Flash Notifications */}
            {flash?.success && (
                <div className="fixed top-4 right-4 z-50 flex items-center p-4 mb-4 text-emerald-800 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-lg animate-in slide-in-from-top-2">
                    <CheckCircle2 className="w-5 h-5 mr-3 flex-shrink-0 text-emerald-600 dark:text-emerald-400" />
                    <div className="text-sm font-medium">{flash.success}</div>
                </div>
            )}

            {flash?.error && (
                <div className="fixed top-4 right-4 z-50 flex items-center p-4 mb-4 text-rose-800 rounded-xl bg-rose-50 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shadow-lg animate-in slide-in-from-top-2">
                    <AlertCircle className="w-5 h-5 mr-3 flex-shrink-0 text-rose-600 dark:text-rose-400" />
                    <div className="text-sm font-medium">{flash.error}</div>
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
                        {navigation.map((group, groupIdx) => (
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
                        ))}
                    </nav>

                    {/* Sidebar Footer User Card */}
                    <div className="p-4 border-t border-gray-200 dark:border-gray-800">
                        <div className="flex items-center justify-between p-2 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200/60 dark:border-gray-700/50">
                            <div className="flex items-center gap-3 overflow-hidden">
                                <div className="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                                    {auth?.user?.name ? auth.user.name.charAt(0).toUpperCase() : 'U'}
                                </div>
                                <div className="flex flex-col truncate">
                                    <span className="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                        {auth?.user?.name}
                                    </span>
                                    <span className="text-[10px] text-gray-500 dark:text-gray-400 capitalize truncate">
                                        {userRoles.join(', ') || 'User'}
                                    </span>
                                </div>
                            </div>
                            <button
                                onClick={() => setLogoutModalOpen(true)}
                                title="Logout"
                                className="p-1.5 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors"
                            >
                                <LogOut className="w-4 h-4" />
                            </button>
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

                            <div className="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <User className="w-3.5 h-3.5" />
                                <span>{auth?.user?.name}</span>
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
