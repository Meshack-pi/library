@props(['notice' => null, 'failure' => null])

@if ($notice)
    <div role="status" class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $notice }}</div>
@endif

@if ($failure)
    <div role="alert" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $failure }}</div>
@endif
