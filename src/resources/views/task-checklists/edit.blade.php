{{-- No.10 業務の編集（F-08・F-09）：投稿者だけ --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h1 class="mb-6 text-2xl font-bold text-gray-900">業務の編集</h1>

                @include('task-checklists.partials.form', [
                    'statuses' => $statuses,
                    'taskChecklist' => $taskChecklist,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
