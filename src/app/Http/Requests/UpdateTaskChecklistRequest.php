<?php

namespace App\Http\Requests;

/**
 * 業務の更新（F-08）
 * 入力項目とルールは投稿と同じ。投稿者かどうかはコントローラーで TaskChecklistPolicy を使って確認する。
 */
class UpdateTaskChecklistRequest extends StoreTaskChecklistRequest
{
}
