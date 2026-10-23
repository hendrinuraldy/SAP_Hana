<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Membatasi akses ke fitur sensitif (SQL manual / Ad-Hoc Query) hanya untuk
 * user SAP yang terdaftar sebagai admin. Dipasang DI DALAM grup 'sap.auth'
 * (jadi user pasti sudah login dulu sebelum middleware ini jalan) --
 * lihat routes/web.php.
 *
 * Daftar admin diambil dari config/services.php -> sap_b1.adhoc_admin_users,
 * yang dibaca dari .env: SAP_ADHOC_ADMIN_USERS=manager,it_admin
 */
class SapAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $username = Session::get('sap_username');
        $adminUsers = array_filter(array_map('trim', config('services.sap_b1.adhoc_admin_users', [])));

        if (!$username || !in_array($username, $adminUsers, true)) {
            abort(403, 'Fitur ini hanya bisa diakses oleh admin.');
        }

        return $next($request);
    }
}