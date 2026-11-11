{{-- No.6 自分の業務一覧（F-06・F-13・F-14） --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <h1 class="text-2xl font-bold text-gray-900">自分の業務一覧</h1>
                <a href="{{ route('task-checklists.create') }}"
                   class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    ＋ 新規投稿
                </a>
            </div>

            @include('task-checklists.partials.legend')

            @include('task-checklists.partials.list', [
                'taskChecklists' => $taskChecklists,
                'emptyMessage' => 'まだ業務を投稿していません',
            ])
        </div>
    </div>
</x-app-layout>
