<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sangla - Process Transaction
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('transactions.sangla.store') }}">
                        @csrf

                        <!-- First Name -->
                        <div>
                            <x-input-label for="first_name" value="First Name" />
                            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name')" required autofocus />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <!-- Last Name -->
                        <div class="mt-4">
                            <x-input-label for="last_name" value="Last Name" />
                            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name')" required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>

                        <!-- Loan Amount -->
                        <div class="mt-4">
                            <x-input-label for="loan_amount" value="Loan Amount" />
                            <x-text-input id="loan_amount" name="loan_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('loan_amount')" required />
                            <x-input-error :messages="$errors->get('loan_amount')" class="mt-2" />
                        </div>

                        <!-- Effective Interest Rate -->
                        <div class="mt-4">
                            <x-input-label for="effective_interest_rate" value="Effective Interest Rate (%)" />
                            <x-text-input id="effective_interest_rate" name="effective_interest_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('effective_interest_rate')" required />
                            <x-input-error :messages="$errors->get('effective_interest_rate')" class="mt-2" />
                        </div>

                        <!-- Interest Rate Period -->
                        <div class="mt-4">
                            <x-input-label value="Interest Rate Period" />
                            <div class="mt-2 space-y-2">
                                <label class="inline-flex items-center w-full p-4 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors">
                                    <input type="radio" name="interest_rate_period" value="per_annum" {{ old('interest_rate_period') === 'per_annum' ? 'checked' : '' }} class="w-5 h-5 text-indigo-600 focus:ring-indigo-500" required>
                                    <span class="ms-3 text-base text-gray-700 font-medium">Per Annum</span>
                                </label>
                                <label class="inline-flex items-center w-full p-4 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors">
                                    <input type="radio" name="interest_rate_period" value="per_month" {{ old('interest_rate_period') === 'per_month' ? 'checked' : '' }} class="w-5 h-5 text-indigo-600 focus:ring-indigo-500" required>
                                    <span class="ms-3 text-base text-gray-700 font-medium">Per Month</span>
                                </label>
                                <label class="inline-flex items-center w-full p-4 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors">
                                    <input type="radio" name="interest_rate_period" value="others" {{ old('interest_rate_period') === 'others' ? 'checked' : '' }} class="w-5 h-5 text-indigo-600 focus:ring-indigo-500" required>
                                    <span class="ms-3 text-base text-gray-700 font-medium">Others</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('interest_rate_period')" class="mt-2" />
                        </div>

                        <!-- Item Type -->
                        <div class="mt-4">
                            <x-input-label for="item_type" value="Item Type" />
                            <select id="item_type" name="item_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Select Item Type</option>
                                <option value="jewelry" {{ old('item_type') === 'jewelry' ? 'selected' : '' }}>Jewelry</option>
                                <option value="electronics" {{ old('item_type') === 'electronics' ? 'selected' : '' }}>Electronics</option>
                                <option value="appliances" {{ old('item_type') === 'appliances' ? 'selected' : '' }}>Appliances</option>
                                <option value="vehicles" {{ old('item_type') === 'vehicles' ? 'selected' : '' }}>Vehicles</option>
                                <option value="other" {{ old('item_type') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <x-input-error :messages="$errors->get('item_type')" class="mt-2" />
                        </div>

                        <!-- Item Description -->
                        <div class="mt-4">
                            <x-input-label for="item_description" value="Item Description" />
                            <textarea id="item_description" name="item_description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('item_description') }}</textarea>
                            <x-input-error :messages="$errors->get('item_description')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900 font-medium">
                                Cancel
                            </a>
                            <x-primary-button>Submit</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

