{{-- ステータスのバッジ（未着手／進行中／完了） --}}
@php
    $statusClass = [
        'todo' => 'border-gray-300 bg-gray-100 text-gray-700',
        'doing' => 'border-blue-200 bg-blue-50 text-blue-700',
        'done' => 'border-gray-200 bg-gray-50 text-gray-400',
    ][$status] ?? 'border-gray-300 bg-gray-100 text-gray-700';
@endphp
<span class="inline-block rounded-full border px-2 py-0.5 text-xs {{ $statusClass }}">
    {{ \App\Models\TaskChecklist::STATUSES[$status] ?? $status }}
</span>
