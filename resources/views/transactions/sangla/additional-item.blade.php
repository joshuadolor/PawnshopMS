<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Additional Item - Sangla Transaction
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                            <p class="text-sm text-green-800 font-medium">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-sm text-red-800 font-medium">{{ session('error') }}</p>
                        </div>
                    @endif

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

                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm text-blue-800">
                            <strong>Pawn Ticket Number:</strong> {{ $pawnTicketNumber }}
                        </p>
                        <p class="text-sm text-blue-700 mt-1">
                            Adding an additional item to an existing transaction. Pawner information and pawn ticket details are readonly.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('transactions.sangla.store-additional-item') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="pawn_ticket_number" value="{{ $pawnTicketNumber }}">
                        <input type="hidden" name="branch_id" value="{{ $firstTransaction->branch_id }}">

                        <!-- First Name (Readonly) -->
                        <div>
                            <x-input-label for="first_name" value="First Name" />
                            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full bg-gray-100" :value="old('first_name', $firstTransaction->first_name)" readonly />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <!-- Last Name (Readonly) -->
                        <div class="mt-4">
                            <x-input-label for="last_name" value="Last Name" />
                            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full bg-gray-100" :value="old('last_name', $firstTransaction->last_name)" readonly />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>

                        <!-- Address (Readonly) -->
                        <div class="mt-4">
                            <x-input-label for="address" value="Address" />
                            <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>{{ old('address', $firstTransaction->address) }}</textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <!-- Appraised Value (Readonly) -->
                        <div class="mt-4">
                            <x-input-label for="appraised_value" value="Appraised Value" />
                            <x-text-input 
                                id="appraised_value" 
                                name="appraised_value" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                class="mt-1 block w-full bg-gray-100" 
                                :value="old('appraised_value', number_format($firstTransaction->appraised_value, 2, '.', ''))" 
                                readonly 
                                required 
                            />
                            <p class="mt-1 text-sm text-gray-500">Appraised value is readonly for additional items (same as parent transaction).</p>
                            <x-input-error :messages="$errors->get('appraised_value')" class="mt-2" />
                        </div>

                        <!-- Loan Amount (Readonly) -->
                        <div class="mt-4">
                            <x-input-label for="loan_amount" value="Loan Amount" />
                            <x-text-input 
                                id="loan_amount" 
                                name="loan_amount" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                class="mt-1 block w-full bg-gray-100" 
                                :value="old('loan_amount', number_format($firstTransaction->loan_amount, 2, '.', ''))" 
                                readonly 
                                required 
                            />
                            <p class="mt-1 text-sm text-gray-500">Loan amount is readonly for additional items (same as parent transaction).</p>
                            <x-input-error :messages="$errors->get('loan_amount')" class="mt-2" />
                        </div>

                        <!-- Interest Rate (Readonly) -->
                        <div class="mt-4">
                            <x-input-label for="interest_rate" value="Interest Rate (%)" />
                            <x-text-input 
                                id="interest_rate" 
                                name="interest_rate" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                max="100" 
                                class="mt-1 block w-full bg-gray-100" 
                                :value="old('interest_rate', number_format($firstTransaction->interest_rate, 2, '.', ''))" 
                                readonly 
                                required 
                            />
                            <p class="mt-1 text-sm text-gray-500">Interest rate is readonly for additional items (same as parent transaction).</p>
                            <x-input-error :messages="$errors->get('interest_rate')" class="mt-2" />
                        </div>

                        <!-- Interest Rate Period (Readonly) -->
                        <div class="mt-4">
                            <x-input-label value="Interest Rate Period" />
                            <div class="mt-2 flex flex-wrap gap-2">
                                @php
                                    $selectedPeriod = old('interest_rate_period', $firstTransaction->interest_rate_period);
                                @endphp
                                <label class="inline-flex items-center px-4 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed opacity-75">
                                    <input type="radio" name="interest_rate_period" value="per_annum" {{ $selectedPeriod === 'per_annum' ? 'checked' : '' }} disabled class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ms-2 text-sm text-gray-700 font-medium">Per Annum</span>
                                </label>
                                <label class="inline-flex items-center px-4 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed opacity-75">
                                    <input type="radio" name="interest_rate_period" value="per_month" {{ $selectedPeriod === 'per_month' ? 'checked' : '' }} disabled class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ms-2 text-sm text-gray-700 font-medium">Per Month</span>
                                </label>
                                <label class="inline-flex items-center px-4 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed opacity-75">
                                    <input type="radio" name="interest_rate_period" value="others" {{ $selectedPeriod === 'others' ? 'checked' : '' }} disabled class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ms-2 text-sm text-gray-700 font-medium">Others</span>
                                </label>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Interest rate period is readonly for additional items (same as parent transaction).</p>
                            <input type="hidden" name="interest_rate_period" value="{{ $selectedPeriod }}">
                            <x-input-error :messages="$errors->get('interest_rate_period')" class="mt-2" />
                        </div>

                        <!-- Maturity Date (Readonly) -->
                        <div class="mt-4">
                            <x-input-label for="maturity_date" value="Maturity Date" />
                            <x-text-input 
                                id="maturity_date" 
                                name="maturity_date" 
                                type="date" 
                                class="mt-1 block w-full bg-gray-100" 
                                :value="old('maturity_date', $firstTransaction->maturity_date->format('Y-m-d'))" 
                                readonly 
                                required 
                            />
                            <p class="mt-1 text-sm text-gray-500">Maturity date is readonly for additional items.</p>
                            <x-input-error :messages="$errors->get('maturity_date')" class="mt-2" />
                        </div>

                        <!-- Expiry Date of Redemption (Readonly) -->
                        <div class="mt-4">
                            <x-input-label for="expiry_date" value="Expiry Date of Redemption" />
                            <x-text-input 
                                id="expiry_date" 
                                name="expiry_date" 
                                type="date" 
                                class="mt-1 block w-full bg-gray-100" 
                                :value="old('expiry_date', $firstTransaction->expiry_date->format('Y-m-d'))" 
                                readonly 
                                required 
                            />
                            <p class="mt-1 text-sm text-gray-500">Expiry date is readonly for additional items.</p>
                            <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                        </div>

                        <!-- Auction Sale Date (Readonly) -->
                        <div class="mt-4">
                            <x-input-label for="auction_sale_date" value="Auction Sale Date" />
                            <x-text-input 
                                id="auction_sale_date" 
                                name="auction_sale_date" 
                                type="date" 
                                class="mt-1 block w-full bg-gray-100" 
                                :value="old('auction_sale_date', $firstTransaction->auction_sale_date ? $firstTransaction->auction_sale_date->format('Y-m-d') : '')" 
                                readonly 
                            />
                            <p class="mt-1 text-sm text-gray-500">Auction sale date is readonly for additional items.</p>
                            <x-input-error :messages="$errors->get('auction_sale_date')" class="mt-2" />
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
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-right" id="service_charge_amount">₱0.00 <span class="text-xs">(Already deducted on first item)</span></td>
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
                                    <option 
                                        value="{{ $itemType->id }}" 
                                        data-has-subtypes="{{ $itemType->subtypes->count() > 0 ? '1' : '0' }}"
                                        data-has-tags="{{ $itemType->tags->count() > 0 ? '1' : '0' }}"
                                        data-subtypes="{{ json_encode($itemType->subtypes->pluck('name', 'id')->toArray()) }}"
                                        data-tags="{{ json_encode($itemType->tags->pluck('name', 'id')->toArray()) }}"
                                        {{ old('item_type') == $itemType->id ? 'selected' : '' }}>
                                        {{ $itemType->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('item_type')" class="mt-2" />
                        </div>

                        <!-- Item Type Subtype (shown when item type has subtypes) -->
                        <div id="item_type_subtype_container" class="mt-4 hidden">
                            <x-input-label for="item_type_subtype" value="Subtype" />
                            <select id="item_type_subtype" name="item_type_subtype" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a subtype</option>
                            </select>
                            <x-input-error :messages="$errors->get('item_type_subtype')" class="mt-2" />
                        </div>

                        <!-- Item Type Tags (shown when item type has tags) -->
                        <div id="item_type_tags_container" class="mt-4 hidden">
                            <x-input-label for="item_type_tags" value="Tags" />
                            <div id="item_type_tags_checkboxes" class="mt-2 flex flex-wrap gap-2">
                                <!-- Tags will be dynamically added here as checkboxes -->
                            </div>
                            <x-input-error :messages="$errors->get('item_type_tags')" class="mt-2" />
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

                        <!-- Grams (shown when "Jewelry" is selected) -->
                        <div id="grams_container" class="mt-4 hidden">
                            <x-input-label for="grams" value="Grams" />
                            <x-text-input 
                                id="grams" 
                                name="grams" 
                                type="number" 
                                class="mt-1 block w-full" 
                                :value="old('grams')"
                                placeholder="0.0"
                                step="0.1"
                                min="0"
                            />
                            <p class="mt-1 text-sm text-gray-500">Enter weight in grams (single decimal place)</p>
                            <x-input-error :messages="$errors->get('grams')" class="mt-2" />
                        </div>

                        <!-- OR&CR/Serial (shown when "Vehicles" is selected) -->
                        <div id="orcr_serial_container" class="mt-4 hidden">
                            <x-input-label for="orcr_serial" value="OR&CR/Serial" />
                            <x-text-input 
                                id="orcr_serial" 
                                name="orcr_serial" 
                                type="text" 
                                class="mt-1 block w-full" 
                                :value="old('orcr_serial')"
                                placeholder="Enter OR&CR/Serial number"
                            />
                            <x-input-error :messages="$errors->get('orcr_serial')" class="mt-2" />
                        </div>

                        <!-- Item Description -->
                        <div class="mt-4">
                            <x-input-label for="item_description" value="Item Description" />
                            <textarea id="item_description" name="item_description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('item_description') }}</textarea>
                            <x-input-error :messages="$errors->get('item_description')" class="mt-2" />
                        </div>

                        <!-- Item Image -->
                        <x-image-capture 
                            name="item_image" 
                            label="Item Image" 
                            :value="old('item_image')" 
                        />

                        <!-- Pawner ID/Photo (Readonly - Show existing image) -->
                        <div class="mt-4">
                            <x-input-label value="Pawner ID/Photo" />
                            @if($firstTransaction->pawner_id_image_path)
                                <div class="mt-2">
                                    <img src="{{ route('images.show', $firstTransaction->pawner_id_image_path) }}" alt="Pawner ID Image" class="max-w-xs rounded-md border border-gray-300">
                                    <p class="mt-1 text-sm text-gray-500">Using the same pawner ID image from the first transaction.</p>
                                </div>
                            @else
                                <p class="mt-1 text-sm text-gray-500">No pawner ID image available.</p>
                            @endif
                        </div>

                        <!-- Pawn Ticket No. (Readonly) -->
                        <div class="mt-4">
                            <x-input-label for="pawn_ticket_number" value="Pawn Ticket No." />
                            <x-text-input 
                                id="pawn_ticket_number" 
                                name="pawn_ticket_number_display" 
                                type="text" 
                                class="mt-1 block w-full bg-gray-100" 
                                :value="$pawnTicketNumber" 
                                readonly 
                            />
                            <p class="mt-1 text-sm text-gray-500">Pawn ticket number is readonly for additional items.</p>
                        </div>

                        <!-- Pawn Ticket Image (Readonly - Show existing image) -->
                        <div class="mt-4">
                            <x-input-label value="Pawn Ticket Image" />
                            @if($firstTransaction->pawn_ticket_image_path)
                                <div class="mt-2">
                                    <img src="{{ route('images.show', $firstTransaction->pawn_ticket_image_path) }}" alt="Pawn Ticket Image" class="max-w-xs rounded-md border border-gray-300">
                                    <p class="mt-1 text-sm text-gray-500">Using the same pawn ticket image from the first transaction.</p>
                                </div>
                            @else
                                <p class="mt-1 text-sm text-gray-500">No pawn ticket image available.</p>
                            @endif
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
            console.log('DOMContentLoaded fired for additional item form');
            
            // Image capture functions - use event delegation to avoid timing issues
            // Handle image capture button clicks using event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.image-capture-btn')) {
                    const btn = e.target.closest('.image-capture-btn');
                    const action = btn.getAttribute('data-action');
                    const fieldName = btn.getAttribute('data-field');
                    
                    console.log('Image capture button clicked:', { action, fieldName });
                    
                    if (action === 'camera') {
                        const input = document.getElementById(fieldName + '_input');
                        console.log('Camera action - input found:', !!input);
                        if (input) {
                            input.setAttribute('capture', 'environment');
                            input.click();
                        }
                    } else if (action === 'select') {
                        const input = document.getElementById(fieldName + '_input');
                        console.log('Select action - input found:', !!input);
                        if (input) {
                            input.removeAttribute('capture');
                            input.click();
                        } else {
                            console.error('Input element not found for field:', fieldName);
                        }
                    } else if (action === 'remove') {
                        const input = document.getElementById(fieldName + '_input');
                        const preview = document.getElementById(fieldName + '_preview');
                        const previewContainer = document.getElementById(fieldName + '_preview_container');
                        const removeBtn = document.getElementById(fieldName + '_remove_btn');
                        
                        if (input) input.value = '';
                        if (preview) preview.src = '';
                        if (previewContainer) previewContainer.classList.add('hidden');
                        if (removeBtn) removeBtn.classList.add('hidden');
                    }
                }
            });
            
            const itemTypeSelect = document.getElementById('item_type');
            const customItemTypeContainer = document.getElementById('custom_item_type_container');
            const customItemTypeInput = document.getElementById('custom_item_type');
            const itemTypeSubtypeContainer = document.getElementById('item_type_subtype_container');
            const itemTypeSubtypeSelect = document.getElementById('item_type_subtype');
            const itemTypeTagsContainer = document.getElementById('item_type_tags_container');
            const itemTypeTagsCheckboxes = document.getElementById('item_type_tags_checkboxes');
            const gramsContainer = document.getElementById('grams_container');
            const gramsInput = document.getElementById('grams');
            const orcrSerialContainer = document.getElementById('orcr_serial_container');
            const orcrSerialInput = document.getElementById('orcr_serial');
            const maturityDateInput = document.getElementById('maturity_date');
            const expiryDateInput = document.getElementById('expiry_date');
            const loanAmountInput = document.getElementById('loan_amount');
            const interestRateInput = document.getElementById('interest_rate');
            const interestRatePeriodInputs = document.querySelectorAll('input[name="interest_rate_period"]');
            
            // Config values from backend
            const serviceCharge = @json($serviceCharge ?? 0);
            const daysBeforeRedemption = @json($daysBeforeRedemption ?? 90);
            const daysBeforeAuctionSale = @json($daysBeforeAuctionSale ?? 85);
            const auctionSaleDateInput = document.getElementById('auction_sale_date');
            
            console.log('Elements found:', {
                loanAmountInput: !!loanAmountInput,
                interestRateInput: !!interestRateInput,
                principalAmount: !!document.getElementById('principal_amount'),
                interestAmount: !!document.getElementById('interest_amount'),
                netProceedsAmount: !!document.getElementById('net_proceeds_amount')
            });
            
            // Function to handle item type specific fields
            function handleItemTypeSpecificFields(itemTypeName) {
                const isJewelry = itemTypeName === 'Jewelry';
                const isVehicles = itemTypeName === 'Vehicles' || itemTypeName === 'Cars';
                
                // Handle Grams field for Jewelry
                if (isJewelry) {
                    gramsContainer.classList.remove('hidden');
                    gramsInput.required = true;
                } else {
                    gramsContainer.classList.add('hidden');
                    gramsInput.required = false;
                    gramsInput.value = '';
                }
                
                // Handle OR&CR/Serial field for Vehicles
                if (isVehicles) {
                    orcrSerialContainer.classList.remove('hidden');
                    orcrSerialInput.required = true;
                } else {
                    orcrSerialContainer.classList.add('hidden');
                    orcrSerialInput.required = false;
                    orcrSerialInput.value = '';
                }
            }
            
            // Check if "Other" is selected on page load (for validation errors)
            const otherItemType = Array.from(itemTypeSelect.options).find(option => option.text === 'Other');
            if (otherItemType && itemTypeSelect.value === otherItemType.value) {
                customItemTypeContainer.classList.remove('hidden');
                customItemTypeInput.required = true;
            }

            // Check if item type with subtypes or tags is selected on page load
            const selectedOption = itemTypeSelect.options[itemTypeSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const hasSubtypes = selectedOption.getAttribute('data-has-subtypes') === '1';
                const hasTags = selectedOption.getAttribute('data-has-tags') === '1';
                
                if (hasSubtypes) {
                    itemTypeSubtypeContainer.classList.remove('hidden');
                    itemTypeSubtypeSelect.required = true;
                    updateSubtypeDropdown(selectedOption);
                }
                
                if (hasTags) {
                    itemTypeTagsContainer.classList.remove('hidden');
                    updateTagsCheckboxes(selectedOption);
                }
                
                // Handle item type specific fields on page load
                handleItemTypeSpecificFields(selectedOption.text);
            }

            itemTypeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const itemTypeName = selectedOption.text;
                const isOther = itemTypeName === 'Other';
                const hasSubtypes = selectedOption.getAttribute('data-has-subtypes') === '1';
                const hasTags = selectedOption.getAttribute('data-has-tags') === '1';
                
                // Handle "Other" item type
                if (isOther) {
                    customItemTypeContainer.classList.remove('hidden');
                    customItemTypeInput.required = true;
                    itemTypeSubtypeContainer.classList.add('hidden');
                    itemTypeSubtypeSelect.required = false;
                    itemTypeSubtypeSelect.innerHTML = '<option value="">Select a subtype</option>';
                    itemTypeTagsContainer.classList.add('hidden');
                    itemTypeTagsCheckboxes.innerHTML = '';
                } else {
                    customItemTypeContainer.classList.add('hidden');
                    customItemTypeInput.required = false;
                    customItemTypeInput.value = '';
                }
                
                // Handle subtypes
                if (hasSubtypes) {
                    itemTypeSubtypeContainer.classList.remove('hidden');
                    itemTypeSubtypeSelect.required = true;
                    updateSubtypeDropdown(selectedOption);
                } else {
                    itemTypeSubtypeContainer.classList.add('hidden');
                    itemTypeSubtypeSelect.required = false;
                    itemTypeSubtypeSelect.innerHTML = '<option value="">Select a subtype</option>';
                }
                
                // Handle tags
                if (hasTags) {
                    itemTypeTagsContainer.classList.remove('hidden');
                    updateTagsCheckboxes(selectedOption);
                } else {
                    itemTypeTagsContainer.classList.add('hidden');
                    itemTypeTagsCheckboxes.innerHTML = '';
                }
                
                // Handle item type specific fields
                handleItemTypeSpecificFields(itemTypeName);
            });

            function updateSubtypeDropdown(option) {
                const subtypesJson = option.getAttribute('data-subtypes');
                if (subtypesJson) {
                    const subtypes = JSON.parse(subtypesJson);
                    const oldSubtype = @json(old('item_type_subtype'));
                    itemTypeSubtypeSelect.innerHTML = '<option value="">Select a subtype</option>';
                    Object.entries(subtypes).forEach(([id, name]) => {
                        const optionElement = document.createElement('option');
                        optionElement.value = id;
                        optionElement.textContent = name;
                        // Check if this was the old value
                        if (oldSubtype && oldSubtype == id) {
                            optionElement.selected = true;
                        }
                        itemTypeSubtypeSelect.appendChild(optionElement);
                    });
                }
            }

            function updateTagsCheckboxes(option) {
                const tagsJson = option.getAttribute('data-tags');
                if (tagsJson) {
                    const tags = JSON.parse(tagsJson);
                    const oldTags = @json(old('item_type_tags', []));
                    itemTypeTagsCheckboxes.innerHTML = '';
                    Object.entries(tags).forEach(([id, name]) => {
                        const checkboxContainer = document.createElement('label');
                        checkboxContainer.className = 'inline-flex items-center px-4 py-2 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors';
                        
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = 'item_type_tags[]';
                        checkbox.value = id;
                        checkbox.className = 'w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded';
                        
                        // Check if this was the old value
                        if (oldTags && oldTags.includes(parseInt(id))) {
                            checkbox.checked = true;
                        }
                        
                        const label = document.createElement('span');
                        label.className = 'ms-2 text-sm text-gray-700 font-medium';
                        label.textContent = name;
                        
                        checkboxContainer.appendChild(checkbox);
                        checkboxContainer.appendChild(label);
                        itemTypeTagsCheckboxes.appendChild(checkboxContainer);
                    });
                }
            }

            // Dates are readonly for additional items, so no date calculation is needed
            // The dates are already populated from the first transaction

            // Calculate amounts function
            function calculateAmounts() {
                if (!loanAmountInput || !interestRateInput) {
                    console.error('Input elements not found');
                    return;
                }
                
                const principal = parseFloat(loanAmountInput.value) || 0;
                const interestRate = parseFloat(interestRateInput.value) || 0;
                
                // Calculate interest as percentage of principal (no time-based calculation)
                const interest = principal > 0 && interestRate > 0 
                    ? principal * (interestRate / 100) 
                    : 0;
                
                // Net proceeds = principal - (principal * interest) - NO service charge for additional items
                const netProceeds = principal - interest;
                
                // Update display
                const principalAmountEl = document.getElementById('principal_amount');
                const interestAmountEl = document.getElementById('interest_amount');
                const serviceChargeAmountEl = document.getElementById('service_charge_amount');
                const netProceedsAmountEl = document.getElementById('net_proceeds_amount');
                
                if (principalAmountEl) {
                    principalAmountEl.textContent = '₱' + principal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }
                if (interestAmountEl) {
                    interestAmountEl.textContent = '₱' + interest.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }
                if (serviceChargeAmountEl) {
                    serviceChargeAmountEl.innerHTML = '₱0.00 <span class="text-xs">(Already deducted on first item)</span>';
                }
                if (netProceedsAmountEl) {
                    netProceedsAmountEl.textContent = '₱' + Math.max(0, netProceeds).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }
            }

            // Fields are readonly, so no event listeners needed - just calculate on page load
            // calculateAmounts() is called at the end of the script
            
            // Handle grams input to ensure single decimal place (no rounding)
            if (gramsInput) {
                gramsInput.addEventListener('input', function() {
                    let value = this.value;
                    if (value && value.includes('.')) {
                        const parts = value.split('.');
                        if (parts[1] && parts[1].length > 1) {
                            // Truncate to one decimal place (no rounding)
                            this.value = parts[0] + '.' + parts[1].substring(0, 1);
                        }
                    }
                });
            }
            
            // Initial calculation if values exist
            calculateAmounts();

            // Function to compress and resize image
            function compressImage(file, maxWidth, maxHeight, quality) {
                return new Promise(function(resolve, reject) {
                    // For HEIC/HEIF files, we need to handle them specially
                    // iOS Safari should convert them automatically, but let's ensure compatibility
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const img = new Image();
                        img.onload = function() {
                            try {
                                // Calculate new dimensions
                                let width = img.width;
                                let height = img.height;
                                
                                if (width > maxWidth || height > maxHeight) {
                                    if (width > height) {
                                        if (width > maxWidth) {
                                            height = Math.round((height * maxWidth) / width);
                                            width = maxWidth;
                                        }
                                    } else {
                                        if (height > maxHeight) {
                                            width = Math.round((width * maxHeight) / height);
                                            height = maxHeight;
                                        }
                                    }
                                }
                                
                                // Create canvas and resize
                                const canvas = document.createElement('canvas');
                                canvas.width = width;
                                canvas.height = height;
                                const ctx = canvas.getContext('2d');
                                
                                // Use high-quality image rendering
                                ctx.imageSmoothingEnabled = true;
                                ctx.imageSmoothingQuality = 'high';
                                
                                // Draw image to canvas
                                ctx.drawImage(img, 0, 0, width, height);
                                
                                // Convert to blob with compression
                                canvas.toBlob(function(blob) {
                                    if (blob) {
                                        // Create a new File object from the blob
                                        // Use original filename but change extension to .jpg
                                        const fileName = file.name.replace(/\.[^/.]+$/, '') + '.jpg';
                                        const compressedFile = new File([blob], fileName, {
                                            type: 'image/jpeg',
                                            lastModified: Date.now()
                                        });
                                        resolve(compressedFile);
                                    } else {
                                        reject(new Error('Failed to compress image'));
                                    }
                                }, 'image/jpeg', quality);
                            } catch (error) {
                                reject(error);
                            }
                        };
                        img.onerror = function(error) {
                            console.error('Image load error:', error);
                            reject(new Error('Failed to load image. Please try a different image.'));
                        };
                        img.src = event.target.result;
                    };
                    reader.onerror = function(error) {
                        console.error('File read error:', error);
                        reject(new Error('Failed to read file'));
                    };
                    reader.readAsDataURL(file);
                });
            }

            // Handle file selection for image capture components (only item_image for additional items)
            ['item_image'].forEach(function(fieldName) {
                const input = document.getElementById(fieldName + '_input');
                if (input) {
                    // Use both 'change' and 'input' events to catch all file selections
                    function handleFileSelection(e) {
                        const file = e.target.files[0];
                        if (!file) return;
                        
                        console.log('File selected:', {
                            name: file.name,
                            type: file.type,
                            size: (file.size / 1024 / 1024).toFixed(2) + 'MB'
                        });
                        
                        // Accept all image types (including HEIC/HEIF from iPhone)
                        // The browser will handle conversion if needed
                        if (!file.type.startsWith('image/')) {
                            alert('Invalid file type. Please upload an image file.');
                            input.value = '';
                            return;
                        }
                        
                        // Show loading indicator
                        const previewContainer = document.getElementById(fieldName + '_preview_container');
                        if (previewContainer) {
                            previewContainer.innerHTML = '<div class="text-center p-4"><p class="text-sm text-gray-600">Compressing image...</p><p class="text-xs text-gray-500 mt-1">Original: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB</p></div>';
                            previewContainer.classList.remove('hidden');
                        }
                        
                            // Compress image: max 1280x1280, quality 0.75 (more aggressive compression)
                            compressImage(file, 1280, 1280, 0.75)
                            .then(function(compressedFile) {
                                console.log('Compression complete:', {
                                    original: (file.size / 1024 / 1024).toFixed(2) + 'MB',
                                    compressed: (compressedFile.size / 1024 / 1024).toFixed(2) + 'MB',
                                    reduction: ((1 - compressedFile.size / file.size) * 100).toFixed(1) + '%'
                                });
                                
                                // Replace the original file with compressed version
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(compressedFile);
                                input.files = dataTransfer.files;
                                
                                // Validate compressed file size (5MB = 5 * 1024 * 1024 bytes)
                                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                                if (compressedFile.size > maxSize) {
                                    alert('Image is still too large after compression (' + (compressedFile.size / 1024 / 1024).toFixed(2) + 'MB). Please try a different image.');
                                    input.value = '';
                                    
                                    // Hide preview
                                    const preview = document.getElementById(fieldName + '_preview');
                                    const previewContainer = document.getElementById(fieldName + '_preview_container');
                                    const removeBtn = document.getElementById(fieldName + '_remove_btn');
                                    
                                    if (preview) preview.src = '';
                                    if (previewContainer) previewContainer.classList.add('hidden');
                                    if (removeBtn) removeBtn.classList.add('hidden');
                                    
                                    return;
                                }
                                
                                // Show preview
                                const reader = new FileReader();
                                reader.onload = function(event) {
                                    const preview = document.getElementById(fieldName + '_preview');
                                    const previewContainer = document.getElementById(fieldName + '_preview_container');
                                    const removeBtn = document.getElementById(fieldName + '_remove_btn');
                                    
                                    if (previewContainer) {
                                        previewContainer.innerHTML = '<img id="' + fieldName + '_preview" src="' + event.target.result + '" alt="Preview" class="w-full h-auto rounded-lg border-2 border-gray-300 object-cover max-h-64">';
                                    }
                                    if (preview) preview.src = event.target.result;
                                    if (previewContainer) previewContainer.classList.remove('hidden');
                                    if (removeBtn) removeBtn.classList.remove('hidden');
                                };
                                reader.readAsDataURL(compressedFile);
                            })
                            .catch(function(error) {
                                console.error('Error compressing image:', error);
                                alert('Error processing image: ' + error.message + '. Please try again.');
                                input.value = '';
                                
                                // Hide preview
                                const preview = document.getElementById(fieldName + '_preview');
                                const previewContainer = document.getElementById(fieldName + '_preview_container');
                                const removeBtn = document.getElementById(fieldName + '_remove_btn');
                                
                                if (preview) preview.src = '';
                                if (previewContainer) previewContainer.classList.add('hidden');
                                if (removeBtn) removeBtn.classList.add('hidden');
                            });
                    }
                    
                    // Attach to both change and input events to catch all file selections
                    input.addEventListener('change', handleFileSelection);
                    input.addEventListener('input', handleFileSelection);
                }
            });
            
            // Add form submission validation
            const form = document.querySelector('form[action*="sangla"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let hasError = false;
                    const errorMessages = [];
                    
                    // Check all image inputs (only item_image for additional items)
                    ['item_image'].forEach(function(fieldName) {
                        const input = document.getElementById(fieldName + '_input');
                        if (input && input.files.length > 0) {
                            const file = input.files[0];
                            const maxSize = 5 * 1024 * 1024; // 5MB
                            
                            if (file.size > maxSize) {
                                hasError = true;
                                errorMessages.push(fieldName.replace(/_/g, ' ') + ' exceeds 5MB limit');
                            }
                            
                            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                            if (!allowedTypes.includes(file.type)) {
                                hasError = true;
                                errorMessages.push(fieldName.replace(/_/g, ' ') + ' must be a JPEG or PNG image');
                            }
                        }
                    });
                    
                    if (hasError) {
                        e.preventDefault();
                        alert('Please fix the following errors:\n\n' + errorMessages.join('\n'));
                        return false;
                    }
                });
            }
        });
    </script>

</x-app-layout>

