{{-- Note thread on a task. Needs $task (with taskNotes.user loaded) and $postRoute. --}}
<div>
    <h2 class="mb-4 text-lg font-semibold">Notes</h2>

    <ol class="space-y-3 mb-5" aria-label="Task notes">
        @forelse($task->taskNotes as $note)
        @php
            $mine = $note->user_id === auth()->id();
            $fromAdmin = $note->isFromAdmin();
        @endphp
        <li class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[85%]">
                <div class="rounded-lg px-4 py-2 text-sm whitespace-pre-line {{ $mine ? 'bg-primary text-white' : ($fromAdmin ? 'bg-amber-50 text-gray-800 ring-1 ring-amber-200' : 'bg-gray-100 text-gray-800') }}">{{ $note->body }}</div>
                <p class="mt-1 text-xs text-gray-500 {{ $mine ? 'text-right' : '' }}">
                    {{ $note->user->name ?? 'Earlier notes' }}
                    @if($note->user)<span class="text-gray-400">· {{ $fromAdmin ? 'Admin' : 'Staff' }}</span>@endif
                    · {{ $note->created_at->format('M d, g:i A') }}
                </p>
            </div>
        </li>
        @empty
        <li class="text-sm text-gray-500">No notes yet.</li>
        @endforelse
    </ol>

    <form method="POST" action="{{ $postRoute }}" class="space-y-2">
        @csrf
        <label for="task-note" class="sr-only">Add a note</label>
        <textarea id="task-note" name="notes" rows="3" required maxlength="1000" class="w-full rounded border p-3 text-sm" placeholder="{{ auth()->user()->hasAnyRole(['super_admin', 'admin']) ? 'Reply to the staff member…' : 'Add a note for the admin team…' }}">{{ old('notes') }}</textarea>
        @error('notes')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        <button type="submit" class="rounded bg-primary px-4 py-2 text-sm text-white hover:bg-primary/90">
            <i class="fas fa-paper-plane mr-1"></i> {{ $task->taskNotes->isEmpty() ? 'Add note' : 'Reply' }}
        </button>
    </form>
</div>
