<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Redeem Transaction (Tubos)
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
                            <strong>Found {{ $allTransactions->count() }} transaction(s)</strong> - Payment is calculated based on the principal amount.
                        </p>
                        @if($allTransactions->count() > 1)
                            <p class="text-xs text-blue-600 mt-1">
                                <strong>Note:</strong> All item descriptions will be combined in the tubos transaction.
                            </p>
                        @endif
                    </div>

                    <!-- Payment Summary -->
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                        <h3 class="text-lg font-semibold text-green-900 mb-3">Payment Required</h3>
                        <div class="space-y-2">
                            <!-- Principal Amount -->
                            <div class="mb-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-green-800">Principal Amount:</span>
                                    <span class="font-medium text-green-900">₱{{ number_format($principalAmount, 2) }}</span>
                                </div>
                            </div>
                            
                            
                            <!-- Additional Charge -->
                            <div class="mb-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-green-800">
                                        @if($additionalChargeAmount > 0 && $additionalChargeConfig)
                                            Additional Charge ({{ $additionalChargeType === 'EC' ? 'Exceeded Charge' : 'Late Days' }} - {{ $daysExceeded }} day(s), {{ $additionalChargeConfig->percentage }}% of ₱{{ number_format($currentPrincipalAmount, 2) }}):
                                        @else
                                            Additional Charge:
                                        @endif
                                    </span>
                                    <span class="font-medium text-green-900">₱{{ number_format($additionalChargeAmount, 2) }}</span>
                                </div>
                            </div>
                            
                            <!-- Total -->
                            <div class="border-t-2 border-green-400 pt-3 mt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-green-900">Total Amount to Pay:</span>
                                    <span class="text-lg font-bold text-green-900">₱{{ number_format($totalAmountToPay, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pawner ID Verification -->
                    @if($transaction->pawner_id_image_path)
                        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Pawner ID Verification</h3>
                            <p class="text-sm text-gray-600 mb-3">Please verify that the person redeeming this transaction matches the ID shown below:</p>
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
                                    <p class="text-sm text-gray-600">Loan Amount (Principal)</p>
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
                                    <p class="text-sm text-gray-600">Current Maturity Date</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $transaction->maturity_date->format('M d, Y') }}</p>
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
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('transactions.tubos.store') }}">
                        @csrf
                        <input type="hidden" name="pawn_ticket_number" value="{{ $pawnTicketNumber }}">

                        <div class="space-y-6">
                            <!-- Principal Amount (Readonly, calculated) -->
                            <div>
                                <x-input-label for="principal_amount" value="Principal Amount to Pay *" />
                                <x-text-input 
                                    id="principal_amount" 
                                    name="principal_amount" 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    class="mt-1 block w-full bg-gray-100" 
                                    :value="old('principal_amount', number_format($principalAmount, 2, '.', ''))" 
                                    required 
                                    readonly
                                />
                                <x-input-error :messages="$errors->get('principal_amount')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">This is the principal amount (loan amount) to be redeemed.</p>
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
                                    @if($additionalChargeAmount > 0 && $additionalChargeConfig)
                                        {{ $additionalChargeType === 'EC' ? 'Exceeded Charge' : 'Late Days' }} - {{ $daysExceeded }} day(s) exceeded, {{ $additionalChargeConfig->percentage }}% of current principal (₱{{ number_format($currentPrincipalAmount, 2) }})
                                    @else
                                        No additional charge applicable
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
                                    Total amount: Principal
                                    @if($additionalChargeAmount > 0 && $additionalChargeConfig)
                                        + Additional Charge ({{ $additionalChargeType === 'EC' ? 'Exceeded' : 'Late Days' }})
                                    @else
                                        + Additional Charge (if applicable)
                                    @endif
                                </p>
                            </div>

                            <!-- Hidden input for additional charge amount -->
                            <input type="hidden" name="additional_charge_amount" value="{{ number_format($additionalChargeAmount, 2, '.', '') }}">
                        </div>

                        <!-- Signature Section -->
                        <div class="mt-8 border-t pt-6">
                            <x-input-label for="signature" value="Pawner Signature *" class="text-base font-semibold" />
                            <p class="mt-1 text-sm text-gray-500 mb-4">Please sign below to confirm the redemption of this pawn ticket.</p>
                            
                            <div class="bg-white border-2 border-gray-300 rounded-lg p-4">
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
                            <input type="hidden" name="signature" id="signatureData">
                            <x-input-error :messages="$errors->get('signature')" class="mt-2" />
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
                                placeholder="Add any additional notes or comments about this redemption..."
                            >{{ old('note') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Optional: Add any notes or comments for staff reference.</p>
                            <x-input-error :messages="$errors->get('note')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <a href="{{ route('transactions.tubos.search') }}" class="text-gray-600 hover:text-gray-900 font-medium">
                                Cancel
                            </a>
                            <x-primary-button>
                                Redeem Transaction(s) (Tubos)
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
            const ctx = canvas.getContext('2d');
            const signatureInput = document.getElementById('signatureData');
            const clearBtn = document.getElementById('clearSignature');
            const form = document.querySelector('form');
            
            let isDrawing = false;
            let lastX = 0;
            let lastY = 0;

            // Set canvas background to white
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
                // Convert canvas to base64 data URL
                const dataURL = canvas.toDataURL('image/png');
                signatureInput.value = dataURL;
            }

            clearBtn.addEventListener('click', function() {
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                signatureInput.value = '';
            });

            // Validate signature before form submission
            form.addEventListener('submit', function(e) {
                if (!signatureInput.value || signatureInput.value.trim() === '') {
                    e.preventDefault();
                    alert('Please provide a signature before submitting.');
                    return false;
                }
            });
        });
    </script>
</x-app-layout>

