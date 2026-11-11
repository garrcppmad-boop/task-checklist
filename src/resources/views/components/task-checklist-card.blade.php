{{--
    業務カード（一覧の1件分）：チームの業務一覧（No.5）・自分の業務一覧（No.6）・ブックマーク一覧（No.7）で共通
    使い方：<x-task-checklist-card :task-checklist="$taskChecklist" />

    表示するもの
    - 画像（添付があるときだけ・カードの上に表示）（F-09）
    - タイトル（詳細へのリンク）・説明の冒頭（F-05）
    - 担当者・期限（色とマークを変える：F-13）・ステータス・コメント数
    - ブックマークボタン（Ajax：F-14）

    $taskChecklist は、コントローラーで with('user')・withCount('comments')・withExists(is_bookmarked) を付けて取得しておく
    （付けないと1件ごとに SQL が走る N+1 問題になる）
--}}
@props(['taskChecklist'])

@php
    $isDone = $taskChecklist->status === 'done';
@endphp

<article {{ $attributes->class([
    // w-full：グリッドのマスいっぱいに広げ、同じ行のカードの高さをそろえる
    'flex w-full flex-col gap-3 rounded-lg border p-4 shadow-sm transition hover:shadow-md',
    'border-gray-200 bg-white' => ! $isDone,
    'border-gray-100 bg-gray-50' => $isDone, // 完了した業務は目立たなくする
]) }}>
    @if ($taskChecklist->image_url)
        <a href="{{ route('task-checklists.show', $taskChecklist) }}" class="shrink-0" tabindex="-1" aria-hidden="true">
            <img src="{{ $taskChecklist->image_url }}" alt=""
                 loading="lazy"
                 class="h-36 w-full rounded-md object-cover">
        </a>
    @endif

    <div class="min-w-0 flex-1">
        <h3 class="truncate text-base font-bold">
            <a href="{{ route('task-checklists.show', $taskChecklist) }}"
               class="hover:underline {{ $isDone ? 'text-gray-400' : 'text-gray-900' }}">
                {{ $taskChecklist->title }}
            </a>
        </h3>

        @if (filled($taskChecklist->description))
            <p class="mt-1 line-clamp-2 text-sm text-gray-600">
                {{ Str::limit($taskChecklist->description, 100) }}
            </p>
        @endif

        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
            <span>担当：{{ $taskChecklist->user->name }}</span>
            <span>期限：@include('task-checklists.partials.deadline', ['taskChecklist' => $taskChecklist])</span>
            @include('task-checklists.partials.status', ['status' => $taskChecklist->status])
            <span title="コメント数">💬 {{ $taskChecklist->comments_count ?? 0 }}</span>
        </div>
    </div>

    @include('task-checklists.partials.bookmark-button', [
        'taskChecklist' => $taskChecklist,
        'isBookmarked' => (bool) $taskChecklist->is_bookmarked,
        'class' => 'mt-auto self-start', // 本文の長さが違っても、ボタンはカードの下にそろえる
    ])
</article>
