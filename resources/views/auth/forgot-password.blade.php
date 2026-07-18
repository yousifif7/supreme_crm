<x-guest-layout>
    <div class="fl-guest-brand">
        <img src="{{ brand_logo_url('login_logo') }}" alt="{{ brand_name() }}">
        <h1>{{ brand_name() }}</h1>
        <p>{{ __('Reset your password') }}</p>
    </div>

    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="fl-login-btn">
            {{ __('Email Password Reset Link') }}
        </button>

        <div class="flex items-center justify-center mt-4">
            <a href="{{ route('login') }}">{{ __('Back to login') }}</a>
        </div>
    </form>
</x-guest-layout>
