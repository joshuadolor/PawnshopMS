<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Configuration Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                    <p class="text-sm text-green-800 font-medium">{{ session('status') }}</p>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                    <p class="text-sm text-green-800 font-medium">{{ session('success') }}</p>
                </div>
            @endif

            <!-- General Configurations Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">General Configurations</h3>
                    <form method="POST" action="{{ route('configs.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            @foreach($configs as $config)
                                <div class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0">
                                    <div class="mb-2">
                                        <x-input-label :for="'config_' . $config->key" :value="$config->label" />
                                        @if($config->description)
                                            <p class="mt-1 text-sm text-gray-500">{{ $config->description }}</p>
                                        @endif
                                    </div>
                                    
                                    @if($config->type === 'percentage')
                                        <div class="mt-2">
                                            <div class="flex items-center">
                                                <x-text-input 
                                                    :id="'config_' . $config->key" 
                                                    name="configs[{{ $config->key }}]" 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0" 
                                                    max="100"
                                                    class="block w-full" 
                                                    :value="old('configs.' . $config->key, $config->value)"
                                                />
                                                <span class="ml-2 text-gray-600">%</span>
                                            </div>
                                        </div>
                                    @elseif($config->type === 'decimal')
                                        <div class="mt-2">
                                            <x-text-input 
                                                :id="'config_' . $config->key" 
                                                name="configs[{{ $config->key }}]" 
                                                type="number" 
                                                step="0.01" 
                                                min="0"
                                                class="block w-full" 
                                                :value="old('configs.' . $config->key, $config->value)"
                                            />
                                        </div>
                                    @elseif($config->type === 'number')
                                        <div class="mt-2">
                                            <x-text-input 
                                                :id="'config_' . $config->key" 
                                                name="configs[{{ $config->key }}]" 
                                                type="number" 
                                                min="0"
                                                class="block w-full" 
                                                :value="old('configs.' . $config->key, $config->value)"
                                            />
                                        </div>
                                    @else
                                        <div class="mt-2">
                                            <x-text-input 
                                                :id="'config_' . $config->key" 
                                                name="configs[{{ $config->key }}]" 
                                                type="text" 
                                                class="block w-full" 
                                                :value="old('configs.' . $config->key, $config->value)"
                                            />
                                        </div>
                                    @endif
                                    
                                    <x-input-error :messages="$errors->get('configs.' . $config->key)" class="mt-2" />
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <x-primary-button>Save Configuration</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Additional Charge Configurations Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Additional Charge Configurations</h3>
                            <p class="text-sm text-gray-500 mt-1">Configure additional charges for renewals and tubos that exceed maturity/redemption dates</p>
                        </div>
                        @if(Auth::user()->isSuperAdmin())
                            <a href="{{ route('config.additional-charge-configs.create') }}" 
                               class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Add Configuration
                            </a>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        @forelse($additionalChargeConfigs as $transactionType => $configs)
                            <div class="mb-8 last:mb-0">
                                <h4 class="text-md font-semibold text-gray-900 mb-4 capitalize">
                                    {{ $transactionType }} Transactions
                                </h4>
                                <table class="min-w-full divide-y divide-gray-200 mb-6">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Day</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Day</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage (%)</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            @if(Auth::user()->isSuperAdmin())
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($configs as $config)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $config->start_day }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $config->end_day }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($config->percentage, 2) }}%</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        {{ $config->type === 'LD' ? 'bg-yellow-100 text-yellow-800' : 'bg-orange-100 text-orange-800' }}">
                                                        {{ $config->type }}
                                                    </span>
                                                </td>
                                                @if(Auth::user()->isSuperAdmin())
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <a href="{{ route('config.additional-charge-configs.edit', $config) }}" 
                                                           class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                                        <form action="{{ route('config.additional-charge-configs.destroy', $config) }}" 
                                                              method="POST" 
                                                              class="inline"
                                                              onsubmit="return confirm('Are you sure you want to delete this configuration?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @empty
                            <div class="text-center text-sm text-gray-500 py-8">
                                No additional charge configurations found. <a href="{{ route('config.additional-charge-configs.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

