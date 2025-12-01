<?php

namespace App\Http\Controllers\ItemType;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItemType\StoreItemTypeRequest;
use App\Models\ItemType;
use App\Models\ItemTypeSubtype;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemTypeController extends Controller
{
    /**
     * Display a listing of the item types.
     */
    public function index(): View
    {
        $itemTypes = ItemType::with('subtypes')->orderBy('name', 'asc')->where('name', '!=', 'Other')->paginate(15);

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
     * Store a newly created subtype for an item type.
     */
    public function storeSubtype(Request $request, ItemType $itemType): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        // Check if subtype already exists for this item type
        $existing = ItemTypeSubtype::where('item_type_id', $itemType->id)
            ->where('name', $request->name)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This subtype already exists for this item type.',
            ], 422);
        }

        $subtype = ItemTypeSubtype::create([
            'item_type_id' => $itemType->id,
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'subtype' => [
                'id' => $subtype->id,
                'name' => $subtype->name,
            ],
        ]);
    }

    /**
     * Remove the specified subtype.
     */
    public function destroySubtype(ItemType $itemType, ItemTypeSubtype $subtype): JsonResponse
    {
        // Verify the subtype belongs to the item type
        if ($subtype->item_type_id !== $itemType->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid subtype for this item type.',
            ], 422);
        }

        $subtype->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subtype deleted successfully.',
        ]);
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

