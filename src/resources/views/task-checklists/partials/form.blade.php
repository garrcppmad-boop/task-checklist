{{--
    業務のフォーム（新規投稿・編集で共通）
    受け取る変数：$statuses、$taskChecklist（編集のときだけ。新規投稿では null）
    入力ルールは StoreTaskChecklistRequest と合わせる
--}}
@php
    $isEdit = isset($taskChecklist);
    $inputClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<form method="POST"
      action="{{ $isEdit ? route('task-checklists.update', $taskChecklist) : route('task-checklists.store') }}"
      enctype="multipart/form-data"
      class="space-y-5">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- タイトル（必須・100文字まで） --}}
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700">
            タイトル <span class="text-red-600">*</span>
        </label>
        <input type="text" id="title" name="title" maxlength="100" required
               value="{{ old('title', $taskChecklist->title ?? '') }}"
               class="{{ $inputClass }}">
        <p class="mt-1 text-xs text-gray-500">100文字まで</p>
        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- 説明（任意） --}}
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">説明</label>
        <textarea id="description" name="description" rows="6"
                  class="{{ $inputClass }}">{{ old('description', $taskChecklist->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        {{-- 期限（必須） --}}
        <div>
            <label for="due_date" class="block text-sm font-medium text-gray-700">
                期限 <span class="text-red-600">*</span>
            </label>
            <input type="date" id="due_date" name="due_date" required
                   value="{{ old('due_date', isset($taskChecklist) ? $taskChecklist->due_date->format('Y-m-d') : '') }}"
                   class="{{ $inputClass }}">
            @error('due_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ステータス（必須・初期値は未着手） --}}
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">
                ステータス <span class="text-red-600">*</span>
            </label>
            <select id="status" name="status" required class="{{ $inputClass }}">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $taskChecklist->status ?? 'todo') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- 画像（任意・1枚、jpg / png / webp、2MBまで）（F-09） --}}
    <div>
        <label for="image" class="block text-sm font-medium text-gray-700">画像</label>
        @if ($isEdit && $taskChecklist->image_url)
            <img src="{{ $taskChecklist->image_url }}" alt="現在の添付画像"
                 class="mt-2 max-h-40 rounded border border-gray-200">
        @endif
        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp"
               class="mt-2 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200">
        <p class="mt-1 text-xs text-gray-500">
            jpg / png / webp、2MBまで（1枚）@if ($isEdit && $taskChecklist->image_path)。選ぶと今の画像と差し替えます@endif
        </p>
        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            {{ $isEdit ? '更新する' : '投稿する' }}
        </button>
        <a href="{{ $isEdit ? route('task-checklists.show', $taskChecklist) : route('task-checklists.index') }}"
           class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            キャンセル
        </a>
    </div>
</form>
