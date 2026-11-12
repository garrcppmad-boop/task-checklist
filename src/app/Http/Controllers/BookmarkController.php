<?php

namespace App\Http\Controllers;

use App\Models\TaskChecklist;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    /**
     * ブックマークの登録・解除（F-14・Ajax）
     * 画面は返さず、登録されているかどうかを JSON で返す（例：{"bookmarked": true}）
     * 同じチームの業務だけ（他のチームの業務は 403）
     */
    public function toggle(Request $request, TaskChecklist $taskChecklist): JsonResponse
    {
        $this->authorize('view', $taskChecklist);

        $user = $request->user();

        if ($user->bookmarks()->whereKey($taskChecklist->id)->exists()) {
            $user->bookmarks()->detach($taskChecklist->id);
            $bookmarked = false;
        } else {
            try {
                $user->bookmarks()->attach($taskChecklist->id);
            } catch (UniqueConstraintViolationException) {
                // ボタンを素早く2回押したときなど、もう登録されていた場合は「登録済み」として扱う
            }
            $bookmarked = true;
        }

        return response()->json([
            'bookmarked' => $bookmarked,
        ]);
    }
}
