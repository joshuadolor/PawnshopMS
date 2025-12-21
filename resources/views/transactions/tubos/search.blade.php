<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Redeem Transaction (Tubos)
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
                        <p class="text-sm text-gray-600">Enter the pawn ticket number to find the transaction(s) to redeem (tubos), or scan the QR code.</p>
                    </div>

                    <form method="POST" action="{{ route('transactions.tubos.find') }}" id="tubosSearchForm">
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
    <script>
        // Track library loading state
        window.qrScannerLibraryLoaded = false;
        window.qrScannerLibraryError = null;
        window.qrScannerLibraryPath = '/js/html5-qrcode.min.js';
    </script>
    <script 
        src="/js/html5-qrcode.min.js" 
        onload="
            console.log('QR Scanner library script loaded');
            // Give it a moment to initialize
            setTimeout(function() {
                if (typeof Html5Qrcode !== 'undefined' || (window.__Html5QrcodeLibrary__ && window.__Html5QrcodeLibrary__.Html5Qrcode)) {
                    window.qrScannerLibraryLoaded = true;
                    console.log('QR Scanner library initialized successfully');
                } else {
                    console.warn('QR Scanner library script loaded but Html5Qrcode not found');
                }
            }, 100);
        " 
        onerror="
            window.qrScannerLibraryError = 'Failed to load script file';
            console.error('Failed to load QR Scanner library from: ' + window.qrScannerLibraryPath);
        "
    ></script>
    
    <script>
        let html5QrCode = null;
        let qrScannerActive = false;

        // Get Html5Qrcode from library
        function getHtml5Qrcode() {
            // Try global first
            if (typeof Html5Qrcode !== 'undefined') {
                return Html5Qrcode;
            }
            // Try window object
            if (window.__Html5QrcodeLibrary__ && window.__Html5QrcodeLibrary__.Html5Qrcode) {
                return window.__Html5QrcodeLibrary__.Html5Qrcode;
            }
            return null;
        }

        // Wait for library to load
        function waitForLibrary(callback, maxAttempts = 30) {
            let attempts = 0;
            const checkLibrary = () => {
                const Html5QrcodeClass = getHtml5Qrcode();
                if (Html5QrcodeClass) {
                    // Set global for easier access
                    if (typeof Html5Qrcode === 'undefined') {
                        window.Html5Qrcode = Html5QrcodeClass;
                    }
                    callback();
                } else if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(checkLibrary, 200);
                } else {
                    let errorMsg = 'QR Scanner library failed to load.\n\n';
                    errorMsg += 'Please check:\n';
                    errorMsg += '1. The file exists at: ' + (window.qrScannerLibraryPath || 'js/html5-qrcode.min.js') + '\n';
                    errorMsg += '2. Your browser console for errors\n';
                    errorMsg += '3. Try refreshing the page\n\n';
                    
                    if (window.qrScannerLibraryError) {
                        errorMsg += 'Error: ' + window.qrScannerLibraryError;
                    } else {
                        errorMsg += 'Library script loaded but Html5Qrcode class not found.';
                    }
                    
                    alert(errorMsg);
                    console.error('Html5Qrcode library not found after', maxAttempts, 'attempts');
                    console.error('window.__Html5QrcodeLibrary__:', window.__Html5QrcodeLibrary__);
                    console.error('window.qrScannerLibraryLoaded:', window.qrScannerLibraryLoaded);
                    console.error('window.qrScannerLibraryError:', window.qrScannerLibraryError);
                    console.error('typeof Html5Qrcode:', typeof Html5Qrcode);
                }
            };
            checkLibrary();
        }

        function startQRScanner() {
            // Check if library is loaded
            const Html5QrcodeClass = getHtml5Qrcode();
            if (!Html5QrcodeClass) {
                waitForLibrary(() => {
                    startQRScanner();
                });
                return;
            }

            const modal = document.getElementById('qrScannerModal');
            const qrReaderElement = document.getElementById('qr-reader');
            
            if (!modal) {
                alert('QR Scanner modal not found');
                return;
            }

            if (!qrReaderElement) {
                alert('QR Reader element not found');
                return;
            }

            // Clear previous content
            qrReaderElement.innerHTML = '';
            
            modal.showModal();
            
            // Initialize scanner
            try {
                const Html5QrcodeClass = getHtml5Qrcode();
                if (!Html5QrcodeClass) {
                    alert('QR Scanner library not loaded. Please refresh the page.');
                    stopQRScanner();
                    return;
                }
                
                html5QrCode = new Html5QrcodeClass("qr-reader");
                qrScannerActive = true;
                
                html5QrCode.start(
                    { facingMode: "environment" }, // Use back camera
                    {
                        fps: 10,
                        qrbox: function(viewfinderWidth, viewfinderHeight) {
                            // Make QR box responsive (70% of smaller dimension)
                            let minEdgePercentage = 0.7;
                            let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                            let qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
                            return {
                                width: qrboxSize,
                                height: qrboxSize
                            };
                        },
                        aspectRatio: 1.0
                    },
                    (decodedText, decodedResult) => {
                        // Successfully scanned
                        if (qrScannerActive) {
                            console.log('QR Code scanned:', decodedText);
                            stopQRScanner();
                            const inputField = document.getElementById('pawn_ticket_number');
                            const form = document.getElementById('tubosSearchForm');
                            
                            if (inputField) {
                                inputField.value = decodedText;
                            }
                            
                            // Auto-submit the form
                            if (form) {
                                form.submit();
                            } else if (inputField) {
                                // Fallback: trigger form submission manually
                                inputField.form?.submit();
                            }
                        }
                    },
                    (errorMessage) => {
                        // Error handling - ignore scanning errors (they're frequent during scanning)
                        // console.log('Scan error:', errorMessage);
                    }
                ).catch((err) => {
                    console.error("Unable to start scanning", err);
                    let errorMsg = 'Unable to access camera. ';
                    
                    if (err.name === 'NotAllowedError') {
                        errorMsg += 'Please grant camera permissions and try again.';
                    } else if (err.name === 'NotFoundError') {
                        errorMsg += 'No camera found on this device.';
                    } else if (err.name === 'NotReadableError') {
                        errorMsg += 'Camera is already in use by another application.';
                    } else if (err.name === 'OverconstrainedError') {
                        errorMsg += 'Camera constraints could not be satisfied.';
                    } else {
                        errorMsg += 'Error: ' + (err.message || err.toString());
                    }
                    
                    alert(errorMsg);
                    stopQRScanner();
                });
            } catch (err) {
                console.error("Error initializing QR scanner", err);
                alert('Error initializing QR scanner: ' + err.message);
                stopQRScanner();
            }
        }

        function stopQRScanner() {
            if (html5QrCode && qrScannerActive) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    qrScannerActive = false;
                }).catch((err) => {
                    console.error("Error stopping scanner", err);
                    qrScannerActive = false;
                });
            }
            
            const modal = document.getElementById('qrScannerModal');
            if (modal) {
                modal.close();
            }
        }

        // Initialize event listeners when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('qrScannerModal');
            if (modal) {
                // Close modal when clicking outside
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        stopQRScanner();
                    }
                });

                // Stop scanner when modal is closed
                modal.addEventListener('close', function() {
                    stopQRScanner();
                });
            }
            
            // Check if library is available after page load
            setTimeout(() => {
                const Html5QrcodeClass = getHtml5Qrcode();
                if (!Html5QrcodeClass) {
                    console.warn('QR Scanner library not found on page load. It may load when you click the scan button.');
                    // Try to verify the file is accessible
                    if (window.qrScannerLibraryPath) {
                        fetch(window.qrScannerLibraryPath, { method: 'HEAD' })
                            .then(response => {
                                if (response.ok) {
                                    console.log('QR Scanner library file is accessible');
                                } else {
                                    console.error('QR Scanner library file returned status:', response.status);
                                }
                            })
                            .catch(err => {
                                console.error('Error checking QR Scanner library file:', err);
                            });
                    }
                } else {
                    console.log('QR Scanner library is ready');
                }
            }, 1000);
        });
    </script>
</x-app-layout>

