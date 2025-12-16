<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Renew Transaction
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
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

                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm text-blue-800">
                            <strong>Pawn Ticket Number:</strong> {{ $pawnTicketNumber }}
                        </p>
                        <p class="text-sm text-blue-700 mt-1">
                            <strong>Found {{ $transactions->count() }} transaction(s)</strong> - All transactions with this pawn ticket number will be renewed.
                        </p>
                    </div>

                    <!-- Interest Payment Summary -->
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <h3 class="text-lg font-semibold text-yellow-900 mb-3">Interest Payment Required</h3>
                        <div class="space-y-2">
                            @foreach ($transactions as $transaction)
                                @php
                                    $interest = (float) $transaction->loan_amount * ((float) $transaction->interest_rate / 100);
                                @endphp
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-yellow-800">
                                        {{ $transaction->transaction_number }} (₱{{ number_format($transaction->loan_amount, 2) }} × {{ $transaction->interest_rate }}%)
                                    </span>
                                    <span class="font-medium text-yellow-900">₱{{ number_format($interest, 2) }}</span>
                                </div>
                            @endforeach
                            <div class="border-t border-yellow-300 pt-2 mt-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-yellow-900">Total Interest to Pay:</span>
                                    <span class="text-lg font-bold text-yellow-900">₱{{ number_format($totalInterest, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Display Transaction Details -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Transaction Details</h3>
                        <div class="space-y-4">
                            @foreach ($transactions as $transaction)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-600">Transaction Number</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $transaction->transaction_number }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Pawner</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $transaction->first_name }} {{ $transaction->last_name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Item Description</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $transaction->item_description }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Loan Amount</p>
                                            <p class="text-sm font-medium text-gray-900">₱{{ number_format($transaction->loan_amount, 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Interest Rate</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $transaction->interest_rate }}% ({{ $transaction->interest_rate_period === 'per_annum' ? 'Per Annum' : ($transaction->interest_rate_period === 'per_month' ? 'Per Month' : 'Other') }})</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Interest Amount</p>
                                            <p class="text-sm font-medium text-gray-900">₱{{ number_format((float) $transaction->loan_amount * ((float) $transaction->interest_rate / 100), 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Current Maturity Date</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $transaction->maturity_date->format('M d, Y') }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Current Expiry Date</p>
                                            <p class="text-sm font-medium text-gray-900">{{ $transaction->expiry_date->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <form method="POST" action="{{ route('transactions.renewal.store') }}">
                        @csrf
                        <input type="hidden" name="pawn_ticket_number" value="{{ $pawnTicketNumber }}">

                        <div class="space-y-6">
                            <!-- Maturity Date -->
                            <div>
                                <x-input-label for="maturity_date" value="New Maturity Date" />
                                <x-text-input 
                                    id="maturity_date" 
                                    name="maturity_date" 
                                    type="date" 
                                    class="mt-1 block w-full" 
                                    :value="old('maturity_date', $defaultMaturityDate)" 
                                    required 
                                />
                                <x-input-error :messages="$errors->get('maturity_date')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">The new maturity date for the renewed transaction.</p>
                            </div>

                            <!-- Expiry Date of Redemption -->
                            <div>
                                <x-input-label for="expiry_date" value="New Expiry Date of Redemption" />
                                <x-text-input 
                                    id="expiry_date" 
                                    name="expiry_date" 
                                    type="date" 
                                    class="mt-1 block w-full" 
                                    :value="old('expiry_date')" 
                                    required 
                                />
                                <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">This will be auto-calculated based on maturity date + {{ $daysBeforeRedemption }} days.</p>
                            </div>

                            <!-- Auction Sale Date -->
                            <div>
                                <x-input-label for="auction_sale_date" value="New Auction Sale Date" />
                                <x-text-input 
                                    id="auction_sale_date" 
                                    name="auction_sale_date" 
                                    type="date" 
                                    class="mt-1 block w-full" 
                                    :value="old('auction_sale_date')" 
                                />
                                <x-input-error :messages="$errors->get('auction_sale_date')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">This will be auto-calculated based on expiry date + {{ $daysBeforeAuctionSale }} days.</p>
                            </div>

                            <!-- Interest Amount (Readonly, calculated) -->
                            <div>
                                <x-input-label for="interest_amount" value="Interest Amount to Pay" />
                                <x-text-input 
                                    id="interest_amount" 
                                    name="interest_amount" 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    class="mt-1 block w-full bg-gray-100" 
                                    :value="old('interest_amount', number_format($totalInterest, 2, '.', ''))" 
                                    required 
                                    readonly
                                />
                                <x-input-error :messages="$errors->get('interest_amount')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">This is the total interest amount calculated from all transactions. The pawner will pay this amount to renew.</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <a href="{{ route('transactions.renewal.search') }}" class="text-gray-600 hover:text-gray-900 font-medium">
                                Cancel
                            </a>
                            <x-primary-button>
                                Renew Transaction(s)
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const maturityDateInput = document.getElementById('maturity_date');
            const expiryDateInput = document.getElementById('expiry_date');
            const auctionSaleDateInput = document.getElementById('auction_sale_date');
            
            const daysBeforeRedemption = {{ $daysBeforeRedemption }};
            const daysBeforeAuctionSale = {{ $daysBeforeAuctionSale }};

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            maturityDateInput.setAttribute('min', today);
            expiryDateInput.setAttribute('min', today);
            if (auctionSaleDateInput) {
                auctionSaleDateInput.setAttribute('min', today);
            }

            // Calculate dates when maturity date changes
            function calculateDatesFromMaturity() {
                const maturityDate = maturityDateInput.value;
                if (maturityDate) {
                    const maturity = new Date(maturityDate);
                    
                    // Calculate expiry redemption date: maturity date + days before redemption
                    const expiryDate = new Date(maturity);
                    expiryDate.setDate(expiryDate.getDate() + daysBeforeRedemption);
                    const expiryDateStr = expiryDate.toISOString().split('T')[0];
                    
                    // Calculate auction sale date: expiry redemption date + days before auction sale
                    const auctionDate = new Date(expiryDate);
                    auctionDate.setDate(auctionDate.getDate() + daysBeforeAuctionSale);
                    const auctionDateStr = auctionDate.toISOString().split('T')[0];
                    
                    // Update expiry date
                    expiryDateInput.setAttribute('min', maturityDate);
                    expiryDateInput.value = expiryDateStr;
                    
                    // Update auction sale date
                    if (auctionSaleDateInput) {
                        auctionSaleDateInput.setAttribute('min', expiryDateStr);
                        auctionSaleDateInput.value = auctionDateStr;
                    }
                }
            }

            // Update expiry date minimum when maturity date changes
            maturityDateInput.addEventListener('change', function() {
                calculateDatesFromMaturity();
            });

            // Update auction sale date minimum when expiry date changes manually
            expiryDateInput.addEventListener('change', function() {
                const expiryDate = this.value;
                if (expiryDate && auctionSaleDateInput) {
                    // Calculate auction sale date: expiry redemption date + days before auction sale
                    const expiry = new Date(expiryDate);
                    const auctionDate = new Date(expiry);
                    auctionDate.setDate(auctionDate.getDate() + daysBeforeAuctionSale);
                    const auctionDateStr = auctionDate.toISOString().split('T')[0];
                    
                    auctionSaleDateInput.setAttribute('min', expiryDate);
                    // Only auto-update if auction date is empty or before the new expiry date
                    if (!auctionSaleDateInput.value || auctionSaleDateInput.value < expiryDate) {
                        auctionSaleDateInput.value = auctionDateStr;
                    }
                }
            });

            // Calculate dates on page load if maturity date is set
            if (maturityDateInput.value) {
                calculateDatesFromMaturity();
            }
        });
    </script>
</x-app-layout>

