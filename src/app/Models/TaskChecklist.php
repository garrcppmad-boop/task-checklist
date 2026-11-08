<?php

namespace App\Models;

use App\Services\TaskChecklistImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 業務（テーブル：task_checklists） */
class TaskChecklist extends Model
{
    /** ステータス（todo：未着手／doing：進行中／done：完了） */
    public const STATUSES = [
        'todo' => '未着手',
        'doing' => '進行中',
        'done' => '完了',
    ];

    /** 期限まで何日以内を「期限間近」とするか（F-13） */
    public const DUE_SOON_DAYS = 3;

    // user_id はフォームから受け取らず、ログイン中のユーザーから設定する
    protected $fillable = [
        'title',
        'description',
        'image_path',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /** 業務 N : 1 投稿者 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 業務 1 : N コメント */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** この業務をブックマークしたユーザー（N : N） */
    public function bookmarkedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bookmarks')->withTimestamps();
    }

    // ───────── 検索・絞り込み（ローカルスコープ） ─────────

    /** 指定したチームのメンバーが投稿した業務だけ（F-05） */
    public function scopeForTeam(Builder $query, int $teamId): void
    {
        $query->whereRelation('user', 'team_id', $teamId);
    }

    /** タイトルと説明をキーワードで部分一致検索（F-11） */
    public function scopeSearch(Builder $query, ?string $keyword): void
    {
        $keyword = trim((string) $keyword);
        if ($keyword === '') {
            return;
        }

        // % や _ を入力されても「何にでも一致」にならないようエスケープする
        // エスケープ文字は ESCAPE で明示する（MySQL とテスト用の SQLite で同じ動きにするため）
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $keyword).'%';

        // orWhere をかっこでまとめ、チームの条件が外れないようにする
        $query->where(function (Builder $q) use ($like) {
            $q->whereRaw("title like ? escape '!'", [$like])
                ->orWhereRaw("description like ? escape '!'", [$like]);
        });
    }

    /** 期限切れ：期限を過ぎていて未完了（F-12） */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', '!=', 'done')
            ->where('due_date', '<', today()->toDateString());
    }

    /** 期限間近：期限まで3日以内で未完了（F-12） */
    public function scopeDueSoon(Builder $query): void
    {
        $query->where('status', '!=', 'done')
            ->whereBetween('due_date', [
                today()->toDateString(),
                today()->addDays(self::DUE_SOON_DAYS)->toDateString(),
            ]);
    }

    // ───────── 表示用 ─────────

    /**
     * 期限による表示の状態（F-13）
     *
     * @return 'done'|'overdue'|'due_soon'|'normal'
     */
    public function deadlineState(): string
    {
        if ($this->status === 'done') {
            return 'done';
        }
        if ($this->due_date->lt(today())) {
            return 'overdue';
        }
        if ($this->due_date->lte(today()->addDays(self::DUE_SOON_DAYS))) {
            return 'due_soon';
        }

        return 'normal';
    }

    /** 添付画像の URL（画像がなければ null）：$taskChecklist->image_url で使う */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => app(TaskChecklistImageService::class)->url($this->image_path));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
