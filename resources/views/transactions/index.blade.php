<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Transactions
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

                    <!-- Filters Section -->
                    <form method="GET" action="{{ route('transactions.index') }}" class="mb-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Search -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input 
                                    type="text" 
                                    id="search" 
                                    name="search" 
                                    value="{{ $filters['search'] }}"
                                    placeholder="Item, name, or transaction #"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                            </div>

                            @if(auth()->user()->isAdminOrSuperAdmin())
                                <!-- Date Filter -->
                                <div>
                                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                    <input 
                                        type="date" 
                                        id="date" 
                                        name="date" 
                                        value="{{ $filters['date'] }}"
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                </div>

                                <!-- Today Only Button -->
                                <div class="flex items-end">
                                    <button
                                        type="submit"
                                        name="today_only"
                                        value="1"
                                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 text-sm font-medium transition-colors">
                                        Today Only
                                    </button>
                                </div>

                                <!-- Branch Filter -->
                                @if($branches && $branches->count() > 1)
                                    <div>
                                        <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                        <select 
                                            id="branch_id" 
                                            name="branch_id" 
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        >
                                            <option value="">All Branches</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ $filters['branch_id'] == $branch->id ? 'selected' : '' }}>
                                                    {{ $branch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            @endif

                            <!-- Apply/Reset Buttons -->
                            <div class="flex items-end gap-2">
                                <button
                                    type="submit"
                                    class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 text-sm font-medium transition-colors">
                                    Apply Filters
                                </button>
                                <a
                                    href="{{ route('transactions.index') }}"
                                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 text-sm font-medium transition-colors text-center">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Transactions Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Pawn Ticket #
                                        <br/> 
                                        Transaction #
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pawner</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">By</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($transactions as $transaction)
                                    <tr 
                                        class="hover:bg-gray-50 transition-colors cursor-pointer transaction-row"
                                        data-item-image="{{ route('images.show', ['path' => $transaction->item_image_path]) }}"
                                        data-pawner-image="{{ route('images.show', ['path' => $transaction->pawner_id_image_path]) }}"
                                        data-pawn-ticket-image="{{ $transaction->pawn_ticket_image_path ? route('images.show', ['path' => $transaction->pawn_ticket_image_path]) : '' }}"
                                        data-transaction-number="{{ $transaction->transaction_number }}"
                                        data-maturity-date="{{ $transaction->maturity_date ? $transaction->maturity_date->format('M d, Y') : '' }}"
                                        data-expiry-date="{{ $transaction->expiry_date ? $transaction->expiry_date->format('M d, Y') : '' }}"
                                        data-auction-sale-date="{{ $transaction->auction_sale_date ? $transaction->auction_sale_date->format('M d, Y') : '' }}"
                                        data-loan-amount="{{ number_format($transaction->loan_amount, 2) }}"
                                        data-interest-rate="{{ number_format($transaction->interest_rate, 2) }}"
                                        data-service-charge="{{ number_format($transaction->service_charge, 2) }}"
                                        data-net-proceeds="{{ number_format($transaction->net_proceeds, 2) }}"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $transaction->pawn_ticket_number }}</div>
                                            <div class="text-xs text-gray-500">Pawn Ticket #</div>
                                            <div class="text-sm mt-4 font-medium text-gray-900">{{ $transaction->transaction_number }}</div>
                                            <div class="text-xs text-gray-500">Transaction #</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $transaction->created_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $transaction->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $transaction->pawner_name }}</div>
                                            <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($transaction->address, 30) }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $transaction->itemType->name }}
                                                @if($transaction->itemTypeSubtype)
                                                    <span class="text-gray-500">- {{ $transaction->itemTypeSubtype->name }}</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($transaction->item_description, 40) }}</div>
                                            @if($transaction->tags->count() > 0)
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach($transaction->tags as $tag)
                                                        <span class="inline-block px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded">
                                                            {{ $tag->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $transaction->type === 'sangla' ? 'bg-blue-100 text-blue-800' : 
                                                   ($transaction->type === 'tubos' ? 'bg-green-100 text-green-800' : 
                                                   ($transaction->type === 'renew' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800')) }}">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">₱{{ number_format($transaction->loan_amount, 2) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $transaction->branch->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $transaction->user->name }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                            @if(auth()->user()->isStaff())
                                                No transactions found for today.
                                            @else
                                                No transactions found.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $transactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <dialog id="transactionImagesModal" class="rounded-lg p-0 w-[90vw] max-w-6xl backdrop:bg-black/50">
        <div class="bg-white rounded-lg max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 sticky top-0 bg-white z-10">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Transaction Details - <span id="modalTransactionNumber" class="text-indigo-600"></span>
                    </h3>
                    <button
                        type="button"
                        onclick="closeTransactionImagesModal()"
                        class="text-gray-400 hover:text-gray-500 focus:outline-none"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <!-- Transaction Details Section -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Transaction Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Maturity Date</p>
                            <p id="modalMaturityDate" class="text-sm font-medium text-gray-900 mt-1">-</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Expiry Redemption Date</p>
                            <p id="modalExpiryDate" class="text-sm font-medium text-gray-900 mt-1">-</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Auction Sale Date</p>
                            <p id="modalAuctionSaleDate" class="text-sm font-medium text-gray-900 mt-1">-</p>
                        </div>
                    </div>
                </div>

                <!-- Transaction Summary Section -->
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Transaction Summary</h4>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Principal</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right" id="modalPrincipal">₱0.00</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Interest</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right" id="modalInterest">₱0.00</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Service Charge</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right" id="modalServiceCharge">₱0.00</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">Net Proceeds</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right" id="modalNetProceeds">₱0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Images Section -->
                <div>
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Images</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Item Image -->
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Item Image</h5>
                            <div class="border-2 border-gray-200 rounded-lg overflow-hidden">
                                <img 
                                    id="modalItemImage" 
                                    src="" 
                                    alt="Item Image" 
                                    class="w-full h-auto object-contain max-h-64"
                                />
                            </div>
                        </div>
                        
                        <!-- Pawner ID Image -->
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Pawner ID/Photo</h5>
                            <div class="border-2 border-gray-200 rounded-lg overflow-hidden">
                                <img 
                                    id="modalPawnerImage" 
                                    src="" 
                                    alt="Pawner ID Image" 
                                    class="w-full h-auto object-contain max-h-64"
                                />
                            </div>
                        </div>
                        
                        <!-- Pawn Ticket Image -->
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Pawn Ticket</h5>
                            <div class="border-2 border-gray-200 rounded-lg overflow-hidden">
                                <img 
                                    id="modalPawnTicketImage" 
                                    src="" 
                                    alt="Pawn Ticket Image" 
                                    class="w-full h-auto object-contain max-h-64"
                                />
                                <div id="modalPawnTicketPlaceholder" class="hidden p-8 text-center text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm">No image available</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end sticky bottom-0">
                <button
                    type="button"
                    onclick="closeTransactionImagesModal()"
                    class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Close
                </button>
            </div>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handler to transaction rows
            document.querySelectorAll('.transaction-row').forEach(function(row) {
                row.addEventListener('click', function(e) {
                    // Don't trigger if clicking on a link, button, or other interactive element
                    if (e.target.closest('a, button, input, select, textarea')) {
                        return;
                    }
                    
                    const itemImageUrl = this.getAttribute('data-item-image');
                    const pawnerImageUrl = this.getAttribute('data-pawner-image');
                    const pawnTicketImageUrl = this.getAttribute('data-pawn-ticket-image');
                    const transactionNumber = this.getAttribute('data-transaction-number');
                    const maturityDate = this.getAttribute('data-maturity-date');
                    const expiryDate = this.getAttribute('data-expiry-date');
                    const auctionSaleDate = this.getAttribute('data-auction-sale-date');
                    const loanAmount = this.getAttribute('data-loan-amount');
                    const interestRate = this.getAttribute('data-interest-rate');
                    const serviceCharge = this.getAttribute('data-service-charge');
                    const netProceeds = this.getAttribute('data-net-proceeds');
                    
                    showTransactionDetails({
                        itemImageUrl,
                        pawnerImageUrl,
                        pawnTicketImageUrl,
                        transactionNumber,
                        maturityDate,
                        expiryDate,
                        auctionSaleDate,
                        loanAmount,
                        interestRate,
                        serviceCharge,
                        netProceeds
                    });
                });
            });
        });

        function showTransactionDetails(data) {
            const modal = document.getElementById('transactionImagesModal');
            
            // Set transaction number
            document.getElementById('modalTransactionNumber').textContent = data.transactionNumber;
            
            // Set dates
            document.getElementById('modalMaturityDate').textContent = data.maturityDate || '-';
            document.getElementById('modalExpiryDate').textContent = data.expiryDate || '-';
            document.getElementById('modalAuctionSaleDate').textContent = data.auctionSaleDate || '-';
            
            // Calculate and set transaction summary
            const principal = parseFloat(data.loanAmount.replace(/,/g, '')) || 0;
            const interestRate = parseFloat(data.interestRate) || 0;
            const serviceCharge = parseFloat(data.serviceCharge.replace(/,/g, '')) || 0;
            const interest = principal * (interestRate / 100);
            const netProceeds = parseFloat(data.netProceeds.replace(/,/g, '')) || 0;
            
            document.getElementById('modalPrincipal').textContent = '₱' + principal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            document.getElementById('modalInterest').textContent = '₱' + interest.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            document.getElementById('modalServiceCharge').textContent = '₱' + serviceCharge.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            document.getElementById('modalNetProceeds').textContent = '₱' + netProceeds.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            
            // Set images
            document.getElementById('modalItemImage').src = data.itemImageUrl;
            document.getElementById('modalPawnerImage').src = data.pawnerImageUrl;
            
            // Handle pawn ticket image (may not exist)
            const pawnTicketImage = document.getElementById('modalPawnTicketImage');
            const pawnTicketPlaceholder = document.getElementById('modalPawnTicketPlaceholder');
            
            if (data.pawnTicketImageUrl && data.pawnTicketImageUrl.trim() !== '') {
                pawnTicketImage.src = data.pawnTicketImageUrl;
                pawnTicketImage.classList.remove('hidden');
                pawnTicketPlaceholder.classList.add('hidden');
            } else {
                pawnTicketImage.classList.add('hidden');
                pawnTicketPlaceholder.classList.remove('hidden');
            }
            
            modal.showModal();
        }

        function closeTransactionImagesModal() {
            document.getElementById('transactionImagesModal').close();
        }

        // Close modal when clicking outside
        document.getElementById('transactionImagesModal').addEventListener('click', function(event) {
            if (event.target === this) {
                this.close();
            }
        });
    </script>
</x-app-layout>

