<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class ItemsController extends Controller
{
    /**
     * Display a listing of items.
     */
    public function index(Request $request): View
    {
        // Query sangla transactions (items)
        $query = Transaction::where('type', 'sangla')
            ->with(['branch', 'user', 'itemType', 'itemTypeSubtype', 'tags', 'voided'])
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_description', 'like', "%{$search}%")
                  ->orWhere('pawn_ticket_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('transaction_number', 'like', "%{$search}%")
                  ->orWhereHas('itemType', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('tags', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by branches (multiple selection)
        if ($request->filled('branch_ids') && is_array($request->branch_ids)) {
            $query->whereIn('branch_id', $request->branch_ids);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'available') {
                // Available: not voided and not past auction date
                $query->whereDoesntHave('voided')
                      ->where(function ($q) {
                          $q->whereNull('auction_sale_date')
                            ->orWhere('auction_sale_date', '>', Carbon::today());
                      });
            } elseif ($request->status === 'ready_for_auction') {
                // Ready for auction: not voided and auction date is today or past
                $query->whereDoesntHave('voided')
                      ->whereNotNull('auction_sale_date')
                      ->where('auction_sale_date', '<=', Carbon::today());
            } elseif ($request->status === 'voided') {
                $query->whereHas('voided');
            }
        } else {
            // Default: show only non-voided items
            $query->whereDoesntHave('voided');
        }

        // Filter: Show only auctionable items
        if ($request->boolean('auctionable_only')) {
            // Auctionable: not voided and has auction_sale_date that is today or past
            $query->whereDoesntHave('voided')
                  ->whereNotNull('auction_sale_date')
                  ->where('auction_sale_date', '<=', Carbon::today());
        }

        $items = $query->paginate(20)->withQueryString();

        // Get branches for filter
        $branches = \App\Models\Branch::orderBy('name', 'asc')->get();

        return view('items.index', [
            'items' => $items,
            'branches' => $branches,
            'filters' => [
                'search' => $request->search ?? null,
                'branch_ids' => $request->branch_ids ?? [],
                'status' => $request->status ?? null,
                'auctionable_only' => $request->boolean('auctionable_only'),
            ],
        ]);
    }
}

