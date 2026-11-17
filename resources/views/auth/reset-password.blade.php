@extends('layouts.app')

@section('title', 'Reset Password - BookTala')

@section('content')
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-6 max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold mb-6 text-center">Reset Password</h1>
            
            @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ request('email') }}">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" required>
                        <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Confirm New Password</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="password_confirmation" class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" required>
                        <button type="button" onclick="togglePassword('password_confirmation')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="password_confirmation-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-primary/90 transition">
                    Reset Password
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-primary hover:underline">Back to Login</a>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
@endsection
