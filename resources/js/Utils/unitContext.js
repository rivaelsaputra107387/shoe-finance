import axios from 'axios';
import { router } from '@inertiajs/react';

export const UNIT_CONFLICT_EVENT = 'finlog:unit-conflict';

let unitCode = null;
let unitName = null;

export const currentUnitCode = () => unitCode;
export const currentUnitName = () => unitName;

function isUnitConflict(response) {
    return response?.status === 409 && response?.data?.code === 'unit_conflict';
}

function rememberUnit(page) {
    unitCode = page?.props?.unit?.active?.code ?? null;
    unitName = page?.props?.unit?.active?.name ?? null;
}

/**
 * Setiap permintaan menyebut unit yang sedang ditampilkan halaman ini. Unit aktif tersimpan di sesi yang
 * dipakai bersama semua tab, jadi server menolak permintaan dari tab yang masih menampilkan unit lain.
 */
export function setupUnitContext(initialPage) {
    rememberUnit(initialPage);

    router.on('navigate', (event) => rememberUnit(event.detail.page));

    // Inertia memakai instance axios yang sama, jadi ini mencakup semua permintaan.
    axios.interceptors.request.use((config) => {
        if (unitCode) {
            config.headers['X-Finlog-Unit'] = unitCode;
        }

        return config;
    });

    axios.interceptors.response.use(
        (response) => response,
        (error) => {
            if (isUnitConflict(error.response)) {
                window.dispatchEvent(new CustomEvent(UNIT_CONFLICT_EVENT, {
                    detail: { activeUnit: error.response.data.active_unit },
                }));
            }

            return Promise.reject(error);
        },
    );

    router.on('invalid', (event) => {
        if (isUnitConflict(event.detail.response)) {
            event.preventDefault();
        }
    });
}
