<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if($method) @method($method) @endif
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
        <input type="text" name="name" value="{{ old('name', $position->name) }}" required placeholder="e.g. Night Security Guard" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
        <select name="department" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            @foreach(\App\Models\Position::DEPARTMENTS as $value => $label)
            <option value="{{ $value }}" @selected(old('department', $position->department) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
        <select name="level" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
            @foreach(\App\Models\Position::LEVELS as $value => $label)
            <option value="{{ $value }}" @selected(old('level', $position->level) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Supervisors and managers will get oversight tools for their sites in the next phase.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <input type="text" name="description" value="{{ old('description', $position->description) }}" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
    </div>
    <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-primary/90">{{ $submit }}</button>
</form>
