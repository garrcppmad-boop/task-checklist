{{-- No.8 業務の新規投稿（F-04・F-09） --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h1 class="mb-6 text-2xl font-bold text-gray-900">業務の新規投稿</h1>

                @include('task-checklists.partials.form', ['statuses' => $statuses])
            </div>
        </div>
    </div>
</x-app-layout>
