<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Items Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Search and Filters -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-200">
                <form method="GET" action="{{ route('items.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div>
                            <x-input-label for="search" value="Search" />
                            <x-text-input 
                                id="search" 
                                name="search" 
                                type="text" 
                                class="mt-1 block w-full" 
                                :value="old('search', $filters['search'])" 
                                placeholder="Search by item description, pawn ticket, name, or tags..."
                            />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <x-input-label for="status" value="Status" />
                            <select 
                                id="status" 
                                name="status" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Status</option>
                                <option value="available" {{ $filters['status'] == 'available' ? 'selected' : '' }}>Available</option>
                                <option value="ready_for_auction" {{ $filters['status'] == 'ready_for_auction' ? 'selected' : '' }}>Ready for Auction</option>
                                <option value="voided" {{ $filters['status'] == 'voided' ? 'selected' : '' }}>Voided</option>
                            </select>
                        </div>

                        <!-- Auctionable Only Checkbox -->
                        <div class="flex items-end">
                            <div class="flex items-center">
                                <input 
                                    id="auctionable_only" 
                                    name="auctionable_only" 
                                    type="checkbox" 
                                    value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    {{ $filters['auctionable_only'] ? 'checked' : '' }}
                                >
                                <x-input-label for="auctionable_only" value="Show only auctionable" class="ml-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Branch Checkboxes -->
                    <div>
                        <x-input-label value="Branches" />
                        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            @foreach($branches as $branch)
                                <div class="flex items-center">
                                    <input 
                                        id="branch_{{ $branch->id }}" 
                                        name="branch_ids[]" 
                                        type="checkbox" 
                                        value="{{ $branch->id }}"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        {{ in_array($branch->id, $filters['branch_ids']) ? 'checked' : '' }}
                                    >
                                    <label for="branch_{{ $branch->id }}" class="ml-2 text-sm text-gray-700 cursor-pointer">
                                        {{ $branch->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('items.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Clear
                        </a>
                        <x-primary-button>
                            Search
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Items Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                @if($items->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tags</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Handled By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Entered</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pawn Ticket</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($items as $item)
                                    @php
                                        $isVoided = $item->isVoided();
                                        // Check if this item is redeemed (either via tubos transaction OR marked as redeemed via partial flow)
                                        $isRedeemed = $item->status === 'redeemed' || ($item->pawn_ticket_number && $redeemedPawnTickets->contains($item->pawn_ticket_number));
                                        $isReadyForAuction = !$isVoided && !$isRedeemed && $item->auction_sale_date && \Carbon\Carbon::parse($item->auction_sale_date)->lte(\Carbon\Carbon::today());
                                        $isAvailable = !$isVoided && !$isRedeemed && (!$item->auction_sale_date || \Carbon\Carbon::parse($item->auction_sale_date)->gt(\Carbon\Carbon::today()));
                                        
                                        // Build category string
                                        $category = $item->itemType->name;
                                        if ($item->itemTypeSubtype) {
                                            $category .= ' - ' . $item->itemTypeSubtype->name;
                                        }
                                        if ($item->custom_item_type) {
                                            $category .= ' - ' . $item->custom_item_type;
                                        }
                                    @endphp
                                    <tr 
                                        class="hover:bg-gray-50 transition-colors {{ $isVoided ? 'opacity-60' : '' }}"
                                        data-item-image="{{ $item->item_image_path ? route('images.show', ['path' => $item->item_image_path]) : '' }}"
                                    >
                                        <!-- Image -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="w-16 h-16 bg-gray-100 rounded overflow-hidden flex items-center justify-center">
                                                @if($item->item_image_path)
                                                    <img 
                                                        src="{{ route('images.show', ['path' => $item->item_image_path]) }}" 
                                                        alt="Item Image" 
                                                        class="w-full h-full object-cover"
                                                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\'%3E%3Crect fill=\'%23e5e7eb\' width=\'64\' height=\'64\'/%3E%3Ctext fill=\'%239ca3af\' font-family=\'sans-serif\' font-size=\'10\' dy=\'10.5\' font-weight=\'bold\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E';"
                                                    >
                                                @else
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Category -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">{{ $category }}</div>
                                        </td>

                                        <!-- Tags -->
                                        <td class="px-6 py-4">
                                            @if($item->tags->count() > 0)
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($item->tags as $tag)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            {{ $tag->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-400">-</span>
                                            @endif
                                        </td>

                                        <!-- Description -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $item->item_description }}">
                                                {{ $item->item_description }}
                                            </div>
                                        </td>

                                        <!-- Branch -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $item->branch->name }}</div>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($isVoided)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Voided
                                                </span>
                                            @elseif($isRedeemed)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    Already Redeemed
                                                </span>
                                            @elseif($isReadyForAuction)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    Ready for Auction
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Available
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Transaction -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $item->user->name }}</div>
                                        </td>

                                        <!-- Date Entered -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $item->created_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $item->created_at->format('h:i A') }}</div>
                                        </td>

                                        <!-- Pawn Ticket -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">#{{ $item->pawn_ticket_number }}</div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end flex-col gap-3">
                                                @if($item->item_image_path)
                                                    <button 
                                                        onclick="showItemImage('{{ route('images.show', ['path' => $item->item_image_path]) }}')"
                                                        class="text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        View Image
                                                    </button>
                                                @endif
                                                <a 
                                                    href="{{ route('transactions.index', ['search' => $item->pawn_ticket_number]) }}" 
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    View Details
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $items->links() }}
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No items found</h3>
                        <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Item Image Modal -->
    <dialog id="itemImageModal" class="rounded-lg p-0 w-[90vw] max-w-4xl backdrop:bg-black/50">
        <div class="bg-white rounded-lg">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Item Image</h3>
                <button
                    onclick="closeItemImageModal()"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-center bg-gray-100 rounded-lg overflow-hidden">
                    <img 
                        id="modalItemImage" 
                        src="" 
                        alt="Item Image" 
                        class="max-w-full max-h-[70vh] object-contain"
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'400\'%3E%3Crect fill=\'%23e5e7eb\' width=\'400\' height=\'400\'/%3E%3Ctext fill=\'%239ca3af\' font-family=\'sans-serif\' font-size=\'20\' dy=\'10.5\' font-weight=\'bold\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\'%3ENo Image Available%3C/text%3E%3C/svg%3E';"
                    >
                </div>
            </div>
        </div>
    </dialog>

    <script>
        function showItemImage(imageUrl) {
            const modal = document.getElementById('itemImageModal');
            const imageElement = document.getElementById('modalItemImage');
            
            if (imageUrl) {
                imageElement.src = imageUrl;
                modal.showModal();
            }
        }

        function closeItemImageModal() {
            const modal = document.getElementById('itemImageModal');
            modal.close();
        }

        // Close modal when clicking outside
        document.getElementById('itemImageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeItemImageModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeItemImageModal();
            }
        });
    </script>
</x-app-layout>

