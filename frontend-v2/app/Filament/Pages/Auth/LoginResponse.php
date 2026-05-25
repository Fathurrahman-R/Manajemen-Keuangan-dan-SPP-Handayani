<?php

namespace App\Filament\Pages\Auth;

use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        if (session()->has('intended_url')) {
            $intendedUrl = session()->pull('intended_url');
            return redirect()->to($intendedUrl);
        }

        $permissions = session()->get('data.permissions', []);

        // Map permission ke halaman, urut berdasarkan prioritas
        $permissionRoutes = [
            'view-siswa' => '/data-master-siswa',
            'view-pembayaran' => '/transaksi-pembayaran',
            'view-tagihan' => '/transaksi-tagihan',
            'view-jenis-tagihan' => '/transaksi-jenis-tagihan',
            'view-kategori' => '/data-master-category',
            'view-kelas' => '/data-master-kelas',
            'view-pengeluaran' => '/transaksi-pengeluaran',
            'view-kas-harian' => '/laporan-kas-harian',
            'view-rekap-bulanan' => '/laporan-rekap-bulanan',
            'view-roles' => '/role-management',
            'view-user' => '/user-management',
        ];

        foreach ($permissionRoutes as $permission => $route) {
            if (in_array($permission, $permissions)) {
                return redirect()->to(filament()->getUrl() . $route);
            }
        }

        // Fallback: jika tidak ada permission yang cocok, arahkan ke setting
        return redirect()->to(filament()->getUrl() . '/setting');
    }
}
