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
                        <div class="flex flex-col gap-4">
                            <div>
                                <p class="text-sm text-blue-800">
                                    <strong>Pawn Ticket Number:</strong> {{ $pawnTicketNumber }}
                                </p>
                                <p class="text-sm text-blue-700 mt-1">
                                    <strong>Found {{ $allTransactions->count() }} transaction(s)</strong> - Payment is calculated based on the latest transaction only.
                                </p>
                                @if($allTransactions->count() > 1)
                                    <p class="text-xs text-blue-600 mt-1">
                                        <strong>Note:</strong> All item descriptions will be combined in the renewal transaction.
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

                    <!-- Items List -->
                    @if($allTransactions->count() > 0)
                        <div class="mb-6">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-700 mb-3">Items in this Pawn Ticket:</p>
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
                                                // We'll use the first partial transaction after the item was created
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
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Payment Summary -->
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <h3 class="text-lg font-semibold text-yellow-900 mb-3">Payment Required</h3>
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
                                        @if($backDate)
                                            Additional Charge (Back Dated - No Charge):
                                        @elseif(isset($additionalChargeBreakdown) && count($additionalChargeBreakdown) > 0)
                                            Additional Charge (
                                            {{ collect($additionalChargeBreakdown)->map(fn ($c) => ($c['type'] === 'EC' ? 'Exceeded' : 'Late Days') . " {$c['days']} day(s) @ {$c['percentage']}%")->implode(' + ') }}
                                            ):
                                        @else
                                            Additional Charge:
                                        @endif
                                    </span>
                                    <span class="font-medium text-yellow-900">₱{{ number_format($additionalChargeAmount, 2) }}</span>
                                </div>
                            </div>
                            
                            <!-- Late Days Charge -->
                            <div class="mb-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-yellow-800">
                                        @if($backDate)
                                            Late Days Charge (Back Dated - No Charge):
                                        @elseif($lateDaysCharge > 0 && isset($lateDaysChargeBreakdown))
                                            Late Days Charge ({{ $lateDaysChargeBreakdown['late_days'] }} day(s), ({{ number_format($lateDaysChargeBreakdown['interest'], 2) }} / 30) × {{ $lateDaysChargeBreakdown['late_days'] }}):
                                        @else
                                            Late Days Charge:
                                        @endif
                                    </span>
                                    <span class="font-medium text-yellow-900">₱{{ number_format($lateDaysCharge, 2) }}</span>
                                </div>
                            </div>
                            
                            <!-- Total -->
                            <div class="border-t-2 border-yellow-400 pt-3 mt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-yellow-900">Total Amount to Pay:</span>
                                    <span class="text-lg font-bold text-yellow-900">₱{{ number_format($totalAmountToPay, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pawner ID Verification -->
                    @if($transaction->pawner_id_image_path)
                        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Pawner ID Verification</h3>
                            <p class="text-sm text-gray-600 mb-3">Please verify that the person renewing this transaction matches the ID shown below:</p>
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

                    <!-- Display Latest Transaction Details -->
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
                                    <p class="text-sm text-gray-600">Item Description</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $transaction->item_description }}</p>
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
                                    <p class="text-sm font-medium text-gray-900">{{ $transaction->interest_rate }}% ({{ $transaction->interest_rate_period === 'per_annum' ? 'Per Annum' : ($transaction->interest_rate_period === 'per_month' ? 'Per Month' : 'Other') }})</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Interest Amount</p>
                                    <p class="text-sm font-medium text-gray-900">₱{{ number_format((float) $transaction->loan_amount * ((float) $transaction->interest_rate / 100), 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Current Maturity Date</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $latestTransaction->maturity_date->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Current Expiry Date</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $latestTransaction->expiry_date->format('M d, Y') }}</p>
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
                            
                            @if($allTransactions->count() > 1)
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <p class="text-xs text-gray-600 mb-2">All Items in this Pawn Ticket:</p>
                                    <div class="space-y-3">
                                        @foreach($allTransactions as $tx)
                                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                                <div class="flex items-start gap-4">
                                                    @if($tx->item_image_path)
                                                        <div class="flex-shrink-0">
                                                            <img 
                                                                src="{{ route('images.show', ['path' => $tx->item_image_path]) }}" 
                                                                alt="Item Image" 
                                                                class="w-24 h-24 object-cover rounded-lg border border-gray-300"
                                                                onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'96\' height=\'96\'%3E%3Crect fill=\'%23e5e7eb\' width=\'96\' height=\'96\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'12\'%3ENo Image%3C/text%3E%3C/svg%3E'"
                                                            />
                                                        </div>
                                                    @endif
                                                    <div class="flex-1">
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
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                {{-- Show single item with image if only one transaction --}}
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <p class="text-xs text-gray-600 mb-2">Item Details:</p>
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                        <div class="flex items-start gap-4">
                                            @if($transaction->item_image_path)
                                                <div class="flex-shrink-0">
                                                    <img 
                                                        src="{{ route('images.show', ['path' => $transaction->item_image_path]) }}" 
                                                        alt="Item Image" 
                                                        class="w-24 h-24 object-cover rounded-lg border border-gray-300"
                                                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'96\' height=\'96\'%3E%3Crect fill=\'%23e5e7eb\' width=\'96\' height=\'96\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'12\'%3ENo Image%3C/text%3E%3C/svg%3E'"
                                                    />
                                                </div>
                                            @endif
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ $transaction->itemType->name }}
                                                    @if($transaction->itemTypeSubtype)
                                                        <span class="text-gray-600">- {{ $transaction->itemTypeSubtype->name }}</span>
                                                    @endif
                                                    @if($transaction->custom_item_type)
                                                        <span class="text-gray-600">- {{ $transaction->custom_item_type }}</span>
                                                    @endif
                                                </p>
                                                <p class="text-sm text-gray-700 mt-1">{{ $transaction->item_description }}</p>
                                                @if($transaction->tags && $transaction->tags->count() > 0)
                                                    <div class="flex flex-wrap gap-1 mt-2">
                                                        @foreach($transaction->tags as $tag)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                                {{ $tag->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('transactions.renewal.store') }}">
                        @csrf
                        <input type="hidden" name="pawn_ticket_number" value="{{ $pawnTicketNumber }}">

                        <div class="space-y-6">
                            <!-- Back Date Checkbox (only show if transaction is overdue) -->
                            @if($isOverdue)
                                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                    <div class="flex items-center">
                                        <input 
                                            id="back_date" 
                                            name="back_date" 
                                            type="checkbox" 
                                            value="1"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            {{ old('back_date', $backDate) ? 'checked' : '' }}
                                        />
                                        <label for="back_date" class="ml-2 block text-sm font-medium text-gray-900">
                                            Back Date
                                        </label>
                                    </div>
                                    <p class="mt-2 text-xs text-blue-700">
                                        When checked, the renewal will be processed as if today is the maturity date. This will remove additional charges and late days charges, and adjust the date calculations accordingly.
                                    </p>
                                </div>
                            @endif

                            <!-- Maturity Date -->
                            <div>
                                <x-input-label for="maturity_date" value="New Maturity Date *" />
                                <x-text-input 
                                    id="maturity_date" 
                                    name="maturity_date" 
                                    type="date" 
                                    class="mt-1 block w-full bg-gray-100" 
                                    :value="old('maturity_date', $defaultMaturityDate)" 
                                    required 
                                    readonly
                                />
                                <x-input-error :messages="$errors->get('maturity_date')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">The new maturity date for the renewed transaction.</p>
                            </div>

                            <!-- Expiry Date of Redemption -->
                            <div>
                                <x-input-label for="expiry_date" value="New Expiry Date of Redemption *" />
                                <x-text-input 
                                    id="expiry_date" 
                                    name="expiry_date" 
                                    type="date" 
                                    class="mt-1 block w-full bg-gray-100" 
                                    :value="old('expiry_date', $defaultExpiryDate)" 
                                    required 
                                    readonly
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
                                    class="mt-1 block w-full bg-gray-100" 
                                    :value="old('auction_sale_date', $defaultAuctionSaleDate)" 
                                    readonly
                                />
                                <x-input-error :messages="$errors->get('auction_sale_date')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">This will be auto-calculated based on expiry date + {{ $daysBeforeAuctionSale }} days.</p>
                            </div>

                            <!-- Interest Amount (Readonly, calculated) -->
                            <div>
                                <x-input-label for="interest_amount" value="Interest Amount to Pay *" />
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
                                <p class="mt-1 text-xs text-gray-500">This is the total interest amount calculated from all transactions.</p>
                            </div>

                            <!-- Service Charge (Readonly, calculated) -->
                            <div>
                                <x-input-label for="service_charge" value="Service Charge *" />
                                <x-text-input 
                                    id="service_charge" 
                                    name="service_charge" 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    class="mt-1 block w-full bg-gray-100" 
                                    :value="old('service_charge', number_format($totalServiceCharge, 2, '.', ''))" 
                                    required 
                                    readonly
                                />
                                <x-input-error :messages="$errors->get('service_charge')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">Service charge (₱{{ number_format($serviceCharge, 2) }}) per pawn ticket.</p>
                            </div>

                            <!-- Additional Charge (Readonly, calculated) -->
                            <div>
                                <x-input-label for="additional_charge" value="Additional Charge" />
                                <x-text-input 
                                    id="additional_charge" 
                                    name="additional_charge_display" 
                                    type="text" 
                                    class="mt-1 block w-full bg-gray-100" 
                                    value="₱{{ number_format($additionalChargeAmount, 2) }}" 
                                    readonly
                                    disabled
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    @if(isset($additionalChargeBreakdown) && count($additionalChargeBreakdown) > 0)
                                        {{ collect($additionalChargeBreakdown)->map(fn ($c) => ($c['type'] === 'EC' ? 'Exceeded' : 'Late Days') . " {$c['days']} day(s) @ {$c['percentage']}%")->implode(' + ') }}
                                    @else
                                        No additional charge applicable
                                    @endif
                                </p>
                            </div>

                            <!-- Late Days Charge (Readonly, calculated) -->
                            <div>
                                <x-input-label for="late_days_charge" value="Late Days Charge" />
                                <x-text-input 
                                    id="late_days_charge" 
                                    name="late_days_charge_display" 
                                    type="text" 
                                    class="mt-1 block w-full bg-gray-100" 
                                    value="₱{{ number_format($lateDaysCharge, 2) }}" 
                                    readonly
                                    disabled
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    @if($lateDaysCharge > 0 && isset($lateDaysChargeBreakdown))
                                        {{ $lateDaysChargeBreakdown['late_days'] }} day(s) late, (Interest: ₱{{ number_format($lateDaysChargeBreakdown['interest'], 2) }} / 30) × {{ $lateDaysChargeBreakdown['late_days'] }} = ₱{{ number_format($lateDaysCharge, 2) }}
                                    @else
                                        No late days charge applicable
                                    @endif
                                </p>
                            </div>

                            <!-- Total Amount (Readonly, calculated) -->
                            <div>
                                <x-input-label for="total_amount" value="Total Amount to Pay" />
                                <x-text-input 
                                    id="total_amount" 
                                    name="total_amount_display" 
                                    type="text" 
                                    class="mt-1 block w-full bg-gray-100 font-semibold text-lg" 
                                    value="₱{{ number_format($totalAmountToPay, 2) }}" 
                                    readonly
                                    disabled
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Total amount: Interest + Service Charge
                                    @if(isset($additionalChargeBreakdown) && count($additionalChargeBreakdown) > 0)
                                        + Additional Charge
                                    @else
                                        + Additional Charge (if applicable)
                                    @endif
                                    @if($lateDaysCharge > 0)
                                        + Late Days Charge
                                    @else
                                        + Late Days Charge (if applicable)
                                    @endif
                                </p>
                            </div>

                            <!-- Hidden input for additional charge amount -->
                            <input type="hidden" name="additional_charge_amount" value="{{ number_format($additionalChargeAmount, 2, '.', '') }}">
                            
                            <!-- Hidden input for late days charge amount -->
                            <input type="hidden" name="late_days_charge_amount" value="{{ number_format($lateDaysCharge, 2, '.', '') }}">
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
                                placeholder="Add any additional notes or comments about this renewal..."
                            >{{ old('note') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Optional: Add any notes or comments for staff reference.</p>
                            <x-input-error :messages="$errors->get('note')" class="mt-2" />
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
            const backDateCheckbox = document.getElementById('back_date');
            const form = document.querySelector('form');
            
            const daysBeforeRedemption = {{ $daysBeforeRedemption }};
            const daysBeforeAuctionSale = {{ $daysBeforeAuctionSale }};
            const maturityDateStr = @json($maturityDate); // Previous transaction's maturity date
            const backDateChecked = {{ old('back_date', $backDate) ? 'true' : 'false' }};

            // Set minimum date to today (no past dates allowed)
            const actualToday = new Date();
            actualToday.setHours(0, 0, 0, 0);
            const actualTodayStr = actualToday.toISOString().split('T')[0];
            
            // Determine reference date: if back_date is checked, use maturity date; otherwise use today
            let referenceDate = actualToday;
            let referenceDateStr = actualTodayStr;
            
            if (backDateChecked && maturityDateStr) {
                referenceDate = new Date(maturityDateStr);
                referenceDate.setHours(0, 0, 0, 0);
                referenceDateStr = referenceDate.toISOString().split('T')[0];
            }
            
            maturityDateInput.setAttribute('min', referenceDateStr);
            expiryDateInput.setAttribute('min', referenceDateStr);
            if (auctionSaleDateInput) {
                auctionSaleDateInput.setAttribute('min', referenceDateStr);
            }
            
            // Handle back_date checkbox toggle (only if checkbox exists)
            if (backDateCheckbox) {
                backDateCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    
                    // Create a form to submit with pawn ticket number and back_date
                    const pawnTicketNumber = document.querySelector('input[name="pawn_ticket_number"]').value;
                    const submitForm = document.createElement('form');
                    submitForm.method = 'POST';
                    submitForm.action = '{{ route("transactions.renewal.find") }}';
                    
                    // Add CSRF token
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    submitForm.appendChild(csrfInput);
                    
                    // Add pawn ticket number
                    const pawnTicketInput = document.createElement('input');
                    pawnTicketInput.type = 'hidden';
                    pawnTicketInput.name = 'pawn_ticket_number';
                    pawnTicketInput.value = pawnTicketNumber;
                    submitForm.appendChild(pawnTicketInput);
                    
                    // Add back_date if checked
                    if (isChecked) {
                        const backDateInput = document.createElement('input');
                        backDateInput.type = 'hidden';
                        backDateInput.name = 'back_date';
                        backDateInput.value = '1';
                        submitForm.appendChild(backDateInput);
                    }
                    
                    // Submit form
                    document.body.appendChild(submitForm);
                    submitForm.submit();
                });
            }

            // Validation function to check date relationships
            function validateDates() {
                const maturityDate = maturityDateInput.value;
                const expiryDate = expiryDateInput.value;
                const auctionDate = auctionSaleDateInput ? auctionSaleDateInput.value : null;

                // Clear previous validation messages
                clearValidationMessages();

                let isValid = true;
                const errors = [];

                // Check if maturity date is before reference date
                if (maturityDate && new Date(maturityDate) < referenceDate) {
                    isValid = false;
                    errors.push('Maturity date cannot be before the reference date.');
                    showFieldError(maturityDateInput, 'Maturity date cannot be before the reference date.');
                }

                // Check if expiry date is before maturity date
                if (maturityDate && expiryDate) {
                    const maturity = new Date(maturityDate);
                    const expiry = new Date(expiryDate);
                    
                    if (expiry < maturity) {
                        isValid = false;
                        errors.push('Expiry date must be on or after maturity date.');
                        showFieldError(expiryDateInput, 'Expiry date must be on or after maturity date.');
                    }
                }

                // Check if auction date is before expiry date
                if (expiryDate && auctionDate) {
                    const expiry = new Date(expiryDate);
                    const auction = new Date(auctionDate);
                    
                    if (auction < expiry) {
                        isValid = false;
                        errors.push('Auction sale date must be on or after expiry date.');
                        showFieldError(auctionSaleDateInput, 'Auction sale date must be on or after expiry date.');
                    }
                }

                return isValid;
            }

            // Show error message for a field
            function showFieldError(input, message) {
                input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                
                // Create or update error message
                let errorDiv = input.parentElement.querySelector('.field-error-message');
                if (!errorDiv) {
                    errorDiv = document.createElement('p');
                    errorDiv.className = 'mt-1 text-xs text-red-600 field-error-message';
                    input.parentElement.appendChild(errorDiv);
                }
                errorDiv.textContent = message;
            }

            // Clear validation messages
            function clearValidationMessages() {
                [maturityDateInput, expiryDateInput, auctionSaleDateInput].forEach(input => {
                    if (input) {
                        input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                        const errorDiv = input.parentElement.querySelector('.field-error-message');
                        if (errorDiv) {
                            errorDiv.remove();
                        }
                    }
                });
            }

            // Calculate dates when maturity date changes
            function calculateDatesFromMaturity() {
                const maturityDate = maturityDateInput.value;
                if (maturityDate) {
                    const maturity = new Date(maturityDate);
                    
                    // Validate maturity date is not before reference date
                    if (maturity < referenceDate) {
                        showFieldError(maturityDateInput, 'Maturity date cannot be before the reference date.');
                        return;
                    }
                    
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
                    
                    // Clear any validation errors
                    clearValidationMessages();
                }
            }

            // Update expiry date minimum when maturity date changes
            maturityDateInput.addEventListener('change', function() {
                clearValidationMessages();
                calculateDatesFromMaturity();
                validateDates();
            });

            // Update auction sale date minimum when expiry date changes manually
            expiryDateInput.addEventListener('change', function() {
                clearValidationMessages();
                const expiryDate = this.value;
                
                if (expiryDate) {
                    const expiry = new Date(expiryDate);
                    
                    // Check if expiry date is before maturity date
                    if (maturityDateInput.value) {
                        const maturity = new Date(maturityDateInput.value);
                        if (expiry < maturity) {
                            showFieldError(this, 'Expiry date must be on or after maturity date.');
                            return;
                        }
                    }
                    
                    if (auctionSaleDateInput) {
                        // Calculate auction sale date: expiry redemption date + days before auction sale
                        const auctionDate = new Date(expiry);
                        auctionDate.setDate(auctionDate.getDate() + daysBeforeAuctionSale);
                        const auctionDateStr = auctionDate.toISOString().split('T')[0];
                        
                        auctionSaleDateInput.setAttribute('min', expiryDate);
                        // Only auto-update if auction date is empty or before the new expiry date
                        if (!auctionSaleDateInput.value || new Date(auctionSaleDateInput.value) < expiry) {
                            auctionSaleDateInput.value = auctionDateStr;
                        }
                    }
                }
                
                validateDates();
            });

            // Validate auction sale date when it changes
            if (auctionSaleDateInput) {
                auctionSaleDateInput.addEventListener('change', function() {
                    clearValidationMessages();
                    validateDates();
                });
            }

            // Validate on form submit
            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    // Show general error message
                    alert('Please fix the date validation errors before submitting.');
                    return false;
                }
            });

            // Calculate dates on page load if maturity date is set
            if (maturityDateInput.value) {
                calculateDatesFromMaturity();
            }
        });
    </script>
</x-app-layout>

