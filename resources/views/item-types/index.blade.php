<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Item Type Management
            </h2>
            <a href="{{ route('item-types.create') }}">
                <x-primary-button>Create New Item Type</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                            <p class="text-sm text-green-800 font-medium">{{ session('status') }}</p>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-sm text-red-800 font-medium">{{ session('error') }}</p>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtypes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($itemTypes as $itemType)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $itemType->name }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($itemType->subtypes->count() > 0)
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($itemType->subtypes as $subtype)
                                                        <span class="inline-block px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">
                                                            {{ $subtype->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-xs">No subtypes</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $itemType->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button 
                                                    type="button" 
                                                    onclick="openManageSubtypesDialog({{ $itemType->id }}, {{ json_encode($itemType->name) }}, {{ json_encode($itemType->subtypes->pluck('name', 'id')->toArray()) }})"
                                                    class="text-blue-600 hover:text-blue-900">
                                                    Manage Subtypes
                                                </button>
                                                <button 
                                                    type="button" 
                                                    onclick="openDeleteDialog({{ $itemType->id }}, {{ json_encode($itemType->name) }}, {{ json_encode(route('item-types.destroy', $itemType)) }})"
                                                    class="text-red-600 hidden hover:text-red-900">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No item types found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Subtypes Dialog -->
    <dialog id="manageSubtypesDialog" class="rounded-lg p-6 max-w-2xl w-full shadow-xl">
        <form method="POST" id="manageSubtypesForm">
            @csrf
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Manage Subtypes for <span id="manageSubtypesItemTypeName" class="text-indigo-600"></span></h3>
            
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <x-input-label for="newSubtypeName" value="Add New Subtype" class="mb-0" />
                    <x-text-input 
                        id="newSubtypeName" 
                        name="name" 
                        type="text" 
                        class="flex-1" 
                        placeholder="Enter subtype name"
                        minlength="3"
                    />
                    <button 
                        type="button" 
                        id="addSubtypeButton"
                        onclick="addSubtype()"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
                        Add
                    </button>
                </div>
                
                <div id="subtypesList" class="space-y-2 max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3">
                    <!-- Subtypes will be dynamically added here -->
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="closeManageSubtypesDialog()" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Close
                </button>
            </div>
        </form>
    </dialog>

    <!-- Delete Item Type Confirmation Dialog -->
    <dialog id="deleteDialog" class="rounded-lg p-6 max-w-md w-full shadow-xl">
        <form method="POST" id="deleteForm">
            @csrf
            @method('DELETE')
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Delete Item Type</h3>
            <p class="text-sm text-gray-600 mb-4">
                Are you sure you want to delete the item type <span id="deleteItemTypeName" class="font-medium"></span>? This action cannot be undone.
            </p>
            <p class="text-sm text-gray-600 mb-4 font-medium">
                To confirm, please type the item type name: <span id="deleteItemTypeNameConfirm" class="text-red-600"></span>
            </p>
            <div class="mb-6">
                <x-input-label for="deleteNameConfirm" value="Type item type name to confirm" />
                <x-text-input 
                    id="deleteNameConfirm" 
                    name="name_confirm" 
                    type="text" 
                    class="mt-1 block w-full" 
                    placeholder="Enter item type name"
                    autocomplete="off"
                />
                <p id="deleteNameError" class="mt-2 text-sm text-red-600 hidden">Item type name does not match</p>
            </div>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="closeDeleteDialog()" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    id="deleteConfirmButton"
                    disabled
                    class="px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Delete
                </button>
            </div>
        </form>
    </dialog>

    <script>
        // Manage Subtypes Dialog
        let currentItemTypeId = null;
        let currentSubtypes = {};

        function openManageSubtypesDialog(itemTypeId, itemTypeName, subtypes) {
            const dialog = document.getElementById('manageSubtypesDialog');
            const itemTypeNameSpan = document.getElementById('manageSubtypesItemTypeName');
            const subtypesList = document.getElementById('subtypesList');
            const newSubtypeInput = document.getElementById('newSubtypeName');
            
            currentItemTypeId = itemTypeId;
            currentSubtypes = subtypes || {};
            itemTypeNameSpan.textContent = itemTypeName;
            newSubtypeInput.value = '';
            
            // Clear and populate subtypes list
            subtypesList.innerHTML = '';
            Object.entries(currentSubtypes).forEach(([id, name]) => {
                addSubtypeToList(id, name);
            });
            
            dialog.showModal();
        }

        function closeManageSubtypesDialog() {
            document.getElementById('manageSubtypesDialog').close();
            currentItemTypeId = null;
            currentSubtypes = {};
        }

        function addSubtype() {
            const input = document.getElementById('newSubtypeName');
            const name = input.value.trim();
            
            if (name.length < 3) {
                alert('Subtype name must be at least 3 characters long.');
                return;
            }
            
            // Check if subtype already exists
            const existingNames = Object.values(currentSubtypes).map(n => n.toLowerCase());
            if (existingNames.includes(name.toLowerCase())) {
                alert('This subtype already exists.');
                return;
            }
            
            // Add to list immediately (optimistic update)
            const tempId = 'temp_' + Date.now();
            addSubtypeToList(tempId, name);
            currentSubtypes[tempId] = name;
            input.value = '';
            
            // Save to server
            fetch(`/item-types/${currentItemTypeId}/subtypes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ name: name })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update with real ID
                    delete currentSubtypes[tempId];
                    currentSubtypes[data.subtype.id] = data.subtype.name;
                    // Update the DOM element
                    const element = document.querySelector(`[data-subtype-id="${tempId}"]`);
                    if (element) {
                        element.setAttribute('data-subtype-id', data.subtype.id);
                        element.querySelector('.delete-subtype-btn').setAttribute('onclick', `deleteSubtype(${data.subtype.id})`);
                    }
                } else {
                    alert(data.message || 'Failed to add subtype.');
                    // Remove from list
                    const element = document.querySelector(`[data-subtype-id="${tempId}"]`);
                    if (element) {
                        element.remove();
                    }
                    delete currentSubtypes[tempId];
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the subtype.');
                // Remove from list
                const element = document.querySelector(`[data-subtype-id="${tempId}"]`);
                if (element) {
                    element.remove();
                }
                delete currentSubtypes[tempId];
            });
        }

        function addSubtypeToList(id, name) {
            const subtypesList = document.getElementById('subtypesList');
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200';
            div.setAttribute('data-subtype-id', id);
            div.innerHTML = `
                <span class="text-sm text-gray-900">${name}</span>
                <button 
                    type="button" 
                    class="delete-subtype-btn text-red-600 hover:text-red-900 text-sm font-medium"
                    onclick="deleteSubtype(${id})">
                    Delete
                </button>
            `;
            subtypesList.appendChild(div);
        }

        function deleteSubtype(subtypeId) {
            if (!confirm('Are you sure you want to delete this subtype?')) {
                return;
            }
            
            fetch(`/item-types/${currentItemTypeId}/subtypes/${subtypeId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove from list
                    const element = document.querySelector(`[data-subtype-id="${subtypeId}"]`);
                    if (element) {
                        element.remove();
                    }
                    delete currentSubtypes[subtypeId];
                } else {
                    alert(data.message || 'Failed to delete subtype.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the subtype.');
            });
        }

        // Allow Enter key to add subtype
        document.addEventListener('DOMContentLoaded', function() {
            const newSubtypeInput = document.getElementById('newSubtypeName');
            if (newSubtypeInput) {
                newSubtypeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addSubtype();
                    }
                });
            }
        });

        // Delete Dialog
        let deleteItemTypeName = '';

        function openDeleteDialog(itemTypeId, itemTypeName, routeUrl) {
            const dialog = document.getElementById('deleteDialog');
            const form = document.getElementById('deleteForm');
            const itemTypeNameSpan = document.getElementById('deleteItemTypeName');
            const itemTypeNameConfirmSpan = document.getElementById('deleteItemTypeNameConfirm');
            const nameInput = document.getElementById('deleteNameConfirm');
            const deleteButton = document.getElementById('deleteConfirmButton');
            const errorMessage = document.getElementById('deleteNameError');
            
            deleteItemTypeName = itemTypeName;
            itemTypeNameSpan.textContent = itemTypeName;
            itemTypeNameConfirmSpan.textContent = itemTypeName;
            form.action = routeUrl;
            
            // Reset form state
            nameInput.value = '';
            deleteButton.disabled = true;
            deleteButton.className = 'px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
            errorMessage.classList.add('hidden');
            
            dialog.showModal();
        }

        function closeDeleteDialog() {
            const dialog = document.getElementById('deleteDialog');
            const nameInput = document.getElementById('deleteNameConfirm');
            const deleteButton = document.getElementById('deleteConfirmButton');
            const errorMessage = document.getElementById('deleteNameError');
            
            // Reset form
            nameInput.value = '';
            deleteButton.disabled = true;
            deleteButton.className = 'px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
            errorMessage.classList.add('hidden');
            
            dialog.close();
        }

        // Validate name input for delete dialog
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('deleteNameConfirm');
            const deleteButton = document.getElementById('deleteConfirmButton');
            const errorMessage = document.getElementById('deleteNameError');
            
            if (nameInput) {
                nameInput.addEventListener('input', function() {
                    const inputValue = this.value.trim();
                    
                    if (inputValue === deleteItemTypeName) {
                        // Name matches - enable delete button
                        deleteButton.disabled = false;
                        deleteButton.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
                        errorMessage.classList.add('hidden');
                    } else {
                        // Name doesn't match - disable delete button
                        deleteButton.disabled = true;
                        deleteButton.className = 'px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
                        
                        if (inputValue.length > 0) {
                            errorMessage.classList.remove('hidden');
                        } else {
                            errorMessage.classList.add('hidden');
                        }
                    }
                });
            }
        });

        // Close dialog when clicking outside
        document.getElementById('deleteDialog').addEventListener('click', function(event) {
            if (event.target === this) {
                this.close();
            }
        });
    </script>

    <style>
        dialog::backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>
</x-app-layout>

