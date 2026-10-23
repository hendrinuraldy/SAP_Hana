<?php

namespace App\Http\Controllers;

use App\Models\SapItem;
use Illuminate\Http\Request;

class SapItemController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('q');

        $items = SapItem::query()
            ->when($search, function ($query, $search) {
                $query->where('item_code', 'like', "%{$search}%")
                      ->orWhere('item_name', 'like', "%{$search}%");
            })
            ->orderBy('item_name')
            ->paginate(20)
            ->withQueryString();

        return view('items.index', compact('items', 'search'));
    }
}