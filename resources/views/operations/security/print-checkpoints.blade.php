<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patrol QR codes · {{ $site->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/qr.js'])
    <style>
        @media print { .no-print { display: none !important; } .card { break-inside: avoid; } body { background: #fff; } }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="no-print max-w-4xl mx-auto px-4 py-4 flex items-center justify-between gap-3">
        <p class="text-sm text-gray-600">Print this page, cut out each code and fix it at the checkpoint. Guards scan it with their phone camera while signed in to BookTala.</p>
        <button type="button" onclick="window.print()" class="shrink-0 bg-primary text-white px-4 py-2 rounded-lg">Print</button>
    </div>
    <div class="max-w-4xl mx-auto px-4 pb-8 grid grid-cols-2 gap-4">
        @forelse($checkpoints as $checkpoint)
        <div class="card bg-white rounded-lg ring-1 ring-gray-300 p-5 text-center">
            <div class="text-xs uppercase tracking-wide text-gray-500">{{ $site->name }} · patrol checkpoint</div>
            <div class="text-lg font-bold mb-3">{{ $checkpoint->name }}</div>
            <canvas class="js-qr mx-auto" data-url="{{ $checkpoint->scanUrl() }}" aria-label="QR code for {{ $checkpoint->name }}"></canvas>
            <div class="mt-2 text-[10px] text-gray-400 break-all">{{ $checkpoint->scanUrl() }}</div>
        </div>
        @empty
        <p class="col-span-2 text-gray-500">No checkpoints at this site.</p>
        @endforelse
    </div>
</body>
</html>
