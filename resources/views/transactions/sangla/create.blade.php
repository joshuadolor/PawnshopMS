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

                    <form method="POST" action="{{ route('transactions.sangla.store') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Branch Selection (only show if user has multiple branches) -->
                        @if($showBranchSelection)
                            <div class="mb-6">
                                <x-input-label for="branch_id" value="Branch" />
                                <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Select a branch</option>
                                    @foreach($userBranches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                            </div>
                        @else
                            <!-- Hidden input for single branch -->
                            @if($userBranches->count() === 1)
                                <input type="hidden" name="branch_id" value="{{ $userBranches->first()->id }}">
                            @endif
                        @endif

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
                            <div class="mt-2 flex flex-wrap gap-2">
                                @php
                                    $selectedPeriod = old('interest_rate_period', $interestPeriod);
                                @endphp
                                <label class="inline-flex items-center px-4 py-2 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors">
                                    <input type="radio" name="interest_rate_period" value="per_annum" {{ $selectedPeriod === 'per_annum' ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500" required>
                                    <span class="ms-2 text-sm text-gray-700 font-medium">Per Annum</span>
                                </label>
                                <label class="inline-flex items-center px-4 py-2 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors">
                                    <input type="radio" name="interest_rate_period" value="per_month" {{ $selectedPeriod === 'per_month' ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500" required>
                                    <span class="ms-2 text-sm text-gray-700 font-medium">Per Month</span>
                                </label>
                                <label class="inline-flex items-center px-4 py-2 border-2 border-gray-300 rounded-lg hover:bg-gray-50 active:bg-gray-100 cursor-pointer touch-manipulation transition-colors">
                                    <input type="radio" name="interest_rate_period" value="others" {{ $selectedPeriod === 'others' ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500" required>
                                    <span class="ms-2 text-sm text-gray-700 font-medium">Others</span>
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

                        <!-- Auction Sale Date -->
                        <div class="mt-4">
                            <x-input-label for="auction_sale_date" value="Auction Sale Date" />
                            <x-text-input 
                                id="auction_sale_date" 
                                name="auction_sale_date" 
                                type="date" 
                                class="mt-1 block w-full" 
                                :value="old('auction_sale_date')" 
                            />
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

                        <!-- Pawner ID/Photo -->
                        <x-image-capture 
                            name="pawner_id_image" 
                            label="Pawner ID/Photo" 
                            :value="old('pawner_id_image')" 
                        />

                        <!-- Pawn Ticket No. -->
                        <div class="mt-4">
                            <x-input-label for="pawn_ticket_number" value="Pawn Ticket No." />
                            <x-text-input 
                                id="pawn_ticket_number" 
                                name="pawn_ticket_number" 
                                type="text" 
                                class="mt-1 block w-full" 
                                :value="old('pawn_ticket_number')" 
                                required 
                            />
                            <x-input-error :messages="$errors->get('pawn_ticket_number')" class="mt-2" />
                        </div>

                        <!-- Pawn Ticket Image -->
                        <x-image-capture 
                            name="pawn_ticket_image" 
                            label="Pawn Ticket Image" 
                            :value="old('pawn_ticket_image')" 
                        />

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
        // Image capture functions - use event delegation to avoid timing issues
        document.addEventListener('DOMContentLoaded', function() {
            // Handle image capture button clicks using event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.image-capture-btn')) {
                    const btn = e.target.closest('.image-capture-btn');
                    const action = btn.getAttribute('data-action');
                    const fieldName = btn.getAttribute('data-field');
                    
                    if (action === 'camera') {
                        const input = document.getElementById(fieldName + '_input');
                        if (input) {
                            input.setAttribute('capture', 'environment');
                            input.click();
                        }
                    } else if (action === 'select') {
                        const input = document.getElementById(fieldName + '_input');
                        if (input) {
                            input.removeAttribute('capture');
                            input.click();
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
        });

        document.addEventListener('DOMContentLoaded', function() {
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
            const serviceCharge = {{ $serviceCharge }};
            
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
            if (selectedOption) {
                if (selectedOption.getAttribute('data-has-subtypes') === '1') {
                    updateSubtypeDropdown(selectedOption);
                }
                if (selectedOption.getAttribute('data-has-tags') === '1') {
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
                    const oldTags = {{ json_encode(old('item_type_tags', [])) }};
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
            });

            // Calculate amounts function
            function calculateAmounts() {
                const principal = parseFloat(loanAmountInput.value) || 0;
                const interestRate = parseFloat(interestRateInput.value) || 0;
                
                // Calculate interest as percentage of principal (no time-based calculation)
                const interest = principal > 0 && interestRate > 0 
                    ? principal * (interestRate / 100) 
                    : 0;
                
                // Net proceeds = principal - (principal * interest) - service charge
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
            
            // Handle grams input to ensure single decimal place (no rounding)
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
            
            // Initial calculation if values exist
            calculateAmounts();

            // Handle file selection for image capture components
            ['item_image', 'pawner_id_image', 'pawn_ticket_image'].forEach(function(fieldName) {
                const input = document.getElementById(fieldName + '_input');
                if (input) {
                    input.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            // Validate file size (5MB = 5 * 1024 * 1024 bytes)
                            const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                            if (file.size > maxSize) {
                                alert('File size exceeds 5MB limit. Please choose a smaller image.');
                                this.value = ''; // Clear the input
                                
                                // Hide preview if it exists
                                const preview = document.getElementById(fieldName + '_preview');
                                const previewContainer = document.getElementById(fieldName + '_preview_container');
                                const removeBtn = document.getElementById(fieldName + '_remove_btn');
                                
                                if (preview) preview.src = '';
                                if (previewContainer) previewContainer.classList.add('hidden');
                                if (removeBtn) removeBtn.classList.add('hidden');
                                
                                return;
                            }
                            
                            // Validate file type
                            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                            if (!allowedTypes.includes(file.type)) {
                                alert('Invalid file type. Please upload a JPEG or PNG image.');
                                this.value = '';
                                return;
                            }
                            
                            const reader = new FileReader();
                            reader.onload = function(event) {
                                const preview = document.getElementById(fieldName + '_preview');
                                const previewContainer = document.getElementById(fieldName + '_preview_container');
                                const removeBtn = document.getElementById(fieldName + '_remove_btn');
                                
                                if (preview) preview.src = event.target.result;
                                if (previewContainer) previewContainer.classList.remove('hidden');
                                if (removeBtn) removeBtn.classList.remove('hidden');
                            };
                            reader.onerror = function() {
                                alert('Error reading file. Please try again.');
                                input.value = '';
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            });
            
            // Add form submission validation
            const form = document.querySelector('form[action*="sangla.store"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let hasError = false;
                    const errorMessages = [];
                    
                    // Check all image inputs
                    ['item_image', 'pawner_id_image', 'pawn_ticket_image'].forEach(function(fieldName) {
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

