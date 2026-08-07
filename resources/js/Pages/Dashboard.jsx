import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import {
    Wallet,
    LogOut,
    AlertCircle,
    Calendar,
    FileText,
    Lock,
    LockOpen,
    FileEdit,
    Clock,
    ArrowUpRight,
    TrendingUp,
    PieChart as PieChartIcon,
    BarChart3,
    Sparkles,
} from 'lucide-react';
import CustomSelect from '@/Components/CustomSelect';
import {
    ResponsiveContainer,
    BarChart,
    Bar,
    LineChart,
    Line,
    PieChart,
    Pie,
    Cell,
    Tooltip,
    XAxis,
    YAxis,
    CartesianGrid,
    Legend,
    Label,
} from 'recharts';

export default function Dashboard({
    activePeriod,
    cashBalance,
    charts,
    financialSummary,
    recentJournals,
    staffWidgets,
    userRole,
    periods = [],
    selectedPeriodId,
}) {
    const isOwnerOrFinance = userRole === 'owner' || userRole === 'finance';
    const isStaff = userRole === 'staff';

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    // Color palettes for Recharts
    const COLORS_EXPENSE = [
        '#ef4444', '#f97316', '#f59e0b', '#84cc16', '#22c55e', '#10b981',
        '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6',
    ];

    const COLORS_REVENUE = [
        '#3b82f6', '#0ea5e9', '#06b6d4', '#14b8a6', '#10b981', '#22c55e',
        '#84cc16', '#f59e0b', '#f97316', '#ef4444', '#a855f7', '#6366f1',
    ];

    // Format data for Revenue vs Expense Bar Chart
    const barData = charts?.labels?.map((label, idx) => ({
        name: label,
        Pendapatan: charts.revenue[idx] || 0,
        Beban: charts.expense[idx] || 0,
    })) || [];

    // Format data for Net Profit Line Chart
    const lineData = charts?.labels?.map((label, idx) => ({
        name: label,
        'Laba Bersih': charts.net_profit[idx] || 0,
    })) || [];

    // Format Donut Data (Expense)
    const donutData = charts?.expense_donut?.labels?.map((label, idx) => ({
        name: label,
        value: charts.expense_donut.data[idx] || 0,
    })) || [];

    // Format Pie Data (Revenue)
    const pieData = charts?.revenue_pie?.labels?.map((label, idx) => ({
        name: label,
        value: charts.revenue_pie.data[idx] || 0,
    })) || [];

    return (
        <AppLayout title="Dashboard Overview">
            <Head title="Dashboard - SIA Shoe Workshop" />

            <div className="space-y-8">
                {/* Header Banner */}
                <div className="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-600 via-emerald-700 to-teal-800 p-6 md:p-8 text-white shadow-xl">
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-2">
                            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-xs font-semibold tracking-wide">
                                <Sparkles className="w-3.5 h-3.5 text-amber-300" />
                                <span>Sistem Informasi Akuntansi Shoe Workshop</span>
                            </div>
                            <h2 className="text-2xl md:text-3xl font-extrabold tracking-tight">
                                Ringkasan Keuangan Operasional
                            </h2>
                            <p className="text-sm text-emerald-100 max-w-xl">
                                Pantau arus kas, status periode akuntansi, dan analisis statistik pendapatan & beban secara real-time.
                            </p>
                        </div>

                        {/* Quick Action Buttons */}
                        <div className="flex flex-wrap items-center gap-3">
                            <CustomSelect
                                className="w-auto"
                                value={selectedPeriodId || ''}
                                onChange={(e) => {
                                    router.get('/app/dashboard', { period_id: e.target.value }, { preserveState: true });
                                }}
                            >
                                <option value="">Periode Aktif (Default)</option>
                                {periods.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name} {p.status === 'open' ? '(Aktif)' : ''}
                                    </option>
                                ))}
                            </CustomSelect>

                            <Link
                                href="/app/journal-entries/create"
                                className="px-4 py-2.5 bg-white text-emerald-800 hover:bg-emerald-50 font-bold text-xs rounded-xl shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center gap-2"
                            >
                                <FileText className="w-4 h-4" />
                                <span>+ Input Jurnal Baru</span>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Top Summary Cards (Periode & Stats) */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    {/* Active Period Card */}
                    <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm relative overflow-hidden">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Periode Aktif
                            </span>
                            <div className={`p-2.5 rounded-xl ${activePeriod?.is_open ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600' : 'bg-rose-50 text-rose-600'}`}>
                                {activePeriod?.is_open ? <LockOpen className="w-5 h-5" /> : <Lock className="w-5 h-5" />}
                            </div>
                        </div>
                        <div className="mt-3">
                            <h3 className="text-xl font-bold text-gray-900 dark:text-white">
                                {activePeriod?.period_name}
                            </h3>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {activePeriod?.start_date} - {activePeriod?.end_date}
                            </p>
                        </div>
                        <div className="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-between text-xs">
                            <span className="text-gray-500">Total Transaksi:</span>
                            <span className="font-bold text-gray-900 dark:text-white">{activePeriod?.journal_count} entri</span>
                        </div>
                    </div>

                    {/* Owner/Finance Stats */}
                    {isOwnerOrFinance && cashBalance && (
                        <>
                            {/* Kas & Bank */}
                            <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Total Kas & Bank
                                    </span>
                                    <div className="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600">
                                        <Wallet className="w-5 h-5" />
                                    </div>
                                </div>
                                <div className="mt-3">
                                    <h3 className="text-xl font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {formatRupiah(cashBalance.total_kas_bank)}
                                    </h3>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Saldo Tunai + Rekening Bank
                                    </p>
                                </div>
                            </div>

                            {/* Piutang Usaha */}
                            <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Total Piutang Usaha
                                    </span>
                                    <div className="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/50 text-sky-600">
                                        <ArrowUpRight className="w-5 h-5" />
                                    </div>
                                </div>
                                <div className="mt-3">
                                    <h3 className="text-xl font-mono font-bold text-sky-600 dark:text-sky-400">
                                        {formatRupiah(cashBalance.total_piutang)}
                                    </h3>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Uang Masuk Tertunda
                                    </p>
                                </div>
                            </div>

                            {/* Hutang Lancar */}
                            <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Total Hutang
                                    </span>
                                    <div className="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600">
                                        <AlertCircle className="w-5 h-5" />
                                    </div>
                                </div>
                                <div className="mt-3">
                                    <h3 className="text-xl font-mono font-bold text-rose-600 dark:text-rose-400">
                                        {formatRupiah(cashBalance.total_hutang)}
                                    </h3>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Kewajiban Jangka Pendek
                                    </p>
                                </div>
                            </div>
                        </>
                    )}

                    {/* Staff Todos */}
                    {isStaff && staffWidgets && (
                        <>
                            <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-gray-500 uppercase tracking-wider">Mutasi Bank Baru</span>
                                    <div className="p-2.5 rounded-xl bg-amber-50 text-amber-600"><Clock className="w-5 h-5" /></div>
                                </div>
                                <div className="mt-3">
                                    <h3 className="text-2xl font-bold text-gray-900 dark:text-white">{staffWidgets.pending_mutations}</h3>
                                    <p className="text-xs text-gray-500 mt-1">Memerlukan pembuatan draft jurnal</p>
                                </div>
                            </div>

                            <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-gray-500 uppercase tracking-wider">Draft Jurnal Saya</span>
                                    <div className="p-2.5 rounded-xl bg-indigo-50 text-indigo-600"><FileEdit className="w-5 h-5" /></div>
                                </div>
                                <div className="mt-3">
                                    <h3 className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{staffWidgets.draft_journals}</h3>
                                    <p className="text-xs text-gray-500 mt-1">Belum di-submit untuk approval</p>
                                </div>
                            </div>

                            <div className="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-gray-500 uppercase tracking-wider">Menunggu Approval</span>
                                    <div className="p-2.5 rounded-xl bg-sky-50 text-sky-600"><Clock className="w-5 h-5" /></div>
                                </div>
                                <div className="mt-3">
                                    <h3 className="text-2xl font-bold text-sky-600 dark:text-sky-400">{staffWidgets.unapproved_journals}</h3>
                                    <p className="text-xs text-gray-500 mt-1">Sedang direview Finance Manager</p>
                                </div>
                            </div>
                        </>
                    )}
                </div>

                {/* Analytics Charts Grid (Owner & Finance) */}
                {isOwnerOrFinance && charts && (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* 1. Bar Chart: Revenue vs Expense */}
                        <div className="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <BarChart3 className="w-4 h-4 text-indigo-500" />
                                        Pendapatan vs Beban Operasional
                                    </h3>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Perbandingan total pendapatan dan total beban selama 6 periode terakhir.
                                    </p>
                                </div>
                            </div>
                            <div className="h-72 w-full pt-4">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={barData} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" opacity={0.15} />
                                        <XAxis dataKey="name" stroke="#888888" fontSize={11} tickLine={false} />
                                        <YAxis
                                            stroke="#888888"
                                            fontSize={11}
                                            tickLine={false}
                                            tickFormatter={(val) => `Rp ${(val / 1000000).toFixed(0)}M`}
                                        />
                                        <Tooltip
                                            formatter={(value) => [formatRupiah(value), '']}
                                            contentStyle={{ backgroundColor: '#1f2937', borderRadius: '12px', border: 'none', color: '#fff', fontSize: '12px' }}
                                        />
                                        <Legend wrapperStyle={{ fontSize: '12px', paddingTop: '10px' }} />
                                        <Bar dataKey="Pendapatan" fill="#3b82f6" radius={[6, 6, 0, 0]} />
                                        <Bar dataKey="Beban" fill="#ef4444" radius={[6, 6, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* 2. Line Chart: Net Profit Trend */}
                        <div className="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <TrendingUp className="w-4 h-4 text-emerald-500" />
                                        Tren Laba Bersih (Net Profit)
                                    </h3>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Perkembangan laba bersih selama 6 periode akuntansi.
                                    </p>
                                </div>
                            </div>
                            <div className="h-72 w-full pt-4">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={lineData} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" opacity={0.15} />
                                        <XAxis dataKey="name" stroke="#888888" fontSize={11} tickLine={false} />
                                        <YAxis
                                            stroke="#888888"
                                            fontSize={11}
                                            tickLine={false}
                                            tickFormatter={(val) => `Rp ${(val / 1000000).toFixed(0)}M`}
                                        />
                                        <Tooltip
                                            formatter={(value) => [formatRupiah(value), 'Laba Bersih']}
                                            contentStyle={{ backgroundColor: '#1f2937', borderRadius: '12px', border: 'none', color: '#fff', fontSize: '12px' }}
                                        />
                                        <Line type="monotone" dataKey="Laba Bersih" stroke="#10b981" strokeWidth={3} dot={{ r: 4 }} />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* 3. Donut Chart: Expense Composition */}
                        <div className="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                            <div>
                                <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <PieChartIcon className="w-4 h-4 text-rose-500" />
                                    Komposisi Beban ({activePeriod?.period_name})
                                </h3>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Rincian persentase pengeluaran berdasarkan akun beban.
                                </p>
                            </div>
                            <div className="h-64 w-full pt-2">
                                {donutData.length > 0 ? (
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={donutData}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={60}
                                                outerRadius={85}
                                                paddingAngle={3}
                                                dataKey="value"
                                                labelLine={false}
                                                label={({ cx, cy, midAngle, innerRadius, outerRadius, percent }) => {
                                                    if (percent < 0.05) return null;
                                                    const RADIAN = Math.PI / 180;
                                                    const radius = innerRadius + (outerRadius - innerRadius) * 0.55;
                                                    const x = cx + radius * Math.cos(-midAngle * RADIAN);
                                                    const y = cy + radius * Math.sin(-midAngle * RADIAN);
                                                    return (
                                                        <text x={x} y={y} fill="white" textAnchor="middle" dominantBaseline="central" fontSize={10} fontWeight="bold">
                                                            {`${(percent * 100).toFixed(0)}%`}
                                                        </text>
                                                    );
                                                }}
                                            >
                                                {donutData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS_EXPENSE[index % COLORS_EXPENSE.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip
                                                formatter={(value) => [formatRupiah(value), 'Total']}
                                                contentStyle={{ backgroundColor: '#1f2937', borderRadius: '12px', border: 'none', color: '#fff', fontSize: '12px' }}
                                            />
                                            <Legend wrapperStyle={{ fontSize: '11px', paddingTop: '10px' }} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="h-full flex items-center justify-center text-xs text-gray-400">
                                        Belum ada data beban pada periode ini.
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* 4. Pie Chart: Revenue Composition */}
                        <div className="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-4">
                            <div>
                                <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <PieChartIcon className="w-4 h-4 text-sky-500" />
                                    Komposisi Pendapatan ({activePeriod?.period_name})
                                </h3>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Rincian pendapatan berdasarkan jenis jasa & layanan.
                                </p>
                            </div>
                            <div className="h-64 w-full pt-2">
                                {pieData.length > 0 ? (
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={pieData}
                                                cx="50%"
                                                cy="50%"
                                                outerRadius={85}
                                                paddingAngle={3}
                                                dataKey="value"
                                                labelLine={false}
                                                label={({ cx, cy, midAngle, innerRadius, outerRadius, percent }) => {
                                                    if (percent < 0.05) return null;
                                                    const RADIAN = Math.PI / 180;
                                                    const radius = innerRadius + (outerRadius - innerRadius) * 0.55;
                                                    const x = cx + radius * Math.cos(-midAngle * RADIAN);
                                                    const y = cy + radius * Math.sin(-midAngle * RADIAN);
                                                    return (
                                                        <text x={x} y={y} fill="white" textAnchor="middle" dominantBaseline="central" fontSize={10} fontWeight="bold">
                                                            {`${(percent * 100).toFixed(0)}%`}
                                                        </text>
                                                    );
                                                }}
                                            >
                                                {pieData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS_REVENUE[index % COLORS_REVENUE.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip
                                                formatter={(value) => [formatRupiah(value), 'Total']}
                                                contentStyle={{ backgroundColor: '#1f2937', borderRadius: '12px', border: 'none', color: '#fff', fontSize: '12px' }}
                                            />
                                            <Legend wrapperStyle={{ fontSize: '11px', paddingTop: '10px' }} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="h-full flex items-center justify-center text-xs text-gray-400">
                                        Belum ada data pendapatan pada periode ini.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* ─── RINGKASAN LAPORAN KEUANGAN ─── */}
                {financialSummary && (
                    <div className="space-y-4">
                        <div className="flex items-center gap-3">
                            <div className="h-px flex-1 bg-gray-200 dark:bg-gray-800" />
                            <span className="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest whitespace-nowrap">Ringkasan Laporan Keuangan</span>
                            <div className="h-px flex-1 bg-gray-200 dark:bg-gray-800" />
                        </div>

                        {/* Row 1: Laba Rugi + Neraca */}
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">

                            {/* LABA RUGI */}
                            <div className="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between mb-5">
                                    <div>
                                        <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                            <span className="w-2 h-2 rounded-full bg-emerald-500 inline-block" />
                                            Laba Rugi
                                        </h3>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{activePeriod?.period_name}</p>
                                    </div>
                                    <a href="/app/income-statement" className="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Detail →</a>
                                </div>
                                <div className="space-y-3">
                                    <div className="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800">
                                        <span className="text-xs text-gray-500">Total Pendapatan</span>
                                        <span className="text-sm font-bold text-emerald-600">{formatRupiah(financialSummary.incomeStatement.total_revenue)}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800">
                                        <span className="text-xs text-gray-500">Total Beban</span>
                                        <span className="text-sm font-bold text-rose-600">{formatRupiah(financialSummary.incomeStatement.total_expense)}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800">
                                        <span className="text-xs text-gray-500">Laba Kotor</span>
                                        <span className={`text-sm font-bold ${financialSummary.incomeStatement.gross_profit >= 0 ? 'text-sky-600' : 'text-rose-600'}`}>
                                            {formatRupiah(financialSummary.incomeStatement.gross_profit)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center pt-2">
                                        <span className="text-sm font-bold text-gray-700 dark:text-gray-200">Laba Bersih</span>
                                        <span className={`text-lg font-extrabold ${
                                            financialSummary.incomeStatement.net_profit >= 0
                                                ? 'text-emerald-600'
                                                : 'text-rose-600'
                                        }`}>
                                            {formatRupiah(financialSummary.incomeStatement.net_profit)}
                                        </span>
                                    </div>
                                </div>
                                {/* Profit margin bar */}
                                {financialSummary.incomeStatement.total_revenue > 0 && (
                                    <div className="mt-4">
                                        <div className="flex justify-between text-[10px] text-gray-400 mb-1">
                                            <span>Margin Laba Bersih</span>
                                            <span className="font-bold">{((financialSummary.incomeStatement.net_profit / financialSummary.incomeStatement.total_revenue) * 100).toFixed(1)}%</span>
                                        </div>
                                        <div className="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5">
                                            <div
                                                className={`h-1.5 rounded-full transition-all ${
                                                    financialSummary.incomeStatement.net_profit >= 0 ? 'bg-emerald-500' : 'bg-rose-500'
                                                }`}
                                                style={{ width: `${Math.min(Math.abs((financialSummary.incomeStatement.net_profit / financialSummary.incomeStatement.total_revenue) * 100), 100)}%` }}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* NERACA */}
                            <div className="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between mb-5">
                                    <div>
                                        <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                            <span className="w-2 h-2 rounded-full bg-blue-500 inline-block" />
                                            Neraca
                                        </h3>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{activePeriod?.period_name}</p>
                                    </div>
                                    <a href="/app/balance-sheet" className="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Detail →</a>
                                </div>
                                <div className="space-y-3 mb-4">
                                    <div className="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800">
                                        <span className="text-xs text-gray-500">Total Aset</span>
                                        <span className="text-sm font-bold text-blue-600">{formatRupiah(financialSummary.balanceSheet.total_assets)}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800">
                                        <span className="text-xs text-gray-500">Total Kewajiban</span>
                                        <span className="text-sm font-bold text-rose-600">{formatRupiah(financialSummary.balanceSheet.total_liabilities)}</span>
                                    </div>
                                    <div className="flex justify-between items-center py-2">
                                        <span className="text-xs text-gray-500">Total Ekuitas</span>
                                        <span className="text-sm font-bold text-violet-600">{formatRupiah(financialSummary.balanceSheet.total_equity)}</span>
                                    </div>
                                </div>
                                {/* Aset breakdown bar */}
                                {financialSummary.balanceSheet.total_assets > 0 && (
                                    <div className="mt-2 space-y-1.5">
                                        <div className="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mb-2">Komposisi Kewajiban + Ekuitas</div>
                                        <div className="w-full h-3 rounded-full overflow-hidden flex">
                                            <div
                                                className="bg-rose-400 h-full transition-all"
                                                style={{ width: `${(financialSummary.balanceSheet.total_liabilities / financialSummary.balanceSheet.total_assets * 100).toFixed(1)}%` }}
                                                title={`Kewajiban: ${((financialSummary.balanceSheet.total_liabilities / financialSummary.balanceSheet.total_assets) * 100).toFixed(1)}%`}
                                            />
                                            <div
                                                className="bg-violet-400 h-full flex-1 transition-all"
                                                title={`Ekuitas: ${((financialSummary.balanceSheet.total_equity / financialSummary.balanceSheet.total_assets) * 100).toFixed(1)}%`}
                                            />
                                        </div>
                                        <div className="flex justify-between text-[10px] text-gray-400">
                                            <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-full bg-rose-400 inline-block" />Kewajiban {((financialSummary.balanceSheet.total_liabilities / financialSummary.balanceSheet.total_assets) * 100).toFixed(0)}%</span>
                                            <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-full bg-violet-400 inline-block" />Ekuitas {((financialSummary.balanceSheet.total_equity / financialSummary.balanceSheet.total_assets) * 100).toFixed(0)}%</span>
                                        </div>
                                    </div>
                                )}
                                <div className={`mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold ${
                                    financialSummary.balanceSheet.is_balanced
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400'
                                }`}>
                                    {financialSummary.balanceSheet.is_balanced ? '✓ Neraca Seimbang' : '⚠ Neraca Tidak Seimbang'}
                                </div>
                            </div>
                        </div>

                        {/* Row 2: Arus Kas + Ekuitas + Neraca Lajur */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">

                            {/* ARUS KAS */}
                            <div className="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <span className="w-2 h-2 rounded-full bg-cyan-500 inline-block" />
                                        Arus Kas
                                    </h3>
                                    <a href="/app/cash-flow-statement" className="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Detail →</a>
                                </div>
                                <div className="space-y-2.5">
                                    {[
                                        { label: 'Operasi', value: financialSummary.cashFlow.total_operating, color: 'emerald' },
                                        { label: 'Investasi', value: financialSummary.cashFlow.total_investing, color: 'blue' },
                                        { label: 'Pendanaan', value: financialSummary.cashFlow.total_financing, color: 'violet' },
                                    ].map(({ label, value, color }) => (
                                        <div key={label} className="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800">
                                            <span className="text-xs text-gray-500 flex items-center gap-1.5">
                                                <span className={`w-1.5 h-1.5 rounded-full bg-${color}-500 inline-block`} />
                                                {label}
                                            </span>
                                            <span className={`text-xs font-bold ${value >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                {value >= 0 ? '+' : ''}{formatRupiah(value)}
                                            </span>
                                        </div>
                                    ))}
                                    <div className="flex justify-between items-center pt-2">
                                        <span className="text-xs font-bold text-gray-700 dark:text-gray-200">Kas Akhir</span>
                                        <span className="text-sm font-extrabold text-cyan-600">{formatRupiah(financialSummary.cashFlow.ending_cash)}</span>
                                    </div>
                                </div>
                            </div>

                            {/* PERUBAHAN EKUITAS */}
                            <div className="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <span className="w-2 h-2 rounded-full bg-violet-500 inline-block" />
                                        Perubahan Ekuitas
                                    </h3>
                                    <a href="/app/equity-statement" className="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Detail →</a>
                                </div>
                                <div className="space-y-2">
                                    <div className="flex justify-between text-xs py-1.5 border-b border-gray-100 dark:border-gray-800">
                                        <span className="text-gray-500">Modal Awal</span>
                                        <span className="font-semibold text-gray-700 dark:text-gray-300">{formatRupiah(financialSummary.equity.beginning_capital)}</span>
                                    </div>
                                    <div className="flex justify-between text-xs py-1.5 border-b border-gray-100 dark:border-gray-800">
                                        <span className="text-gray-500">+ Laba Bersih</span>
                                        <span className={`font-semibold ${financialSummary.equity.net_profit >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                            {financialSummary.equity.net_profit >= 0 ? '+' : ''}{formatRupiah(financialSummary.equity.net_profit)}
                                        </span>
                                    </div>
                                    {financialSummary.equity.prive > 0 && (
                                        <div className="flex justify-between text-xs py-1.5 border-b border-gray-100 dark:border-gray-800">
                                            <span className="text-gray-500">- Prive</span>
                                            <span className="font-semibold text-rose-600">−{formatRupiah(financialSummary.equity.prive)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between text-xs pt-2">
                                        <span className="text-sm font-bold text-gray-700 dark:text-gray-200">Modal Akhir</span>
                                        <span className="text-sm font-extrabold text-violet-600">{formatRupiah(financialSummary.equity.ending_capital)}</span>
                                    </div>
                                </div>
                            </div>

                            {/* NERACA LAJUR */}
                            <div className="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <span className="w-2 h-2 rounded-full bg-amber-500 inline-block" />
                                        Neraca Lajur
                                    </h3>
                                    <a href="/app/trial-balance" className="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Detail →</a>
                                </div>
                                <div className="flex flex-col items-center justify-center py-3 gap-3">
                                    <div className={`w-20 h-20 rounded-full flex items-center justify-center text-2xl font-black border-4 ${
                                        financialSummary.trialBalance.is_balanced
                                            ? 'border-emerald-400 bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40'
                                            : 'border-rose-400 bg-rose-50 text-rose-600 dark:bg-rose-950/40'
                                    }`}>
                                        {financialSummary.trialBalance.is_balanced ? '✓' : '!'}
                                    </div>
                                    <p className={`text-sm font-bold ${
                                        financialSummary.trialBalance.is_balanced ? 'text-emerald-600' : 'text-rose-600'
                                    }`}>
                                        {financialSummary.trialBalance.is_balanced ? 'Neraca Seimbang' : 'Tidak Seimbang'}
                                    </p>
                                </div>
                                <div className="space-y-2 mt-1">
                                    <div className="flex justify-between text-xs py-1.5 border-b border-gray-100 dark:border-gray-800">
                                        <span className="text-gray-500">Total Debit</span>
                                        <span className="font-bold text-gray-800 dark:text-gray-200">{formatRupiah(financialSummary.trialBalance.total_debit)}</span>
                                    </div>
                                    <div className="flex justify-between text-xs py-1.5">
                                        <span className="text-gray-500">Total Kredit</span>
                                        <span className="font-bold text-gray-800 dark:text-gray-200">{formatRupiah(financialSummary.trialBalance.total_credit)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
                <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden space-y-4">
                    <div className="p-6 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                Jurnal Transaksi Terbaru
                            </h3>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                5 transaksi ter-posting paling akhir dalam sistem.
                            </p>
                        </div>
                        <Link
                            href="/app/journal-entries"
                            className="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
                        >
                            <span>Lihat Semua</span>
                            <ArrowUpRight className="w-3.5 h-3.5" />
                        </Link>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th className="py-3 px-6">Tanggal</th>
                                    <th className="py-3 px-6">Referensi</th>
                                    <th className="py-3 px-6">Keterangan</th>
                                    <th className="py-3 px-6 text-right">Total Debit</th>
                                    <th className="py-3 px-6 text-center">Tipe</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800 text-sm font-mono">
                                {recentJournals?.length > 0 ? (
                                    recentJournals.map((row) => (
                                        <tr key={row.id} className="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                                            <td className="py-3.5 px-6 text-xs text-gray-600 dark:text-gray-400">{row.entry_date}</td>
                                            <td className="py-3.5 px-6 text-xs font-semibold text-gray-900 dark:text-white">{row.reference || '-'}</td>
                                            <td className="py-3.5 px-6 text-xs font-sans text-gray-700 dark:text-gray-300 max-w-xs truncate">{row.description}</td>
                                            <td className="py-3.5 px-6 text-right text-xs font-bold text-gray-900 dark:text-white">{formatRupiah(row.total_debit)}</td>
                                            <td className="py-3.5 px-6 text-center">
                                                {row.is_closing ? (
                                                    <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                        Penutup
                                                    </span>
                                                ) : (
                                                    <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                        Umum
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="5" className="py-8 text-center text-gray-500 font-sans text-xs">
                                            Belum ada data jurnal ter-posting.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
