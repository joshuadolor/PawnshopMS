<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Renew Transaction
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
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

                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Search by Pawn Ticket Number</h3>
                        <p class="text-sm text-gray-600">Enter the pawn ticket number to find the transaction(s) to renew.</p>
                    </div>

                    <form method="POST" action="{{ route('transactions.renewal.find') }}">
                        @csrf

                        <div class="mt-4">
                            <x-input-label for="pawn_ticket_number" value="Pawn Ticket Number" />
                            <x-text-input 
                                id="pawn_ticket_number" 
                                name="pawn_ticket_number" 
                                type="text" 
                                class="mt-1 block w-full" 
                                :value="old('pawn_ticket_number')" 
                                placeholder="Enter pawn ticket number"
                                required 
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('pawn_ticket_number')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900 font-medium">
                                Cancel
                            </a>
                            <x-primary-button>
                                Search Transaction
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

