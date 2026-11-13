{{-- No.5 チームの業務一覧（F-05・F-11・F-12・F-13・F-14） --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ auth()->user()->team?->name }}の業務一覧
                </h1>
                <a href="{{ route('task-checklists.create') }}"
                   class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    ＋ 新規投稿
                </a>
            </div>

            {{-- 検索・絞り込み（F-11・F-12） --}}
            <form method="GET" action="{{ route('task-checklists.index') }}"
                  class="mb-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
                <div class="flex-1 min-w-[12rem]">
                    <label for="keyword" class="block text-xs font-medium text-gray-600">キーワード</label>
                    <input type="search" id="keyword" name="keyword" value="{{ $keyword }}"
                           placeholder="タイトル・説明で検索"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="filter" class="block text-xs font-medium text-gray-600">期限</label>
                    <select id="filter" name="filter"
                            class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">すべて</option>
                        <option value="overdue" @selected($filter === 'overdue')>期限切れ</option>
                        <option value="due_soon" @selected($filter === 'due_soon')>期限間近（{{ \App\Models\TaskChecklist::DUE_SOON_DAYS }}日以内）</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-xs font-medium text-gray-600">ステータス</label>
                    <select id="status" name="status"
                            class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">すべて</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        検索
                    </button>
                    @if (request()->hasAny(['keyword', 'filter', 'status']))
                        <a href="{{ route('task-checklists.index') }}" class="text-sm text-gray-500 underline hover:text-gray-700">条件をクリア</a>
                    @endif
                </div>
            </form>

            @include('task-checklists.partials.legend')

            @include('task-checklists.partials.list', [
                'taskChecklists' => $taskChecklists,
                'emptyMessage' => request()->hasAny(['keyword', 'filter', 'status'])
                    ? '条件に合う業務がありません'
                    : 'まだ業務が投稿されていません',
            ])
        </div>
    </div>
</x-app-layout>
