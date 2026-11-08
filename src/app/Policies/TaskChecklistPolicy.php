<?php

namespace App\Policies;

use App\Models\TaskChecklist;
use App\Models\User;

/** 権限管理（F-15） */
class TaskChecklistPolicy
{
    /** 閲覧・コメント・ブックマーク：同じチームの業務だけ */
    public function view(User $user, TaskChecklist $taskChecklist): bool
    {
        return (int) $taskChecklist->user->team_id === (int) $user->team_id;
    }

    /** 編集：投稿者だけ */
    public function update(User $user, TaskChecklist $taskChecklist): bool
    {
        return (int) $taskChecklist->user_id === (int) $user->id;
    }

    /** 削除：投稿者だけ */
    public function delete(User $user, TaskChecklist $taskChecklist): bool
    {
        return (int) $taskChecklist->user_id === (int) $user->id;
    }
}
