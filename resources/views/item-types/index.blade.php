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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tags</th>
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
                                        <td class="px-6 py-4">
                                            @if($itemType->tags->count() > 0)
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($itemType->tags as $tag)
                                                        <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
                                                            {{ $tag->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-xs">No tags</span>
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
                                                    onclick="openManageTagsDialog({{ $itemType->id }}, {{ json_encode($itemType->name) }}, {{ json_encode($itemType->tags->pluck('name', 'id')->toArray()) }})"
                                                    class="text-green-600 hover:text-green-900">
                                                    Manage Tags
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
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
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

    <!-- Manage Tags Dialog -->
    <dialog id="manageTagsDialog" class="rounded-lg p-6 max-w-2xl w-full shadow-xl">
        <form method="POST" id="manageTagsForm">
            @csrf
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Manage Tags for <span id="manageTagsItemTypeName" class="text-green-600"></span></h3>
            
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <x-input-label for="newTagName" value="Add New Tag" class="mb-0" />
                    <x-text-input 
                        id="newTagName" 
                        name="name" 
                        type="text" 
                        class="flex-1" 
                        placeholder="Enter tag name"
                        minlength="1"
                    />
                    <button 
                        type="button" 
                        id="addTagButton"
                        onclick="addTag()"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                        Add
                    </button>
                </div>
                
                <div id="tagsList" class="space-y-2 max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3">
                    <!-- Tags will be dynamically added here -->
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="closeManageTagsDialog()" 
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
                    // Update the DOM element with real ID
                    const element = document.querySelector(`[data-subtype-id="${tempId}"]`);
                    if (element) {
                        element.setAttribute('data-subtype-id', data.subtype.id);
                        // Update all IDs in the element
                        const nameSpan = element.querySelector(`#subtype-name-${tempId}`);
                        const editInput = element.querySelector(`#subtype-edit-input-${tempId}`);
                        const editBtn = element.querySelector(`#edit-subtype-btn-${tempId}`);
                        const saveBtn = element.querySelector(`#save-subtype-btn-${tempId}`);
                        const cancelBtn = element.querySelector(`#cancel-subtype-btn-${tempId}`);
                        const deleteBtn = element.querySelector('.delete-subtype-btn');
                        
                        if (nameSpan) nameSpan.id = `subtype-name-${data.subtype.id}`;
                        if (editInput) editInput.id = `subtype-edit-input-${data.subtype.id}`;
                        if (editBtn) {
                            editBtn.id = `edit-subtype-btn-${data.subtype.id}`;
                            editBtn.setAttribute('onclick', `editSubtype(${data.subtype.id})`);
                        }
                        if (saveBtn) {
                            saveBtn.id = `save-subtype-btn-${data.subtype.id}`;
                            saveBtn.setAttribute('onclick', `saveSubtype(${data.subtype.id})`);
                        }
                        if (cancelBtn) {
                            cancelBtn.id = `cancel-subtype-btn-${data.subtype.id}`;
                            cancelBtn.setAttribute('onclick', `cancelEditSubtype(${data.subtype.id}, ${JSON.stringify(data.subtype.name)})`);
                        }
                        if (deleteBtn) deleteBtn.setAttribute('onclick', `deleteSubtype(${data.subtype.id})`);
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
                <span class="text-sm text-gray-900 flex-1" id="subtype-name-${id}">${name}</span>
                <input 
                    type="text" 
                    class="hidden flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                    id="subtype-edit-input-${id}"
                    value="${name}"
                    minlength="3"
                />
                <div class="flex gap-2">
                    <button 
                        type="button" 
                        class="edit-subtype-btn text-blue-600 hover:text-blue-900 text-sm font-medium"
                        onclick="editSubtype(${id})"
                        id="edit-subtype-btn-${id}">
                        Edit
                    </button>
                    <button 
                        type="button" 
                        class="hidden save-subtype-btn text-green-600 hover:text-green-900 text-sm font-medium"
                        onclick="saveSubtype(${id})"
                        id="save-subtype-btn-${id}">
                        Save
                    </button>
                    <button 
                        type="button" 
                        class="hidden cancel-subtype-btn text-gray-600 hover:text-gray-900 text-sm font-medium"
                        onclick="cancelEditSubtype(${id}, ${JSON.stringify(name)})"
                        id="cancel-subtype-btn-${id}">
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        class="delete-subtype-btn text-red-600 hover:text-red-900 text-sm font-medium"
                        onclick="deleteSubtype(${id})">
                        Delete
                    </button>
                </div>
            `;
            subtypesList.appendChild(div);
        }

        function editSubtype(subtypeId) {
            const nameSpan = document.getElementById(`subtype-name-${subtypeId}`);
            const editInput = document.getElementById(`subtype-edit-input-${subtypeId}`);
            const editBtn = document.getElementById(`edit-subtype-btn-${subtypeId}`);
            const saveBtn = document.getElementById(`save-subtype-btn-${subtypeId}`);
            const cancelBtn = document.getElementById(`cancel-subtype-btn-${subtypeId}`);
            const deleteBtn = document.querySelector(`[data-subtype-id="${subtypeId}"] .delete-subtype-btn`);
            
            nameSpan.classList.add('hidden');
            editInput.classList.remove('hidden');
            editBtn.classList.add('hidden');
            saveBtn.classList.remove('hidden');
            cancelBtn.classList.remove('hidden');
            deleteBtn.classList.add('hidden');
            editInput.focus();
            editInput.select();
        }

        function cancelEditSubtype(subtypeId, originalName) {
            const nameSpan = document.getElementById(`subtype-name-${subtypeId}`);
            const editInput = document.getElementById(`subtype-edit-input-${subtypeId}`);
            const editBtn = document.getElementById(`edit-subtype-btn-${subtypeId}`);
            const saveBtn = document.getElementById(`save-subtype-btn-${subtypeId}`);
            const cancelBtn = document.getElementById(`cancel-subtype-btn-${subtypeId}`);
            const deleteBtn = document.querySelector(`[data-subtype-id="${subtypeId}"] .delete-subtype-btn`);
            
            editInput.value = originalName;
            nameSpan.classList.remove('hidden');
            editInput.classList.add('hidden');
            editBtn.classList.remove('hidden');
            saveBtn.classList.add('hidden');
            cancelBtn.classList.add('hidden');
            deleteBtn.classList.remove('hidden');
        }

        function saveSubtype(subtypeId) {
            const editInput = document.getElementById(`subtype-edit-input-${subtypeId}`);
            const newName = editInput.value.trim();
            
            if (newName.length < 3) {
                alert('Subtype name must be at least 3 characters long.');
                return;
            }
            
            // Check if subtype already exists (excluding current one)
            const existingNames = Object.entries(currentSubtypes)
                .filter(([id, name]) => id != subtypeId)
                .map(([id, name]) => name.toLowerCase());
            if (existingNames.includes(newName.toLowerCase())) {
                alert('This subtype already exists.');
                return;
            }
            
            fetch(`/item-types/${currentItemTypeId}/subtypes/${subtypeId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ name: newName })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update local data
                    currentSubtypes[subtypeId] = newName;
                    // Update display
                    const nameSpan = document.getElementById(`subtype-name-${subtypeId}`);
                    nameSpan.textContent = newName;
                    cancelEditSubtype(subtypeId, newName);
                } else {
                    alert(data.message || 'Failed to update subtype.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the subtype.');
            });
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

        // Manage Tags Dialog
        let currentItemTypeIdForTags = null;
        let currentTags = {};

        function openManageTagsDialog(itemTypeId, itemTypeName, tags) {
            const dialog = document.getElementById('manageTagsDialog');
            const itemTypeNameSpan = document.getElementById('manageTagsItemTypeName');
            const tagsList = document.getElementById('tagsList');
            const newTagInput = document.getElementById('newTagName');
            
            currentItemTypeIdForTags = itemTypeId;
            currentTags = tags || {};
            itemTypeNameSpan.textContent = itemTypeName;
            newTagInput.value = '';
            
            // Clear and populate tags list
            tagsList.innerHTML = '';
            Object.entries(currentTags).forEach(([id, name]) => {
                addTagToList(id, name);
            });
            
            dialog.showModal();
        }

        function closeManageTagsDialog() {
            document.getElementById('manageTagsDialog').close();
            currentItemTypeIdForTags = null;
            currentTags = {};
        }

        function addTag() {
            const input = document.getElementById('newTagName');
            const name = input.value.trim();
            
            if (name.length < 1) {
                alert('Tag name cannot be empty.');
                return;
            }
            
            // Check if tag already exists
            const existingNames = Object.values(currentTags).map(n => n.toLowerCase());
            if (existingNames.includes(name.toLowerCase())) {
                alert('This tag already exists.');
                return;
            }
            
            // Add to list immediately (optimistic update)
            const tempId = 'temp_' + Date.now();
            addTagToList(tempId, name);
            currentTags[tempId] = name;
            input.value = '';
            
            // Save to server
            fetch(`/item-types/${currentItemTypeIdForTags}/tags`, {
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
                    delete currentTags[tempId];
                    currentTags[data.tag.id] = data.tag.name;
                    // Update the DOM element with real ID
                    const element = document.querySelector(`[data-tag-id="${tempId}"]`);
                    if (element) {
                        element.setAttribute('data-tag-id', data.tag.id);
                        // Update all IDs in the element
                        const nameSpan = element.querySelector(`#tag-name-${tempId}`);
                        const editInput = element.querySelector(`#tag-edit-input-${tempId}`);
                        const editBtn = element.querySelector(`#edit-tag-btn-${tempId}`);
                        const saveBtn = element.querySelector(`#save-tag-btn-${tempId}`);
                        const cancelBtn = element.querySelector(`#cancel-tag-btn-${tempId}`);
                        const deleteBtn = element.querySelector('.delete-tag-btn');
                        
                        if (nameSpan) nameSpan.id = `tag-name-${data.tag.id}`;
                        if (editInput) editInput.id = `tag-edit-input-${data.tag.id}`;
                        if (editBtn) {
                            editBtn.id = `edit-tag-btn-${data.tag.id}`;
                            editBtn.setAttribute('onclick', `editTag(${data.tag.id})`);
                        }
                        if (saveBtn) {
                            saveBtn.id = `save-tag-btn-${data.tag.id}`;
                            saveBtn.setAttribute('onclick', `saveTag(${data.tag.id})`);
                        }
                        if (cancelBtn) {
                            cancelBtn.id = `cancel-tag-btn-${data.tag.id}`;
                            cancelBtn.setAttribute('onclick', `cancelEditTag(${data.tag.id}, ${JSON.stringify(data.tag.name)})`);
                        }
                        if (deleteBtn) deleteBtn.setAttribute('onclick', `deleteTag(${data.tag.id})`);
                    }
                } else {
                    alert(data.message || 'Failed to add tag.');
                    // Remove from list
                    const element = document.querySelector(`[data-tag-id="${tempId}"]`);
                    if (element) {
                        element.remove();
                    }
                    delete currentTags[tempId];
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the tag.');
                // Remove from list
                const element = document.querySelector(`[data-tag-id="${tempId}"]`);
                if (element) {
                    element.remove();
                }
                delete currentTags[tempId];
            });
        }

        function addTagToList(id, name) {
            const tagsList = document.getElementById('tagsList');
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200';
            div.setAttribute('data-tag-id', id);
            div.innerHTML = `
                <span class="text-sm text-gray-900 flex-1" id="tag-name-${id}">${name}</span>
                <input 
                    type="text" 
                    class="hidden flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                    id="tag-edit-input-${id}"
                    value="${name}"
                    minlength="1"
                />
                <div class="flex gap-2">
                    <button 
                        type="button" 
                        class="edit-tag-btn text-blue-600 hover:text-blue-900 text-sm font-medium"
                        onclick="editTag(${id})"
                        id="edit-tag-btn-${id}">
                        Edit
                    </button>
                    <button 
                        type="button" 
                        class="hidden save-tag-btn text-green-600 hover:text-green-900 text-sm font-medium"
                        onclick="saveTag(${id})"
                        id="save-tag-btn-${id}">
                        Save
                    </button>
                    <button 
                        type="button" 
                        class="hidden cancel-tag-btn text-gray-600 hover:text-gray-900 text-sm font-medium"
                        onclick="cancelEditTag(${id}, ${JSON.stringify(name)})"
                        id="cancel-tag-btn-${id}">
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        class="delete-tag-btn text-red-600 hover:text-red-900 text-sm font-medium"
                        onclick="deleteTag(${id})">
                        Delete
                    </button>
                </div>
            `;
            tagsList.appendChild(div);
        }

        function editTag(tagId) {
            const nameSpan = document.getElementById(`tag-name-${tagId}`);
            const editInput = document.getElementById(`tag-edit-input-${tagId}`);
            const editBtn = document.getElementById(`edit-tag-btn-${tagId}`);
            const saveBtn = document.getElementById(`save-tag-btn-${tagId}`);
            const cancelBtn = document.getElementById(`cancel-tag-btn-${tagId}`);
            const deleteBtn = document.querySelector(`[data-tag-id="${tagId}"] .delete-tag-btn`);
            
            nameSpan.classList.add('hidden');
            editInput.classList.remove('hidden');
            editBtn.classList.add('hidden');
            saveBtn.classList.remove('hidden');
            cancelBtn.classList.remove('hidden');
            deleteBtn.classList.add('hidden');
            editInput.focus();
            editInput.select();
        }

        function cancelEditTag(tagId, originalName) {
            const nameSpan = document.getElementById(`tag-name-${tagId}`);
            const editInput = document.getElementById(`tag-edit-input-${tagId}`);
            const editBtn = document.getElementById(`edit-tag-btn-${tagId}`);
            const saveBtn = document.getElementById(`save-tag-btn-${tagId}`);
            const cancelBtn = document.getElementById(`cancel-tag-btn-${tagId}`);
            const deleteBtn = document.querySelector(`[data-tag-id="${tagId}"] .delete-tag-btn`);
            
            editInput.value = originalName;
            nameSpan.classList.remove('hidden');
            editInput.classList.add('hidden');
            editBtn.classList.remove('hidden');
            saveBtn.classList.add('hidden');
            cancelBtn.classList.add('hidden');
            deleteBtn.classList.remove('hidden');
        }

        function saveTag(tagId) {
            const editInput = document.getElementById(`tag-edit-input-${tagId}`);
            const newName = editInput.value.trim();
            
            if (newName.length < 1) {
                alert('Tag name cannot be empty.');
                return;
            }
            
            // Check if tag already exists (excluding current one)
            const existingNames = Object.entries(currentTags)
                .filter(([id, name]) => id != tagId)
                .map(([id, name]) => name.toLowerCase());
            if (existingNames.includes(newName.toLowerCase())) {
                alert('This tag already exists.');
                return;
            }
            
            fetch(`/item-types/${currentItemTypeIdForTags}/tags/${tagId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ name: newName })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update local data
                    currentTags[tagId] = newName;
                    // Update display
                    const nameSpan = document.getElementById(`tag-name-${tagId}`);
                    nameSpan.textContent = newName;
                    cancelEditTag(tagId, newName);
                } else {
                    alert(data.message || 'Failed to update tag.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the tag.');
            });
        }

        function deleteTag(tagId) {
            if (!confirm('Are you sure you want to delete this tag?')) {
                return;
            }
            
            fetch(`/item-types/${currentItemTypeIdForTags}/tags/${tagId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove from list
                    const element = document.querySelector(`[data-tag-id="${tagId}"]`);
                    if (element) {
                        element.remove();
                    }
                    delete currentTags[tagId];
                } else {
                    alert(data.message || 'Failed to delete tag.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the tag.');
            });
        }

        // Allow Enter key to add tag
        document.addEventListener('DOMContentLoaded', function() {
            const newTagInput = document.getElementById('newTagName');
            if (newTagInput) {
                newTagInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addTag();
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

        // Close dialogs when clicking outside
        ['manageSubtypesDialog', 'manageTagsDialog', 'deleteDialog'].forEach(dialogId => {
            const dialog = document.getElementById(dialogId);
            if (dialog) {
                dialog.addEventListener('click', function(event) {
                    if (event.target === this) {
                        this.close();
                    }
                });
            }
        });
    </script>

    <style>
        dialog::backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>
</x-app-layout>

