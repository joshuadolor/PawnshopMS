<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Config\UpdateConfigRequest;
use App\Models\Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfigController extends Controller
{
    /**
     * Display the configuration management page.
     */
    public function index(): View
    {
        $configs = Config::orderBy('label', 'asc')->get();
        $additionalChargeConfigs = \App\Models\AdditionalChargeConfig::orderBy('transaction_type')
            ->orderBy('type')
            ->orderBy('start_day')
            ->get()
            ->groupBy('transaction_type');

        return view('configs.index', [
            'configs' => $configs,
            'additionalChargeConfigs' => $additionalChargeConfigs,
        ]);
    }

    /**
     * Update the specified configuration.
     */
    public function update(UpdateConfigRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($validated['configs'] as $key => $value) {
            Config::where('key', $key)->update(['value' => $value]);
        }

        return redirect()->route('configs.index')
            ->with('status', 'Configuration updated successfully.');
    }
}

