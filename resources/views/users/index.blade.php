<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                User Management
            </h2>
            <a href="{{ route('users.create') }}">
                <x-primary-button>Create New User</x-primary-button>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branches</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $user->username }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $user->role === 'superadmin' ? 'bg-purple-100 text-purple-800' : 
                                                   ($user->role === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                @if($user->branches->count() > 0)
                                                    @foreach($user->branches as $branch)
                                                        <span class="inline-block px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded mr-1 mb-1">
                                                            {{ $branch->name }}
                                                        </span>
                                                    @endforeach
                                                @elseif($user->role === 'superadmin' || $user->role === 'admin')
                                                    <span class="text-green-600 text-xs">All Branches</span>
                                                @else
                                                    <span class="text-red-600 text-xs">No branches</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2 flex-col">
                                                <button 
                                                    type="button" 
                                                    onclick="openStatusDialog({{ $user->id }}, {{ json_encode($user->name) }}, {{ $user->is_active ? 'false' : 'true' }}, {{ json_encode(route('users.update-status', $user)) }})"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                                @if(!$user->isSuperAdmin())
                                                    <button 
                                                        type="button" 
                                                        onclick="openRoleDialog({{ $user->id }}, {{ json_encode($user->name) }}, {{ json_encode($user->role) }}, {{ json_encode(route('users.update-role', $user)) }})"
                                                        class="text-blue-600 hover:text-blue-900">
                                                        Change Role
                                                    </button>
                                                @endif
                                                @if($user->isStaff())
                                                    <button 
                                                        type="button" 
                                                        onclick="openBranchDialog({{ $user->id }}, {{ json_encode($user->name) }}, {{ json_encode($user->branches->pluck('id')->toArray()) }}, {{ json_encode(route('users.update-branches', $user)) }})"
                                                        class="text-green-600 hover:text-green-900">
                                                        Assign Branches
                                                    </button>
                                                @endif
                                                <button 
                                                    type="button" 
                                                    onclick="openResetPasswordDialog({{ $user->id }}, {{ json_encode($user->name) }}, {{ json_encode(route('users.reset-password', $user)) }})"
                                                    class="text-yellow-600 hover:text-yellow-900">
                                                    Reset Password
                                                </button>
                                                @if($user->id !== Auth::id())
                                                    <button 
                                                        type="button" 
                                                        onclick="openDeleteDialog({{ $user->id }}, {{ json_encode($user->name) }}, {{ json_encode($user->username) }}, {{ json_encode(route('users.destroy', $user)) }})"
                                                        class="text-red-600 hover:text-red-900">
                                                        Delete
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No users found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activate/Deactivate Confirmation Dialog -->
    <dialog id="statusDialog" class="rounded-lg p-6 max-w-md w-full shadow-xl">
        <form method="POST" id="statusForm">
            @csrf
            @method('PATCH')
            <input type="hidden" name="is_active" id="statusIsActive">
            <h3 class="text-lg font-semibold text-gray-900 mb-4" id="statusDialogTitle">Change User Status</h3>
            <p class="text-sm text-gray-600 mb-6">
                Are you sure you want to <span id="statusAction" class="font-medium"></span> the account for <span id="statusUserName" class="font-medium"></span>?
            </p>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="closeStatusDialog()" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Confirm
                </button>
            </div>
        </form>
    </dialog>

    <!-- Change Role Dialog -->
    <dialog id="roleDialog" class="rounded-lg p-6 max-w-md w-full shadow-xl">
        <form method="POST" id="roleForm">
            @csrf
            @method('PATCH')
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Change User Role</h3>
            <p class="text-sm text-gray-600 mb-4">
                Change the role for <span id="roleUserName" class="font-medium"></span>:
            </p>
            <div class="mb-6">
                <label for="roleSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Role</label>
                <select 
                    id="roleSelect" 
                    name="role" 
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="closeRoleDialog()" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Change Role
                </button>
            </div>
        </form>
    </dialog>

    <!-- Assign Branches Dialog -->
    <dialog id="branchDialog" class="rounded-lg p-6 max-w-md w-full shadow-xl">
        <form method="POST" id="branchForm">
            @csrf
            @method('PATCH')
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Assign Branches</h3>
            <p class="text-sm text-gray-600 mb-4">
                Assign branches for <span id="branchUserName" class="font-medium"></span>:
            </p>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Branches</label>
                <div class="space-y-2 max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3">
                    @foreach($branches as $branch)
                        <label class="inline-flex items-center w-full p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="branches[]" 
                                value="{{ $branch->id }}" 
                                class="branch-checkbox text-indigo-600 focus:ring-indigo-500"
                            >
                            <span class="ms-3 text-sm text-gray-700">{{ $branch->name }}</span>
                        </label>
                    @endforeach
                </div>
                <p id="branchError" class="mt-2 text-sm text-red-600 hidden">Please select at least one branch</p>
            </div>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="closeBranchDialog()" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    id="branchConfirmButton"
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Assign Branches
                </button>
            </div>
        </form>
    </dialog>

    <!-- Reset Password Confirmation Dialog -->
    <dialog id="resetPasswordDialog" class="rounded-lg p-6 max-w-md w-full shadow-xl">
        <form method="POST" id="resetPasswordForm">
            @csrf
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Reset Password</h3>
            <p class="text-sm text-gray-600 mb-6">
                Are you sure you want to reset the password for <span id="resetPasswordUserName" class="font-medium"></span> to the default password?
            </p>
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="closeResetPasswordDialog()" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    Reset Password
                </button>
            </div>
        </form>
    </dialog>

    <!-- Delete User Confirmation Dialog -->
    <dialog id="deleteDialog" class="rounded-lg p-6 max-w-md w-full shadow-xl">
        <form method="POST" id="deleteForm">
            @csrf
            @method('DELETE')
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Delete User</h3>
            <p class="text-sm text-gray-600 mb-4">
                Are you sure you want to delete the account for <span id="deleteUserName" class="font-medium"></span>? This action cannot be undone.
            </p>
            <p class="text-sm text-gray-600 mb-4 font-medium">
                To confirm, please type the username: <span id="deleteUserUsername" class="text-red-600"></span>
            </p>
            <div class="mb-6">
                <x-input-label for="deleteUsernameConfirm" value="Type username to confirm" />
                <x-text-input 
                    id="deleteUsernameConfirm" 
                    name="username_confirm" 
                    type="text" 
                    class="mt-1 block w-full" 
                    placeholder="Enter username"
                    autocomplete="off"
                />
                <p id="deleteUsernameError" class="mt-2 text-sm text-red-600 hidden">Username does not match</p>
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
        // Status (Activate/Deactivate) Dialog
        function openStatusDialog(userId, userName, isActive, routeUrl) {
            const dialog = document.getElementById('statusDialog');
            const form = document.getElementById('statusForm');
            const userNameSpan = document.getElementById('statusUserName');
            const statusAction = document.getElementById('statusAction');
            const statusIsActive = document.getElementById('statusIsActive');
            
            userNameSpan.textContent = userName;
            statusIsActive.value = isActive ? '1' : '0';
            statusAction.textContent = isActive ? 'activate' : 'deactivate';
            form.action = routeUrl;
            
            dialog.showModal();
        }

        function closeStatusDialog() {
            document.getElementById('statusDialog').close();
        }

        // Role Change Dialog
        function openRoleDialog(userId, userName, currentRole, routeUrl) {
            const dialog = document.getElementById('roleDialog');
            const form = document.getElementById('roleForm');
            const userNameSpan = document.getElementById('roleUserName');
            const roleSelect = document.getElementById('roleSelect');
            
            userNameSpan.textContent = userName;
            roleSelect.value = currentRole;
            form.action = routeUrl;
            
            dialog.showModal();
        }

        function closeRoleDialog() {
            document.getElementById('roleDialog').close();
        }

        // Branch Assignment Dialog
        function openBranchDialog(userId, userName, currentBranches, routeUrl) {
            const dialog = document.getElementById('branchDialog');
            const form = document.getElementById('branchForm');
            const userNameSpan = document.getElementById('branchUserName');
            const checkboxes = document.querySelectorAll('.branch-checkbox');
            
            userNameSpan.textContent = userName;
            form.action = routeUrl;
            
            // Reset all checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check current branches
            if (currentBranches && currentBranches.length > 0) {
                currentBranches.forEach(branchId => {
                    const checkbox = document.querySelector(`input[name="branches[]"][value="${branchId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
            
            dialog.showModal();
        }

        function closeBranchDialog() {
            document.getElementById('branchDialog').close();
        }

        // Validate branch selection
        document.addEventListener('DOMContentLoaded', function() {
            const branchForm = document.getElementById('branchForm');
            const branchConfirmButton = document.getElementById('branchConfirmButton');
            const branchError = document.getElementById('branchError');
            
            if (branchForm) {
                branchForm.addEventListener('submit', function(e) {
                    const checked = document.querySelectorAll('.branch-checkbox:checked');
                    if (checked.length === 0) {
                        e.preventDefault();
                        branchError.classList.remove('hidden');
                        return false;
                    }
                });
                
                // Update error message on checkbox change
                document.querySelectorAll('.branch-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const checked = document.querySelectorAll('.branch-checkbox:checked');
                        if (checked.length > 0) {
                            branchError.classList.add('hidden');
                        }
                    });
                });
            }
        });

        // Reset Password Dialog
        function openResetPasswordDialog(userId, userName, routeUrl) {
            const dialog = document.getElementById('resetPasswordDialog');
            const form = document.getElementById('resetPasswordForm');
            const userNameSpan = document.getElementById('resetPasswordUserName');
            
            userNameSpan.textContent = userName;
            form.action = routeUrl;
            
            dialog.showModal();
        }

        function closeResetPasswordDialog() {
            document.getElementById('resetPasswordDialog').close();
        }

        // Delete Dialog
        let deleteUserUsername = '';

        function openDeleteDialog(userId, userName, userUsername, routeUrl) {
            const dialog = document.getElementById('deleteDialog');
            const form = document.getElementById('deleteForm');
            const userNameSpan = document.getElementById('deleteUserName');
            const userUsernameSpan = document.getElementById('deleteUserUsername');
            const usernameInput = document.getElementById('deleteUsernameConfirm');
            const deleteButton = document.getElementById('deleteConfirmButton');
            const errorMessage = document.getElementById('deleteUsernameError');
            
            deleteUserUsername = userUsername;
            userNameSpan.textContent = userName;
            userUsernameSpan.textContent = userUsername;
            form.action = routeUrl;
            
            // Reset form state
            usernameInput.value = '';
            deleteButton.disabled = true;
            deleteButton.className = 'px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
            errorMessage.classList.add('hidden');
            
            dialog.showModal();
        }

        function closeDeleteDialog() {
            const dialog = document.getElementById('deleteDialog');
            const usernameInput = document.getElementById('deleteUsernameConfirm');
            const deleteButton = document.getElementById('deleteConfirmButton');
            const errorMessage = document.getElementById('deleteUsernameError');
            
            // Reset form
            usernameInput.value = '';
            deleteButton.disabled = true;
            deleteButton.className = 'px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
            errorMessage.classList.add('hidden');
            
            dialog.close();
        }

        // Validate username input for delete dialog
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('deleteUsernameConfirm');
            const deleteButton = document.getElementById('deleteConfirmButton');
            const errorMessage = document.getElementById('deleteUsernameError');
            
            if (usernameInput) {
                usernameInput.addEventListener('input', function() {
                    const inputValue = this.value.trim();
                    
                    if (inputValue === deleteUserUsername) {
                        // Username matches - enable delete button
                        deleteButton.disabled = false;
                        deleteButton.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
                        errorMessage.classList.add('hidden');
                    } else {
                        // Username doesn't match - disable delete button
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
        ['statusDialog', 'roleDialog', 'branchDialog', 'resetPasswordDialog', 'deleteDialog'].forEach(dialogId => {
            document.getElementById(dialogId).addEventListener('click', function(event) {
                if (event.target === this) {
                    this.close();
                }
            });
        });
    </script>

    <style>
        dialog::backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>
</x-app-layout>

