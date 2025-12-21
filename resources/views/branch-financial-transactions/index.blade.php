<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Financial Transactions
            </h2>
            <a href="{{ route('branch-financial-transactions.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Transaction
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg shadow-sm">
                    <p class="text-sm text-green-800 font-medium">{{ session('success') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg shadow-sm">
                    <p class="text-sm text-red-800 font-medium">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Total Replenish -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl shadow-lg p-6 border border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-600 uppercase tracking-wide">Total Replenish</p>
                            <p class="mt-2 text-3xl font-bold text-green-900">₱{{ number_format($summary['total_replenish'], 2) }}</p>
                        </div>
                        <div class="p-3 bg-green-200 rounded-full">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Expense -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl shadow-lg p-6 border border-red-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-600 uppercase tracking-wide">Total Expense</p>
                            <p class="mt-2 text-3xl font-bold text-red-900">₱{{ number_format($summary['total_expense'], 2) }}</p>
                        </div>
                        <div class="p-3 bg-red-200 rounded-full">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Net Balance -->
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl shadow-lg p-6 border border-indigo-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-indigo-600 uppercase tracking-wide">Net Balance</p>
                            <p class="mt-2 text-3xl font-bold {{ $summary['net_balance'] >= 0 ? 'text-indigo-900' : 'text-red-900' }}">
                                ₱{{ number_format($summary['net_balance'], 2) }}
                            </p>
                        </div>
                        <div class="p-3 bg-indigo-200 rounded-full">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Branch Balances (Admin/Superadmin only) -->
            @if(auth()->user()->isAdminOrSuperAdmin() && count($branchBalances) > 0)
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Branch Balances</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($branchBalances as $balance)
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <p class="text-sm font-medium text-gray-600 mb-3">{{ $balance['branch']->name }}</p>
                                
                                <!-- Additions and Expenses side by side -->
                                <div class="flex gap-4 mb-3">
                                    <!-- Additions -->
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Additions</p>
                                        <p class="text-lg font-semibold text-green-600">
                                            +₱{{ number_format($balance['additions'], 2) }}
                                        </p>
                                    </div>
                                    
                                    <!-- Expenses -->
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Expenses</p>
                                        <p class="text-lg font-semibold text-red-600">
                                            -₱{{ number_format($balance['expenses'], 2) }}
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Net Balance -->
                                <div class="pt-2 border-t border-gray-300">
                                    <p class="text-xs text-gray-500">Net Balance</p>
                                    <p class="mt-1 text-2xl font-bold {{ $balance['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ₱{{ number_format($balance['balance'], 2) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Filters (Admin/Superadmin only) -->
            @if(auth()->user()->isAdminOrSuperAdmin())
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-200">
                    <form method="GET" action="{{ route('branch-financial-transactions.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- Branch Filter -->
                            <div>
                                <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                                <select name="branch_id" id="branch_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $filters['branch_id'] == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Type Filter -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select name="type" id="type" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Types</option>
                                    <option value="replenish" {{ $filters['type'] === 'replenish' ? 'selected' : '' }}>Replenish</option>
                                    <option value="expense" {{ $filters['type'] === 'expense' ? 'selected' : '' }}>Expense</option>
                                    <option value="transaction" {{ $filters['type'] === 'transaction' ? 'selected' : '' }}>Transaction</option>
                                </select>
                            </div>

                            <!-- Date From -->
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] }}" 
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Date To -->
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] }}" 
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            
                            <!-- All Dates Checkbox -->
                            <div class="flex items-end">
                                <label class="flex items-center">
                                    <input type="checkbox" name="all_dates" value="1" {{ $filters['all_dates'] ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">All Dates</span>
                                </label>
                            </div>

                            <!-- Search -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" name="search" id="search" value="{{ $filters['search'] }}" 
                                       placeholder="Description..." 
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Apply Filters
                            </button>
                            <a href="{{ route('branch-financial-transactions.index') }}" 
                               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Transactions Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                                @if(auth()->user()->isAdminOrSuperAdmin())
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($transactions as $transaction)
                                @php
                                    $isVoided = $transaction->isVoided();
                                    $voidReason = $isVoided && $transaction->voided ? $transaction->voided->reason : null;
                                    $voidedBy = $isVoided && $transaction->voided && $transaction->voided->voidedBy ? $transaction->voided->voidedBy->name : null;
                                    $voidedAt = $isVoided && $transaction->voided ? $transaction->voided->voided_at->format('M d, Y h:i A') : null;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors {{ $isVoided ? 'opacity-40' : '' }}"
                                    data-transaction-id="{{ $transaction->id }}"
                                    data-is-voided="{{ $isVoided ? '1' : '0' }}"
                                    data-void-reason="{{ $isVoided ? e($voidReason) : '' }}"
                                    data-voided-by="{{ $isVoided ? e($voidedBy) : '' }}"
                                    data-voided-at="{{ $isVoided ? e($voidedAt) : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->transaction_date->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $transaction->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $transaction->branch->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($transaction->isReplenish())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                Replenish
                                            </span>
                                        @elseif($transaction->isTransaction())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
                                                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                                </svg>
                                                Transaction
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                                Expense
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $transaction->description }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            // Determine sign based on money direction
                                            // + for Replenish and Renewal (transaction IN)
                                            // - for Expense and Sangla (transaction OUT)
                                            $isIncoming = $transaction->isReplenish() || $transaction->isRenewalTransactionEntry();
                                            $sign = $isIncoming ? '+' : '-';

                                            if ($transaction->isReplenish()) {
                                                $amountClass = 'text-green-600';
                                            } elseif ($transaction->isTransaction()) {
                                                $amountClass = 'text-blue-600';
                                            } else {
                                                $amountClass = 'text-red-600';
                                            }
                                        @endphp
                                        <div class="text-sm font-semibold {{ $amountClass }}">
                                            {{ $sign }}₱{{ number_format($transaction->amount, 2) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $transaction->user->name }}</div>
                                    </td>
                                    @if(auth()->user()->isAdminOrSuperAdmin())
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            @if(!$isVoided)
                                                @php
                                                    // Hide void button for type "transaction"
                                                    $canVoid = !$transaction->isTransaction();
                                                @endphp
                                                @if($canVoid)
                                                    <button
                                                        type="button"
                                                        onclick="openVoidDialog({{ $transaction->id }})"
                                                        class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                        Void
                                                    </button>
                                                @endif
                                            @else
                                                <div class="flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        onclick="showVoidReason({{ $transaction->id }})"
                                                        class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                        See Reason
                                                    </button>
                                                    <span class="text-xs text-gray-400">•</span>
                                                    <span class="text-xs text-gray-400">Voided</span>
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->isAdminOrSuperAdmin() ? '8' : '7' }}" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="mt-4 text-sm text-gray-500">No transactions found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($transactions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Void Financial Transaction Dialog (Admin/Superadmin only) -->
    @if(auth()->user()->isAdminOrSuperAdmin())
        <dialog id="voidFinancialTransactionModal" class="rounded-lg p-0 w-[90vw] max-w-md backdrop:bg-black/50">
            <div class="bg-white rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Void Financial Transaction</h3>
                    <p class="mt-1 text-sm text-gray-500">Please provide a reason for voiding this transaction.</p>
                </div>
                
                <form id="voidFinancialTransactionForm" method="POST" class="p-6">
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
            function openVoidDialog(transactionId) {
                const form = document.getElementById('voidFinancialTransactionForm');
                form.action = `/branch-financial-transactions/${transactionId}/void`;
                document.getElementById('void_reason').value = '';
                document.getElementById('voidFinancialTransactionModal').showModal();
            }

            function closeVoidDialog() {
                document.getElementById('voidFinancialTransactionModal').close();
            }

            // Close void modal when clicking outside
            document.getElementById('voidFinancialTransactionModal').addEventListener('click', function(event) {
                if (event.target === this) {
                    this.close();
                }
            });

            // Handle void form submission
            document.getElementById('voidFinancialTransactionForm').addEventListener('submit', function(e) {
                const reason = document.getElementById('void_reason').value.trim();
                if (reason.length < 5) {
                    e.preventDefault();
                    alert('Please provide a reason with at least 5 characters.');
                    return false;
                }
            });
        </script>

        <!-- Void Reason Dialog -->
        <dialog id="voidReasonModal" class="rounded-lg p-0 w-[90vw] max-w-md backdrop:bg-black/50">
            <div class="bg-white rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Void Reason</h3>
                    <p class="mt-1 text-sm text-gray-500">Details about why this transaction was voided.</p>
                </div>
                
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-md">
                            <p id="voidReasonText" class="text-sm text-gray-900"></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Voided By</label>
                        <p id="voidedByText" class="text-sm text-gray-900"></p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Voided At</label>
                        <p id="voidedAtText" class="text-sm text-gray-900"></p>
                    </div>
                    
                    <div class="flex justify-end">
                        <button
                            type="button"
                            onclick="closeVoidReasonDialog()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </dialog>

        <script>
            function showVoidReason(transactionId) {
                // Find the row with this transaction ID
                const row = document.querySelector(`tr[data-transaction-id="${transactionId}"]`);
                if (!row) return;
                
                const reason = row.getAttribute('data-void-reason') || '';
                const voidedBy = row.getAttribute('data-voided-by') || '';
                const voidedAt = row.getAttribute('data-voided-at') || '';
                
                document.getElementById('voidReasonText').textContent = reason;
                document.getElementById('voidedByText').textContent = voidedBy;
                document.getElementById('voidedAtText').textContent = voidedAt;
                
                document.getElementById('voidReasonModal').showModal();
            }

            function closeVoidReasonDialog() {
                document.getElementById('voidReasonModal').close();
            }

            // Close void reason modal when clicking outside
            document.getElementById('voidReasonModal').addEventListener('click', function(event) {
                if (event.target === this) {
                    this.close();
                }
            });
        </script>
    @endif
</x-app-layout>

