<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create New User
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <!-- First Name -->
                        <div>
                            <x-input-label for="first_name" value="First Name" />
                            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name')" required autofocus />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <!-- Last Name -->
                        <div class="mt-4">
                            <x-input-label for="last_name" value="Last Name" />
                            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name')" required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>

                        <!-- Username -->
                        <div class="mt-4">
                            <x-input-label for="username" value="Username" />
                            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username')" required />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                        <!-- Role -->
                        <div class="mt-4">
                            <x-input-label for="role" value="Role" />
                            <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-input-label for="password" value="Password (if not provided, default: {{ env('DEFAULT_PASSWORD', 'password9988') }})" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Confirm Password -->
                        <div class="mt-4">
                            <x-input-label for="password_confirmation" value="Confirm Password" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <!-- Is Active -->
                        <div class="mt-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active') || true ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ms-2 text-sm text-gray-600">Activate user immediately</span>
                            </label>
                        </div>

                        <!-- Email -->
                        <div class="mt-4">
                            <x-input-label for="email" value="Email (Optional)" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Phone Number -->
                        <div class="mt-4">
                            <x-input-label for="phone_number" value="Phone Number (Optional)" />
                            <x-text-input id="phone_number" name="phone_number" type="text" class="mt-1 block w-full" :value="old('phone_number')" />
                            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('users.index') }}" class="text-gray-600 hover:text-gray-900 me-4">
                                Cancel
                            </a>
                            <x-primary-button>Create User</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

