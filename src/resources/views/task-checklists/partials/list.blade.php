{{--
    業務の一覧（チームの業務一覧・自分の業務一覧で共通）
    受け取る変数：$taskChecklists（ページネーション済み）、$emptyMessage
    コントローラーで with('user')・withCount('comments')・withExists(is_bookmarked) を付けて取得している
--}}
@if ($taskChecklists->isEmpty())
    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-10 text-center text-gray-500">
        {{ $emptyMessage }}
    </div>
@else
    {{-- レスポンシブ対応（非機能要件）：スマホ 1列／タブレット 2列／PC 3列。期限の近い順に左上から並ぶ --}}
    <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($taskChecklists as $taskChecklist)
            <li class="flex">
                <x-task-checklist-card :task-checklist="$taskChecklist" />
            </li>
        @endforeach
    </ul>

    <div class="mt-6">
        {{ $taskChecklists->links() }}
    </div>
@endif
