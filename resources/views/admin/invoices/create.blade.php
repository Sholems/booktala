@extends('layouts.admin')
@section('page_title', 'Create Invoice')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.invoices.index') }}" class="text-primary hover:underline text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to Invoices
    </a>
    <h2 class="text-2xl font-bold mt-2">Create Invoice</h2>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('admin.invoices.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Select Reservation *</label>
                <select name="reservation_id" id="reservation_id" class="w-full border rounded px-3 py-2" required>
                    <option value="">Select a reservation</option>
                    @foreach($reservations as $res)
                    <option value="{{ $res->id }}" data-nights="{{ $res->nights }}" data-price="{{ $res->total_price }}">
                        {{ $res->property->name }} - {{ $res->user->name ?? 'Guest' }} 
                        ({{ \Carbon\Carbon::parse($res->check_in)->format('M d') }} - {{ \Carbon\Carbon::parse($res->check_out)->format('M d, Y') }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Due Date</label>
                <input type="date" name="due_date" class="w-full border rounded px-3 py-2" value="{{ now()->addDays(7)->format('Y-m-d') }}">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2">Notes</label>
                <textarea name="notes" rows="3" class="w-full border rounded px-3 py-2" placeholder="Optional notes..."></textarea>
            </div>
        </div>

        <div class="mt-6 p-4 bg-gray-50 rounded">
            <h4 class="font-semibold mb-3">Invoice Preview</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">Subtotal</p>
                    <p class="font-medium" id="preview-subtotal">-</p>
                </div>
                <div>
                    <p class="text-gray-500">VAT (7.5%)</p>
                    <p class="font-medium" id="preview-vat">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Discount</p>
                    <p class="font-medium" id="preview-discount">₦0.00</p>
                </div>
                <div>
                    <p class="text-gray-500">Total</p>
                    <p class="font-bold text-lg" id="preview-total">-</p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-primary/90">
                Create Invoice
            </button>
            <a href="{{ route('admin.invoices.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('reservation_id').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const subtotal = parseFloat(option.dataset.price) || 0;
    const vat = subtotal * 0.075;
    const total = subtotal + vat;
    
    document.getElementById('preview-subtotal').textContent = '₦' + subtotal.toLocaleString('en-NG', {minimumFractionDigits: 2});
    document.getElementById('preview-vat').textContent = '₦' + vat.toLocaleString('en-NG', {minimumFractionDigits: 2});
    document.getElementById('preview-total').textContent = '₦' + total.toLocaleString('en-NG', {minimumFractionDigits: 2});
});
</script>
@endpush
@endsection