<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskChecklistRequest;
use App\Http\Requests\UpdateTaskChecklistRequest;
use App\Models\TaskChecklist;
use App\Services\TaskChecklistImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Throwable;

class TaskChecklistController extends Controller
{
    /** 1ページの表示件数（要件定義書「4. 非機能要件」） */
    private const PER_PAGE = 20;

    public function __construct(
        private readonly TaskChecklistImageService $images,
    ) {
    }

    /**
     * チームの業務一覧（F-05）
     * キーワード検索（F-11）、期限・ステータスでの絞り込み（F-12）
     * 例：/task-checklists?keyword=見積&filter=due_soon&status=doing
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // ?keyword[]=a のように配列で送られても 500 エラーにならないよう、文字列だけを受け付ける
        $keyword = $this->stringQuery($request, 'keyword');
        $filter = $this->stringQuery($request, 'filter');
        $status = $this->stringQuery($request, 'status');

        $query = TaskChecklist::query()
            ->forTeam($user->team_id)
            ->search($keyword)
            ->when($filter === 'overdue', fn (Builder $q) => $q->overdue())
            ->when($filter === 'due_soon', fn (Builder $q) => $q->dueSoon())
            ->when(
                array_key_exists((string) $status, TaskChecklist::STATUSES),
                fn (Builder $q) => $q->where('status', $status)
            );

        $taskChecklists = $this->paginateForList($query, $user->id);

        return view('task-checklists.index', [
            'taskChecklists' => $taskChecklists,
            'statuses' => TaskChecklist::STATUSES,
            'keyword' => $keyword,
            'filter' => $filter,
            'status' => $status,
        ]);
    }

    /** 自分の業務一覧（F-06） */
    public function mine(Request $request)
    {
        $user = $request->user();

        $taskChecklists = $this->paginateForList(
            TaskChecklist::query()->where('user_id', $user->id),
            $user->id
        );

        return view('task-checklists.mine', [
            'taskChecklists' => $taskChecklists,
            'statuses' => TaskChecklist::STATUSES,
        ]);
    }

    /** 業務の作成フォーム（F-04） */
    public function create()
    {
        return view('task-checklists.create', [
            'statuses' => TaskChecklist::STATUSES,
        ]);
    }

    /** 業務の保存（F-04・F-09） */
    public function store(StoreTaskChecklistRequest $request)
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->images->store($request->file('image'));
        }

        try {
            // user_id はログイン中のユーザーから自動で入る
            $taskChecklist = $request->user()->taskChecklists()->create($data);
        } catch (Throwable $e) {
            // DB への保存に失敗したら、先に保存した画像を消して残骸を残さない
            $this->images->delete($data['image_path'] ?? null);
            throw $e;
        }

        return redirect()->route('task-checklists.show', $taskChecklist)
            ->with('success', '業務を投稿しました。');
    }

    /** 業務の詳細（F-07）：同じチームの業務だけ */
    public function show(Request $request, TaskChecklist $taskChecklist)
    {
        $this->authorize('view', $taskChecklist);

        $taskChecklist->load([
            'user',
            'comments' => fn ($q) => $q->oldest()->with('user'), // コメントは古い順
        ]);

        $isBookmarked = $taskChecklist->bookmarkedBy()
            ->whereKey($request->user()->id)
            ->exists();

        return view('task-checklists.show', [
            'taskChecklist' => $taskChecklist,
            'isBookmarked' => $isBookmarked,
        ]);
    }

    /** 業務の編集フォーム（F-08）：投稿者だけ */
    public function edit(TaskChecklist $taskChecklist)
    {
        $this->authorize('update', $taskChecklist);

        return view('task-checklists.edit', [
            'taskChecklist' => $taskChecklist,
            'statuses' => TaskChecklist::STATUSES,
        ]);
    }

    /** 業務の更新（F-08・F-09）：投稿者だけ */
    public function update(UpdateTaskChecklistRequest $request, TaskChecklist $taskChecklist)
    {
        $this->authorize('update', $taskChecklist);

        $data = $request->safe()->except('image');

        $oldPath = $taskChecklist->image_path;
        $newPath = null;

        // 新しい画像が送られてきたら保存する（古い画像は DB の更新が成功してから消す）
        if ($request->hasFile('image')) {
            $newPath = $data['image_path'] = $this->images->store($request->file('image'));
        }

        try {
            $taskChecklist->update($data);
        } catch (Throwable $e) {
            $this->images->delete($newPath); // 失敗したら新しい画像を消し、古い画像はそのまま
            throw $e;
        }

        if ($newPath) {
            $this->images->delete($oldPath);
        }

        return redirect()->route('task-checklists.show', $taskChecklist)
            ->with('success', '業務を更新しました。');
    }

    /** 業務の削除（F-08）：投稿者だけ。コメント・ブックマークは外部キーの CASCADE で消える */
    public function destroy(TaskChecklist $taskChecklist)
    {
        $this->authorize('delete', $taskChecklist);

        $imagePath = $taskChecklist->image_path;

        $taskChecklist->delete();

        // DB から消えてから画像を消す（削除に失敗したときに画像だけ消えないように）
        $this->images->delete($imagePath);

        // 削除後は自分の業務一覧へ（要件定義書「画面遷移図」）
        return redirect()->route('task-checklists.mine')
            ->with('success', '業務を削除しました。');
    }

    /** クエリ文字列の値を取り出す（文字列でなければ null） */
    private function stringQuery(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) ? $value : null;
    }

    /**
     * 一覧画面で共通の取得処理
     * - 期限の近い順（F-05）、20件ずつ
     * - 投稿者・コメント数・自分がブックマークしているかを一緒に取得し、N+1 問題を防ぐ
     */
    private function paginateForList(Builder $query, int $userId)
    {
        return $query
            ->with('user')
            ->withCount('comments')
            ->withExists(['bookmarkedBy as is_bookmarked' => fn ($q) => $q->where('bookmarks.user_id', $userId)])
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString(); // 2ページ目以降も検索条件を引き継ぐ
    }
}
