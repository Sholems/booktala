{{-- Attendance records with their flags; shared by the supervisor and head-office pages. --}}
<div class="bg-white rounded-lg shadow overflow-hidden table-responsive">
    <table class="min-w-full mobile-card-table text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                @if($showSite)<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>@endif
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">In</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Out</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Worked</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($records as $record)
            <tr>
                <td class="px-4 py-3" data-label="Staff">
                    <div class="font-medium">{{ $record->staff->user->name ?? 'Staff' }}</div>
                    @if($record->handover_note)<div class="text-xs text-gray-500 max-w-xs truncate" title="{{ $record->handover_note }}"><i class="fas fa-sticky-note"></i> {{ $record->handover_note }}</div>@endif
                </td>
                @if($showSite)<td class="px-4 py-3" data-label="Site">{{ $record->site->name ?? '' }}</td>@endif
                <td class="px-4 py-3 text-gray-600" data-label="Shift">{{ $record->schedule?->label() ?? 'Unscheduled' }}</td>
                <td class="px-4 py-3 whitespace-nowrap" data-label="In">{{ $record->clock_in_at->format('D j M, g:i A') }}</td>
                <td class="px-4 py-3 whitespace-nowrap" data-label="Out">{{ $record->clock_out_at?->format('g:i A') ?? 'On duty' }}</td>
                <td class="px-4 py-3" data-label="Worked">{{ $record->workedLabel() }}</td>
                <td class="px-4 py-3" data-label="Check">
                    <div class="flex flex-wrap gap-1">
                        @forelse($record->flags() as $flag)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-800">{{ $flag }}</span>
                        @empty
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800">OK</span>
                        @endforelse
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ $showSite ? 7 : 6 }}" class="px-4 py-6 text-center text-gray-500">No clock-ins in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $records->links() }}</div>
