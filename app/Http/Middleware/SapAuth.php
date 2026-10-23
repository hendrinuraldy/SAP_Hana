<?php

namespace App\Http\Middleware;

use App\Services\SapB1Service;
use Closure;
use Illuminate\Http\Request;

class SapAuth
{
    public function handle(Request $request, Closure $next)
    {
        $sap = app(SapB1Service::class);

        if (!$sap->isLoggedIn()) {
            return redirect()->route('login')->with('error', 'Sesi habis, silakan login kembali.');
        }

        return $next($request);
    }
}
