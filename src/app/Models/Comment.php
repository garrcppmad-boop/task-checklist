<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    // task_checklist_id・user_id はフォームから受け取らず、コントローラーで設定する
    protected $fillable = [
        'body',
    ];

    public function taskChecklist(): BelongsTo
    {
        return $this->belongsTo(TaskChecklist::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
