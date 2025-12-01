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

                        <!-- Address -->
                        <div class="mt-4">
                            <x-input-label for="address" value="Address" />
                            <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('address') }}</textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <!-- Appraised Value -->
                        <div class="mt-4">
                            <x-input-label for="appraised_value" value="Appraised Value" />
                            <x-text-input id="appraised_value" name="appraised_value" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('appraised_value')" required />
                            <x-input-error :messages="$errors->get('appraised_value')" class="mt-2" />
                        </div>

                        <!-- Loan Amount -->
                        <div class="mt-4">
                            <x-input-label for="loan_amount" value="Loan Amount" />
                            <x-text-input id="loan_amount" name="loan_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('loan_amount')" required />
                            <x-input-error :messages="$errors->get('loan_amount')" class="mt-2" />
                        </div>

                        <!-- Interest Rate -->
                        <div class="mt-4">
                            <x-input-label for="interest_rate" value="Interest Rate (%)" />
                            <x-text-input id="interest_rate" name="interest_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('interest_rate')" required />
                            <x-input-error :messages="$errors->get('interest_rate')" class="mt-2" />
                        </div>

                        <!-- Interest Rate Period -->
                        <div class="mt-4">
                            <x-input-label value="Interest Rate Period" />
                            <div class="mt-2 space-y-2">
                                @php
                                    $selectedPeriod = old('interest_rate_period', $interestPeriod);
                                @endphp
                                <label class="inline-flex items-center w-full p-4 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors">
                                    <input type="radio" name="interest_rate_period" value="per_annum" {{ $selectedPeriod === 'per_annum' ? 'checked' : '' }} class="w-5 h-5 text-indigo-600 focus:ring-indigo-500" required>
                                    <span class="ms-3 text-base text-gray-700 font-medium">Per Annum</span>
                                </label>
                                <label class="inline-flex items-center w-full p-4 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors">
                                    <input type="radio" name="interest_rate_period" value="per_month" {{ $selectedPeriod === 'per_month' ? 'checked' : '' }} class="w-5 h-5 text-indigo-600 focus:ring-indigo-500" required>
                                    <span class="ms-3 text-base text-gray-700 font-medium">Per Month</span>
                                </label>
                                <label class="inline-flex items-center w-full p-4 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors">
                                    <input type="radio" name="interest_rate_period" value="others" {{ $selectedPeriod === 'others' ? 'checked' : '' }} class="w-5 h-5 text-indigo-600 focus:ring-indigo-500" required>
                                    <span class="ms-3 text-base text-gray-700 font-medium">Others</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('interest_rate_period')" class="mt-2" />
                        </div>

                        <!-- Maturity Date -->
                        <div class="mt-4">
                            <x-input-label for="maturity_date" value="Maturity Date" />
                            <x-text-input 
                                id="maturity_date" 
                                name="maturity_date" 
                                type="date" 
                                class="mt-1 block w-full" 
                                :value="old('maturity_date', $defaultMaturityDate)" 
                                required 
                            />
                            <x-input-error :messages="$errors->get('maturity_date')" class="mt-2" />
                        </div>

                        <!-- Expiry Date of Redemption -->
                        <div class="mt-4">
                            <x-input-label for="expiry_date" value="Expiry Date of Redemption" />
                            <x-text-input 
                                id="expiry_date" 
                                name="expiry_date" 
                                type="date" 
                                class="mt-1 block w-full" 
                                :value="old('expiry_date')" 
                                required 
                            />
                            <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                        </div>


                        <!-- Calculation Table -->
                        <div class="mt-4">
                            <x-input-label value="Transaction Summary" />
                            <div class="mt-2 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border border-gray-300 rounded-lg">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">Principal</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right" id="principal_amount">₱0.00</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">Interest</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right" id="interest_amount">₱0.00</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">Service Charge</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right" id="service_charge_amount">₱{{ number_format($serviceCharge, 2) }}</td>
                                        </tr>
                                        <tr class="bg-gray-50 font-semibold">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">Net Proceeds</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right" id="net_proceeds_amount">₱0.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Item Type -->
                        <div class="mt-4">
                            <x-input-label for="item_type" value="Item Type" />
                            <select id="item_type" name="item_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Select Item Type</option>
                                @foreach($itemTypes as $itemType)
                                    <option value="{{ $itemType->id }}" {{ old('item_type') == $itemType->id ? 'selected' : '' }}>
                                        {{ $itemType->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('item_type')" class="mt-2" />
                        </div>

                        <!-- Custom Item Type (shown when "Other" is selected) -->
                        <div id="custom_item_type_container" class="mt-4 hidden">
                            <x-input-label for="custom_item_type" value="Custom Item Type" />
                            <x-text-input 
                                id="custom_item_type" 
                                name="custom_item_type" 
                                type="text" 
                                class="mt-1 block w-full" 
                                :value="old('custom_item_type')"
                                placeholder="Enter custom item type"
                                minlength="3"
                            />
                            <p class="mt-1 text-sm text-gray-500">Minimum 3 characters required</p>
                            <x-input-error :messages="$errors->get('custom_item_type')" class="mt-2" />
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const itemTypeSelect = document.getElementById('item_type');
            const customItemTypeContainer = document.getElementById('custom_item_type_container');
            const customItemTypeInput = document.getElementById('custom_item_type');
            const maturityDateInput = document.getElementById('maturity_date');
            const expiryDateInput = document.getElementById('expiry_date');
            const loanAmountInput = document.getElementById('loan_amount');
            const interestRateInput = document.getElementById('interest_rate');
            const interestRatePeriodInputs = document.querySelectorAll('input[name="interest_rate_period"]');
            
            // Config values from backend
            const serviceCharge = {{ $serviceCharge }};
            
            // Check if "Other" is selected on page load (for validation errors)
            const otherItemType = Array.from(itemTypeSelect.options).find(option => option.text === 'Other');
            if (otherItemType && itemTypeSelect.value === otherItemType.value) {
                customItemTypeContainer.classList.remove('hidden');
                customItemTypeInput.required = true;
            }

            itemTypeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const isOther = selectedOption.text === 'Other';
                
                if (isOther) {
                    customItemTypeContainer.classList.remove('hidden');
                    customItemTypeInput.required = true;
                } else {
                    customItemTypeContainer.classList.add('hidden');
                    customItemTypeInput.required = false;
                    customItemTypeInput.value = '';
                }
            });

            // Set minimum date for maturity date (today)
            const today = new Date().toISOString().split('T')[0];
            maturityDateInput.setAttribute('min', today);
            expiryDateInput.setAttribute('min', today);

            // Update expiry date minimum when maturity date changes
            maturityDateInput.addEventListener('change', function() {
                const maturityDate = this.value;
                if (maturityDate) {
                    expiryDateInput.setAttribute('min', maturityDate);
                    // If expiry date is before maturity date, update it
                    if (expiryDateInput.value && expiryDateInput.value < maturityDate) {
                        expiryDateInput.value = maturityDate;
                    }
                }
                calculateAmounts();
            });

            // Calculate amounts function
            function calculateAmounts() {
                const principal = parseFloat(loanAmountInput.value) || 0;
                const interestRate = parseFloat(interestRateInput.value) || 0;
                const maturityDate = maturityDateInput.value;
                const selectedPeriod = document.querySelector('input[name="interest_rate_period"]:checked')?.value;
                
                let interest = 0;
                
                if (principal > 0 && interestRate > 0 && maturityDate && selectedPeriod) {
                    const today = new Date();
                    const maturity = new Date(maturityDate);
                    const timeDiff = maturity - today;
                    const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
                    
                    if (daysDiff > 0) {
                        if (selectedPeriod === 'per_month') {
                            const months = daysDiff / 30; // Approximate months
                            interest = principal * (interestRate / 100) * months;
                        } else if (selectedPeriod === 'per_annum') {
                            const years = daysDiff / 365;
                            interest = principal * (interestRate / 100) * years;
                        } else {
                            // For "others", treat similar to per_month
                            const months = daysDiff / 30;
                            interest = principal * (interestRate / 100) * months;
                        }
                    }
                }
                
                const netProceeds = principal - interest - serviceCharge;
                
                // Update display
                document.getElementById('principal_amount').textContent = '₱' + principal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                document.getElementById('interest_amount').textContent = '₱' + interest.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                document.getElementById('service_charge_amount').textContent = '₱' + serviceCharge.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                document.getElementById('net_proceeds_amount').textContent = '₱' + Math.max(0, netProceeds).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            // Add event listeners for calculation
            loanAmountInput.addEventListener('input', calculateAmounts);
            loanAmountInput.addEventListener('change', calculateAmounts);
            interestRateInput.addEventListener('input', calculateAmounts);
            interestRateInput.addEventListener('change', calculateAmounts);
            maturityDateInput.addEventListener('change', calculateAmounts);
            interestRatePeriodInputs.forEach(input => {
                input.addEventListener('change', calculateAmounts);
            });
            
            // Initial calculation if values exist
            calculateAmounts();
        });
    </script>
</x-app-layout>

