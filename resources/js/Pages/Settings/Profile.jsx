import React, { useState, useRef } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Camera, Save, KeyRound, User, Mail, Phone, X, Check } from 'lucide-react';
import Cropper from 'react-easy-crop';
import getCroppedImg from '@/Utils/cropImage';
import { formatPhone } from '@/Utils/format';

export default function Profile() {
    const { auth } = usePage().props;
    const user = auth.user;
    const photoInputRef = useRef(null);
    const [photoPreview, setPhotoPreview] = useState(user.profile_photo_url);

    const profileForm = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone || '',
        photo: null,
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const [cropModalOpen, setCropModalOpen] = useState(false);
    const [imageSrc, setImageSrc] = useState(null);
    const [crop, setCrop] = useState({ x: 0, y: 0 });
    const [zoom, setZoom] = useState(1);
    const [croppedAreaPixels, setCroppedAreaPixels] = useState(null);

    const handlePhotoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                setImageSrc(reader.result?.toString() || '');
                setCropModalOpen(true);
            });
            reader.readAsDataURL(file);
        }
    };

    const onCropComplete = (croppedArea, croppedAreaPixels) => {
        setCroppedAreaPixels(croppedAreaPixels);
    };

    const handleCropConfirm = async () => {
        try {
            const croppedImageBlob = await getCroppedImg(imageSrc, croppedAreaPixels, 0);
            profileForm.setData('photo', croppedImageBlob);
            setPhotoPreview(URL.createObjectURL(croppedImageBlob));
            setCropModalOpen(false);
            if (photoInputRef.current) photoInputRef.current.value = '';
        } catch (e) {
            console.error(e);
        }
    };

    const submitProfile = (e) => {
        e.preventDefault();
        profileForm.post('/app/profile', {
            preserveScroll: true,
            onSuccess: () => {
                profileForm.clearErrors();
                if (photoInputRef.current) photoInputRef.current.value = '';
            },
        });
    };

    const submitPassword = (e) => {
        e.preventDefault();
        passwordForm.put('/app/profile/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    return (
        <AppLayout title="Edit Profil">
            <Head title="Edit Profil - SIA Shoe Workshop" />

            <div className="max-w-7xl mx-auto space-y-8">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Edit Profil
                    </h2>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Perbarui informasi profil dan kata sandi Anda.
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    {/* Bagian Profil */}
                    <div className="space-y-4">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Informasi Profil</h3>
                            <p className="text-xs text-gray-500 mt-1">
                                Perbarui nama, email, dan foto profil Anda.
                            </p>
                        </div>

                        <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
                            <form onSubmit={submitProfile} className="space-y-6">
                                <div className="flex items-center gap-6">
                                    <div className="relative group">
                                        <div className="w-24 h-24 rounded-full overflow-hidden border-4 border-gray-50 dark:border-gray-800 shadow-md">
                                            <img src={photoPreview} alt="Profile" className="w-full h-full object-cover" />
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => photoInputRef.current.click()}
                                            className="absolute inset-0 bg-black/40 flex items-center justify-center rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            <Camera className="w-6 h-6 text-white" />
                                        </button>
                                        <input
                                            type="file"
                                            ref={photoInputRef}
                                            onChange={handlePhotoChange}
                                            className="hidden"
                                            accept="image/*"
                                        />
                                    </div>
                                    <div className="text-sm">
                                        <button
                                            type="button"
                                            onClick={() => photoInputRef.current.click()}
                                            className="font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500"
                                        >
                                            Ubah Foto
                                        </button>
                                        <p className="text-xs text-gray-500 mt-1">JPG, GIF atau PNG. Maks 2MB.</p>
                                        {profileForm.errors.photo && (
                                            <p className="text-rose-500 text-xs mt-1">{profileForm.errors.photo}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Nama Lengkap
                                        </label>
                                        <div className="relative">
                                            <User className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                                            <input
                                                type="text"
                                                required
                                                value={profileForm.data.name}
                                                onChange={e => profileForm.setData('name', e.target.value)}
                                                className="w-full pl-10 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                            />
                                        </div>
                                        {profileForm.errors.name && <p className="text-rose-500 text-xs mt-1">{profileForm.errors.name}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Email
                                        </label>
                                        <div className="relative">
                                            <Mail className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                                            <input
                                                type="email"
                                                required
                                                value={profileForm.data.email}
                                                onChange={e => profileForm.setData('email', e.target.value)}
                                                className="w-full pl-10 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                            />
                                        </div>
                                        {profileForm.errors.email && <p className="text-rose-500 text-xs mt-1">{profileForm.errors.email}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Nomor Handphone
                                        </label>
                                        <div className="relative">
                                            <Phone className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                                            <input
                                                type="text"
                                                value={profileForm.data.phone}
                                                onChange={e => profileForm.setData('phone', formatPhone(e.target.value))}
                                                placeholder="+62-8xx-xxxx-xxxx"
                                                className="w-full pl-10 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                            />
                                        </div>
                                        {profileForm.errors.phone && <p className="text-rose-500 text-xs mt-1">{profileForm.errors.phone}</p>}
                                    </div>
                                </div>

                                <div className="flex items-center justify-end pt-4 border-t border-gray-100 dark:border-gray-800">
                                    <button
                                        type="submit"
                                        disabled={profileForm.processing}
                                        className="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold shadow-md transition-colors"
                                    >
                                        <Save className="w-4 h-4" />
                                        {profileForm.processing ? 'Menyimpan...' : 'Simpan Profil'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Bagian Password */}
                    <div className="space-y-4">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Ubah Password</h3>
                            <p className="text-xs text-gray-500 mt-1">
                                Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.
                            </p>
                        </div>

                        <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
                            <form onSubmit={submitPassword} className="space-y-6">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Password Saat Ini
                                    </label>
                                    <div className="relative">
                                        <KeyRound className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                                        <input
                                            type="password"
                                            required
                                            value={passwordForm.data.current_password}
                                            onChange={e => passwordForm.setData('current_password', e.target.value)}
                                            className="w-full pl-10 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                        />
                                    </div>
                                    {passwordForm.errors.current_password && <p className="text-rose-500 text-xs mt-1">{passwordForm.errors.current_password}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Password Baru
                                    </label>
                                    <div className="relative">
                                        <KeyRound className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                                        <input
                                            type="password"
                                            required
                                            value={passwordForm.data.password}
                                            onChange={e => passwordForm.setData('password', e.target.value)}
                                            className="w-full pl-10 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                        />
                                    </div>
                                    {passwordForm.errors.password && <p className="text-rose-500 text-xs mt-1">{passwordForm.errors.password}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Konfirmasi Password Baru
                                    </label>
                                    <div className="relative">
                                        <KeyRound className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                                        <input
                                            type="password"
                                            required
                                            value={passwordForm.data.password_confirmation}
                                            onChange={e => passwordForm.setData('password_confirmation', e.target.value)}
                                            className="w-full pl-10 pr-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                        />
                                    </div>
                                    {passwordForm.errors.password_confirmation && <p className="text-rose-500 text-xs mt-1">{passwordForm.errors.password_confirmation}</p>}
                                </div>

                                <div className="flex items-center justify-end pt-4 border-t border-gray-100 dark:border-gray-800">
                                    <button
                                        type="submit"
                                        disabled={passwordForm.processing}
                                        className="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-xl text-sm font-semibold shadow-md transition-colors"
                                    >
                                        <Save className="w-4 h-4" />
                                        {passwordForm.processing ? 'Menyimpan...' : 'Simpan Password'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {/* Crop Modal */}
            {cropModalOpen && (
                <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col h-[80vh] max-h-[600px]">
                        <div className="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-white dark:bg-gray-900 z-10">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                Sesuaikan Foto Profil
                            </h3>
                            <button onClick={() => {
                                setCropModalOpen(false);
                                if (photoInputRef.current) photoInputRef.current.value = '';
                            }} className="text-gray-400 hover:text-gray-600">
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        
                        <div className="relative flex-1 bg-black/5">
                            <Cropper
                                image={imageSrc}
                                crop={crop}
                                zoom={zoom}
                                aspect={1}
                                cropShape="round"
                                showGrid={false}
                                onCropChange={setCrop}
                                onCropComplete={onCropComplete}
                                onZoomChange={setZoom}
                            />
                        </div>

                        <div className="p-4 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
                            <div className="mb-4">
                                <label className="text-xs font-semibold text-gray-500 mb-1 block">Zoom</label>
                                <input
                                    type="range"
                                    value={zoom}
                                    min={1}
                                    max={3}
                                    step={0.1}
                                    aria-labelledby="Zoom"
                                    onChange={(e) => setZoom(e.target.value)}
                                    className="w-full accent-emerald-500"
                                />
                            </div>
                            <div className="flex justify-end gap-3">
                                <button 
                                    onClick={() => {
                                        setCropModalOpen(false);
                                        if (photoInputRef.current) photoInputRef.current.value = '';
                                    }} 
                                    className="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 font-semibold"
                                >
                                    Batal
                                </button>
                                <button 
                                    onClick={handleCropConfirm} 
                                    className="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold shadow-md"
                                >
                                    <Check className="w-4 h-4" />
                                    <span>Terapkan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
