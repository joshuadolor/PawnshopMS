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
                                <!-- Date Range Filter -->
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Date of Transaction (From)</label>
                                    <input 
                                        type="date" 
                                        id="start_date" 
                                        name="start_date" 
                                        value="{{ $filters['start_date'] }}"
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                </div>

                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Date of Transaction (To)</label>
                                    <input 
                                        type="date" 
                                        id="end_date" 
                                        name="end_date" 
                                        value="{{ $filters['end_date'] }}"
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                </div>

                                <!-- Today Only Button -->
                                <div class="flex items-end">
                                    <button
                                        type="button"
                                        onclick="setTodayOnly()"
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

                                <!-- User Filter -->
                                @if($users && $users->count() > 0)
                                    <div>
                                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                                        <select 
                                            id="user_id" 
                                            name="user_id" 
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        >
                                            <option value="">All Users</option>
                                            @foreach($users as $filterUser)
                                                <option value="{{ $filterUser->id }}" {{ $filters['user_id'] == $filterUser->id ? 'selected' : '' }}>
                                                    {{ $filterUser->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            @endif

                            <!-- Transaction Type Filter -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                                <select 
                                    id="type" 
                                    name="type" 
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">All Types</option>
                                    <option value="sangla" {{ $filters['type'] == 'sangla' ? 'selected' : '' }}>Sangla</option>
                                    <option value="renew" {{ $filters['type'] == 'renew' ? 'selected' : '' }}>Renewal</option>
                                    <option value="tubos" {{ $filters['type'] == 'tubos' ? 'selected' : '' }}>Tubos</option>
                                    <option value="partial" {{ $filters['type'] == 'partial' ? 'selected' : '' }}>Partial</option>
                                </select>
                            </div>

                            <!-- Hide Voided Transactions Checkbox -->
                            <div class="flex items-end">
                                <div class="flex items-center">
                                    <input 
                                        id="hide_voided" 
                                        type="checkbox" 
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        checked
                                    >
                                    <label for="hide_voided" class="ml-2 text-sm text-gray-700">
                                        Hide Voided Transactions
                                    </label>
                                </div>
                            </div>

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
                                            ->unique('id');
                                        
                                        // Get tubos transactions for this pawn ticket
                                        $tubosFromPage = $pawnTicketTransactions->where('type', 'tubos');
                                        $tubosFromCollection = ($pawnTicketNumber && isset($tubosForPawnTickets)) 
                                            ? $tubosForPawnTickets->where('pawn_ticket_number', $pawnTicketNumber)
                                            : collect();
                                        
                                        // Merge and deduplicate tubos
                                        $tubosTransactions = $tubosFromPage->concat($tubosFromCollection)
                                            ->unique('id');
                                        
                                        // Get partial transactions for this pawn ticket
                                        $partialsFromPage = $pawnTicketTransactions->where('type', 'partial');
                                        $partialsFromCollection = ($pawnTicketNumber && isset($partialsForPawnTickets)) 
                                            ? $partialsForPawnTickets->where('pawn_ticket_number', $pawnTicketNumber)
                                            : collect();
                                        
                                        // Merge and deduplicate partials
                                        $partialTransactions = $partialsFromPage->concat($partialsFromCollection)
                                            ->unique('id');
                                        
                                        // Combine all child transactions (renewals, tubos, and partials) and sort in ascending order
                                        $childTransactions = $renewalTransactions->concat($tubosTransactions)->concat($partialTransactions)
                                            ->unique('id')
                                            ->sortBy('created_at');
                                        
                                        // Check if there are any non-voided tubos transactions
                                        $hasTubosTransactions = $tubosTransactions->filter(function($tx) {
                                            return !$tx->isVoided();
                                        })->count() > 0;
                                        
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
                                            $originalPrincipal = $oldestSanglaTransaction ? $oldestSanglaTransaction->loan_amount : 0;
                                            $netProceeds = $oldestSanglaTransaction ? $oldestSanglaTransaction->net_proceeds : 0;
                                            
                                            // Get current principal amount (after any partial payments)
                                            $latestPartialTransaction = \App\Models\Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                                                ->where('type', 'partial')
                                                ->whereDoesntHave('voided')
                                                ->orderBy('created_at', 'desc')
                                                ->first();
                                            
                                            // Use current principal from latest partial if exists, otherwise use original
                                            $principal = $latestPartialTransaction 
                                                ? (float) $latestPartialTransaction->loan_amount 
                                                : $originalPrincipal;
                                            
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
                                            // Fallback to any transaction in the group if no sangla transactions exist
                                            $firstTransaction = $nonVoidedSanglaTransactions->first() ?? $sanglaTransactions->first() ?? $pawnTicketTransactions->first();
                                            
                                            // Use first transaction for images if no sangla transaction exists
                                            $transactionForImages = $firstSanglaTransaction ?? $firstTransaction;
                                            
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
                                            class="{{ $hasTubosTransactions ? 'bg-white' : 'bg-violet-100' }} border-t-2 border-gray-300 {{ $hasTubosTransactions ? 'hover:bg-gray-50' : 'hover:bg-violet-200' }} transition-colors cursor-pointer pawn-ticket-row {{ $allSanglaTransactionsVoided ? 'opacity-40' : '' }}"
                                            data-pawn-ticket-number="{{ $pawnTicketNumber }}"
                                            data-pawner-name="{{ $firstTransaction->pawner_name }}"
                                            data-pawner-image="{{ $transactionForImages && $transactionForImages->pawner_id_image_path ? route('images.show', ['path' => $transactionForImages->pawner_id_image_path]) : '' }}"
                                            data-pawn-ticket-image="{{ $transactionForImages && $transactionForImages->pawn_ticket_image_path ? route('images.show', ['path' => $transactionForImages->pawn_ticket_image_path]) : '' }}"
                                            data-has-voided-transactions="{{ $allSanglaTransactionsVoided ? '1' : '0' }}"
                                            data-has-child-transactions="{{ $hasChildTransactions ? '1' : '0' }}"
                                            data-is-older-than-6-hours="{{ $isOlderThan6Hours ? '1' : '0' }}"
                                            data-sangla-count="{{ $sanglaTransactions->count() }}"
                                            data-renewal-count="{{ $renewalTransactions->count() }}"
                                            data-principal="{{ number_format($principal, 2) }}"
                                            data-net-proceeds="{{ number_format($netProceeds, 2) }}"
                                            data-maturity-date="{{ $latestMaturityDate }}"
                                            data-expiry-date="{{ $latestExpiryDate }}"
                                            data-auction-sale-date="{{ $latestAuctionSaleDate }}"
                                        >
                                            <td class="px-6 py-3">
                                                <div class="flex  flex-col justify-between">
                                                    <div>
                                                        <span class="text-sm font-bold text-gray-900">PAWN TICKET #{{ $pawnTicketNumber }}</span>
                                                    </div>
                                                    <span class="text-sm text-gray-600">{{ $firstTransaction->pawner_name }}</span>
                                                    <div class="text-xs text-gray-500">
                                                        @php
                                                            $nonVoidedSanglaCount = $sanglaTransactions->filter(function($tx) {
                                                                return !$tx->isVoided();
                                                            })->count();
                                                            $nonVoidedRenewalCount = $renewalTransactions->filter(function($tx) {
                                                                return !$tx->isVoided();
                                                            })->count();
                                                        @endphp
                                                        {{ $nonVoidedSanglaCount }} Item(s) 
                                                        @if($nonVoidedRenewalCount > 0)
                                                            • {{ $nonVoidedRenewalCount }} Renewal(s)
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <button 
                                                            onclick="showQRCode('{{ $pawnTicketNumber }}')"
                                                            class="my-4 inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                        >
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                                            </svg>
                                                            View QR Code
                                                        </button>
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
                                                <div class="text-sm font-medium text-gray-900">
                                                    @if($latestPartialTransaction && $principal != $originalPrincipal)
                                                        <span class="text-gray-500 line-through">₱{{ number_format($originalPrincipal, 2) }}</span>
                                                        <span class="text-blue-600 ml-2">₱{{ number_format($principal, 2) }}</span>
                                                    @else
                                                        ₱{{ number_format($principal, 2) }}
                                                    @endif
                                                </div>
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
                                            data-pawner-image="{{ $transaction->pawner_id_image_path ? route('images.show', ['path' => $transaction->pawner_id_image_path]) : '' }}"
                                            data-pawn-ticket-image="{{ $transaction->pawn_ticket_image_path ? route('images.show', ['path' => $transaction->pawn_ticket_image_path]) : '' }}"
                                            data-signature-image="{{ $transaction->signature_path ? route('images.show', ['path' => $transaction->signature_path]) : '' }}"
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
                                            data-orcr-serial="{{ $transaction->orcr_serial ?? '' }}"
                                            data-grams="{{ $transaction->grams ? number_format($transaction->grams, 1) : '' }}"
                                            data-back-date="{{ $transaction->back_date ? '1' : '0' }}"
                                            data-note="{{ $transaction->note ?? '' }}"
                                            data-transaction-pawn-ticket="{{ $transaction->transaction_pawn_ticket ?? '' }}"
                                        >
                                            <td class="px-6 py-4 whitespace-nowrap {{ $pawnTicketNumber ? 'pl-12' : '' }}">
                                                <div class="text-xs text-gray-500">Transaction #</div>
                                                <div class="text-sm font-medium text-gray-900">{{ $transaction->transaction_number }}</div>
                                                @if($transaction->transaction_pawn_ticket)
                                                    <div class="text-xs text-gray-500 mt-1">Pawn ticket: {{ $transaction->transaction_pawn_ticket }}</div>
                                                @endif
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

                                    {{-- Show all child transactions (renewals and tubos) in ascending order --}}
                                    @foreach($childTransactions as $childTransaction)
                                        @php
                                            $isVoided = $childTransaction->isVoided();
                                            $voidedInfo = $childTransaction->voided;
                                            // Check if this is the latest child transaction
                                            $isLatestChild = true;
                                            if ($childTransaction->pawn_ticket_number) {
                                                $firstSangla = \App\Models\Transaction::where('pawn_ticket_number', $childTransaction->pawn_ticket_number)
                                                    ->where('type', 'sangla')
                                                    ->orderBy('created_at', 'asc')
                                                    ->first();
                                                
                                                // Child transaction is always a child, check if it's the latest
                                                if ($firstSangla) {
                                                    $latestChild = \App\Models\Transaction::where('pawn_ticket_number', $childTransaction->pawn_ticket_number)
                                                        ->where('id', '!=', $firstSangla->id)
                                                        ->whereDoesntHave('voided')
                                                        ->orderBy('created_at', 'desc')
                                                        ->first();
                                                    $isLatestChild = $latestChild && $childTransaction->id === $latestChild->id;
                                                }
                                            }
                                            // Check if transaction is older than 6 hours
                                            $hoursSinceCreation = $childTransaction->created_at->diffInHours(now());
                                            $isOlderThan6Hours = $hoursSinceCreation > 6;
                                            
                                            // Determine styling and content based on transaction type
                                            $isRenewal = $childTransaction->type === 'renew';
                                            $isTubos = $childTransaction->type === 'tubos';
                                            $isPartial = $childTransaction->type === 'partial';
                                            
                                            // Check if partial transaction is a principal increase (negative amount)
                                            $isPrincipalIncrease = false;
                                            if ($isPartial) {
                                                $financialTransaction = \App\Models\BranchFinancialTransaction::where('transaction_id', $childTransaction->id)
                                                    ->where('type', 'expense')
                                                    ->where('description', 'like', '%Principal increase%')
                                                    ->whereDoesntHave('voided')
                                                    ->first();
                                                $isPrincipalIncrease = $financialTransaction !== null;
                                            }
                                            
                                            $bgColor = $isRenewal ? 'bg-yellow-50 hover:bg-yellow-100' : ($isTubos ? 'bg-green-50 hover:bg-green-100' : ($isPartial ? 'bg-purple-50 hover:bg-purple-100' : 'bg-gray-50'));
                                            $textColor = $isRenewal ? 'text-yellow-900' : ($isTubos ? 'text-green-900' : ($isPartial ? 'text-purple-900' : 'text-gray-900'));
                                            $badgeColor = $isRenewal ? 'bg-yellow-100 text-yellow-800' : ($isTubos ? 'bg-green-100 text-green-800' : ($isPartial ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'));
                                            $label = $isRenewal ? 'Renewal' : ($isTubos ? 'Tubos' : ($isPartial ? 'Partial' : ucfirst($childTransaction->type)));
                                            $description = $isRenewal 
                                                ? "Renewal of Pawn Ticket #{$childTransaction->pawn_ticket_number}" 
                                                : ($isTubos 
                                                    ? "Redemption of Pawn Ticket #{$childTransaction->pawn_ticket_number}"
                                                    : ($isPartial
                                                        ? ($isPrincipalIncrease 
                                                            ? "Principal Increase of Pawn Ticket #{$childTransaction->pawn_ticket_number}"
                                                            : "Partial Payment of Pawn Ticket #{$childTransaction->pawn_ticket_number}")
                                                        : "Transaction #{$childTransaction->transaction_number}"));
                                            $subDescription = $isRenewal 
                                                ? "Interest payment to extend maturity and expiry dates."
                                                : ($isTubos 
                                                    ? "Principal + Service Charge + Additional Charge payment."
                                                    : ($isPartial
                                                        ? ($isPrincipalIncrease
                                                            ? "Principal increase that adds to the loan amount."
                                                            : "Partial payment that reduces principal amount.")
                                                        : ""));
                                            $amountLabel = $isRenewal ? "Interest Paid:" : ($isTubos ? "Amount Paid:" : ($isPartial ? ($isPrincipalIncrease ? "Principal Increase:" : "Partial Amount Paid:") : "Amount:"));
                                        @endphp
                                        <tr 
                                            class="{{ $bgColor }} transition-colors cursor-pointer transaction-row {{ $isVoided ? 'opacity-40' : '' }}"
                                            data-item-image="{{ route('images.show', ['path' => $childTransaction->item_image_path]) }}"
                                            data-pawner-image="{{ $childTransaction->pawner_id_image_path ? route('images.show', ['path' => $childTransaction->pawner_id_image_path]) : '' }}"
                                            data-pawn-ticket-image="{{ $childTransaction->pawn_ticket_image_path ? route('images.show', ['path' => $childTransaction->pawn_ticket_image_path]) : '' }}"
                                            data-signature-image="{{ $childTransaction->signature_path ? route('images.show', ['path' => $childTransaction->signature_path]) : '' }}"
                                            data-transaction-id="{{ $childTransaction->id }}"
                                            data-transaction-number="{{ $childTransaction->transaction_number }}"
                                            data-pawn-ticket-number="{{ $childTransaction->pawn_ticket_number ?? '' }}"
                                            data-item-type="{{ $childTransaction->itemType->name }}"
                                            data-item-subtype="{{ $childTransaction->itemTypeSubtype ? $childTransaction->itemTypeSubtype->name : '' }}"
                                            data-custom-item-type="{{ $childTransaction->custom_item_type ?? '' }}"
                                            data-item-description="{{ $childTransaction->item_description }}"
                                            data-item-tags="{{ $childTransaction->tags->pluck('name')->toJson() }}"
                                            data-transaction-date="{{ $childTransaction->created_at->format('M d, Y') }} {{ $childTransaction->created_at->format('h:i A') }}"
                                            data-is-voided="{{ $isVoided ? '1' : '0' }}"
                                            data-voided-by="{{ $voidedInfo && $voidedInfo->voidedBy ? $voidedInfo->voidedBy->name : '' }}"
                                            data-voided-at="{{ $voidedInfo && $voidedInfo->voided_at ? $voidedInfo->voided_at->format('M d, Y h:i A') : '' }}"
                                            data-void-reason="{{ $voidedInfo ? $voidedInfo->reason : '' }}"
                                            data-has-child-transactions="0"
                                            data-is-latest-child="{{ $isLatestChild ? '1' : '0' }}"
                                            data-is-older-than-6-hours="{{ $isOlderThan6Hours ? '1' : '0' }}"
                                            data-transaction-type="{{ $childTransaction->type }}"
                                            data-maturity-date="{{ $childTransaction->maturity_date ? $childTransaction->maturity_date->format('M d, Y') : '' }}"
                                            data-expiry-date="{{ $childTransaction->expiry_date ? $childTransaction->expiry_date->format('M d, Y') : '' }}"
                                            data-auction-sale-date="{{ $childTransaction->auction_sale_date ? $childTransaction->auction_sale_date->format('M d, Y') : '' }}"
                                            data-loan-amount="{{ number_format($childTransaction->loan_amount, 2) }}"
                                            data-interest-rate="{{ number_format($childTransaction->interest_rate, 2) }}"
                                            data-service-charge="{{ number_format($childTransaction->service_charge, 2) }}"
                                            data-net-proceeds="{{ number_format($childTransaction->net_proceeds, 2) }}"
                                            data-late-days-charge="{{ number_format($childTransaction->late_days_charge ?? 0, 2) }}"
                                            data-back-date="{{ $childTransaction->back_date ? '1' : '0' }}"
                                            data-note="{{ $childTransaction->note ?? '' }}"
                                            data-transaction-pawn-ticket="{{ $childTransaction->transaction_pawn_ticket ?? '' }}"
                                            data-orcr-serial="{{ $childTransaction->orcr_serial ?? '' }}"
                                            data-grams="{{ $childTransaction->grams ? number_format($childTransaction->grams, 1) : '' }}"
                                        >
                                            <td class="px-6 py-2 whitespace-nowrap {{ $pawnTicketNumber ? 'pl-12' : '' }}">
                                                <div class="text-xs font-semibold {{ $textColor }}">
                                                    {{ $label }}
                                                </div>
                                                <div class="text-[11px] text-gray-600 mt-1">
                                                    {{ $childTransaction->transaction_number }}
                                                </div>
                                                @if($childTransaction->transaction_pawn_ticket)
                                                    <div class="text-[11px] text-gray-500 mt-1">Pawn ticket: {{ $childTransaction->transaction_pawn_ticket }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <div class="text-xs text-gray-900">
                                                    {{ $childTransaction->created_at->format('M d, Y') }}
                                                </div>
                                                <div class="text-[11px] text-gray-500">
                                                    {{ $childTransaction->created_at->format('h:i A') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-2">
                                                <div class="text-xs font-medium text-gray-900">
                                                    {{ $description }}
                                                </div>
                                                @if($subDescription)
                                                    <div class="text-[11px] text-gray-500 mt-1">
                                                        {{ $subDescription }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <div class="flex flex-col gap-1">
                                                    <span class="px-2 inline-flex text-[11px] leading-5 font-semibold rounded-full {{ $badgeColor }}">
                                                        {{ $isRenewal ? 'Renew' : ($isTubos ? 'Tubos' : ($isPartial ? 'Partial' : ucfirst($childTransaction->type))) }}
                                                    </span>
                                                    @if($childTransaction->status === 'redeemed')
                                                        <span class="px-2 inline-flex text-[11px] leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                            Redeemed
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <div class="text-[11px] text-gray-500">{{ $amountLabel }}</div>
                                                @if($isPartial && $isPrincipalIncrease)
                                                    <div class="text-xs font-medium text-red-700">
                                                        -₱{{ number_format($childTransaction->net_proceeds, 2) }}
                                                    </div>
                                                @else
                                                    <div class="text-xs font-medium text-green-700">
                                                        +₱{{ number_format($childTransaction->net_proceeds, 2) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <div class="text-xs text-gray-900">
                                                    {{ $childTransaction->branch->name }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-2 whitespace-nowrap">
                                                <div class="text-xs text-gray-900">
                                                    {{ $childTransaction->user->name }}
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
                                            data-pawner-image="{{ $transaction->pawner_id_image_path ? route('images.show', ['path' => $transaction->pawner_id_image_path]) : '' }}"
                                            data-pawn-ticket-image="{{ $transaction->pawn_ticket_image_path ? route('images.show', ['path' => $transaction->pawn_ticket_image_path]) : '' }}"
                                            data-signature-image="{{ $transaction->signature_path ? route('images.show', ['path' => $transaction->signature_path]) : '' }}"
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
                                            data-orcr-serial="{{ $transaction->orcr_serial ?? '' }}"
                                            data-grams="{{ $transaction->grams ? number_format($transaction->grams, 1) : '' }}"
                                            data-back-date="{{ $transaction->back_date ? '1' : '0' }}"
                                            data-note="{{ $transaction->note ?? '' }}"
                                            data-transaction-pawn-ticket="{{ $transaction->transaction_pawn_ticket ?? '' }}"
                                        >
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-xs text-gray-500">Transaction #</div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $transaction->transaction_number }}</div>
                                                    @if($transaction->transaction_pawn_ticket)
                                                        <div class="text-xs text-gray-500 mt-1">Pawn ticket: {{ $transaction->transaction_pawn_ticket }}</div>
                                                    @endif
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
                                                    <div class="flex flex-col gap-1">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                            {{ $transaction->type === 'sangla' ? 'bg-blue-100 text-blue-800' : 
                                                               ($transaction->type === 'tubos' ? 'bg-green-100 text-green-800' : 
                                                               ($transaction->type === 'renew' ? 'bg-yellow-100 text-yellow-800' : 
                                                               ($transaction->type === 'partial' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'))) }}">
                                                            {{ $transaction->type === 'partial' ? 'Partial' : ucfirst($transaction->type) }}
                                                        </span>
                                                        @if($transaction->status === 'redeemed')
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                                Redeemed
                                                            </span>
                                                        @endif
                                                    </div>
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
                <!-- Voided Transaction Notice -->
                <div id="voidedTransactionNotice" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md hidden">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">This transaction has been voided</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p><strong>Voided by:</strong> <span id="modalVoidedBy">-</span></p>
                                <p><strong>Voided at:</strong> <span id="modalVoidedAt">-</span></p>
                                <p><strong>Reason:</strong> <span id="modalVoidReason">-</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Dated Transaction Notice -->
                <div id="backDatedTransactionNotice" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md hidden">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Back Dated Transaction</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>This transaction was processed with the "Back Date" option enabled. The renewal was processed as if it occurred on the maturity date, and no additional charges or late days charges were applied.</p>
                            </div>
                        </div>
                    </div>
                </div>

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
                        <div id="modalTransactionPawnTicketContainer" class="hidden">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Transaction Pawn Ticket</p>
                            <p id="modalTransactionPawnTicket" class="text-sm font-medium text-gray-900 mt-1">-</p>
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
                            <!-- ORCR/Serial (for Vehicle items) -->
                            <div id="modalOrcrSection" class="mt-2 hidden">
                                <p class="text-sm text-gray-600">OR&CR/Serial:</p>
                                <p id="modalOrcrSerial" class="text-sm font-medium text-gray-900 mt-1">-</p>
                            </div>
                            <!-- Grams (for Jewelry items) -->
                            <div id="modalGramsSection" class="mt-2 hidden">
                                <p class="text-sm text-gray-600">Grams:</p>
                                <p id="modalGrams" class="text-sm font-medium text-gray-900 mt-1">-</p>
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
                <div id="transactionSummarySection" class="mb-6">
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
                                <!-- Additional Charge row - only shown conditionally when it exists and is > 0 (for renewals) -->
                                <tr id="modalAdditionalChargeRow" class="hidden">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Additional Charge</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right" id="modalAdditionalCharge">₱0.00</td>
                                </tr>
                                <!-- Late Days Charge row - only shown conditionally when it exists and is > 0 -->
                                <tr id="modalLateDaysChargeRow" class="hidden">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Late Days Charge</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right" id="modalLateDaysCharge">₱0.00</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">Net Proceeds</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right" id="modalNetProceeds">₱0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Note Section -->
                <div id="noteSection" class="mb-6 hidden">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Note</h4>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-700 whitespace-pre-wrap" id="modalNote">-</p>
                        <p class="mt-2 text-xs text-gray-500">Optional notes added by staff for reference.</p>
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
                        <div id="modalItemImageSection">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="text-sm font-medium text-gray-700">Item Image</h5>
                                <button 
                                    type="button"
                                    onclick="toggleModalImage('modalItemImageContainer')"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 px-3 py-1 border border-indigo-300 rounded-md hover:bg-indigo-50 transition-colors"
                                >
                                    <span id="modalItemImageToggleText">Hide</span>
                                    <svg id="modalItemImageToggleIcon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="modalItemImageContainer" class="border-2 border-gray-200 rounded-lg overflow-hidden">
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
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="text-sm font-medium text-gray-700">All Item Images</h5>
                                <button 
                                    type="button"
                                    onclick="toggleModalImage('allItemImagesContainer')"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 px-3 py-1 border border-indigo-300 rounded-md hover:bg-indigo-50 transition-colors"
                                >
                                    <span id="allItemImagesToggleText">Hide</span>
                                    <svg id="allItemImagesToggleIcon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="allItemImagesContainer" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Item images will be dynamically inserted here -->
                            </div>
                        </div>
                        
                        <!-- Pawner ID Image -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="text-sm font-medium text-gray-700">Pawner ID/Photo</h5>
                                <button 
                                    type="button"
                                    onclick="toggleModalImage('modalPawnerImageContainer')"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 px-3 py-1 border border-indigo-300 rounded-md hover:bg-indigo-50 transition-colors"
                                >
                                    <span id="modalPawnerImageToggleText">Hide</span>
                                    <svg id="modalPawnerImageToggleIcon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="modalPawnerImageContainer" class="border-2 border-gray-200 rounded-lg overflow-hidden">
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
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="text-sm font-medium text-gray-700">Pawn Ticket</h5>
                                <button 
                                    type="button"
                                    onclick="toggleModalImage('modalPawnTicketImageContainer')"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 px-3 py-1 border border-indigo-300 rounded-md hover:bg-indigo-50 transition-colors"
                                >
                                    <span id="modalPawnTicketImageToggleText">Hide</span>
                                    <svg id="modalPawnTicketImageToggleIcon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="modalPawnTicketImageContainer" class="border-2 border-gray-200 rounded-lg overflow-hidden">
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
                        
                        <!-- Signature Image (for Tubos transactions) -->
                        <div id="signatureSection" class="hidden">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="text-sm font-medium text-gray-700">Pawner Signature</h5>
                                <button 
                                    type="button"
                                    onclick="toggleModalImage('modalSignatureImageContainer')"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 px-3 py-1 border border-indigo-300 rounded-md hover:bg-indigo-50 transition-colors"
                                >
                                    <span id="modalSignatureImageToggleText">Hide</span>
                                    <svg id="modalSignatureImageToggleIcon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="modalSignatureImageContainer" class="border-2 border-gray-200 rounded-lg overflow-hidden">
                                <img 
                                    id="modalSignatureImage" 
                                    src="" 
                                    alt="Pawner Signature" 
                                    class="w-auto h-full"
                                />
                                <div id="modalSignaturePlaceholder" class="hidden p-8 text-center text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm">No signature available</p>
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
        function setTodayOnly() {
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            // Clear existing date values
            if (startDateInput) startDateInput.value = '';
            if (endDateInput) endDateInput.value = '';
            
            // Set to today
            if (startDateInput) startDateInput.value = today;
            if (endDateInput) endDateInput.value = today;
            
            // Create a hidden input for today_only
            const form = document.querySelector('form[method="GET"]');
            if (form) {
                const existingInput = form.querySelector('input[name="today_only"]');
                if (existingInput) {
                    existingInput.remove();
                }
                const todayOnlyInput = document.createElement('input');
                todayOnlyInput.type = 'hidden';
                todayOnlyInput.name = 'today_only';
                todayOnlyInput.value = '1';
                form.appendChild(todayOnlyInput);
                
                // Submit the form
                form.submit();
            }
        }
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
                    const maturityDate = this.getAttribute('data-maturity-date') || '';
                    const expiryDate = this.getAttribute('data-expiry-date') || '';
                    const auctionSaleDate = this.getAttribute('data-auction-sale-date') || '';
                    
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
                        netProceeds,
                        maturityDate,
                        expiryDate,
                        auctionSaleDate
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
                    const signatureImageUrl = this.getAttribute('data-signature-image');
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
                    // Get date attributes - getAttribute returns null if attribute doesn't exist, empty string if attribute exists but is empty
                    const maturityDateAttr = this.getAttribute('data-maturity-date');
                    const expiryDateAttr = this.getAttribute('data-expiry-date');
                    const auctionSaleDateAttr = this.getAttribute('data-auction-sale-date');
                    
                    // Normalize: convert null or empty string to empty string for consistent handling
                    // Empty string will be handled in setDateValue function
                    const maturityDate = (maturityDateAttr && maturityDateAttr.trim() !== '') ? maturityDateAttr : '';
                    const expiryDate = (expiryDateAttr && expiryDateAttr.trim() !== '') ? expiryDateAttr : '';
                    const auctionSaleDate = (auctionSaleDateAttr && auctionSaleDateAttr.trim() !== '') ? auctionSaleDateAttr : '';
                    const loanAmount = this.getAttribute('data-loan-amount');
                    const interestRate = this.getAttribute('data-interest-rate');
                    const serviceCharge = this.getAttribute('data-service-charge');
                    const netProceeds = this.getAttribute('data-net-proceeds');
                    const lateDaysCharge = this.getAttribute('data-late-days-charge') || '0';
                    const orcrSerial = this.getAttribute('data-orcr-serial') || '';
                    const grams = this.getAttribute('data-grams') || '';
                    const note = this.getAttribute('data-note') || '';
                    const transactionPawnTicket = this.getAttribute('data-transaction-pawn-ticket') || '';
                    const backDateAttr = this.getAttribute('data-back-date');
                    const backDate = backDateAttr === '1';
                    console.log('Reading backDate attribute:', { backDateAttr, backDate, type: typeof backDate });
                    
                    showTransactionDetails({
                        itemImageUrl,
                        pawnerImageUrl,
                        pawnTicketImageUrl,
                        signatureImageUrl,
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
                        netProceeds,
                        lateDaysCharge: lateDaysCharge || '0',
                        orcrSerial,
                        grams,
                        note: note || '',
                        transactionPawnTicket: transactionPawnTicket || '',
                        backDate: backDate
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
            
            // Hide transaction pawn ticket for pawn ticket view (it's per transaction)
            const transactionPawnTicketContainer = document.getElementById('modalTransactionPawnTicketContainer');
            if (transactionPawnTicketContainer) {
                transactionPawnTicketContainer.classList.add('hidden');
            }
            
            // Hide note section for pawn ticket view (notes are per transaction)
            const noteSection = document.getElementById('noteSection');
            if (noteSection) {
                noteSection.classList.add('hidden');
            }
            
            // Hide item details section (we'll show all items instead)
            document.getElementById('itemDetailsSection').classList.add('hidden');
            
            // Hide transaction summary for pawn tickets
            const summarySection = document.getElementById('transactionSummarySection');
            if (summarySection) {
                summarySection.style.display = 'none';
            }
            
            // Set dates from latest transaction
            const maturityDateEl = document.getElementById('modalMaturityDate');
            const expiryDateEl = document.getElementById('modalExpiryDate');
            const auctionSaleDateEl = document.getElementById('modalAuctionSaleDate');
            
            // Helper function to check if a value is a valid non-empty string
            const isValidDate = (value) => {
                return value != null && value !== undefined && typeof value === 'string' && value.trim() !== '';
            };
            
            if (maturityDateEl) {
                maturityDateEl.textContent = isValidDate(data.maturityDate) ? data.maturityDate : '-';
            }
            if (expiryDateEl) {
                expiryDateEl.textContent = isValidDate(data.expiryDate) ? data.expiryDate : '-';
            }
            if (auctionSaleDateEl) {
                auctionSaleDateEl.textContent = isValidDate(data.auctionSaleDate) ? data.auctionSaleDate : '-';
            }
            
            // Set pawner image (handle null/empty)
            const modalPawnerImage = document.getElementById('modalPawnerImage');
            const modalPawnerImageContainer = document.getElementById('modalPawnerImageContainer');
            if (modalPawnerImage && modalPawnerImageContainer) {
                if (data.pawnerImageUrl && data.pawnerImageUrl.trim() !== '') {
                    modalPawnerImage.src = data.pawnerImageUrl;
                    modalPawnerImageContainer.classList.remove('hidden');
                } else {
                    modalPawnerImage.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23e5e7eb\' width=\'400\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'14\'%3ENo image available%3C/text%3E%3C/svg%3E';
                }
            }
            
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
            
            // Ensure transaction summary is visible for individual transactions
            const summarySection = document.getElementById('transactionSummarySection');
            if (summarySection) {
                summarySection.style.display = 'block';
                summarySection.classList.remove('hidden');
            }
            
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
            
            // Show/hide back dated transaction notice
            const backDatedNotice = document.getElementById('backDatedTransactionNotice');
            // Handle backDate: it can be boolean true/false, string '1'/'0', or number 1/0
            // Convert to boolean for consistent checking
            const backDateValue = data.backDate;
            // More robust check - handle all possible formats
            let isBackDated = false;
            if (backDateValue === true || backDateValue === 'true' || backDateValue === 1 || backDateValue === '1') {
                isBackDated = true;
            } else if (typeof backDateValue === 'string' && backDateValue.toLowerCase() === 'true') {
                isBackDated = true;
            } else if (backDateValue === false || backDateValue === 'false' || backDateValue === 0 || backDateValue === '0' || backDateValue === null || backDateValue === undefined) {
                isBackDated = false;
            }
            
            console.log('Back date notice check:', {
                backDateValue: backDateValue,
                backDateType: typeof backDateValue,
                backDateString: String(backDateValue),
                isBackDated: isBackDated,
                noticeElement: !!backDatedNotice
            });
            
            if (backDatedNotice) {
                if (isBackDated) {
                    backDatedNotice.classList.remove('hidden');
                    backDatedNotice.style.display = 'block';
                    backDatedNotice.style.visibility = 'visible';
                    console.log('Back dated notice should be visible now');
                } else {
                    backDatedNotice.classList.add('hidden');
                    backDatedNotice.style.display = 'none';
                    console.log('Back dated notice hidden - backDateValue:', backDateValue, 'type:', typeof backDateValue);
                }
            } else {
                console.error('Back dated notice element not found!');
            }
            
            // Set transaction number
            document.getElementById('modalTransactionNumber').textContent = data.transactionNumber;
            
            // Set transaction date
            document.getElementById('modalTransactionDate').textContent = data.transactionDate || '-';
            
            // Set pawn ticket number
            document.getElementById('modalPawnTicketNumber').textContent = data.pawnTicketNumber || '-';
            
            // Show/hide transaction pawn ticket (only for renewal, partial, tubos)
            const transactionPawnTicketContainer = document.getElementById('modalTransactionPawnTicketContainer');
            const modalTransactionPawnTicket = document.getElementById('modalTransactionPawnTicket');
            if (transactionPawnTicketContainer && modalTransactionPawnTicket) {
                if (data.transactionPawnTicket && data.transactionPawnTicket.trim() !== '') {
                    transactionPawnTicketContainer.classList.remove('hidden');
                    modalTransactionPawnTicket.textContent = data.transactionPawnTicket;
                } else {
                    transactionPawnTicketContainer.classList.add('hidden');
                    modalTransactionPawnTicket.textContent = '-';
                }
            }
            
            // Show/hide note section
            const noteSection = document.getElementById('noteSection');
            const modalNote = document.getElementById('modalNote');
            if (noteSection && modalNote) {
                if (data.note && data.note.trim() !== '') {
                    noteSection.classList.remove('hidden');
                    modalNote.textContent = data.note;
                } else {
                    noteSection.classList.add('hidden');
                    modalNote.textContent = '-';
                }
            }
            
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

            // Show/hide ORCR section for Vehicle items
            const orcrSection = document.getElementById('modalOrcrSection');
            const orcrSerialEl = document.getElementById('modalOrcrSerial');
            const isVehicle = data.itemType && (data.itemType.toLowerCase() === 'vehicle' || data.itemType.toLowerCase() === 'vehicles' || data.itemType.toLowerCase() === 'cars');
            
            if (isVehicle && orcrSection && orcrSerialEl) {
                orcrSection.classList.remove('hidden');
                orcrSerialEl.textContent = data.orcrSerial && data.orcrSerial.trim() !== '' ? data.orcrSerial : '-';
            } else if (orcrSection) {
                orcrSection.classList.add('hidden');
            }

            // Show/hide Grams section for Jewelry items
            const gramsSection = document.getElementById('modalGramsSection');
            const gramsEl = document.getElementById('modalGrams');
            const isJewelry = data.itemType && data.itemType.toLowerCase() === 'jewelry';
            
            if (isJewelry && gramsSection && gramsEl) {
                gramsSection.classList.remove('hidden');
                if (data.grams && data.grams.trim() !== '' && parseFloat(data.grams) > 0) {
                    // Format grams value (remove trailing zeros if needed)
                    const gramsValue = parseFloat(data.grams);
                    gramsEl.textContent = gramsValue % 1 === 0 ? gramsValue.toFixed(0) + ' g' : gramsValue.toFixed(1) + ' g';
                } else {
                    gramsEl.textContent = '-';
                }
            } else if (gramsSection) {
                gramsSection.classList.add('hidden');
            }

            // Show transaction summary - always show for individual transactions (already set above)
            if (summarySection) {
                summarySection.style.display = 'block';
                summarySection.classList.remove('hidden');
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
                // Show the single Item Image section
                const modalItemImageSection = document.getElementById('modalItemImageSection');
                if (modalItemImageSection) {
                    modalItemImageSection.classList.remove('hidden');
                }
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
            
            // Set dates (handle null, undefined, and empty string values)
            const maturityDateEl = document.getElementById('modalMaturityDate');
            const expiryDateEl = document.getElementById('modalExpiryDate');
            const auctionSaleDateEl = document.getElementById('modalAuctionSaleDate');
            
            // Helper function to check if a value is a valid non-empty string
            const isValidDate = (value) => {
                return value != null && value !== undefined && typeof value === 'string' && value.trim() !== '';
            };
            
            if (maturityDateEl) {
                maturityDateEl.textContent = isValidDate(data.maturityDate) ? data.maturityDate : '-';
            }
            if (expiryDateEl) {
                expiryDateEl.textContent = isValidDate(data.expiryDate) ? data.expiryDate : '-';
            }
            if (auctionSaleDateEl) {
                auctionSaleDateEl.textContent = isValidDate(data.auctionSaleDate) ? data.auctionSaleDate : '-';
            }
            
            // Calculate and set transaction summary
            const principal = parseFloat(data.loanAmount.replace(/,/g, '')) || 0;
            const interestRate = parseFloat(data.interestRate) || 0;
            const serviceCharge = parseFloat(data.serviceCharge.replace(/,/g, '')) || 0;
            const interest = principal * (interestRate / 100);
            const netProceeds = parseFloat(data.netProceeds.replace(/,/g, '')) || 0;
            const lateDaysCharge = parseFloat((data.lateDaysCharge || '0').replace(/,/g, '')) || 0;
            
            // For renewal transactions, calculate additional charge from net_proceeds
            // net_proceeds = interest + service_charge + additional_charge + late_days_charge
            // So: additional_charge = net_proceeds - interest - service_charge - late_days_charge
            const isRenewal = data.transactionType === 'renew';
            let additionalCharge = 0;
            if (isRenewal) {
                additionalCharge = netProceeds - interest - serviceCharge - lateDaysCharge;
                // Round to 2 decimal places and ensure non-negative
                additionalCharge = Math.max(0, Math.round(additionalCharge * 100) / 100);
            }
            
            document.getElementById('modalPrincipal').textContent = '₱' + principal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            document.getElementById('modalInterest').textContent = '₱' + interest.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            document.getElementById('modalServiceCharge').textContent = '₱' + serviceCharge.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            document.getElementById('modalNetProceeds').textContent = '₱' + netProceeds.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            
            // Show/hide additional charge row (only for renewals when > 0)
            const additionalChargeRow = document.getElementById('modalAdditionalChargeRow');
            if (additionalChargeRow) {
                if (isRenewal && additionalCharge > 0) {
                    additionalChargeRow.classList.remove('hidden');
                    document.getElementById('modalAdditionalCharge').textContent = '₱' + additionalCharge.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                } else {
                    additionalChargeRow.classList.add('hidden');
                }
            }
            
            // Show/hide late days charge row (only when > 0)
            const lateDaysChargeRow = document.getElementById('modalLateDaysChargeRow');
            if (lateDaysChargeRow) {
                if (lateDaysCharge > 0) {
                    lateDaysChargeRow.classList.remove('hidden');
                    document.getElementById('modalLateDaysCharge').textContent = '₱' + lateDaysCharge.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                } else {
                    lateDaysChargeRow.classList.add('hidden');
                }
            }
            
            // Hide "All Item Images" section for individual transactions
            document.getElementById('allItemImagesSection').classList.add('hidden');
            // Show the single Item Image section for individual transactions
            const modalItemImageSection = document.getElementById('modalItemImageSection');
            if (modalItemImageSection) {
                modalItemImageSection.classList.remove('hidden');
            }
            
            // Set images
            document.getElementById('modalItemImage').src = data.itemImageUrl;
            const modalPawnerImage = document.getElementById('modalPawnerImage');
            const modalPawnerImageContainer = document.getElementById('modalPawnerImageContainer');
            if (modalPawnerImage && modalPawnerImageContainer) {
                if (data.pawnerImageUrl && data.pawnerImageUrl.trim() !== '') {
                    modalPawnerImage.src = data.pawnerImageUrl;
                    modalPawnerImageContainer.classList.remove('hidden');
                } else {
                    modalPawnerImage.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23e5e7eb\' width=\'400\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'14\'%3ENo image available%3C/text%3E%3C/svg%3E';
                }
            }
            
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
            
            // Handle signature image (only for tubos transactions)
            const signatureSection = document.getElementById('signatureSection');
            const signatureImage = document.getElementById('modalSignatureImage');
            const signaturePlaceholder = document.getElementById('modalSignaturePlaceholder');
            
            if (data.transactionType === 'tubos' || data.transactionType === 'partial') {
                signatureSection.classList.remove('hidden');
                if (data.signatureImageUrl && data.signatureImageUrl.trim() !== '') {
                    signatureImage.src = data.signatureImageUrl;
                    signatureImage.classList.remove('hidden');
                    signaturePlaceholder.classList.add('hidden');
                } else {
                    signatureImage.classList.add('hidden');
                    signaturePlaceholder.classList.remove('hidden');
                }
            } else {
                signatureSection.classList.add('hidden');
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
                    const modalItemImageSection = document.getElementById('modalItemImageSection');

                    // Clear previous content
                    allItemsContainer.innerHTML = '';
                    allItemImagesContainer.innerHTML = '';

                    if (data.items && data.items.length > 0) {
                        // Show all items section
                        allItemsSection.classList.remove('hidden');
                        allItemImagesSection.classList.remove('hidden');
                        // Hide the single Item Image section completely
                        if (modalItemImageSection) {
                            modalItemImageSection.classList.add('hidden');
                        }

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
                        // Show the single Item Image section
                        if (modalItemImageSection) {
                            modalItemImageSection.classList.remove('hidden');
                        }
                        document.getElementById('itemDetailsSection').classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error fetching related items:', error);
                    // Hide sections on error
                    document.getElementById('allItemsSection').classList.add('hidden');
                    document.getElementById('allItemImagesSection').classList.add('hidden');
                    // Show the single Item Image section
                    const modalItemImageSection = document.getElementById('modalItemImageSection');
                    if (modalItemImageSection) {
                        modalItemImageSection.classList.remove('hidden');
                    }
                    document.getElementById('itemDetailsSection').classList.remove('hidden');
                });
        }

        // Function to toggle modal image visibility
        function toggleModalImage(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            // Determine which toggle elements to update based on container ID
            let toggleTextId, toggleIconId;
            
            if (containerId === 'modalItemImageContainer') {
                toggleTextId = 'modalItemImageToggleText';
                toggleIconId = 'modalItemImageToggleIcon';
            } else if (containerId === 'allItemImagesContainer') {
                toggleTextId = 'allItemImagesToggleText';
                toggleIconId = 'allItemImagesToggleIcon';
            } else if (containerId === 'modalPawnerImageContainer') {
                toggleTextId = 'modalPawnerImageToggleText';
                toggleIconId = 'modalPawnerImageToggleIcon';
            } else if (containerId === 'modalPawnTicketImageContainer') {
                toggleTextId = 'modalPawnTicketImageToggleText';
                toggleIconId = 'modalPawnTicketImageToggleIcon';
            } else if (containerId === 'modalSignatureImageContainer') {
                toggleTextId = 'modalSignatureImageToggleText';
                toggleIconId = 'modalSignatureImageToggleIcon';
            }
            
            const toggleText = toggleTextId ? document.getElementById(toggleTextId) : null;
            const toggleIcon = toggleIconId ? document.getElementById(toggleIconId) : null;
            
            if (container) {
                const isHidden = container.classList.contains('hidden');
                
                if (isHidden) {
                    // Show image
                    container.classList.remove('hidden');
                    if (toggleText) toggleText.textContent = 'Hide';
                    if (toggleIcon) toggleIcon.style.transform = 'rotate(0deg)';
                } else {
                    // Hide image
                    container.classList.add('hidden');
                    if (toggleText) toggleText.textContent = 'Show';
                    if (toggleIcon) toggleIcon.style.transform = 'rotate(180deg)';
                }
            }
        }

        function closeTransactionImagesModal() {
            // Reset summary section visibility when closing modal
            const summarySection = document.getElementById('transactionSummarySection');
            if (summarySection) {
                summarySection.style.display = 'block';
                summarySection.classList.remove('hidden');
            }
            // Hide notices when closing modal
            const voidedNotice = document.getElementById('voidedTransactionNotice');
            if (voidedNotice) {
                voidedNotice.classList.add('hidden');
            }
            const backDatedNotice = document.getElementById('backDatedTransactionNotice');
            if (backDatedNotice) {
                backDatedNotice.classList.add('hidden');
            }
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

        // Handle hide/show voided transactions checkbox
        const hideVoidedCheckbox = document.getElementById('hide_voided');
        if (hideVoidedCheckbox) {
            // Function to toggle voided transactions visibility
            function toggleVoidedTransactions() {
                const hideVoided = hideVoidedCheckbox.checked;
                
                // Hide/show all voided transaction rows
                const voidedRows = document.querySelectorAll('[data-is-voided="1"]');
                voidedRows.forEach(row => {
                    if (hideVoided) {
                        row.style.display = 'none';
                    } else {
                        row.style.display = '';
                    }
                });
                
                // Handle pawn ticket header rows - hide if all child transactions are voided
                const allPawnTicketRows = document.querySelectorAll('.pawn-ticket-row');
                allPawnTicketRows.forEach(pawnTicketRow => {
                    if (hideVoided) {
                        const tbody = pawnTicketRow.closest('tbody');
                        if (tbody) {
                            // Get all transaction rows in this tbody (excluding the pawn ticket row itself)
                            const allChildRows = Array.from(tbody.querySelectorAll('.transaction-row'));
                            const hasNonVoidedChild = allChildRows.some(row => {
                                return row.getAttribute('data-is-voided') !== '1';
                            });
                            // Hide pawn ticket row if all children are voided
                            if (!hasNonVoidedChild && allChildRows.length > 0) {
                                pawnTicketRow.style.display = 'none';
                            } else {
                                pawnTicketRow.style.display = '';
                            }
                        }
                    } else {
                        pawnTicketRow.style.display = '';
                    }
                });
            }
            
            // Initial toggle on page load
            toggleVoidedTransactions();
            
            // Toggle on checkbox change
            hideVoidedCheckbox.addEventListener('change', toggleVoidedTransactions);
        }

        // Handle void form submission
        document.getElementById('voidTransactionForm').addEventListener('submit', function(e) {
            const reason = document.getElementById('void_reason').value.trim();
            if (reason.length < 5) {
                e.preventDefault();
                alert('Please provide a reason with at least 5 characters.');
                return false;
            }
        });

        // QR Code Modal Functions
        function showQRCode(pawnTicketNumber) {
            const modal = document.getElementById('qrCodeModal');
            const qrCodeContainer = document.getElementById('qrCodeContainer');
            const pawnTicketElement = document.getElementById('qrCodePawnTicket');
            
            if (!modal || !qrCodeContainer || !pawnTicketElement) {
                alert('QR Code modal elements not found');
                return;
            }
            
            // Clear previous QR code
            qrCodeContainer.innerHTML = '';
            pawnTicketElement.textContent = pawnTicketNumber;
            
            // Generate QR code using qrcode.js library
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(qrCodeContainer, {
                        text: pawnTicketNumber,
                        width: 256,
                        height: 256,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } catch (err) {
                    console.error('Error generating QR code:', err);
                    // Fallback to API
                    generateQRCodeFallback(qrCodeContainer, pawnTicketNumber);
                }
            } else {
                // Fallback: use QR code API
                generateQRCodeFallback(qrCodeContainer, pawnTicketNumber);
            }
            
            modal.showModal();
        }

        function generateQRCodeFallback(container, text) {
            const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=${encodeURIComponent(text)}`;
            const img = document.createElement('img');
            img.src = qrCodeUrl;
            img.alt = 'QR Code';
            img.className = 'mx-auto';
            img.onerror = function() {
                container.innerHTML = '<p class="text-red-600">Failed to generate QR code</p>';
            };
            container.appendChild(img);
        }

        function closeQRCodeModal() {
            const modal = document.getElementById('qrCodeModal');
            if (modal) {
                modal.close();
            }
        }

        // Close QR code modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const qrModal = document.getElementById('qrCodeModal');
            if (qrModal) {
                qrModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeQRCodeModal();
                    }
                });
            }
        });
    </script>

    <!-- QR Code Modal -->
    <dialog id="qrCodeModal" class="rounded-lg p-0 w-[90vw] max-w-md backdrop:bg-black/50">
        <div class="bg-white rounded-lg">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">QR Code</h3>
                <button
                    onclick="closeQRCodeModal()"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div class="text-center">
                    <p class="text-sm text-gray-600 mb-4">Pawn Ticket Number:</p>
                    <p class="text-lg font-semibold text-gray-900 mb-6" id="qrCodePawnTicket"></p>
                    <div id="qrCodeContainer" class="flex items-center justify-center bg-white p-4 rounded-lg border border-gray-200"></div>
                    <p class="text-xs text-gray-500 mt-4">Scan this QR code to quickly access this pawn ticket</p>
                </div>
            </div>
        </div>
    </dialog>

    <!-- QR Code Library -->
    <script src="/js/qrcode.min.js"></script>
</x-app-layout>

