<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Partial Payment
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
                        <div class="flex flex-col gap-4">
                            <div>
                                <p class="text-sm text-blue-800">
                                    <strong>Pawn Ticket Number:</strong> {{ $pawnTicketNumber }}
                                </p>
                                <p class="text-sm text-blue-700 mt-1">
                                    <strong>Found {{ $allTransactions->count() }} transaction(s)</strong> - Partial payment will reduce the principal amount.
                                </p>
                                @if($allTransactions->count() > 1)
                                    <p class="text-xs text-blue-600 mt-1">
                                        <strong>Note:</strong> All item descriptions will be combined in the partial transaction.
                                    </p>
                                @endif
                            </div>
                            @if($transaction->pawn_ticket_image_path)
                                <div>
                                    <div class="text-xs text-blue-600 mb-1">Pawn Ticket:</div>
                                    <img 
                                        src="{{ route('images.show', ['path' => $transaction->pawn_ticket_image_path]) }}" 
                                        alt="Pawn Ticket" 
                                        class="w-full h-auto object-cover rounded-lg border-2 border-blue-300 shadow-sm"
                                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'192\' height=\'256\'%3E%3Crect fill=\'%23e5e7eb\' width=\'192\' height=\'256\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'12\'%3ENo Image%3C/text%3E%3C/svg%3E'"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <h3 class="text-lg font-semibold text-yellow-900 mb-3">Payment Information</h3>
                        <div class="space-y-2">
                            <!-- Interest -->
                            <div class="mb-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-yellow-800">
                                        Interest (₱{{ number_format($currentPrincipalAmount, 2) }} × {{ $transaction->interest_rate }}%):
                                    </span>
                                    <span class="font-medium text-yellow-900">₱{{ number_format($totalInterest, 2) }}</span>
                                </div>
                            </div>
                            
                            <!-- Service Charge -->
                            <div class="mb-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-yellow-800">Service Charge:</span>
                                    <span class="font-medium text-yellow-900">₱{{ number_format($totalServiceCharge, 2) }}</span>
                                </div>
                            </div>
                            
                            <!-- Additional Charge -->
                            <div class="mb-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-yellow-800">
                                        @if($additionalChargeAmount > 0 && $additionalChargeConfig)
                                            Additional Charge ({{ $additionalChargeType === 'EC' ? 'Exceeded Charge' : 'Late Days' }} - {{ $daysExceeded }} day(s), {{ $additionalChargeConfig->percentage }}%):
                                        @else
                                            Additional Charge:
                                        @endif
                                    </span>
                                    <span class="font-medium text-yellow-900">₱{{ number_format($additionalChargeAmount, 2) }}</span>
                                </div>
                            </div>
                            
                            <!-- Late Days Charge -->
                            @if($lateDaysCharge > 0)
                            <div class="mb-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-yellow-800">
                                        @if($lateDaysChargeBreakdown)
                                            Late Days Charge ({{ $lateDaysChargeBreakdown['late_days'] }} day(s)):
                                        @else
                                            Late Days Charge:
                                        @endif
                                    </span>
                                    <span class="font-medium text-yellow-900">₱{{ number_format($lateDaysCharge, 2) }}</span>
                                </div>
                            </div>
                            @endif
                            
                            <!-- Minimum Renewal Amount (Reference Guide) -->
                            <div class="border-t-2 border-yellow-400 pt-3 mt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-yellow-900">Minimum Renewal Amount (Reference):</span>
                                    <span class="text-lg font-bold text-yellow-900">₱{{ number_format($minimumRenewalAmount, 2) }}</span>
                                </div>
                                <p class="text-xs text-yellow-700 mt-1">This is a reference guide only. You can enter any amount (positive or negative). Positive values reduce principal, negative values increase principal.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pawner ID Verification -->
                    @if($transaction->pawner_id_image_path)
                        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Pawner ID Verification</h3>
                            <p class="text-sm text-gray-600 mb-3">Please verify that the person making this partial payment matches the ID shown below:</p>
                            <div class="border-2 border-gray-300 rounded-lg overflow-hidden bg-white">
                                <img 
                                    src="{{ route('images.show', ['path' => $transaction->pawner_id_image_path]) }}" 
                                    alt="Pawner ID Image" 
                                    class="w-full h-auto max-w-md mx-auto"
                                    onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23e5e7eb\' width=\'400\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'14\'%3EImage not available%3C/text%3E%3C/svg%3E'"
                                />
                            </div>
                            <p class="text-xs text-gray-500 mt-2 text-center">Pawner: <strong>{{ $transaction->first_name }} {{ $transaction->last_name }}</strong></p>
                            <p class="text-xs text-gray-500 mt-2 text-center">Address: <strong>{{ $transaction->address }}</strong></p>
                        </div>
                    @endif

                    <!-- Display Transaction Details -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Transaction Details</h3>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="mb-4 pb-4 border-b border-gray-200">
                                <p class="text-sm text-gray-600 mb-2">All Transaction Numbers:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($allTransactions as $tx)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-300">
                                            {{ $tx->transaction_number }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
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
                                    <p class="text-sm text-gray-600">Loan Amount</p>
                                    <div class="flex items-center gap-2">
                                        @if($currentPrincipalAmount != $originalPrincipalAmount)
                                            <span class="text-sm font-medium text-gray-500 line-through">₱{{ number_format($originalPrincipalAmount, 2) }}</span>
                                            <span class="text-sm font-medium text-blue-600">₱{{ number_format($currentPrincipalAmount, 2) }}</span>
                                        @else
                                            <p class="text-sm font-medium text-gray-900">₱{{ number_format($currentPrincipalAmount, 2) }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Interest Rate</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $transaction->interest_rate }}%</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Current Maturity Date</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $latestTransaction->maturity_date ? \Carbon\Carbon::parse($latestTransaction->maturity_date)->format('M d, Y') : ($transaction->maturity_date ? $transaction->maturity_date->format('M d, Y') : 'N/A') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Current Expiry Date</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $transaction->expiry_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            
                            @if($partialTransactions->count() > 0)
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <p class="text-sm font-medium text-gray-900 mb-3">Loan Amount History</p>
                                    <div class="space-y-2">
                                        @php
                                            // Build array of all principal amounts in chronological order
                                            $principalHistory = [];
                                            $principalHistory[] = [
                                                'amount' => $originalPrincipalAmount,
                                                'label' => 'Original Principal',
                                                'date' => $transaction->created_at,
                                                'isCurrent' => false
                                            ];
                                            
                                            foreach($partialTransactions as $partialTx) {
                                                $principalHistory[] = [
                                                    'amount' => (float) $partialTx->loan_amount,
                                                    'label' => 'After Partial Payment',
                                                    'date' => $partialTx->created_at,
                                                    'isCurrent' => false
                                                ];
                                            }
                                            
                                            // Mark the last one as current
                                            if(count($principalHistory) > 0) {
                                                $principalHistory[count($principalHistory) - 1]['isCurrent'] = true;
                                            }
                                        @endphp
                                        
                                        @foreach($principalHistory as $entry)
                                            <div class="flex items-center justify-between text-sm {{ $entry['isCurrent'] ? 'bg-blue-50 p-2 rounded' : '' }}">
                                                <span class="text-gray-600">
                                                    {{ $entry['label'] }}
                                                    @if($entry['label'] === 'After Partial Payment')
                                                        ({{ $entry['date']->format('M d, Y') }})
                                                    @endif
                                                    :
                                                </span>
                                                @if($entry['isCurrent'])
                                                    <span class="font-bold text-blue-600">₱{{ number_format($entry['amount'], 2) }}</span>
                                                @else
                                                    <span class="font-medium text-gray-500 line-through">₱{{ number_format($entry['amount'], 2) }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                        </div>
                    </div>

                    <form method="POST" action="{{ route('transactions.partial.store') }}" id="partialForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="pawn_ticket_number" value="{{ $pawnTicketNumber }}">
                        
                        <!-- Items List -->
                        @if($allTransactions->count() > 0)
                            <div class="mb-6">
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <p class="text-sm font-medium text-gray-700 mb-3">Items in this Pawn Ticket:</p>
                                    @if($allTransactions->count() > 1)
                                        <p class="text-xs text-blue-600 mb-3">Select items to redeem (tubos) - remaining items will be processed as partial payment:</p>
                                    @endif
                                    <div class="space-y-3">
                                        @foreach($allTransactions as $tx)
                                            @php
                                                $isRedeemed = $tx->status === 'redeemed';
                                                $redeemedViaTubos = $isRedeemed && $tubosTransaction;
                                                $redeemedViaPartial = $isRedeemed && !$tubosTransaction;
                                                
                                                $redemptionDate = null;
                                                $redemptionTransactionPawnTicket = null;
                                                
                                                if ($redeemedViaTubos && $tubosTransaction) {
                                                    $redemptionDate = $tubosTransaction->created_at;
                                                    $redemptionTransactionPawnTicket = $tubosTransaction->transaction_pawn_ticket;
                                                } elseif ($redeemedViaPartial) {
                                                    // Find the partial transaction that marked this item as redeemed
                                                    $partialAfterItem = $partialTransactionsForRedemption->filter(function($pt) use ($tx) {
                                                        return $pt->created_at >= $tx->created_at;
                                                    })->first();
                                                    if ($partialAfterItem) {
                                                        $redemptionDate = $partialAfterItem->created_at;
                                                        $redemptionTransactionPawnTicket = $partialAfterItem->transaction_pawn_ticket;
                                                    }
                                                }
                                            @endphp
                                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 {{ $isRedeemed ? 'opacity-75' : '' }}">
                                                <div class="flex items-start gap-4">
                                                    @if($allTransactions->count() > 1 && !$isRedeemed)
                                                        <div class="flex items-center h-5 mt-1">
                                                            <input 
                                                                id="item_{{ $tx->id }}" 
                                                                name="selected_items[]" 
                                                                type="checkbox" 
                                                                value="{{ $tx->id }}"
                                                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 item-checkbox"
                                                            />
                                                        </div>
                                                    @endif
                                                    @if($tx->item_image_path)
                                                        <div class="flex-shrink-0 relative">
                                                            <img 
                                                                src="{{ route('images.show', ['path' => $tx->item_image_path]) }}" 
                                                                alt="Item Image" 
                                                                class="w-24 h-24 object-cover rounded-lg border border-gray-300 {{ $isRedeemed ? 'blur-sm' : '' }}"
                                                                onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'96\' height=\'96\'%3E%3Crect fill=\'%23e5e7eb\' width=\'96\' height=\'96\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'12\'%3ENo Image%3C/text%3E%3C/svg%3E'"
                                                            />
                                                            @if($isRedeemed)
                                                                <div class="absolute inset-0 flex items-center justify-center">
                                                                    <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">REDEEMED</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    <div class="flex-1">
                                                        @if($allTransactions->count() > 1 && !$isRedeemed)
                                                            <label for="item_{{ $tx->id }}" class="cursor-pointer">
                                                        @endif
                                                            <p class="text-sm font-medium text-gray-900">
                                                                {{ $tx->itemType->name }}
                                                                @if($tx->itemTypeSubtype)
                                                                    <span class="text-gray-600">- {{ $tx->itemTypeSubtype->name }}</span>
                                                                @endif
                                                                @if($tx->custom_item_type)
                                                                    <span class="text-gray-600">- {{ $tx->custom_item_type }}</span>
                                                                @endif
                                                            </p>
                                                            <p class="text-sm text-gray-700 mt-1">{{ $tx->item_description }}</p>
                                                            @if($tx->tags && $tx->tags->count() > 0)
                                                                <div class="flex flex-wrap gap-1 mt-2">
                                                                    @foreach($tx->tags as $tag)
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                                            {{ $tag->name }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            <p class="text-xs text-gray-500 mt-2">Transaction: {{ $tx->transaction_number }}</p>
                                                            @if($isRedeemed && $redemptionDate)
                                                                <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded">
                                                                    <p class="text-xs text-red-700">
                                                                        <strong>Redeemed:</strong> {{ $redemptionDate->format('M d, Y') }}
                                                                        @if($redemptionTransactionPawnTicket)
                                                                            <br><strong>Pawn Ticket:</strong> {{ $redemptionTransactionPawnTicket }}
                                                                        @endif
                                                                    </p>
                                                                </div>
                                                            @endif
                                                        @if($allTransactions->count() > 1 && !$isRedeemed)
                                                            </label>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-6">
                            <!-- Partial Amount Input -->
                            <div>
                                <x-input-label for="partial_amount" value="Partial Payment Amount *" />
                                <x-text-input 
                                    id="partial_amount" 
                                    name="partial_amount" 
                                    type="number" 
                                    step="0.01" 
                                    class="mt-1 block w-full" 
                                    :value="old('partial_amount')" 
                                    required 
                                    autofocus
                                />
                                <x-input-error :messages="$errors->get('partial_amount')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Positive: Payment (reduces principal) | Negative: Increase principal (pawner adds money)
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    Reference: Minimum renewal amount is ₱{{ number_format($minimumRenewalAmount, 2) }} (for guidance only)
                                </p>
                                <p class="mt-1 text-xs text-blue-600" id="newPrincipalInfo">
                                    <!-- Will be populated by JavaScript -->
                                </p>
                            </div>

                            <!-- New Principal Amount (Calculated, Readonly) -->
                            <div>
                                <x-input-label for="new_principal_amount" value="New Principal Amount (After Payment)" />
                                <x-text-input 
                                    id="new_principal_amount" 
                                    name="new_principal_amount_display" 
                                    type="text" 
                                    class="mt-1 block w-full bg-gray-100 font-semibold" 
                                    value="₱{{ number_format($currentPrincipalAmount, 2) }}" 
                                    readonly
                                    disabled
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    This will be calculated automatically based on your partial payment amount.
                                </p>
                            </div>

                            <!-- Back Date Checkbox -->
                            @if($isOverdue)
                            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="back_date" 
                                            name="back_date" 
                                            type="checkbox" 
                                            value="1"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                            {{ old('back_date', $backDate) ? 'checked' : '' }}
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="back_date" class="font-medium text-yellow-900 cursor-pointer">
                                            Back Date
                                        </label>
                                        <p class="text-xs text-yellow-700 mt-1">
                                            If checked, this partial payment will be processed as if it was made on the maturity date. Additional charges and late days charges will not apply.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Date Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="maturity_date" value="New Maturity Date *" />
                                    <x-text-input 
                                        id="maturity_date" 
                                        name="maturity_date" 
                                        type="date" 
                                        class="mt-1 block w-full bg-gray-100" 
                                        :value="old('maturity_date', $defaultMaturityDate)" 
                                        readonly
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('maturity_date')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="expiry_date" value="New Expiry Redemption Date *" />
                                    <x-text-input 
                                        id="expiry_date" 
                                        name="expiry_date" 
                                        type="date" 
                                        class="mt-1 block w-full bg-gray-100" 
                                        :value="old('expiry_date', $defaultExpiryDate)" 
                                        readonly
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="auction_sale_date" value="New Auction Sale Date" />
                                    <x-text-input 
                                        id="auction_sale_date" 
                                        name="auction_sale_date" 
                                        type="date" 
                                        class="mt-1 block w-full bg-gray-100" 
                                        :value="old('auction_sale_date', $defaultAuctionSaleDate)" 
                                        readonly
                                    />
                                    <x-input-error :messages="$errors->get('auction_sale_date')" class="mt-2" />
                                </div>
                            </div>
                            
                            <!-- Hidden input for late days charge -->
                            <input type="hidden" name="late_days_charge_amount" value="{{ number_format($lateDaysCharge, 2, '.', '') }}">

                            <!-- Signature Section -->
                            <div class="mt-8 border-t pt-6">
                                <x-input-label value="Pawner Signature" class="text-base font-semibold" />
                                <p class="mt-1 text-sm text-gray-500 mb-4">Please provide a signature by either taking/choosing a photo or drawing below (optional).</p>
                                
                                <!-- Option 1: Photo Signature -->
                                <div class="mb-6">
                                    <x-input-label for="signature_photo" value="Option 1: Photo Signature" class="text-sm font-medium" />
                                    <x-image-capture 
                                        name="signature_photo" 
                                        label="" 
                                        :value="old('signature_photo')" 
                                        :required="false"
                                    />
                                </div>
                                
                                <!-- Option 2: Canvas Signature -->
                                <div class="mt-6">
                                    <x-input-label for="signature_canvas" value="Option 2: Draw Signature" class="text-sm font-medium" />
                                    <div class="bg-white border-2 border-gray-300 rounded-lg p-4 mt-2">
                                        <canvas 
                                            id="signatureCanvas" 
                                            class="border border-gray-300 rounded cursor-crosshair touch-none"
                                            width="600"
                                            height="200"
                                            style="max-width: 100%; height: auto; display: block;"
                                        ></canvas>
                                        <div class="mt-3 flex gap-2">
                                            <button 
                                                type="button" 
                                                id="clearSignature" 
                                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium"
                                            >
                                                Clear Signature
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="signature_canvas" id="signatureData">
                                    <p class="mt-1 text-xs text-gray-500">Draw your signature in the box above.</p>
                                </div>
                                
                                <x-input-error :messages="$errors->get('signature')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Transaction Pawn Ticket -->
                        <div class="mt-6">
                            <x-input-label for="transaction_pawn_ticket" value="Transaction Pawn Ticket *" />
                            <x-text-input 
                                id="transaction_pawn_ticket" 
                                name="transaction_pawn_ticket" 
                                type="text" 
                                class="mt-1 block w-full" 
                                :value="old('transaction_pawn_ticket')" 
                                placeholder="Enter transaction pawn ticket number"
                                required
                            />
                            <p class="mt-1 text-xs text-gray-500">Reference field for staff use.</p>
                            <x-input-error :messages="$errors->get('transaction_pawn_ticket')" class="mt-2" />
                        </div>

                        <!-- Note -->
                        <div class="mt-6">
                            <x-input-label for="note" value="Note" />
                            <textarea 
                                id="note" 
                                name="note" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                rows="3"
                                placeholder="Add any additional notes or comments about this partial payment..."
                            >{{ old('note') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Optional: Add any notes or comments for staff reference.</p>
                            <x-input-error :messages="$errors->get('note')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <a href="{{ route('transactions.partial.search') }}" class="text-gray-600 hover:text-gray-900 font-medium">
                                Cancel
                            </a>
                            <x-primary-button>
                                Process Partial Payment
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('signatureCanvas');
            const ctx = canvas ? canvas.getContext('2d') : null;
            const signatureInput = document.getElementById('signatureData');
            const clearBtn = document.getElementById('clearSignature');
            const form = document.getElementById('partialForm');
            const partialAmountInput = document.getElementById('partial_amount');
            const newPrincipalInput = document.getElementById('new_principal_amount');
            const newPrincipalInfo = document.getElementById('newPrincipalInfo');
            
            let isDrawing = false;
            let lastX = 0;
            let lastY = 0;

            // Set canvas background to white (if canvas exists)
            if (canvas && ctx) {
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.strokeStyle = '#000000';
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                // Mouse events
                canvas.addEventListener('mousedown', startDrawing);
                canvas.addEventListener('mousemove', draw);
                canvas.addEventListener('mouseup', stopDrawing);
                canvas.addEventListener('mouseout', stopDrawing);

                // Touch events for mobile
                canvas.addEventListener('touchstart', handleTouch);
                canvas.addEventListener('touchmove', handleTouch);
                canvas.addEventListener('touchend', stopDrawing);
            }

            function startDrawing(e) {
                isDrawing = true;
                const rect = canvas.getBoundingClientRect();
                lastX = e.clientX - rect.left;
                lastY = e.clientY - rect.top;
            }

            function draw(e) {
                if (!isDrawing) return;
                
                const rect = canvas.getBoundingClientRect();
                const currentX = e.clientX - rect.left;
                const currentY = e.clientY - rect.top;

                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(currentX, currentY);
                ctx.stroke();

                lastX = currentX;
                lastY = currentY;
                updateSignatureData();
            }

            function handleTouch(e) {
                e.preventDefault();
                const touch = e.touches[0] || e.changedTouches[0];
                const rect = canvas.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;

                if (e.type === 'touchstart') {
                    isDrawing = true;
                    lastX = x;
                    lastY = y;
                } else if (e.type === 'touchmove' && isDrawing) {
                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(x, y);
                    ctx.stroke();
                    lastX = x;
                    lastY = y;
                    updateSignatureData();
                }
            }

            function stopDrawing() {
                isDrawing = false;
                updateSignatureData();
            }

            function updateSignatureData() {
                const dataURL = canvas.toDataURL('image/png');
                signatureInput.value = dataURL;
            }

            if (clearBtn && canvas && ctx) {
                clearBtn.addEventListener('click', function() {
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    if (signatureInput) {
                        signatureInput.value = '';
                    }
                });
            }

            // Calculate new principal amount when partial amount changes
            const currentPrincipal = {{ $currentPrincipalAmount }};
            const minimumRenewal = {{ $minimumRenewalAmount }};

            function calculateNewPrincipal() {
                const partialAmount = parseFloat(partialAmountInput.value) || 0;
                const newPrincipal = currentPrincipal - partialAmount;
                
                // Handle negative values (increases principal)
                if (partialAmount < 0) {
                    newPrincipalInput.value = '₱' + newPrincipal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    newPrincipalInfo.textContent = `Principal increase of ₱${Math.abs(partialAmount).toFixed(2)} will increase the principal from ₱${currentPrincipal.toFixed(2)} to ₱${newPrincipal.toFixed(2)}.`;
                    newPrincipalInfo.classList.remove('hidden');
                    newPrincipalInfo.classList.remove('text-red-600');
                    newPrincipalInfo.classList.add('text-green-600');
                } else if (partialAmount > 0) {
                    // Positive values (payment)
                    const finalPrincipal = newPrincipal < 0 ? 0 : newPrincipal;
                    newPrincipalInput.value = '₱' + finalPrincipal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    
                    // Show informational message about minimum (as a guide, not a requirement)
                    if (partialAmount < minimumRenewal) {
                        newPrincipalInfo.textContent = `Partial payment of ₱${partialAmount.toFixed(2)} will reduce the principal from ₱${currentPrincipal.toFixed(2)} to ₱${finalPrincipal.toFixed(2)}. Note: Minimum renewal amount is ₱${minimumRenewal.toFixed(2)} (for reference only).`;
                        newPrincipalInfo.classList.remove('hidden');
                        newPrincipalInfo.classList.remove('text-red-600');
                        newPrincipalInfo.classList.remove('text-green-600');
                        newPrincipalInfo.classList.add('text-yellow-600');
                    } else {
                        newPrincipalInfo.textContent = `Partial payment of ₱${partialAmount.toFixed(2)} will reduce the principal from ₱${currentPrincipal.toFixed(2)} to ₱${finalPrincipal.toFixed(2)}.`;
                        newPrincipalInfo.classList.remove('hidden');
                        newPrincipalInfo.classList.remove('text-red-600');
                        newPrincipalInfo.classList.remove('text-green-600');
                        newPrincipalInfo.classList.remove('text-yellow-600');
                    }
                } else {
                    // Zero or empty
                    newPrincipalInput.value = '₱' + currentPrincipal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    newPrincipalInfo.textContent = '';
                    newPrincipalInfo.classList.add('hidden');
                }
            }

            partialAmountInput.addEventListener('input', calculateNewPrincipal);
            partialAmountInput.addEventListener('change', calculateNewPrincipal);

            // Auto-calculate expiry and auction dates based on maturity date
            const maturityDateInput = document.getElementById('maturity_date');
            const expiryDateInput = document.getElementById('expiry_date');
            const auctionDateInput = document.getElementById('auction_sale_date');
            const daysBeforeRedemption = {{ $daysBeforeRedemption }};
            const daysBeforeAuctionSale = {{ $daysBeforeAuctionSale }};

            maturityDateInput.addEventListener('change', function() {
                if (maturityDateInput.value) {
                    const maturityDate = new Date(maturityDateInput.value);
                    
                    // Set expiry date (maturity + daysBeforeRedemption)
                    const expiryDate = new Date(maturityDate);
                    expiryDate.setDate(expiryDate.getDate() + daysBeforeRedemption);
                    expiryDateInput.value = expiryDate.toISOString().split('T')[0];
                    expiryDateInput.min = expiryDateInput.value;
                    
                    // Set auction date (maturity + daysBeforeAuctionSale)
                    const auctionDate = new Date(maturityDate);
                    auctionDate.setDate(auctionDate.getDate() + daysBeforeAuctionSale);
                    auctionDateInput.value = auctionDate.toISOString().split('T')[0];
                    auctionDateInput.min = auctionDateInput.value;
                }
            });

            // Signature is optional, no validation needed
            
            // Prevent checking all items - at least one item must remain unchecked
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            if (itemCheckboxes.length > 0) {
                itemCheckboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
                        const totalCount = itemCheckboxes.length;
                        
                        // If trying to check the last unchecked item, prevent it
                        if (this.checked && checkedCount === totalCount) {
                            this.checked = false;
                            alert('Cannot select all items. At least one item must remain for partial payment.');
                            return false;
                        }
                    });
                });
            }

            // Handle back date checkbox toggle
            const backDateCheckbox = document.getElementById('back_date');
            if (backDateCheckbox) {
                backDateCheckbox.addEventListener('change', function() {
                    // Create a form to submit back to the find route
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("transactions.partial.find") }}';
                    
                    const csrfToken = document.querySelector('input[name="_token"]').value;
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken;
                    form.appendChild(csrfInput);
                    
                    const pawnTicketInput = document.createElement('input');
                    pawnTicketInput.type = 'hidden';
                    pawnTicketInput.name = 'pawn_ticket_number';
                    pawnTicketInput.value = '{{ $pawnTicketNumber }}';
                    form.appendChild(pawnTicketInput);
                    
                    const backDateInput = document.createElement('input');
                    backDateInput.type = 'hidden';
                    backDateInput.name = 'back_date';
                    backDateInput.value = this.checked ? '1' : '0';
                    form.appendChild(backDateInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                });
            }
            
            // Image capture handlers
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

            // Handle file input change to show preview
            document.addEventListener('change', function(e) {
                if (e.target.type === 'file' && e.target.id && e.target.id.endsWith('_input')) {
                    const input = e.target;
                    const fieldName = input.id.replace('_input', '');
                    const preview = document.getElementById(fieldName + '_preview');
                    const previewContainer = document.getElementById(fieldName + '_preview_container');
                    const removeBtn = document.getElementById(fieldName + '_remove_btn');
                    
                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (preview) {
                                preview.src = e.target.result;
                            }
                            if (previewContainer) {
                                previewContainer.classList.remove('hidden');
                            }
                            if (removeBtn) {
                                removeBtn.classList.remove('hidden');
                            }
                        };
                        reader.readAsDataURL(input.files[0]);
                    }
                }
            });
        });
    </script>
</x-app-layout>

