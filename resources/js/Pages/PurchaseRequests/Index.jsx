import React, { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Head, router, Link } from '@inertiajs/react';
import { Search, Eye, CheckCircle, XCircle, ShoppingBag, PackageCheck } from 'lucide-react';
import dayjs from 'dayjs';

export default function Index({ purchaseRequests, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [selectedRequest, setSelectedRequest] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [rejectionReason, setRejectionReason] = useState('');
    const [isUpdating, setIsUpdating] = useState(false);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/app/purchase-requests', { search, status }, { preserveState: true });
    };

    const handleStatusFilter = (newStatus) => {
        setStatus(newStatus);
        router.get('/app/purchase-requests', { search, status: newStatus }, { preserveState: true });
    };

    const handleUpdateStatus = (request, newStatus) => {
        setIsUpdating(true);
        router.post(`/app/purchase-requests/${request.id}/status`, {
            status: newStatus,
            rejection_reason: newStatus === 'REJECTED' ? rejectionReason : null
        }, {
            onSuccess: () => {
                setIsModalOpen(false);
                setRejectionReason('');
                setIsUpdating(false);
            },
            onError: () => setIsUpdating(false)
        });
    };

    const openDetails = (req) => {
        setSelectedRequest(req);
        setIsModalOpen(true);
    };

    const formatCurrency = (val) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val);
    };

    const StatusBadge = ({ status }) => {
        const styles = {
            PENDING: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border-amber-200 dark:border-amber-800',
            APPROVED: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800',
            PURCHASED: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800',
            RECEIVED: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
            REJECTED: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 border-rose-200 dark:border-rose-800',
            CANCELLED: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400 border-gray-200 dark:border-gray-700',
        };

        return (
            <span className={`px-2.5 py-1 text-xs font-semibold rounded-full border ${styles[status]}`}>
                {status}
            </span>
        );
    };

    return (
        <AppLayout title="Pengajuan Belanja (Workshop)">
            <Head title="Pengajuan Belanja" />

            <div className="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col">
                {/* Header & Filters */}
                <div className="p-4 md:p-6 border-b border-gray-200 dark:border-gray-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <ShoppingBag className="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                        <h2 className="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Daftar Pengajuan</h2>
                    </div>

                    <div className="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        <select
                            value={status}
                            onChange={(e) => handleStatusFilter(e.target.value)}
                            className="w-full sm:w-auto bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors"
                        >
                            <option value="all">Semua Status</option>
                            <option value="PENDING">Pending</option>
                            <option value="APPROVED">Approved</option>
                            <option value="PURCHASED">Purchased</option>
                            <option value="RECEIVED">Received</option>
                            <option value="REJECTED">Rejected</option>
                        </select>

                        <form onSubmit={handleSearch} className="relative w-full sm:w-auto">
                            <input
                                type="text"
                                placeholder="Cari No. Req / SPK..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-gray-900 dark:text-white transition-colors placeholder:text-gray-400"
                            />
                            <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        </form>
                    </div>
                </div>

                {/* Table Area */}
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm whitespace-nowrap">
                        <thead className="bg-gray-50/50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th className="px-6 py-4">Nomor Req / SPK</th>
                                <th className="px-6 py-4">Pemohon</th>
                                <th className="px-6 py-4 text-right">Estimasi Biaya</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4">Waktu Masuk</th>
                                <th className="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800/50">
                            {purchaseRequests.data.map((req) => (
                                <tr key={req.id} className="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="font-semibold text-gray-900 dark:text-gray-100">{req.request_number}</div>
                                        <div className="text-xs text-gray-500 mt-0.5">{req.is_batch ? `${req.total_spks} SPK (Batch)` : req.primary_spk_number}</div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="font-medium text-gray-900 dark:text-gray-200">{req.requested_by_name}</div>
                                        <div className="text-xs text-gray-500">{req.requested_by_role}</div>
                                    </td>
                                    <td className="px-6 py-4 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                        {formatCurrency(req.total_estimated_cost)}
                                    </td>
                                    <td className="px-6 py-4">
                                        <StatusBadge status={req.status} />
                                    </td>
                                    <td className="px-6 py-4 text-gray-600 dark:text-gray-400 text-sm">
                                        {dayjs(req.received_at).format('DD MMM YYYY, HH:mm')}
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        <button
                                            onClick={() => openDetails(req)}
                                            className="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-400 rounded-lg transition-colors"
                                        >
                                            <Eye className="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {purchaseRequests.data.length === 0 && (
                                <tr>
                                    <td colSpan="6" className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        Tidak ada data pengajuan belanja ditemukan.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-center">
                    <div className="flex items-center gap-1">
                        {purchaseRequests.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={`px-3 py-1.5 text-sm rounded-lg border transition-colors ${
                                    link.active 
                                    ? 'bg-emerald-600 text-white border-emerald-600 font-medium' 
                                    : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'
                                } ${!link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            </div>

            {/* Details Modal */}
            {isModalOpen && selectedRequest && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
                    <div className="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onClick={() => setIsModalOpen(false)}></div>
                    <div className="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-800 w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col relative z-10 animate-in zoom-in-95 duration-200">
                        
                        {/* Modal Header */}
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/30">
                            <div className="flex items-center gap-3">
                                <div className="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                                    <ShoppingBag className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <div>
                                    <h3 className="font-bold text-gray-900 dark:text-white tracking-tight">Detail Pengajuan: {selectedRequest.request_number}</h3>
                                    <p className="text-xs font-medium text-gray-500 mt-0.5">Tipe: {selectedRequest.type}</p>
                                </div>
                            </div>
                            <button onClick={() => setIsModalOpen(false)} className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                                <XCircle className="w-6 h-6" />
                            </button>
                        </div>

                        {/* Modal Body */}
                        <div className="p-6 overflow-y-auto flex-1 bg-white dark:bg-gray-900 custom-scrollbar">
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div className="space-y-4">
                                    <div>
                                        <label className="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Status Saat Ini</label>
                                        <div className="mt-1.5"><StatusBadge status={selectedRequest.status} /></div>
                                    </div>
                                    <div>
                                        <label className="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Pemohon</label>
                                        <p className="font-semibold text-gray-900 dark:text-gray-200 mt-1">{selectedRequest.requested_by_name} ({selectedRequest.requested_by_role})</p>
                                    </div>
                                    <div>
                                        <label className="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Catatan</label>
                                        <p className="text-sm text-gray-700 dark:text-gray-300 mt-1 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg border border-gray-100 dark:border-gray-800">{selectedRequest.notes || 'Tidak ada catatan.'}</p>
                                    </div>
                                </div>
                                <div className="space-y-4 md:text-right">
                                    <div>
                                        <label className="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Estimasi</label>
                                        <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 tracking-tight">{formatCurrency(selectedRequest.total_estimated_cost)}</p>
                                    </div>
                                    <div>
                                        <label className="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Waktu Pengajuan</label>
                                        <p className="text-sm font-medium text-gray-700 dark:text-gray-300 mt-1">{dayjs(selectedRequest.received_at).format('DD MMMM YYYY, HH:mm')}</p>
                                    </div>
                                    <div>
                                        <label className="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">No. SPK (Utama)</label>
                                        <p className="text-sm font-bold text-gray-900 dark:text-gray-200 mt-1 bg-gray-100 dark:bg-gray-800 inline-block px-2.5 py-1 rounded-md">{selectedRequest.primary_spk_number}</p>
                                    </div>
                                </div>
                            </div>

                            <h4 className="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <PackageCheck className="w-5 h-5 text-gray-400" />
                                Daftar Material yang Diajukan
                            </h4>
                            <div className="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                                <table className="w-full text-left text-sm whitespace-nowrap">
                                    <thead className="bg-gray-50 dark:bg-gray-800/80 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                                        <tr>
                                            <th className="px-4 py-3 font-semibold">Barang / Spesifikasi</th>
                                            <th className="px-4 py-3 font-semibold">Qty</th>
                                            <th className="px-4 py-3 text-right font-semibold">Harga Satuan</th>
                                            <th className="px-4 py-3 text-right font-semibold">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800/50">
                                        {selectedRequest.items.map(item => (
                                            <tr key={item.id} className="hover:bg-gray-50/80 dark:hover:bg-gray-800/30 transition-colors">
                                                <td className="px-4 py-3">
                                                    <div className="font-semibold text-gray-900 dark:text-gray-100">{item.material_name}</div>
                                                    <div className="text-xs text-gray-500 mt-0.5">{item.specification} | SPK: <span className="font-medium text-gray-600 dark:text-gray-400">{item.spk_number}</span></div>
                                                </td>
                                                <td className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">
                                                    <span className="bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded text-xs border border-emerald-100 dark:border-emerald-800/50">
                                                        {item.quantity} {item.unit}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{formatCurrency(item.estimated_price)}</td>
                                                <td className="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{formatCurrency(item.subtotal)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                        </div>

                        {/* Modal Footer (Actions) */}
                        <div className="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-950 flex flex-wrap items-center justify-end gap-3 rounded-b-2xl">
                            {selectedRequest.status === 'PENDING' && (
                                <>
                                    <button 
                                        disabled={isUpdating}
                                        onClick={() => {
                                            const reason = prompt("Masukkan alasan penolakan:");
                                            if (reason) {
                                                setRejectionReason(reason);
                                                handleUpdateStatus(selectedRequest, 'REJECTED');
                                            }
                                        }}
                                        className="px-5 py-2.5 rounded-xl text-sm font-semibold text-rose-600 bg-white dark:bg-gray-900 border border-rose-200 dark:border-rose-800 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-all shadow-sm disabled:opacity-50"
                                    >
                                        Tolak (Reject)
                                    </button>
                                    <button 
                                        disabled={isUpdating}
                                        onClick={() => handleUpdateStatus(selectedRequest, 'APPROVED')}
                                        className="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/20 flex items-center gap-2 disabled:opacity-50"
                                    >
                                        <CheckCircle className="w-4 h-4" />
                                        Setujui & Teruskan
                                    </button>
                                </>
                            )}

                            {selectedRequest.status === 'APPROVED' && (
                                <button 
                                    disabled={isUpdating}
                                    onClick={() => handleUpdateStatus(selectedRequest, 'PURCHASED')}
                                    className="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-sm shadow-indigo-500/20 flex items-center gap-2 disabled:opacity-50"
                                >
                                    <ShoppingBag className="w-4 h-4" />
                                    Tandai Sudah Dibelanjakan
                                </button>
                            )}

                            {selectedRequest.status === 'PURCHASED' && (
                                <button 
                                    disabled={isUpdating}
                                    onClick={() => handleUpdateStatus(selectedRequest, 'RECEIVED')}
                                    className="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all shadow-sm shadow-emerald-500/20 flex items-center gap-2 disabled:opacity-50"
                                >
                                    <PackageCheck className="w-4 h-4" />
                                    Konfirmasi Material Diterima Gudang
                                </button>
                            )}

                            {selectedRequest.status === 'RECEIVED' && (
                                <div className="text-sm text-emerald-700 dark:text-emerald-400 font-semibold flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800/50 px-5 py-2.5 rounded-xl">
                                    <CheckCircle className="w-5 h-5" />
                                    Transaksi Selesai & Material Masuk
                                </div>
                            )}

                            {selectedRequest.status === 'REJECTED' && (
                                <div className="text-sm text-rose-700 dark:text-rose-400 font-semibold flex items-center gap-2 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800/50 px-5 py-2.5 rounded-xl">
                                    <XCircle className="w-5 h-5" />
                                    Ditolak: {selectedRequest.rejection_reason}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
