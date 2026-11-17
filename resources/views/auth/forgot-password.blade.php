@extends('layouts.app')

@section('title', 'Forgot Password - BookTala')

@section('content')
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-6 max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold mb-6 text-center">Forgot Password</h1>
            
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
            @endif
            
            @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                    <input type="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" required>
                </div>
                <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-primary/90 transition">
                    Send Password Reset Link
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-primary hover:underline">Back to Login</a>
            </div>
        </div>
    </div>
</section>
@endsection
