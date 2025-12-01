<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Update Password
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Ensure your account is using a long, random password to stay secure.
        </p>
    </header>

    @if (session('status') === 'password-updated')
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <p class="text-sm text-green-800 font-medium">Password updated successfully.</p>
        </div>
    @endif

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Current Password" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            @php
                $currentPasswordErrors = isset($errors->updatePassword) && $errors->updatePassword->has('current_password') 
                    ? $errors->updatePassword->get('current_password') 
                    : $errors->get('current_password');
            @endphp
            <x-input-error :messages="$currentPasswordErrors" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="New Password" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            @php
                $passwordErrors = isset($errors->updatePassword) && $errors->updatePassword->has('password') 
                    ? $errors->updatePassword->get('password') 
                    : $errors->get('password');
            @endphp
            <x-input-error :messages="$passwordErrors" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Confirm Password" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            @php
                $passwordConfirmationErrors = isset($errors->updatePassword) && $errors->updatePassword->has('password_confirmation') 
                    ? $errors->updatePassword->get('password_confirmation') 
                    : $errors->get('password_confirmation');
            @endphp
            <x-input-error :messages="$passwordConfirmationErrors" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Save</x-primary-button>
        </div>
    </form>
</section>
