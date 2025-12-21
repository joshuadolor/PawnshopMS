<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            TEST - Update Transaction Dates
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p class="text-sm text-yellow-800 font-medium">
                            ⚠️ TEST PAGE - This page is for testing purposes only and will be removed before production.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pawn Ticket #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Maturity Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auction Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($transactions as $transaction)
                                    <tr class="{{ $transaction->isVoided() ? 'opacity-40' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $transaction->transaction_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction->pawn_ticket_number }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div class="max-w-xs truncate">
                                                {{ $transaction->itemType->name }}
                                                @if($transaction->itemTypeSubtype)
                                                    - {{ $transaction->itemTypeSubtype->name }}
                                                @endif
                                                @if($transaction->custom_item_type)
                                                    - {{ $transaction->custom_item_type }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction->branch->name }}
                                        </td>
                                        @php
                                            // Get the latest child transaction for this pawn ticket, or use the Sangla transaction
                                            $transactionForDates = $transaction->pawn_ticket_number && isset($latestChildTransactions[$transaction->pawn_ticket_number])
                                                ? $latestChildTransactions[$transaction->pawn_ticket_number]
                                                : $transaction;
                                        @endphp
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transactionForDates->maturity_date ? $transactionForDates->maturity_date->format('M d, Y') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transactionForDates->expiry_date ? $transactionForDates->expiry_date->format('M d, Y') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transactionForDates->auction_sale_date ? $transactionForDates->auction_sale_date->format('M d, Y') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $hasActiveTubos = $transaction->pawn_ticket_number && 
                                                    $activeTubosForPawnTickets->contains($transaction->pawn_ticket_number);
                                            @endphp
                                            @if($transaction->isVoided())
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Voided
                                                </span>
                                            @elseif($hasActiveTubos)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                    Completed
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @php
                                                // Get the latest child transaction for this pawn ticket, or use the Sangla transaction
                                                $transactionForDates = $transaction->pawn_ticket_number && isset($latestChildTransactions[$transaction->pawn_ticket_number])
                                                    ? $latestChildTransactions[$transaction->pawn_ticket_number]
                                                    : $transaction;
                                            @endphp
                                            <button
                                                onclick="openEditModal({{ $transaction->id }}, '{{ $transaction->transaction_number }}', '{{ $transactionForDates->maturity_date ? $transactionForDates->maturity_date->format('Y-m-d') : '' }}', '{{ $transactionForDates->expiry_date ? $transactionForDates->expiry_date->format('Y-m-d') : '' }}', '{{ $transactionForDates->auction_sale_date ? $transactionForDates->auction_sale_date->format('Y-m-d') : '' }}')"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Edit Dates
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No Sangla transactions found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $transactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Dates Modal -->
    <dialog id="editDatesModal" class="rounded-lg p-0 w-[90vw] max-w-2xl backdrop:bg-black/50">
        <div class="bg-white rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Update Dates - Transaction #<span id="modalTransactionNumber"></span>
                    </h3>
                    <button
                        type="button"
                        onclick="closeEditModal()"
                        class="text-gray-400 hover:text-gray-500 focus:outline-none"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <form id="editDatesForm" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <x-input-label for="maturity_date" value="Maturity Date *" />
                        <x-text-input 
                            id="maturity_date" 
                            name="maturity_date" 
                            type="date" 
                            class="mt-1 block w-full" 
                            required
                        />
                        <x-input-error :messages="$errors->get('maturity_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="expiry_date" value="Expiry Redemption Date *" />
                        <x-text-input 
                            id="expiry_date" 
                            name="expiry_date" 
                            type="date" 
                            class="mt-1 block w-full" 
                            required
                        />
                        <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="auction_sale_date" value="Auction Sale Date" />
                        <x-text-input 
                            id="auction_sale_date" 
                            name="auction_sale_date" 
                            type="date" 
                            class="mt-1 block w-full" 
                        />
                        <x-input-error :messages="$errors->get('auction_sale_date')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6 gap-4">
                    <button
                        type="button"
                        onclick="closeEditModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <x-primary-button>
                        Update Dates
                    </x-primary-button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        function openEditModal(transactionId, transactionNumber, maturityDate, expiryDate, auctionDate) {
            const modal = document.getElementById('editDatesModal');
            const form = document.getElementById('editDatesForm');
            const transactionNumberEl = document.getElementById('modalTransactionNumber');
            
            // Set transaction number
            transactionNumberEl.textContent = transactionNumber;
            
            // Set form action
            form.action = `/test/transactions/${transactionId}/dates`;
            
            // Set date values
            document.getElementById('maturity_date').value = maturityDate || '';
            document.getElementById('expiry_date').value = expiryDate || '';
            document.getElementById('auction_sale_date').value = auctionDate || '';
            
            // Remove any existing min date restrictions to allow selecting old dates
            document.getElementById('maturity_date').removeAttribute('min');
            document.getElementById('expiry_date').removeAttribute('min');
            document.getElementById('auction_sale_date').removeAttribute('min');
            
            // Auto-set min dates for relative validation (expiry >= maturity, auction >= expiry)
            // but allow past dates
            document.getElementById('maturity_date').addEventListener('change', function() {
                const maturityDateInput = this;
                const expiryDateInput = document.getElementById('expiry_date');
                if (maturityDateInput.value) {
                    expiryDateInput.setAttribute('min', maturityDateInput.value);
                } else {
                    expiryDateInput.removeAttribute('min');
                }
            });
            
            // Auto-set min dates for relative validation
            document.getElementById('expiry_date').addEventListener('change', function() {
                const expiryDateInput = this;
                const auctionDateInput = document.getElementById('auction_sale_date');
                if (expiryDateInput.value) {
                    auctionDateInput.setAttribute('min', expiryDateInput.value);
                } else {
                    auctionDateInput.removeAttribute('min');
                }
            });
            
            modal.showModal();
        }

        function closeEditModal() {
            const modal = document.getElementById('editDatesModal');
            modal.close();
        }
    </script>
</x-app-layout>

