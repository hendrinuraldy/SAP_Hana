<?php

namespace App\Http\Controllers;

use App\Services\SapB1Service;
use Illuminate\Http\Request;

class AdHocQueryController extends Controller
{
    public function index()
    {
        return view('reports.adhoc-query', [
            'sql'     => old('sql', ''),
            'rows'    => [],
            'columns' => [],
            'error'   => null,
            'ran'     => false,
        ]);
    }

    public function run(Request $request, SapB1Service $sap)
    {
        $request->validate([
            'sql' => 'required|string',
        ]);

        $rows = [];
        $columns = [];
        $error = null;

        try {
            $rows = $sap->runAdHocQuery($request->sql);
            $columns = $rows ? array_keys($rows[0]) : [];
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('reports.adhoc-query', [
            'sql'     => $request->sql,
            'rows'    => $rows,
            'columns' => $columns,
            'error'   => $error,
            'ran'     => true,
        ]);
    }
}