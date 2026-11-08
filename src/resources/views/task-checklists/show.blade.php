{{-- No.9 業務の詳細（F-07・F-10・F-13・F-14・F-15） --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h1 class="break-words text-2xl font-bold {{ $taskChecklist->status === 'done' ? 'text-gray-400' : 'text-gray-900' }}">
                        {{ $taskChecklist->title }}
                    </h1>

                    {{-- ブックマーク（F-14）：Ajax の処理はブックマーク機能の実装時に追加する --}}
                    <button type="button" disabled
                            data-bookmark-id="{{ $taskChecklist->id }}"
                            aria-pressed="{{ $isBookmarked ? 'true' : 'false' }}"
                            class="whitespace-nowrap rounded-full border px-3 py-1 text-sm
                                   {{ $isBookmarked ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-gray-300 bg-white text-gray-600' }}">
                        {{ $isBookmarked ? '★ ブックマーク済み' : '☆ ブックマーク' }}
                    </button>
                </div>

                <dl class="mt-4 grid grid-cols-[6rem_1fr] gap-x-4 gap-y-2 text-sm">
                    <dt class="text-gray-500">担当者</dt>
                    <dd class="text-gray-900">{{ $taskChecklist->user->name }}</dd>

                    <dt class="text-gray-500">期限</dt>
                    <dd>@include('task-checklists.partials.deadline', ['taskChecklist' => $taskChecklist])</dd>

                    <dt class="text-gray-500">ステータス</dt>
                    <dd>@include('task-checklists.partials.status', ['status' => $taskChecklist->status])</dd>

                    <dt class="text-gray-500">投稿日</dt>
                    <dd class="text-gray-900">{{ $taskChecklist->created_at->format('Y/m/d') }}</dd>
                </dl>

                <div class="mt-6 border-t border-gray-100 pt-4">
                    @if (filled($taskChecklist->description))
                        <p class="whitespace-pre-line break-words text-gray-800">{{ $taskChecklist->description }}</p>
                    @else
                        <p class="text-sm text-gray-400">説明はありません</p>
                    @endif
                </div>

                {{-- 添付画像（F-09） --}}
                @if ($taskChecklist->image_url)
                    <div class="mt-6">
                        <img src="{{ $taskChecklist->image_url }}"
                             alt="{{ $taskChecklist->title }}の添付画像"
                             class="max-h-[32rem] w-full rounded-md border border-gray-200 object-contain">
                    </div>
                @endif

                {{-- 編集・削除は投稿者だけ（F-15：TaskChecklistPolicy） --}}
                @canany(['update', 'delete'], $taskChecklist)
                    <div class="mt-6 flex items-center gap-3">
                        @can('update', $taskChecklist)
                            <a href="{{ route('task-checklists.edit', $taskChecklist) }}"
                               class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                編集
                            </a>
                        @endcan
                        @can('delete', $taskChecklist)
                            <form method="POST" action="{{ route('task-checklists.destroy', $taskChecklist) }}"
                                  onsubmit="return confirm('この業務を削除しますか？コメントも一緒に削除されます。');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                                    削除
                                </button>
                            </form>
                        @endcan
                    </div>
                @else
                    <p class="mt-6 text-sm text-gray-400">編集・削除は投稿者だけができます</p>
                @endcanany
            </div>

            {{-- コメント（F-10）：投稿フォームはコメント機能の実装時（ルート追加後）に追加する --}}
            <h2 class="mt-8 mb-3 text-lg font-bold text-gray-900">
                コメント（{{ $taskChecklist->comments->count() }}）
            </h2>
            <div class="rounded-lg bg-white p-6 shadow-sm">
                @forelse ($taskChecklist->comments as $comment)
                    <div class="border-b border-gray-100 py-3 first:pt-0 last:border-b-0 last:pb-0">
                        <div class="flex items-baseline gap-3 text-sm">
                            <span class="font-bold text-gray-900">{{ $comment->user->name }}</span>
                            <span class="text-gray-400">{{ $comment->created_at->format('Y/m/d H:i') }}</span>
                        </div>
                        <p class="mt-1 whitespace-pre-line break-words text-gray-800">{{ $comment->body }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">まだコメントはありません</p>
                @endforelse
            </div>

            <p class="mt-6">
                <a href="{{ route('task-checklists.index') }}" class="text-sm text-indigo-600 hover:underline">← 業務一覧に戻る</a>
            </p>
        </div>
    </div>
</x-app-layout>
