@extends('layouts.staff')

@section('title', 'My Profile')
@section('page_title', 'My Profile')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Staff Profile</h2>
            <p class="text-sm text-gray-500 mt-1">Manage your team identity, bio, profile photo, and emergency contacts.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Avatar & Work Summary -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <div class="relative inline-block mx-auto mb-4">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-32 h-32 rounded-full object-cover ring-4 ring-accent/10 shadow-md mx-auto">
                    @else
                        <div class="w-32 h-32 rounded-full bg-gradient-to-tr from-slate-800 to-slate-900 text-white text-3xl font-black flex items-center justify-center ring-4 ring-accent/10 shadow-md mx-auto">
                            {{ $user->initials }}
                        </div>
                    @endif
                </div>

                <h3 class="text-lg font-bold text-gray-900">{{ $user->name }}</h3>
                <p class="text-xs font-semibold text-accent uppercase tracking-wider mt-0.5">{{ $user->job_title ?: ($staff?->department ? ucfirst($staff->department) . ' Specialist' : 'Staff Member') }}</p>
                <p class="text-sm text-gray-500 mt-1 truncate">{{ $user->email }}</p>

                <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-center gap-2">
                    @if($staff?->employee_id)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                        <i class="fas fa-id-badge mr-1 text-[10px]"></i>
                        {{ $staff->employee_id }}
                    </span>
                    @endif
                    @if($staff?->department)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="fas fa-layer-group mr-1 text-[10px]"></i>
                        {{ ucfirst($staff->department) }}
                    </span>
                    @endif
                </div>

                <!-- Avatar Upload Form -->
                <div class="mt-6 pt-6 border-t border-gray-100 text-left">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Upload Profile Picture</label>
                    <form method="POST" action="{{ route('staff.profile.avatar') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/jpg" required class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 cursor-pointer">
                        <p class="text-[11px] text-gray-400">Accepted formats: JPG, PNG, WEBP (max 3MB).</p>
                        <button type="submit" class="w-full bg-accent hover:bg-accent/90 text-white text-xs font-semibold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm">
                            <i class="fas fa-cloud-upload-alt"></i> Update Photo
                        </button>
                    </form>

                    @if($user->avatar)
                    <form method="POST" action="{{ route('staff.profile.avatar.destroy') }}" class="mt-2" onsubmit="return confirm('Remove your profile picture?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold py-1.5 px-3 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-trash-alt text-[10px]"></i> Remove Photo
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Assigned Sites / Department Info -->
            @if($staff && $staff->assignments->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Assigned Locations</h4>
                <div class="space-y-2">
                    @foreach($staff->assignments as $assignment)
                    <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-gray-50 border border-gray-100">
                        <span class="font-medium text-gray-800">{{ $assignment->site?->name ?? 'All Properties' }}</span>
                        @if($assignment->is_primary)
                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">Primary</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column: Edit Forms -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Details Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-user-edit text-accent"></i> Personal Details & Bio
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Keep your work profile, contact information, and bio current.</p>
                </div>

                <form method="POST" action="{{ route('staff.profile.update') }}" class="p-6 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Full Name *</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm font-medium">
                            @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address *</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm font-medium">
                            @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Mobile Phone</label>
                            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+234 800 000 0000" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm">
                            @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Role / Designation</label>
                            <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="e.g. Lead Housekeeper, Concierge Officer" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm">
                            @error('job_title')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Staff Bio</label>
                        <textarea name="bio" rows="4" placeholder="Briefly describe your responsibilities, guest hospitality experience, or background..." class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm">{{ old('bio', $user->bio) }}</textarea>
                        <p class="text-[11px] text-gray-400 mt-1">Maximum 1,000 characters.</p>
                        @error('bio')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Emergency Contact Section -->
                    <div class="pt-4 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <i class="fas fa-heartbeat text-red-500"></i> Emergency Contact Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $staff?->emergency_contact_name) }}" placeholder="Next of kin or contact person" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Emergency Contact Phone</label>
                                <input type="tel" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $staff?->emergency_contact_phone) }}" placeholder="+234 800 000 0000" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-accent hover:bg-accent/90 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                            <i class="fas fa-save"></i> Save Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-lock text-accent"></i> Change Password
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Ensure your account credentials remain secure.</p>
                </div>

                <form method="POST" action="{{ route('staff.profile.password') }}" class="p-6 space-y-5">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Current Password *</label>
                        <input type="password" name="current_password" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm">
                        @error('current_password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">New Password *</label>
                            <input type="password" name="password" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm">
                            @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Confirm New Password *</label>
                            <input type="password" name="password_confirmation" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent text-sm">
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
