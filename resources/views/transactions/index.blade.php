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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">By</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php
                                    // Group transactions by pawn ticket number
                                    // Transactions without pawn_ticket_number will be in a null group
                                    $transactionsByPawnTicket = $transactions->groupBy(function($transaction) {
                                        return $transaction->pawn_ticket_number ?? 'no-pawn-ticket-' . $transaction->id;
                                    });
                                @endphp
                                @forelse ($transactionsByPawnTicket as $pawnTicketKey => $pawnTicketTransactions)
                                    @php
                                        $pawnTicketNumber = $pawnTicketTransactions->first()->pawn_ticket_number;
                                        // Separate sangla and renewal transactions from current page
                                        $sanglaTransactions = $pawnTicketTransactions->where('type', 'sangla')->sortByDesc('created_at');
                                        $renewalsFromPage = $pawnTicketTransactions->where('type', 'renew');
                                        
                                        // Also get renewals from the additional collection (for renewals on different pages)
                                        $renewalsFromCollection = ($pawnTicketNumber && isset($renewalsForPawnTickets)) 
                                            ? $renewalsForPawnTickets->where('pawn_ticket_number', $pawnTicketNumber)
                                            : collect();
                                        
                                        // Merge and deduplicate renewals
                                        $renewalTransactions = $renewalsFromPage->concat($renewalsFromCollection)
                                            ->unique('id')
                                            ->sortByDesc('created_at');
                                        
                                        // Get first transaction for pawner info (for header)
                                        $firstTransaction = $sanglaTransactions->first() ?? $pawnTicketTransactions->first();
                                    @endphp

                                    @if($pawnTicketNumber)
                                        @php
                                            // Use the oldest transaction's values (not sum)
                                            $oldestSanglaTransaction = $sanglaTransactions->sortBy('created_at')->first();
                                            $principal = $oldestSanglaTransaction ? $oldestSanglaTransaction->loan_amount : 0;
                                            $netProceeds = $oldestSanglaTransaction ? $oldestSanglaTransaction->net_proceeds : 0;
                                        @endphp
                                        {{-- Pawn Ticket Header Row --}}
                                        <tr class="bg-violet-100 border-t-2 border-gray-300">
                                            <td colspan="4" class="px-6 py-3">
                                                <div class="flex  flex-col justify-between">
                                                    <div>
                                                        <span class="text-sm font-bold text-gray-900">PAWN TICKET #{{ $pawnTicketNumber }}</span>
                                                    </div>
                                                    <span class="text-sm text-gray-600">{{ $firstTransaction->pawner_name }}</span>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $sanglaTransactions->count() }} Item(s) 
                                                        @if($renewalTransactions->count() > 0)
                                                            • {{ $renewalTransactions->count() }} Renewal(s)
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="text-xs text-gray-500">Principal:</div>
                                                <div class="text-sm font-medium text-gray-900">₱{{ number_format($principal, 2) }}</div>
                                                <div class="mt-2 text-xs text-gray-500">Net Proceeds:</div>
                                                <div class="text-sm text-red-700 font-medium">₱{{ number_format($netProceeds, 2) }}</div>
                                            </td>
                                            <td class="px-6 py-3"></td>
                                            <td class="px-6 py-3"></td>
                                        </tr>
                                    @endif

                                    {{-- Show all Sangla transactions first --}}
                                    @foreach($sanglaTransactions as $transaction)
                                        @php
                                            $isVoided = $transaction->isVoided();
                                        @endphp
                                        <tr 
                                            class="hover:bg-gray-50 transition-colors cursor-pointer transaction-row {{ $isVoided ? 'opacity-40' : '' }} {{ $pawnTicketNumber ? 'bg-gray-50' : '' }}"
                                            data-item-image="{{ route('images.show', ['path' => $transaction->item_image_path]) }}"
                                            data-pawner-image="{{ route('images.show', ['path' => $transaction->pawner_id_image_path]) }}"
                                            data-pawn-ticket-image="{{ $transaction->pawn_ticket_image_path ? route('images.show', ['path' => $transaction->pawn_ticket_image_path]) : '' }}"
                                            data-transaction-id="{{ $transaction->id }}"
                                            data-transaction-number="{{ $transaction->transaction_number }}"
                                            data-pawn-ticket-number="{{ $transaction->pawn_ticket_number ?? '' }}"
                                            data-item-type="{{ $transaction->itemType->name }}"
                                            data-item-subtype="{{ $transaction->itemTypeSubtype ? $transaction->itemTypeSubtype->name : '' }}"
                                            data-item-description="{{ $transaction->item_description }}"
                                            data-transaction-date="{{ $transaction->created_at->format('M d, Y') }} {{ $transaction->created_at->format('h:i A') }}"
                                            data-is-voided="{{ $isVoided ? '1' : '0' }}"
                                            data-maturity-date="{{ $transaction->maturity_date ? $transaction->maturity_date->format('M d, Y') : '' }}"
                                            data-expiry-date="{{ $transaction->expiry_date ? $transaction->expiry_date->format('M d, Y') : '' }}"
                                            data-auction-sale-date="{{ $transaction->auction_sale_date ? $transaction->auction_sale_date->format('M d, Y') : '' }}"
                                            data-loan-amount="{{ number_format($transaction->loan_amount, 2) }}"
                                            data-interest-rate="{{ number_format($transaction->interest_rate, 2) }}"
                                            data-service-charge="{{ number_format($transaction->service_charge, 2) }}"
                                            data-net-proceeds="{{ number_format($transaction->net_proceeds, 2) }}"
                                        >
                                            <td class="px-6 py-4 whitespace-nowrap {{ $pawnTicketNumber ? 'pl-12' : '' }}">
                                                <div class="text-xs text-gray-500">Transaction #</div>
                                                <div class="text-sm font-medium text-gray-900">{{ $transaction->transaction_number }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $transaction->created_at->format('M d, Y') }}</div>
                                                <div class="text-xs text-gray-500">{{ $transaction->created_at->format('h:i A') }}</div>
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
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Sangla
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{-- Amount cell left empty for Sangla transactions - shown on header row --}}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $transaction->branch->name }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $transaction->user->name }}</div>
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Show all Renewal transactions at the bottom --}}
                                    @foreach($renewalTransactions as $renewal)
                                        @php
                                            $isVoided = $renewal->isVoided();
                                        @endphp
                                        <tr 
                                            class="bg-yellow-50 hover:bg-yellow-100 transition-colors cursor-pointer transaction-row {{ $isVoided ? 'opacity-40' : '' }}"
                                            data-item-image="{{ route('images.show', ['path' => $renewal->item_image_path]) }}"
                                            data-pawner-image="{{ route('images.show', ['path' => $renewal->pawner_id_image_path]) }}"
                                            data-pawn-ticket-image="{{ $renewal->pawn_ticket_image_path ? route('images.show', ['path' => $renewal->pawn_ticket_image_path]) : '' }}"
                                            data-transaction-id="{{ $renewal->id }}"
                                            data-transaction-number="{{ $renewal->transaction_number }}"
                                            data-pawn-ticket-number="{{ $renewal->pawn_ticket_number ?? '' }}"
                                            data-item-type="{{ $renewal->itemType->name }}"
                                            data-item-subtype="{{ $renewal->itemTypeSubtype ? $renewal->itemTypeSubtype->name : '' }}"
                                            data-item-description="{{ $renewal->item_description }}"
                                            data-transaction-date="{{ $renewal->created_at->format('M d, Y') }} {{ $renewal->created_at->format('h:i A') }}"
                                            data-is-voided="{{ $isVoided ? '1' : '0' }}"
                                            data-maturity-date="{{ $renewal->maturity_date ? $renewal->maturity_date->format('M d, Y') : '' }}"
                                            data-expiry-date="{{ $renewal->expiry_date ? $renewal->expiry_date->format('M d, Y') : '' }}"
                                            data-auction-sale-date="{{ $renewal->auction_sale_date ? $renewal->auction_sale_date->format('M d, Y') : '' }}"
                                            data-loan-amount="{{ number_format($renewal->loan_amount, 2) }}"
                                            data-interest-rate="{{ number_format($renewal->interest_rate, 2) }}"
                                            data-service-charge="{{ number_format($renewal->service_charge, 2) }}"
                                            data-net-proceeds="{{ number_format($renewal->net_proceeds, 2) }}"
                                        >
                                            <td class="px-6 py-2 whitespace-nowrap {{ $pawnTicketNumber ? 'pl-12' : '' }}">
                                                <div class="text-xs font-semibold text-yellow-900">
                                                    Renewal
                                                </div>
                                                <div class="text-[11px] text-gray-600 mt-1">
                                                    {{ $renewal->transaction_number }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <div class="text-xs text-gray-900">
                                                    {{ $renewal->created_at->format('M d, Y') }}
                                                </div>
                                                <div class="text-[11px] text-gray-500">
                                                    {{ $renewal->created_at->format('h:i A') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-2">
                                                <div class="text-xs font-medium text-gray-900">
                                                    Renewal of {{ $renewal->itemType->name }}
                                                    @if($renewal->itemTypeSubtype)
                                                        <span class="text-gray-500">- {{ $renewal->itemTypeSubtype->name }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-gray-500 mt-1">
                                                    Interest payment to extend maturity and expiry dates.
                                                </div>
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-[11px] leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Renew
                                                </span>
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <div class="text-[11px] text-gray-500">Interest Paid:</div>
                                                <div class="text-xs font-medium text-green-700">
                                                    +₱{{ number_format($renewal->net_proceeds, 2) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <div class="text-xs text-gray-900">
                                                    {{ $renewal->branch->name }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <div class="text-xs text-gray-900">
                                                    {{ $renewal->user->name }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Handle transactions without pawn ticket number --}}
                                    @if(!$pawnTicketNumber)
                                        @foreach($pawnTicketTransactions as $transaction)
                                            @php
                                                $isVoided = $transaction->isVoided();
                                            @endphp
                                        <tr 
                                            class="hover:bg-gray-50 transition-colors cursor-pointer transaction-row {{ $isVoided ? 'opacity-40' : '' }}"
                                            data-item-image="{{ route('images.show', ['path' => $transaction->item_image_path]) }}"
                                            data-pawner-image="{{ route('images.show', ['path' => $transaction->pawner_id_image_path]) }}"
                                            data-pawn-ticket-image="{{ $transaction->pawn_ticket_image_path ? route('images.show', ['path' => $transaction->pawn_ticket_image_path]) : '' }}"
                                            data-transaction-id="{{ $transaction->id }}"
                                            data-transaction-number="{{ $transaction->transaction_number }}"
                                            data-pawn-ticket-number="{{ $transaction->pawn_ticket_number ?? '' }}"
                                            data-item-type="{{ $transaction->itemType->name }}"
                                            data-item-subtype="{{ $transaction->itemTypeSubtype ? $transaction->itemTypeSubtype->name : '' }}"
                                            data-item-description="{{ $transaction->item_description }}"
                                            data-transaction-date="{{ $transaction->created_at->format('M d, Y') }} {{ $transaction->created_at->format('h:i A') }}"
                                            data-is-voided="{{ $isVoided ? '1' : '0' }}"
                                            data-maturity-date="{{ $transaction->maturity_date ? $transaction->maturity_date->format('M d, Y') : '' }}"
                                            data-expiry-date="{{ $transaction->expiry_date ? $transaction->expiry_date->format('M d, Y') : '' }}"
                                            data-auction-sale-date="{{ $transaction->auction_sale_date ? $transaction->auction_sale_date->format('M d, Y') : '' }}"
                                            data-loan-amount="{{ number_format($transaction->loan_amount, 2) }}"
                                            data-interest-rate="{{ number_format($transaction->interest_rate, 2) }}"
                                            data-service-charge="{{ number_format($transaction->service_charge, 2) }}"
                                            data-net-proceeds="{{ number_format($transaction->net_proceeds, 2) }}"
                                        >
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-xs text-gray-500">Transaction #</div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $transaction->transaction_number }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">{{ $transaction->created_at->format('M d, Y') }}</div>
                                                    <div class="text-xs text-gray-500">{{ $transaction->created_at->format('h:i A') }}</div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $transaction->itemType->name }}
                                                        @if($transaction->itemTypeSubtype)
                                                            <span class="text-gray-500">- {{ $transaction->itemTypeSubtype->name }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($transaction->item_description, 40) }}</div>
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
                                                    <div class="text-xs text-gray-500">Principal:</div>
                                                    <div class="text-sm font-medium text-gray-900">₱{{ number_format($transaction->loan_amount, 2) }}</div>
                                                    @if($transaction->type === 'sangla')
                                                        <div class="mt-2 text-xs text-gray-500">Net Proceeds:</div>
                                                        <div class="text-sm text-red-700 font-medium">₱{{ number_format($transaction->net_proceeds, 2) }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">{{ $transaction->branch->name }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">{{ $transaction->user->name }}</div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Transaction Date</p>
                            <p id="modalTransactionDate" class="text-sm font-medium text-gray-900 mt-1">-</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Pawn Ticket Number</p>
                            <p id="modalPawnTicketNumber" class="text-sm font-medium text-gray-900 mt-1">-</p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Item Details</p>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-900">
                                <span id="modalItemType">-</span>
                                <span id="modalItemSubtype" class="text-gray-600"></span>
                            </p>
                            <p id="modalItemDescription" class="text-sm text-gray-700 mt-2">-</p>
                        </div>
                    </div>
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
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                        <!-- Item Image -->
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Item Image</h5>
                            <div class="border-2 border-gray-200 rounded-lg overflow-hidden">
                                <img 
                                    id="modalItemImage" 
                                    src="" 
                                    alt="Item Image" 
                                    class="w-auto h-full"
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
                                    class="w-auto h-full"
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
                                    class="w-auto h-full"
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
            
            <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-between items-center sticky bottom-0">
                <button
                    type="button"
                    id="voidTransactionBtn"
                    onclick="openVoidDialog()"
                    class="px-6 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 hidden">
                    Void Transaction
                </button>
                <button
                    type="button"
                    onclick="closeTransactionImagesModal()"
                    class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Close
                </button>
            </div>
        </div>
    </dialog>

    <!-- Void Transaction Dialog -->
    <dialog id="voidTransactionModal" class="rounded-lg p-0 w-[90vw] max-w-md backdrop:bg-black/50">
        <div class="bg-white rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Void Transaction</h3>
                <p class="mt-1 text-sm text-gray-500">Please provide a reason for voiding this transaction.</p>
            </div>
            
            <form id="voidTransactionForm" method="POST" class="p-6">
                @csrf
                <div class="mb-4">
                    <label for="void_reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        id="void_reason" 
                        name="reason" 
                        rows="4" 
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" 
                        placeholder="Enter the reason for voiding this transaction..."
                        required
                        minlength="5"
                        maxlength="500"
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500">Minimum 5 characters, maximum 500 characters</p>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeVoidDialog()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Void Transaction
                    </button>
                </div>
            </form>
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
                    const transactionId = this.getAttribute('data-transaction-id');
                    const transactionNumber = this.getAttribute('data-transaction-number');
                    const pawnTicketNumber = this.getAttribute('data-pawn-ticket-number');
                    const itemType = this.getAttribute('data-item-type');
                    const itemSubtype = this.getAttribute('data-item-subtype');
                    const itemDescription = this.getAttribute('data-item-description');
                    const transactionDate = this.getAttribute('data-transaction-date');
                    const isVoided = this.getAttribute('data-is-voided') === '1';
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
                        transactionId,
                        transactionNumber,
                        pawnTicketNumber,
                        itemType,
                        itemSubtype,
                        itemDescription,
                        transactionDate,
                        isVoided,
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

        let currentTransactionId = null;

        function showTransactionDetails(data) {
            const modal = document.getElementById('transactionImagesModal');
            currentTransactionId = data.transactionId;
            
            // Set transaction number
            document.getElementById('modalTransactionNumber').textContent = data.transactionNumber;
            
            // Set transaction date
            document.getElementById('modalTransactionDate').textContent = data.transactionDate || '-';
            
            // Set pawn ticket number
            document.getElementById('modalPawnTicketNumber').textContent = data.pawnTicketNumber || '-';
            
            // Set item details
            const itemTypeEl = document.getElementById('modalItemType');
            const itemSubtypeEl = document.getElementById('modalItemSubtype');
            const itemDescriptionEl = document.getElementById('modalItemDescription');
            
            if (itemTypeEl) {
                itemTypeEl.textContent = data.itemType || '-';
            }
            if (itemSubtypeEl) {
                if (data.itemSubtype && data.itemSubtype.trim() !== '') {
                    itemSubtypeEl.textContent = ' - ' + data.itemSubtype;
                    itemSubtypeEl.style.display = 'inline';
                } else {
                    itemSubtypeEl.textContent = '';
                    itemSubtypeEl.style.display = 'none';
                }
            }
            if (itemDescriptionEl) {
                itemDescriptionEl.textContent = data.itemDescription || '-';
            }
            
            // Show/hide void button based on voided status
            const voidBtn = document.getElementById('voidTransactionBtn');
            if (data.isVoided === '1' || data.isVoided === true || data.isVoided === 1) {
                voidBtn.classList.add('hidden');
            } else {
                voidBtn.classList.remove('hidden');
            }
            
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

        // Void transaction functions
        function openVoidDialog() {
            if (!currentTransactionId) {
                alert('Transaction ID not found');
                return;
            }
            
            const form = document.getElementById('voidTransactionForm');
            form.action = `/transactions/${currentTransactionId}/void`;
            document.getElementById('void_reason').value = '';
            document.getElementById('voidTransactionModal').showModal();
        }

        function closeVoidDialog() {
            document.getElementById('voidTransactionModal').close();
        }

        // Close void modal when clicking outside
        document.getElementById('voidTransactionModal').addEventListener('click', function(event) {
            if (event.target === this) {
                this.close();
            }
        });

        // Handle void form submission
        document.getElementById('voidTransactionForm').addEventListener('submit', function(e) {
            const reason = document.getElementById('void_reason').value.trim();
            if (reason.length < 5) {
                e.preventDefault();
                alert('Please provide a reason with at least 5 characters.');
                return false;
            }
        });
    </script>
</x-app-layout>

