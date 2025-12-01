<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Configuration Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                            <p class="text-sm text-green-800 font-medium">{{ session('status') }}</p>
                        </div>
                    @endif

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
        </div>
    </div>
</x-app-layout>

