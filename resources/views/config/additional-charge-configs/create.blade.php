<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Additional Charge Configuration
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        There were {{ $errors->count() }} error(s) with your submission:
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('config.additional-charge-configs.store') }}">
                        @csrf

                        <div class="space-y-6">
                            <!-- Start Day -->
                            <div>
                                <x-input-label for="start_day" value="Start Day" />
                                <x-text-input 
                                    id="start_day" 
                                    name="start_day" 
                                    type="number" 
                                    class="mt-1 block w-full" 
                                    :value="old('start_day')" 
                                    required 
                                    min="0"
                                />
                                <x-input-error :messages="$errors->get('start_day')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">The starting day for this charge range (e.g., 4)</p>
                            </div>

                            <!-- End Day -->
                            <div>
                                <x-input-label for="end_day" value="End Day" />
                                <x-text-input 
                                    id="end_day" 
                                    name="end_day" 
                                    type="number" 
                                    class="mt-1 block w-full" 
                                    :value="old('end_day')" 
                                    required 
                                    min="0"
                                />
                                <x-input-error :messages="$errors->get('end_day')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">The ending day for this charge range (e.g., 31). Must be greater than or equal to start day.</p>
                            </div>

                            <!-- Percentage -->
                            <div>
                                <x-input-label for="percentage" value="Percentage (%)" />
                                <x-text-input 
                                    id="percentage" 
                                    name="percentage" 
                                    type="number" 
                                    step="0.01"
                                    class="mt-1 block w-full" 
                                    :value="old('percentage')" 
                                    required 
                                    min="0"
                                    max="100"
                                />
                                <x-input-error :messages="$errors->get('percentage')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">Percentage to multiply with principal amount (e.g., 2.00 for 2%)</p>
                            </div>

                            <!-- Type -->
                            <div>
                                <x-input-label for="type" value="Type" />
                                <select 
                                    id="type" 
                                    name="type" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select Type</option>
                                    <option value="LD" {{ old('type') === 'LD' ? 'selected' : '' }}>LD - Late Days</option>
                                    <option value="EC" {{ old('type') === 'EC' ? 'selected' : '' }}>EC - Exceeded Charge</option>
                                </select>
                                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">LD = Late Days, EC = Exceeded Charge</p>
                            </div>

                            <!-- Transaction Type -->
                            <div>
                                <x-input-label for="transaction_type" value="Transaction Type" />
                                <select 
                                    id="transaction_type" 
                                    name="transaction_type" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select Transaction Type</option>
                                    <option value="renewal" {{ old('transaction_type') === 'renewal' ? 'selected' : '' }}>Renewal</option>
                                    <option value="tubos" {{ old('transaction_type') === 'tubos' ? 'selected' : '' }}>Tubos</option>
                                </select>
                                <x-input-error :messages="$errors->get('transaction_type')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">The transaction type this charge applies to</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <a href="{{ route('config.additional-charge-configs.index') }}" 
                               class="text-gray-600 hover:text-gray-900 font-medium">
                                Cancel
                            </a>
                            <x-primary-button>
                                Create Configuration
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

