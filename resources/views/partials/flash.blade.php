@if(session('success'))
<div class="mb-6 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-green-700" role="status">
    <i class="fas fa-check-circle mt-0.5"></i>
    <span>{{ session('success') }}</span>
</div>
@endif
@if(session('error'))
<div class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700" role="alert">
    <i class="fas fa-exclamation-circle mt-0.5"></i>
    <span>{{ session('error') }}</span>
</div>
@endif
