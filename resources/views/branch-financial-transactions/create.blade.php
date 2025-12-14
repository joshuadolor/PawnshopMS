<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Financial Transaction
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="list-disc list-inside text-sm text-red-800">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('branch-financial-transactions.store') }}" class="space-y-6">
                        @csrf

                        <!-- Branch -->
                        <div>
                            <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Branch <span class="text-red-500">*</span>
                            </label>
                            <select name="branch_id" id="branch_id" required
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Type <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ old('type') === 'replenish' ? 'border-green-500 bg-green-50' : 'border-gray-300' }}">
                                    <input type="radio" name="type" value="replenish" required 
                                           {{ old('type') === 'replenish' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-green-100 rounded-full mr-3">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">Replenish</div>
                                            <div class="text-xs text-gray-500">Money coming in</div>
                                        </div>
                                    </div>
                                </label>

                                <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ old('type') === 'expense' ? 'border-red-500 bg-red-50' : 'border-gray-300' }}">
                                    <input type="radio" name="type" value="expense" required 
                                           {{ old('type') === 'expense' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-red-100 rounded-full mr-3">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">Expense</div>
                                            <div class="text-xs text-gray-500">Money going out</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description <span class="text-red-500">*</span>
                            </label>
                            <textarea name="description" id="description" rows="4" required
                                      placeholder="Enter a detailed description of this transaction..."
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Minimum 3 characters, maximum 500 characters</p>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">₱</span>
                                </div>
                                <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                                       value="{{ old('amount') }}"
                                       placeholder="0.00"
                                       class="block w-full pl-7 pr-3 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Transaction Date -->
                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Transaction Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="transaction_date" id="transaction_date" required
                                   value="{{ old('transaction_date', date('Y-m-d')) }}"
                                   max="{{ date('Y-m-d') }}"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-3 pt-4">
                            <a href="{{ route('branch-financial-transactions.index') }}" 
                               class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Create Transaction
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update radio button styling on change
            document.querySelectorAll('input[name="type"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('label[for^="type"]').forEach(function(label) {
                        label.classList.remove('border-green-500', 'bg-green-50', 'border-red-500', 'bg-red-50');
                        label.classList.add('border-gray-300');
                    });
                    
                    const selectedLabel = this.closest('label');
                    if (this.value === 'replenish') {
                        selectedLabel.classList.remove('border-gray-300');
                        selectedLabel.classList.add('border-green-500', 'bg-green-50');
                    } else {
                        selectedLabel.classList.remove('border-gray-300');
                        selectedLabel.classList.add('border-red-500', 'bg-red-50');
                    }
                });
            });
        });
    </script>
</x-app-layout>

