<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    /**
     * Display a listing of the branches.
     */
    public function index(): View
    {
        $branches = Branch::orderBy('name', 'asc')->paginate(15);

        return view('branches.index', [
            'branches' => $branches,
        ]);
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create(): View
    {
        return view('branches.create');
    }

    /**
     * Store a newly created branch.
     */
    public function store(StoreBranchRequest $request): RedirectResponse
    {
        Branch::create([
            'name' => $request->name,
            'address' => $request->address,
            'contact_number' => $request->contact_number,
        ]);

        return redirect()->route('branches.index')
            ->with('status', 'Branch created successfully.');
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->delete();

        return redirect()->route('branches.index')
            ->with('status', 'Branch deleted successfully.');
    }
}

