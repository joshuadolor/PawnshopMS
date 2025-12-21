<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pawnshop
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Transaction Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <button 
                    type="button" 
                    onclick="openTransactionDialog('Sangla')"
                    class="w-full px-6 py-6 bg-white border-2 border-blue-500 hover:bg-blue-50 active:bg-blue-100 text-blue-600 font-semibold text-lg rounded-lg shadow-sm transition-all duration-200 touch-manipulation flex flex-col items-center justify-center gap-2"
                >
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Sangla</span>
                </button>
                <button 
                    type="button" 
                    onclick="openTransactionDialog('Tubos')"
                    class="w-full px-6 py-6 bg-white border-2 border-green-500 hover:bg-green-50 active:bg-green-100 text-green-600 font-semibold text-lg rounded-lg shadow-sm transition-all duration-200 touch-manipulation flex flex-col items-center justify-center gap-2"
                >
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Tubos</span>
                </button>
                <button 
                    type="button" 
                    onclick="openTransactionDialog('Renew')"
                    class="w-full px-6 py-6 bg-white border-2 border-yellow-500 hover:bg-yellow-50 active:bg-yellow-100 text-yellow-600 font-semibold text-lg rounded-lg shadow-sm transition-all duration-200 touch-manipulation flex flex-col items-center justify-center gap-2"
                >
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Renew</span>
                </button>
                <button 
                    type="button" 
                    onclick="openTransactionDialog('Partial')"
                    class="w-full px-6 py-6 bg-white border-2 border-purple-500 hover:bg-purple-50 active:bg-purple-100 text-purple-600 font-semibold text-lg rounded-lg shadow-sm transition-all duration-200 touch-manipulation flex flex-col items-center justify-center gap-2"
                >
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <span>Partial</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Transaction Type Selection Dialog -->
    <dialog id="transactionDialog" class="rounded-lg shadow-xl p-0 w-[90vw] max-w-md backdrop:bg-black/50">
        <div class="bg-white rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Select Transaction Type
                </h3>
                <p class="text-sm text-gray-600 mb-6">
                    Choose how you want to process this <span id="transactionType" class="font-medium"></span> transaction:
                </p>
                <div class="space-y-3">
                    <button 
                        type="button"
                        onclick="selectTransactionMode('manual')"
                        class="w-full px-4 py-4 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200 text-left touch-manipulation"
                    >
                        Manual Transaction
                    </button>
                    <button 
                        type="button"
                        onclick="selectTransactionMode('process')"
                        class="w-full px-4 py-4 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200 text-left touch-manipulation"
                    >
                        Process Transaction
                    </button>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end">
                <button 
                    type="button"
                    onclick="closeTransactionDialog()"
                    class="px-6 py-2 text-gray-700 hover:text-gray-900 active:text-gray-950 font-medium touch-manipulation"
                >
                    Cancel
                </button>
            </div>
        </div>
    </dialog>

    <script>
        let currentTransactionType = '';

        function openTransactionDialog(type) {
            currentTransactionType = type;
            const dialog = document.getElementById('transactionDialog');
            const typeSpan = document.getElementById('transactionType');
            
            typeSpan.textContent = type;
            dialog.showModal();
        }

        function closeTransactionDialog() {
            document.getElementById('transactionDialog').close();
        }

        function selectTransactionMode(mode) {
            closeTransactionDialog();
            
            if (mode === 'manual') {
                // TODO: Navigate to manual transaction form
                console.log(`Manual mode for ${currentTransactionType} - to be implemented`);
            } else if (mode === 'process') {
                // Navigate to process transaction form based on transaction type
                if (currentTransactionType === 'Sangla') {
                    window.location.href = '{{ route("transactions.sangla.create") }}';
                } else if (currentTransactionType === 'Renew') {
                    window.location.href = '{{ route("transactions.renewal.search") }}';
                } else if (currentTransactionType === 'Tubos') {
                    window.location.href = '{{ route("transactions.tubos.search") }}';
                } else if (currentTransactionType === 'Partial') {
                    window.location.href = '{{ route("transactions.partial.search") }}';
                } else {
                    // TODO: Add other transaction types
                    console.log(`Process mode for ${currentTransactionType} - to be implemented`);
                }
            }
        }

        // Close dialog when clicking outside
        document.getElementById('transactionDialog').addEventListener('click', function(event) {
            if (event.target === this) {
                this.close();
            }
        });
    </script>
</x-app-layout>
