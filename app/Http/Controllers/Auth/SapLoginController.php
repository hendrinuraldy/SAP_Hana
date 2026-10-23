<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SapB1Service;
use Illuminate\Http\Request;

class SapLoginController extends Controller
{
    protected SapB1Service $sap;

    public function __construct(SapB1Service $sap)
    {
        $this->sap = $sap;
    }

    public function showLoginForm()
    {
        return view('auth.sap-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $companyDb = config('services.sap_b1.company_db');

        $result = $this->sap->login($companyDb, $request->username, $request->password);

        if (!$result) {
            return back()->withErrors(['username' => 'Username atau password SAP salah, atau server tidak dapat dihubungi.']);
        }

        // Simpan juga info user login untuk ditampilkan di UI
        session(['sap_username' => $request->username]);

        return redirect()->intended('/dashboard');
    }

    public function logout()
    {
        $this->sap->logout();
        return redirect('/login');
    }
}
