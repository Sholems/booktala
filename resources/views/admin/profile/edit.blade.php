@extends('layouts.admin')

@section('title', 'My Profile')
@section('page_title', 'My Profile')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Account Profile</h2>
            <p class="text-sm text-gray-500 mt-1">Manage your administrator identity, bio, avatar, and security credentials.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Avatar & Summary Card -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <div class="relative inline-block mx-auto mb-4">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-32 h-32 rounded-full object-cover ring-4 ring-primary/10 shadow-md mx-auto">
                    @else
                        <div class="w-32 h-32 rounded-full bg-gradient-to-tr from-slate-800 to-slate-900 text-white text-3xl font-black flex items-center justify-center ring-4 ring-primary/10 shadow-md mx-auto">
                            {{ $user->initials }}
                        </div>
                    @endif
                </div>

                <h3 class="text-lg font-bold text-gray-900">{{ $user->name }}</h3>
                <p class="text-xs font-semibold text-primary uppercase tracking-wider mt-0.5">{{ $user->job_title ?: 'Administrator' }}</p>
                <p class="text-sm text-gray-500 mt-1 truncate">{{ $user->email }}</p>

                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="fas fa-shield-alt mr-1 text-[10px]"></i>
                        {{ ucfirst(str_replace('_', ' ', $user->getRoleNames()->first() ?? 'Admin')) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                        <i class="fas fa-calendar-alt mr-1 text-[10px]"></i>
                        Joined {{ $user->created_at->format('M Y') }}
                    </span>
                </div>

                <!-- Avatar Upload Form -->
                <div class="mt-6 pt-6 border-t border-gray-100 text-left">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Upload Profile Picture</label>
                    <form method="POST" action="{{ route('admin.profile.avatar') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/jpg" required class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
                        <p class="text-[11px] text-gray-400">Accepted formats: JPG, PNG, WEBP (max 3MB).</p>
                        <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white text-xs font-semibold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-cloud-upload-alt"></i> Update Photo
                        </button>
                    </form>

                    @if($user->avatar)
                    <form method="POST" action="{{ route('admin.profile.avatar.destroy') }}" class="mt-2" onsubmit="return confirm('Remove your profile picture?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold py-1.5 px-3 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-trash-alt text-[10px]"></i> Remove Photo
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Bio Preview Card -->
            @if($user->bio)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">About Me</h4>
                <p class="text-sm text-gray-700 leading-relaxed italic">"{{ $user->bio }}"</p>
            </div>
            @endif
        </div>

        <!-- Right Column: Edit Forms -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Details Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-user-edit text-primary"></i> Personal Details & Bio
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Update your display information and professional background.</p>
                </div>

                <form method="POST" action="{{ route('admin.profile.update') }}" class="p-6 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Full Name *</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm font-medium">
                            @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address *</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm font-medium">
                            @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Phone Number</label>
                            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+234 800 000 0000" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                            @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Job Title / Designation</label>
                            <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="e.g. Operations Director, Property Manager" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                            @error('job_title')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Professional Bio</label>
                        <textarea name="bio" rows="4" placeholder="Briefly describe your responsibilities, background, or hospitality expertise..." class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">{{ old('bio', $user->bio) }}</textarea>
                        <p class="text-[11px] text-gray-400 mt-1">Maximum 1,000 characters.</p>
                        @error('bio')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-primary hover:bg-primary/90 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-lock text-primary"></i> Change Password
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Ensure your account is protected with a strong, unique password.</p>
                </div>

                <form method="POST" action="{{ route('admin.profile.password') }}" class="p-6 space-y-5">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Current Password *</label>
                        <input type="password" name="current_password" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                        @error('current_password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">New Password *</label>
                            <input type="password" name="password" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                            @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Confirm New Password *</label>
                            <input type="password" name="password_confirmation" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                            <i class="fas fa-key"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
