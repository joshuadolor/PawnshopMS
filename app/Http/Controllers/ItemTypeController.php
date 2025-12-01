<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemTypeRequest;
use App\Models\ItemType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ItemTypeController extends Controller
{
    /**
     * Display a listing of the item types.
     */
    public function index(): View
    {
        $itemTypes = ItemType::orderBy('name', 'asc')->paginate(15)->where('name', '!=', 'Other');

        return view('item-types.index', [
            'itemTypes' => $itemTypes,
        ]);
    }

    /**
     * Show the form for creating a new item type.
     */
    public function create(): View
    {
        return view('item-types.create');
    }

    /**
     * Store a newly created item type.
     */
    public function store(StoreItemTypeRequest $request): RedirectResponse
    {
        ItemType::create([
            'name' => $request->name,
        ]);

        return redirect()->route('item-types.index')
            ->with('status', 'Item type created successfully.');
    }

    /**
     * Remove the specified item type.
     */
    public function destroy(ItemType $itemType): RedirectResponse
    {
        $itemType->delete();

        return redirect()->route('item-types.index')
            ->with('status', 'Item type deleted successfully.');
    }
}

