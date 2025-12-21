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
                                            // Filter out voided transactions and get the oldest non-voided Sangla transaction
                                            $nonVoidedSanglaTransactions = $sanglaTransactions->filter(function($tx) {
                                                return !$tx->isVoided();
                                            });
                                            
                                            // Use the oldest non-voided transaction's values (not sum)
                                            $oldestSanglaTransaction = $nonVoidedSanglaTransactions->sortBy('created_at')->first();
                                            $principal = $oldestSanglaTransaction ? $oldestSanglaTransaction->loan_amount : 0;
                                            $netProceeds = $oldestSanglaTransaction ? $oldestSanglaTransaction->net_proceeds : 0;
                                            
                                            // Check if ALL Sangla transactions are voided (for opacity)
                                            $allSanglaTransactionsVoided = $sanglaTransactions->count() > 0 && 
                                                $sanglaTransactions->filter(function($tx) {
                                                    return !$tx->isVoided();
                                                })->count() === 0;
                                            
                                            // Check if pawn ticket has child transactions (renewals)
                                            $hasChildTransactions = $renewalTransactions->filter(function($tx) {
                                                return !$tx->isVoided();
                                            })->count() > 0;
                                            
                                            // Check if oldest non-voided transaction is older than 6 hours
                                            $hoursSinceCreation = $oldestSanglaTransaction ? $oldestSanglaTransaction->created_at->diffInHours(now()) : 0;
                                            $isOlderThan6Hours = $hoursSinceCreation > 6;
                                            
                                            // Get first non-voided transaction for images and details
                                            $firstSanglaTransaction = $nonVoidedSanglaTransactions->first() ?? $sanglaTransactions->first();
                                            
                                            // Get first transaction for pawner info (use non-voided if available)
                                            $firstTransaction = $nonVoidedSanglaTransactions->first() ?? $sanglaTransactions->first() ?? $pawnTicketTransactions->first();
                                            
                                            // Get latest transaction for dates (prefer renewal, then latest sangla)
                                            $latestRenewal = $renewalTransactions->filter(function($tx) {
                                                return !$tx->isVoided();
                                            })->sortByDesc('created_at')->first();
                                            
                                            $latestSangla = $nonVoidedSanglaTransactions->sortByDesc('created_at')->first();
                                            
                                            // Use latest renewal if exists, otherwise use latest sangla
                                            $latestTransaction = $latestRenewal ?? $latestSangla;
                                            
                                            $latestMaturityDate = $latestTransaction && $latestTransaction->maturity_date 
                                                ? $latestTransaction->maturity_date->format('M d, Y') 
                                                : '-';
                                            $latestExpiryDate = $latestTransaction && $latestTransaction->expiry_date 
                                                ? $latestTransaction->expiry_date->format('M d, Y') 
                                                : '-';
                                            $latestAuctionSaleDate = $latestTransaction && $latestTransaction->auction_sale_date 
                                                ? $latestTransaction->auction_sale_date->format('M d, Y') 
                                                : '-';
                                        @endphp
                                        {{-- Pawn Ticket Header Row --}}
                                        <tr 
                                            class="bg-violet-100 border-t-2 border-gray-300 hover:bg-violet-200 transition-colors cursor-pointer pawn-ticket-row {{ $allSanglaTransactionsVoided ? 'opacity-40' : '' }}"
                                            data-pawn-ticket-number="{{ $pawnTicketNumber }}"
                                            data-pawner-name="{{ $firstTransaction->pawner_name }}"
                                            data-pawner-image="{{ route('images.show', ['path' => $firstSanglaTransaction->pawner_id_image_path]) }}"
                                            data-pawn-ticket-image="{{ $firstSanglaTransaction->pawn_ticket_image_path ? route('images.show', ['path' => $firstSanglaTransaction->pawn_ticket_image_path]) : '' }}"
                                            data-has-voided-transactions="{{ $allSanglaTransactionsVoided ? '1' : '0' }}"
                                            data-has-child-transactions="{{ $hasChildTransactions ? '1' : '0' }}"
                                            data-is-older-than-6-hours="{{ $isOlderThan6Hours ? '1' : '0' }}"
                                            data-sangla-count="{{ $sanglaTransactions->count() }}"
                                            data-renewal-count="{{ $renewalTransactions->count() }}"
                                            data-principal="{{ number_format($principal, 2) }}"
                                            data-net-proceeds="{{ number_format($netProceeds, 2) }}"
                                        >
                                            <td class="px-6 py-3">
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
                                            <td class="px-6 py-3 whitespace-nowrap" colspan="3">
                                                <div class="text-xs text-gray-500">Maturity:</div>
                                                <div class="text-xs font-medium text-gray-900">{{ $latestMaturityDate }}</div>
                                                <div class="text-xs text-gray-500 mt-1">Expiry:</div>
                                                <div class="text-xs font-medium text-gray-900">{{ $latestExpiryDate }}</div>
                                                <div class="text-xs text-gray-500 mt-1">Auction:</div>
                                                <div class="text-xs font-medium text-gray-900">{{ $latestAuctionSaleDate }}</div>
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
                                            $voidedInfo = $transaction->voided;
                                            // Check if this Sangla transaction has child transactions (additional items or renewals)
                                            $hasChildTransactions = false;
                                            $isLatestChild = true;
                                            if ($transaction->type === 'sangla' && $transaction->pawn_ticket_number) {
                                                $hasChildTransactions = \App\Models\Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                                                    ->where('id', '!=', $transaction->id)
                                                    ->whereDoesntHave('voided')
                                                    ->exists();
                                                
                                                // Check if this is the latest child transaction
                                                $firstSangla = \App\Models\Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                                                    ->where('type', 'sangla')
                                                    ->orderBy('created_at', 'asc')
                                                    ->first();
                                                
                                                // If this is not the first Sangla, check if it's the latest child
                                                if ($firstSangla && $transaction->id !== $firstSangla->id) {
                                                    $latestChild = \App\Models\Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                                                        ->where('id', '!=', $firstSangla->id)
                                                        ->whereDoesntHave('voided')
                                                        ->orderBy('created_at', 'desc')
                                                        ->first();
                                                    $isLatestChild = $latestChild && $transaction->id === $latestChild->id;
                                                } else {
                                                    // First Sangla is not a child, so isLatestChild doesn't apply
                                                    $isLatestChild = true;
                                                }
                                            }
                                            // Check if transaction is older than 6 hours
                                            $hoursSinceCreation = $transaction->created_at->diffInHours(now());
                                            $isOlderThan6Hours = $hoursSinceCreation > 6;
                                        @endphp
                                        <tr 
                                            class="hover:bg-gray-50 transition-colors transaction-row {{ $isVoided ? 'opacity-40' : '' }} {{ $pawnTicketNumber ? 'bg-gray-50' : '' }}"
                                            data-item-image="{{ route('images.show', ['path' => $transaction->item_image_path]) }}"
                                            data-pawner-image="{{ route('images.show', ['path' => $transaction->pawner_id_image_path]) }}"
                                            data-pawn-ticket-image="{{ $transaction->pawn_ticket_image_path ? route('images.show', ['path' => $transaction->pawn_ticket_image_path]) : '' }}"
                                            data-transaction-id="{{ $transaction->id }}"
                                            data-transaction-number="{{ $transaction->transaction_number }}"
                                            data-pawn-ticket-number="{{ $transaction->pawn_ticket_number ?? '' }}"
                                            data-item-type="{{ $transaction->itemType->name }}"
                                            data-item-subtype="{{ $transaction->itemTypeSubtype ? $transaction->itemTypeSubtype->name : '' }}"
                                            data-custom-item-type="{{ $transaction->custom_item_type ?? '' }}"
                                            data-item-description="{{ $transaction->item_description }}"
                                            data-item-tags="{{ $transaction->tags->pluck('name')->toJson() }}"
                                            data-transaction-date="{{ $transaction->created_at->format('M d, Y') }} {{ $transaction->created_at->format('h:i A') }}"
                                            data-is-voided="{{ $isVoided ? '1' : '0' }}"
                                            data-voided-by="{{ $voidedInfo && $voidedInfo->voidedBy ? $voidedInfo->voidedBy->name : '' }}"
                                            data-voided-at="{{ $voidedInfo && $voidedInfo->voided_at ? $voidedInfo->voided_at->format('M d, Y h:i A') : '' }}"
                                            data-void-reason="{{ $voidedInfo ? $voidedInfo->reason : '' }}"
                                            data-has-child-transactions="{{ $hasChildTransactions ? '1' : '0' }}"
                                            data-is-older-than-6-hours="{{ $isOlderThan6Hours ? '1' : '0' }}"
                                            data-transaction-type="{{ $transaction->type }}"
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
                                            $voidedInfo = $renewal->voided;
                                            // Check if this is the latest child transaction
                                            $isLatestChild = true;
                                            if ($renewal->pawn_ticket_number) {
                                                $firstSangla = \App\Models\Transaction::where('pawn_ticket_number', $renewal->pawn_ticket_number)
                                                    ->where('type', 'sangla')
                                                    ->orderBy('created_at', 'asc')
                                                    ->first();
                                                
                                                // Renewal is always a child, check if it's the latest
                                                if ($firstSangla) {
                                                    $latestChild = \App\Models\Transaction::where('pawn_ticket_number', $renewal->pawn_ticket_number)
                                                        ->where('id', '!=', $firstSangla->id)
                                                        ->whereDoesntHave('voided')
                                                        ->orderBy('created_at', 'desc')
                                                        ->first();
                                                    $isLatestChild = $latestChild && $renewal->id === $latestChild->id;
                                                }
                                            }
                                            // Check if transaction is older than 6 hours
                                            $hoursSinceCreation = $renewal->created_at->diffInHours(now());
                                            $isOlderThan6Hours = $hoursSinceCreation > 6;
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
                                            data-custom-item-type="{{ $renewal->custom_item_type ?? '' }}"
                                            data-item-description="{{ $renewal->item_description }}"
                                            data-item-tags="{{ $renewal->tags->pluck('name')->toJson() }}"
                                            data-transaction-date="{{ $renewal->created_at->format('M d, Y') }} {{ $renewal->created_at->format('h:i A') }}"
                                            data-is-voided="{{ $isVoided ? '1' : '0' }}"
                                            data-voided-by="{{ $voidedInfo && $voidedInfo->voidedBy ? $voidedInfo->voidedBy->name : '' }}"
                                            data-voided-at="{{ $voidedInfo && $voidedInfo->voided_at ? $voidedInfo->voided_at->format('M d, Y h:i A') : '' }}"
                                            data-void-reason="{{ $voidedInfo ? $voidedInfo->reason : '' }}"
                                            data-has-child-transactions="0"
                                            data-is-latest-child="{{ $isLatestChild ? '1' : '0' }}"
                                            data-is-older-than-6-hours="{{ $isOlderThan6Hours ? '1' : '0' }}"
                                            data-transaction-type="{{ $renewal->type }}"
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
                                                    Renewal of Pawn Ticket #{{ $renewal->pawn_ticket_number }}
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
                                                $voidedInfo = $transaction->voided;
                                                // Check if this Sangla transaction has child transactions
                                                $hasChildTransactions = false;
                                                if ($transaction->type === 'sangla' && $transaction->pawn_ticket_number) {
                                                    $hasChildTransactions = \App\Models\Transaction::where('pawn_ticket_number', $transaction->pawn_ticket_number)
                                                        ->where('id', '!=', $transaction->id)
                                                        ->whereDoesntHave('voided')
                                                        ->exists();
                                                }
                                                // Check if transaction is older than 6 hours
                                                $hoursSinceCreation = $transaction->created_at->diffInHours(now());
                                                $isOlderThan6Hours = $hoursSinceCreation > 6;
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
                                            data-custom-item-type="{{ $transaction->custom_item_type ?? '' }}"
                                            data-item-description="{{ $transaction->item_description }}"
                                            data-item-tags="{{ $transaction->tags->pluck('name')->toJson() }}"
                                            data-transaction-date="{{ $transaction->created_at->format('M d, Y') }} {{ $transaction->created_at->format('h:i A') }}"
                                            data-is-voided="{{ $isVoided ? '1' : '0' }}"
                                            data-voided-by="{{ $voidedInfo && $voidedInfo->voidedBy ? $voidedInfo->voidedBy->name : '' }}"
                                            data-voided-at="{{ $voidedInfo && $voidedInfo->voided_at ? $voidedInfo->voided_at->format('M d, Y h:i A') : '' }}"
                                            data-void-reason="{{ $voidedInfo ? $voidedInfo->reason : '' }}"
                                            data-has-child-transactions="{{ $hasChildTransactions ? '1' : '0' }}"
                                            data-is-older-than-6-hours="{{ $isOlderThan6Hours ? '1' : '0' }}"
                                            data-transaction-type="{{ $transaction->type }}"
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
                    <div id="itemDetailsSection" class="mb-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Item Details</p>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-900">
                                <span class="text-gray-600">Category:</span> 
                                <span id="modalItemType">-</span>
                                <span id="modalItemSubtype" class="text-gray-600"></span>
                                <span id="modalCustomItemType" class="text-gray-600"></span>
                            </p>
                            <p class="text-sm text-gray-600 mt-2">Description:</p>
                            <p id="modalItemDescription" class="text-sm text-gray-700 mt-1">-</p>
                            <p class="text-sm text-gray-600 mt-2">Tags:</p>
                            <div id="modalItemTags" class="mt-1 flex flex-wrap gap-1">
                                <!-- Tags will be inserted here -->
                            </div>
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

                <!-- All Items Section (for Renewal transactions) -->
                <div id="allItemsSection" class="mb-6 hidden">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">All Items in this Pawn Ticket</h4>
                    <div id="allItemsContainer" class="space-y-4">
                        <!-- Items will be dynamically inserted here -->
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
                        
                        <!-- All Item Images (for Renewal transactions) -->
                        <div id="allItemImagesSection" class="hidden">
                            <h5 class="text-sm font-medium text-gray-700 mb-2">All Item Images</h5>
                            <div id="allItemImagesContainer" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Item images will be dynamically inserted here -->
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
            // Add click handler to pawn ticket rows
            document.querySelectorAll('.pawn-ticket-row').forEach(function(row) {
                row.addEventListener('click', function(e) {
                    // Don't trigger if clicking on a link, button, or other interactive element
                    if (e.target.closest('a, button, input, select, textarea')) {
                        return;
                    }
                    
                    const pawnTicketNumber = this.getAttribute('data-pawn-ticket-number');
                    const pawnerName = this.getAttribute('data-pawner-name');
                    const pawnerImageUrl = this.getAttribute('data-pawner-image');
                    const pawnTicketImageUrl = this.getAttribute('data-pawn-ticket-image');
                    const hasVoidedTransactions = this.getAttribute('data-has-voided-transactions') === '1';
                    const hasChildTransactions = this.getAttribute('data-has-child-transactions') === '1';
                    const isOlderThan6Hours = this.getAttribute('data-is-older-than-6-hours') === '1';
                    const sanglaCount = this.getAttribute('data-sangla-count');
                    const renewalCount = this.getAttribute('data-renewal-count');
                    const principal = this.getAttribute('data-principal');
                    const netProceeds = this.getAttribute('data-net-proceeds');
                    
                    showPawnTicketDetails({
                        pawnTicketNumber,
                        pawnerName,
                        pawnerImageUrl,
                        pawnTicketImageUrl,
                        hasVoidedTransactions,
                        hasChildTransactions,
                        isOlderThan6Hours,
                        sanglaCount,
                        renewalCount,
                        principal,
                        netProceeds
                    });
                });
            });
            
            // Add click handler to transaction rows (for non-pawn-ticket transactions)
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
                    const customItemType = this.getAttribute('data-custom-item-type');
                    const itemDescription = this.getAttribute('data-item-description');
                    const itemTagsJson = this.getAttribute('data-item-tags');
                    const itemTags = itemTagsJson ? JSON.parse(itemTagsJson) : [];
                    const transactionDate = this.getAttribute('data-transaction-date');
                    const isVoided = this.getAttribute('data-is-voided') === '1';
                    const voidedBy = this.getAttribute('data-voided-by') || '';
                    const voidedAt = this.getAttribute('data-voided-at') || '';
                    const voidReason = this.getAttribute('data-void-reason') || '';
                    const hasChildTransactions = this.getAttribute('data-has-child-transactions') === '1';
                    const isLatestChild = this.getAttribute('data-is-latest-child') === '1';
                    const isOlderThan6Hours = this.getAttribute('data-is-older-than-6-hours') === '1';
                    const transactionType = this.getAttribute('data-transaction-type') || '';
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
                        customItemType,
                        itemDescription,
                        itemTags,
                        transactionDate,
                        isVoided,
                        voidedBy,
                        voidedAt,
                        voidReason,
                        hasChildTransactions: hasChildTransactions || false,
                        isLatestChild: isLatestChild || false,
                        isOlderThan6Hours: isOlderThan6Hours || false,
                        transactionType: transactionType,
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
        let currentPawnTicketNumber = null;

        function showPawnTicketDetails(data) {
            const modal = document.getElementById('transactionImagesModal');
            currentPawnTicketNumber = data.pawnTicketNumber;
            currentTransactionId = null; // Clear transaction ID for pawn ticket voiding
            
            // Set transaction number to pawn ticket number
            document.getElementById('modalTransactionNumber').textContent = `PAWN TICKET #${data.pawnTicketNumber}`;
            
            // Set transaction date (use current date or first transaction date)
            document.getElementById('modalTransactionDate').textContent = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            // Set pawn ticket number
            document.getElementById('modalPawnTicketNumber').textContent = data.pawnTicketNumber || '-';
            
            // Hide item details section (we'll show all items instead)
            document.getElementById('itemDetailsSection').classList.add('hidden');
            
            // Hide transaction summary for pawn tickets
            const summarySection = document.querySelector('.mb-6:has(#modalPrincipal)');
            if (summarySection) {
                summarySection.style.display = 'none';
            }
            
            // Set pawner image
            document.getElementById('modalPawnerImage').src = data.pawnerImageUrl;
            
            // Handle pawn ticket image
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
            
            // Fetch and display all related items
            fetchRelatedItems(data.pawnTicketNumber);
            
            // Show/hide void button based on voided status, child transactions, and 6-hour rule
            const voidBtn = document.getElementById('voidTransactionBtn');
            const isVoided = data.hasVoidedTransactions === '1' || data.hasVoidedTransactions === true || data.hasVoidedTransactions === 1;
            const hasChildTransactions = data.hasChildTransactions === true || data.hasChildTransactions === 1 || data.hasChildTransactions === '1';
            const isOlderThan6Hours = data.isOlderThan6Hours === true || data.isOlderThan6Hours === 1 || data.isOlderThan6Hours === '1';
            
            // Hide void button if:
            // 1. Any transaction is voided, OR
            // 2. Has child transactions (renewals), OR
            // 3. Oldest transaction is older than 6 hours
            if (isVoided || hasChildTransactions || isOlderThan6Hours) {
                voidBtn.classList.add('hidden');
            } else {
                voidBtn.classList.remove('hidden');
            }
            
            modal.showModal();
        }

        function showTransactionDetails(data) {
            const modal = document.getElementById('transactionImagesModal');
            currentTransactionId = data.transactionId;
            currentPawnTicketNumber = null; // Clear pawn ticket number for individual transactions
            
            // Show/hide voided transaction notice
            const voidedNotice = document.getElementById('voidedTransactionNotice');
            const transactionIsVoided = data.isVoided === '1' || data.isVoided === true || data.isVoided === 1;
            if (transactionIsVoided && voidedNotice) {
                voidedNotice.classList.remove('hidden');
                document.getElementById('modalVoidedBy').textContent = data.voidedBy || '-';
                document.getElementById('modalVoidedAt').textContent = data.voidedAt || '-';
                document.getElementById('modalVoidReason').textContent = data.voidReason || '-';
            } else if (voidedNotice) {
                voidedNotice.classList.add('hidden');
            }
            
            // Set transaction number
            document.getElementById('modalTransactionNumber').textContent = data.transactionNumber;
            
            // Set transaction date
            document.getElementById('modalTransactionDate').textContent = data.transactionDate || '-';
            
            // Set pawn ticket number
            document.getElementById('modalPawnTicketNumber').textContent = data.pawnTicketNumber || '-';
            
            // Set item details
            const itemTypeEl = document.getElementById('modalItemType');
            const itemSubtypeEl = document.getElementById('modalItemSubtype');
            const customItemTypeEl = document.getElementById('modalCustomItemType');
            const itemDescriptionEl = document.getElementById('modalItemDescription');
            const itemTagsEl = document.getElementById('modalItemTags');
            
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
            if (customItemTypeEl) {
                if (data.customItemType && data.customItemType.trim() !== '') {
                    customItemTypeEl.textContent = ' - ' + data.customItemType;
                    customItemTypeEl.style.display = 'inline';
                } else {
                    customItemTypeEl.textContent = '';
                    customItemTypeEl.style.display = 'none';
                }
            }
            if (itemDescriptionEl) {
                itemDescriptionEl.textContent = data.itemDescription || '-';
            }
            if (itemTagsEl) {
                itemTagsEl.innerHTML = '';
                if (data.itemTags && Array.isArray(data.itemTags) && data.itemTags.length > 0) {
                    data.itemTags.forEach(tag => {
                        const tagSpan = document.createElement('span');
                        tagSpan.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800';
                        tagSpan.textContent = tag;
                        itemTagsEl.appendChild(tagSpan);
                    });
                } else {
                    itemTagsEl.innerHTML = '<span class="text-xs text-gray-400">No tags</span>';
                }
            }

            // Show transaction summary
            const summarySection = document.querySelector('.mb-6:has(#modalPrincipal)');
            if (summarySection) {
                summarySection.style.display = 'block';
            }
            
            // If this is a renewal transaction, fetch and display all related items
            if (data.transactionType === 'renew' && data.pawnTicketNumber) {
                // Hide item details section for renewal transactions (we show "All Items" instead)
                document.getElementById('itemDetailsSection').classList.add('hidden');
                fetchRelatedItems(data.pawnTicketNumber);
            } else {
                // Show item details section for non-renewal transactions
                document.getElementById('itemDetailsSection').classList.remove('hidden');
                // Hide all items section for non-renewal transactions
                document.getElementById('allItemsSection').classList.add('hidden');
                document.getElementById('allItemImagesSection').classList.add('hidden');
                document.getElementById('modalItemImage').parentElement.style.display = 'block';
            }
            
            // Show/hide void button based on voided status, child transactions, latest child check, and 6-hour rule
            const voidBtn = document.getElementById('voidTransactionBtn');
            const isVoided = data.isVoided === '1' || data.isVoided === true || data.isVoided === 1;
            const hasChildTransactions = data.hasChildTransactions === true || data.hasChildTransactions === 1 || data.hasChildTransactions === '1';
            const isLatestChild = data.isLatestChild === '1' || data.isLatestChild === true || data.isLatestChild === 1;
            const isOlderThan6Hours = data.isOlderThan6Hours === true || data.isOlderThan6Hours === 1 || data.isOlderThan6Hours === '1';
            const isSangla = data.transactionType === 'sangla';
            
            // Hide void button if:
            // 1. Transaction is already voided, OR
            // 2. It's a Sangla transaction with child transactions, OR
            // 3. Transaction is older than 6 hours, OR
            // 4. It's a child transaction but not the latest one
            if (isVoided || (isSangla && hasChildTransactions) || isOlderThan6Hours || !isLatestChild) {
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

        function fetchRelatedItems(pawnTicketNumber) {
            const url = `{{ url('/transactions/related') }}/${encodeURIComponent(pawnTicketNumber)}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const allItemsContainer = document.getElementById('allItemsContainer');
                    const allItemImagesContainer = document.getElementById('allItemImagesContainer');
                    const allItemsSection = document.getElementById('allItemsSection');
                    const allItemImagesSection = document.getElementById('allItemImagesSection');
                    const singleItemImageSection = document.getElementById('modalItemImage').parentElement;

                    // Clear previous content
                    allItemsContainer.innerHTML = '';
                    allItemImagesContainer.innerHTML = '';

                    if (data.items && data.items.length > 0) {
                        // Show all items section
                        allItemsSection.classList.remove('hidden');
                        allItemImagesSection.classList.remove('hidden');
                        singleItemImageSection.style.display = 'none';

                        // Display all items
                        data.items.forEach((item, index) => {
                            let itemTypeText = item.item_type;
                            if (item.item_subtype) {
                                itemTypeText += ` - ${item.item_subtype}`;
                            }
                            if (item.custom_item_type) {
                                itemTypeText += ` - ${item.custom_item_type}`;
                            }

                            let tagsHtml = '';
                            if (item.tags && item.tags.length > 0) {
                                tagsHtml = `<div class="mt-2 flex flex-wrap gap-1">
                                    ${item.tags.map(tag => `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">${tag}</span>`).join('')}
                                </div>`;
                            }

                            // Create item card for "All Items in this Pawn Ticket" section
                            const itemCard = document.createElement('div');
                            itemCard.className = 'bg-gray-50 rounded-lg p-4 border border-gray-200';
                            itemCard.innerHTML = `
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            <span class="text-gray-600">Category:</span> ${itemTypeText}
                                        </p>
                                        <p class="text-sm text-gray-700 mt-1">${item.item_description || '-'}</p>
                                        ${tagsHtml}
                                        <p class="text-xs text-gray-500 mt-2">Transaction: ${item.transaction_number}</p>
                                    </div>
                                </div>
                            `;
                            allItemsContainer.appendChild(itemCard);

                            // Create item image card
                            if (item.item_image_path) {
                                const imageCard = document.createElement('div');
                                imageCard.className = 'border-2 border-gray-200 rounded-lg overflow-hidden';
                                
                                let imageTagsHtml = '';
                                if (item.tags && item.tags.length > 0) {
                                    imageTagsHtml = `<div class="p-2 bg-gray-50 border-t border-gray-200">
                                        <div class="flex flex-wrap gap-1">
                                            ${item.tags.map(tag => `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800">${tag}</span>`).join('')}
                                        </div>
                                    </div>`;
                                }
                                
                                imageCard.innerHTML = `
                                    <div class="p-2 bg-gray-50 border-b border-gray-200">
                                        <p class="text-xs font-medium text-gray-700">
                                            <span class="text-gray-500">Category:</span> ${itemTypeText}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5">Transaction: ${item.transaction_number}</p>
                                    </div>
                                    <img 
                                        src="${item.item_image_path}" 
                                        alt="Item ${index + 1}" 
                                        class="w-full h-auto"
                                        onerror="this.parentElement.innerHTML='<div class=\\'p-8 text-center text-gray-400\\'><p class=\\'text-sm\\'>Image not available</p></div>'"
                                    />
                                    ${imageTagsHtml}
                                `;
                                allItemImagesContainer.appendChild(imageCard);
                            }
                        });
                    } else {
                        // Hide sections if no items found
                        allItemsSection.classList.add('hidden');
                        allItemImagesSection.classList.add('hidden');
                        singleItemImageSection.style.display = 'block';
                        document.getElementById('itemDetailsSection').classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error fetching related items:', error);
                    // Hide sections on error
                    document.getElementById('allItemsSection').classList.add('hidden');
                    document.getElementById('allItemImagesSection').classList.add('hidden');
                    document.getElementById('modalItemImage').parentElement.style.display = 'block';
                    document.getElementById('itemDetailsSection').classList.remove('hidden');
                });
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
            const form = document.getElementById('voidTransactionForm');
            
            // If we have a pawn ticket number, void all Sangla transactions for that pawn ticket
            if (currentPawnTicketNumber) {
                form.action = `/transactions/void-pawn-ticket/${encodeURIComponent(currentPawnTicketNumber)}`;
            } else if (currentTransactionId) {
                form.action = `/transactions/${currentTransactionId}/void`;
            } else {
                alert('Transaction ID or Pawn Ticket Number not found');
                return;
            }
            
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

