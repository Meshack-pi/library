@props(['notice' => null, 'failure' => null, 'version' => 0])

@php($message = $notice ?? $failure)
@php($isSuccess = $notice !== null)

@if ($message)
    <div wire:key="toast-{{ $version }}"
         x-data="{ show: false }"
         x-init="$nextTick(() => show = true); setTimeout(() => show = false, 5000)"
         x-show="show"
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="translate-x-8 opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="translate-x-8 opacity-0"
         role="{{ $isSuccess ? 'status' : 'alert' }}"
         aria-live="{{ $isSuccess ? 'polite' : 'assertive' }}"
         @class([
             'toast',
             'toast-success' => $isSuccess,
             'toast-error' => ! $isSuccess,
         ])>
        <span class="toast-icon">
            <x-icon :name="$isSuccess ? 'check-circle' : 'alert'" class="size-6" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-extrabold">{{ $isSuccess ? 'Done' : 'Something needs attention' }}</p>
            <p class="mt-0.5 text-sm">{{ $message }}</p>
        </div>
        <button type="button" class="toast-close" @click="show = false">
            <x-icon name="x" class="size-4" />
            <span class="sr-only">Dismiss notification</span>
        </button>
        <span class="toast-timer" aria-hidden="true"></span>
    </div>
@endif
