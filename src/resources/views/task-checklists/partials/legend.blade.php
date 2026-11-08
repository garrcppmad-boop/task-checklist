{{-- 期限の色分けの凡例（F-13） --}}
<div class="mb-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
    <span class="font-bold text-red-600">⚠ 期限切れ</span>
    <span class="font-bold text-orange-500">⏰ 期限間近（{{ \App\Models\TaskChecklist::DUE_SOON_DAYS }}日以内）</span>
    <span class="text-gray-900">通常（{{ \App\Models\TaskChecklist::DUE_SOON_DAYS + 1 }}日以上）</span>
    <span class="text-gray-400">✓ 完了</span>
</div>
