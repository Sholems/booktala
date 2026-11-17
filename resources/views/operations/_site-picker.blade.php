@if($sites->count() > 1)
<form method="GET" class="flex gap-2">
    <select name="site" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm" aria-label="Site">
        @foreach($sites as $s)
        <option value="{{ $s->id }}" @selected($s->id === $site->id)>{{ $s->name }}</option>
        @endforeach
    </select>
</form>
@endif
