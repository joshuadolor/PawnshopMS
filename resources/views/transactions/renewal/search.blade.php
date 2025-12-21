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
                        <p class="text-sm text-gray-600">Enter the pawn ticket number to find the transaction(s) to renew, or scan the QR code.</p>
                    </div>

                    <form method="POST" action="{{ route('transactions.renewal.find') }}" id="renewalSearchForm">
                        @csrf

                        <div class="mt-4">
                            <x-input-label for="pawn_ticket_number" value="Pawn Ticket Number" />
                            <div class="mt-1 flex gap-2">
                                <x-text-input 
                                    id="pawn_ticket_number" 
                                    name="pawn_ticket_number" 
                                    type="text" 
                                    class="block w-full flex-1" 
                                    :value="old('pawn_ticket_number')" 
                                    placeholder="Enter pawn ticket number or scan QR code"
                                    required 
                                    autofocus
                                />
                                <button
                                    type="button"
                                    onclick="startQRScanner()"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    title="Scan QR Code"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                    </svg>
                                </button>
                            </div>
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

    <!-- QR Scanner Modal -->
    <dialog id="qrScannerModal" class="rounded-lg p-0 w-[90vw] max-w-md backdrop:bg-black/50">
        <div class="bg-white rounded-lg">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Scan QR Code</h3>
                <button
                    onclick="stopQRScanner()"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div id="qr-reader" class="w-full"></div>
                <p class="text-sm text-gray-600 mt-4 text-center">Position the QR code within the camera view</p>
            </div>
        </div>
    </dialog>

    <!-- HTML5 QR Code Scanner Library -->
    <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
    
    <script>
        let html5QrCode = null;
        let qrScannerActive = false;

        function startQRScanner() {
            const modal = document.getElementById('qrScannerModal');
            modal.showModal();
            
            // Initialize scanner
            html5QrCode = new Html5Qrcode("qr-reader");
            qrScannerActive = true;
            
            html5QrCode.start(
                { facingMode: "environment" }, // Use back camera
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                (decodedText, decodedResult) => {
                    // Successfully scanned
                    if (qrScannerActive) {
                        stopQRScanner();
                        document.getElementById('pawn_ticket_number').value = decodedText;
                        // Optionally auto-submit the form
                        // document.getElementById('renewalSearchForm').submit();
                    }
                },
                (errorMessage) => {
                    // Error handling - ignore for now
                }
            ).catch((err) => {
                console.error("Unable to start scanning", err);
                alert('Unable to access camera. Please make sure you have granted camera permissions.');
                stopQRScanner();
            });
        }

        function stopQRScanner() {
            if (html5QrCode && qrScannerActive) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    qrScannerActive = false;
                }).catch((err) => {
                    console.error("Error stopping scanner", err);
                });
            }
            document.getElementById('qrScannerModal').close();
        }

        // Close modal when clicking outside
        document.getElementById('qrScannerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                stopQRScanner();
            }
        });

        // Stop scanner when modal is closed
        document.getElementById('qrScannerModal').addEventListener('close', function() {
            stopQRScanner();
        });
    </script>
</x-app-layout>

