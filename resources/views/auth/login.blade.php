@extends('layouts.app')

@section('title', 'Login - BookTala')

@section('content')
<section class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-primary">Welcome Back</h2>
            <p class="text-gray-600 mt-2">Sign in to your account</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-8">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
                @endif
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" required>
                        <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="mr-2">
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm text-primary hover:underline">Forgot password?</a>
                </div>
                
                <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-primary/90 transition">
                    Sign In
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">Don't have an account? <a href="{{ route('register') }}" class="text-primary font-bold hover:underline">Register</a></p>
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