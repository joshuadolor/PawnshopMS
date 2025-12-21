<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\AdditionalChargeConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdditionalChargeConfigController extends Controller
{
    /**
     * Display a listing of additional charge configurations.
     */
    public function index(): View
    {
        $configs = AdditionalChargeConfig::orderBy('transaction_type')
            ->orderBy('type')
            ->orderBy('start_day')
            ->get();

        return view('config.additional-charge-configs.index', [
            'configs' => $configs,
        ]);
    }

    /**
     * Show the form for creating a new additional charge configuration.
     */
    public function create(): View
    {
        return view('config.additional-charge-configs.create');
    }

    /**
     * Store a newly created additional charge configuration.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_day' => ['required', 'integer', 'min:0'],
            'end_day' => ['required', 'integer', 'min:0', 'gte:start_day'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'type' => ['required', 'in:LD,EC'],
            'transaction_type' => ['required', 'in:renewal,tubos'],
        ]);

        AdditionalChargeConfig::create($validated);

        return redirect()->route('config.additional-charge-configs.index')
            ->with('success', 'Additional charge configuration created successfully.');
    }

    /**
     * Show the form for editing the specified additional charge configuration.
     */
    public function edit(AdditionalChargeConfig $additionalChargeConfig): View
    {
        return view('config.additional-charge-configs.edit', [
            'config' => $additionalChargeConfig,
        ]);
    }

    /**
     * Update the specified additional charge configuration.
     */
    public function update(Request $request, AdditionalChargeConfig $additionalChargeConfig): RedirectResponse
    {
        $validated = $request->validate([
            'start_day' => ['required', 'integer', 'min:0'],
            'end_day' => ['required', 'integer', 'min:0', 'gte:start_day'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'type' => ['required', 'in:LD,EC'],
            'transaction_type' => ['required', 'in:renewal,tubos'],
        ]);

        $additionalChargeConfig->update($validated);

        return redirect()->route('config.additional-charge-configs.index')
            ->with('success', 'Additional charge configuration updated successfully.');
    }

    /**
     * Remove the specified additional charge configuration.
     */
    public function destroy(AdditionalChargeConfig $additionalChargeConfig): RedirectResponse
    {
        $additionalChargeConfig->delete();

        return redirect()->route('config.additional-charge-configs.index')
            ->with('success', 'Additional charge configuration deleted successfully.');
    }
}
