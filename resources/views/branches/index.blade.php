<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Branch Management
            </h2>
            <a href="{{ route('branches.create') }}">
                <x-primary-button>Create New Branch</x-primary-button>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($branches as $branch)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $branch->name }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">{{ $branch->address }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $branch->contact_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $branch->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button 
                                                type="button" 
                                                onclick="openDeleteDialog({{ $branch->id }}, {{ json_encode($branch->name) }}, {{ json_encode(route('branches.destroy', $branch)) }})"
                                                class="text-red-600 hover:text-red-900">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No branches found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $branches->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Branch Confirmation Dialog -->
    <dialog id="deleteDialog" class="rounded-lg p-6 max-w-md w-full shadow-xl">
        <form method="POST" id="deleteForm">
            @csrf
            @method('DELETE')
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Delete Branch</h3>
            <p class="text-sm text-gray-600 mb-4">
                Are you sure you want to delete the branch <span id="deleteBranchName" class="font-medium"></span>? This action cannot be undone.
            </p>
            <p class="text-sm text-gray-600 mb-4 font-medium">
                To confirm, please type the branch name: <span id="deleteBranchNameConfirm" class="text-red-600"></span>
            </p>
            <div class="mb-6">
                <x-input-label for="deleteNameConfirm" value="Type branch name to confirm" />
                <x-text-input 
                    id="deleteNameConfirm" 
                    name="name_confirm" 
                    type="text" 
                    class="mt-1 block w-full" 
                    placeholder="Enter branch name"
                    autocomplete="off"
                />
                <p id="deleteNameError" class="mt-2 text-sm text-red-600 hidden">Branch name does not match</p>
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
        // Delete Dialog
        let deleteBranchName = '';

        function openDeleteDialog(branchId, branchName, routeUrl) {
            const dialog = document.getElementById('deleteDialog');
            const form = document.getElementById('deleteForm');
            const branchNameSpan = document.getElementById('deleteBranchName');
            const branchNameConfirmSpan = document.getElementById('deleteBranchNameConfirm');
            const nameInput = document.getElementById('deleteNameConfirm');
            const deleteButton = document.getElementById('deleteConfirmButton');
            const errorMessage = document.getElementById('deleteNameError');
            
            deleteBranchName = branchName;
            branchNameSpan.textContent = branchName;
            branchNameConfirmSpan.textContent = branchName;
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
                    
                    if (inputValue === deleteBranchName) {
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

