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
    <ul class="space-y-2">
        @foreach ($taskChecklists as $taskChecklist)
            <li class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('task-checklists.show', $taskChecklist) }}"
                       class="block truncate font-bold hover:underline {{ $taskChecklist->status === 'done' ? 'text-gray-400' : 'text-gray-900' }}">
                        {{ $taskChecklist->title }}
                    </a>
                    <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                        <span>担当：{{ $taskChecklist->user->name }}</span>
                        <span>期限：@include('task-checklists.partials.deadline', ['taskChecklist' => $taskChecklist])</span>
                        @include('task-checklists.partials.status', ['status' => $taskChecklist->status])
                        <span title="コメント数">💬 {{ $taskChecklist->comments_count }}</span>
                    </div>
                </div>

                {{-- ブックマーク（F-14）：Ajax の処理はブックマーク機能の実装時に追加する --}}
                <button type="button" disabled
                        data-bookmark-id="{{ $taskChecklist->id }}"
                        aria-pressed="{{ $taskChecklist->is_bookmarked ? 'true' : 'false' }}"
                        class="self-start whitespace-nowrap rounded-full border px-3 py-1 text-sm sm:self-center
                               {{ $taskChecklist->is_bookmarked ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-gray-300 bg-white text-gray-600' }}">
                    {{ $taskChecklist->is_bookmarked ? '★ ブックマーク済み' : '☆ ブックマーク' }}
                </button>
            </li>
        @endforeach
    </ul>

    <div class="mt-6">
        {{ $taskChecklists->links() }}
    </div>
@endif
