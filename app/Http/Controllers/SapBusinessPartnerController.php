<?php

namespace App\Http\Controllers;

use App\Models\SapBusinessPartner;
use Illuminate\Http\Request;

class SapBusinessPartnerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('q');

        $partners = SapBusinessPartner::query()
            ->when($search, function ($query, $search) {
                $query->where('card_code', 'like', "%{$search}%")
                      ->orWhere('card_name', 'like', "%{$search}%");
            })
            ->orderBy('card_name')
            ->paginate(20)
            ->withQueryString();

        return view('business-partners.index', compact('partners', 'search'));
    }
}