@extends('layouts.customer')

@section('page_title', 'Edit Profile')

@section('content')
<div class="container mx-auto px-6 py-8">
    <h1 class="text-2xl font-bold mb-6">Edit Profile</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Personal Information</h2>
                <form action="{{ route('customer.profile.update') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Full Name</label>
                            <input type="text" name="name" value="{{ $user->name }}" class="w-full p-2 border rounded" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input type="email" value="{{ $user->email }}" class="w-full p-2 border rounded bg-gray-50" disabled>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Phone</label>
                            <input type="text" name="phone" value="{{ $user->phone }}" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">City</label>
                            <input type="text" name="city" value="{{ $user->city }}" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">State</label>
                            <input type="text" name="state" value="{{ $user->state }}" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Country</label>
                            <input type="text" name="country" value="{{ $user->country }}" class="w-full p-2 border rounded">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <textarea name="address" rows="2" class="w-full p-2 border rounded">{{ $user->address }}</textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="mt-4 px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark">Update Profile</button>
                </form>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Change Password</h2>
                <form action="{{ route('customer.profile.password') }}" method="POST">
                    @csrf
                    @method('POST')
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Current Password</label>
                            <input type="password" name="current_password" class="w-full p-2 border rounded" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">New Password</label>
                            <input type="password" name="password" class="w-full p-2 border rounded" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="w-full p-2 border rounded" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="mt-4 px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark">Update Password</button>
                </form>
            </div>
        </div>
        
        <div>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">ID Verification</h2>
                @if($user->id_document)
                <p class="text-green-600 mb-2">ID Document Uploaded</p>
                @if($user->id_verified)
                <p class="text-green-600 font-medium">Verified</p>
                @else
                <p class="text-yellow-600">Pending Verification</p>
                @endif
                @else
                <p class="text-gray-600 mb-4">Upload a valid ID document for verification</p>
                @endif
                
                <form action="{{ route('customer.profile.upload-id') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="id_document" class="w-full mb-2" required>
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark">Upload ID</button>
                </form>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Account Status</h2>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="text-green-600">Verified</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">ID Status:</span>
                        <span class="{{ $user->id_verified ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $user->id_verified ? 'Verified' : 'Pending' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
