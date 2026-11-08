{{--
    期限の表示（F-13）
    期限切れ：赤 ⚠ ／ 期限間近（3日以内）：オレンジ ⏰ ／ 通常 ／ 完了：灰色 ✓
--}}
@php
    $deadline = [
        'overdue' => ['class' => 'font-bold text-red-600', 'mark' => '⚠', 'label' => '期限切れ'],
        'due_soon' => ['class' => 'font-bold text-orange-500', 'mark' => '⏰', 'label' => '期限間近'],
        'normal' => ['class' => 'text-gray-900', 'mark' => '', 'label' => ''],
        'done' => ['class' => 'text-gray-400', 'mark' => '✓', 'label' => ''],
    ][$taskChecklist->deadlineState()];
@endphp
<span class="{{ $deadline['class'] }}">
    @if ($deadline['mark']){{ $deadline['mark'] }} @endif{{ $taskChecklist->due_date->format('Y/m/d') }}@if ($deadline['label'])（{{ $deadline['label'] }}）@endif
</span>
