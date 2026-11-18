@extends($layout)

@section('title', 'Booking Receipt')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    @if(session('success'))
        <div class="rounded-lg bg-green-50 p-4 text-green-700">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between gap-4 print:hidden">
        <a href="{{ route('frontdesk.reservations.create') }}" class="rounded-lg border px-4 py-2">New booking</a>
        <button type="button" onclick="window.print()" class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Print receipt</button>
    </div>

    <section class="rounded-xl bg-white p-6 shadow print:shadow-none">
        <div class="flex justify-between gap-6 border-b pb-5">
            <div><h1 class="text-2xl font-bold">Booking receipt</h1><p class="text-gray-500">{{ $invoice->invoice_number }}</p></div>
            <div class="text-right"><p class="font-semibold">{{ $reservation->unit->site->name }}</p><p class="text-sm text-gray-500">{{ $reservation->unit->title }}</p></div>
        </div>

        <div class="grid gap-4 py-5 sm:grid-cols-2">
            <div><p class="text-xs uppercase text-gray-500">Guest</p><p class="font-semibold">{{ $reservation->guest->name }}</p><p class="text-sm text-gray-600">{{ $reservation->guest->phone }}{{ $reservation->guest->email ? ' · '.$reservation->guest->email : '' }}</p></div>
            <div><p class="text-xs uppercase text-gray-500">Booking</p><p class="font-semibold">{{ $reservation->booking_ref }}</p><p class="text-sm text-gray-600">{{ $reservation->check_in->format('j M Y') }} – {{ $reservation->check_out->format('j M Y') }} · {{ $reservation->nights }} night(s)</p></div>
        </div>

        <div class="divide-y border-y">
            @foreach($invoice->items as $item)
                <div class="flex justify-between gap-4 py-3"><span>{{ $item->description }}</span><strong>₦{{ number_format((float) $item->total, 2) }}</strong></div>
            @endforeach
        </div>

        <div class="ml-auto mt-5 max-w-sm space-y-2">
            <div class="flex justify-between"><span>Total</span><strong>₦{{ number_format((float) $invoice->total_amount, 2) }}</strong></div>
            <div class="flex justify-between text-green-700"><span>Received</span><strong>₦{{ number_format((float) $invoice->paid_amount, 2) }}</strong></div>
            <div class="flex justify-between border-t pt-2 text-lg"><span>Balance</span><strong>₦{{ number_format((float) $invoice->balance, 2) }}</strong></div>
        </div>

        <p class="mt-6 text-sm text-gray-500">Collected by {{ $reservation->payments->first()?->collector?->name ?? 'system' }} on {{ $reservation->payments->first()?->collected_at?->format('j M Y, g:i a') }}. The ₦{{ number_format((float) $reservation->caution_fee, 2) }} caution fee is refundable subject to the booking terms.</p>
    </section>
</div>
@endsection
