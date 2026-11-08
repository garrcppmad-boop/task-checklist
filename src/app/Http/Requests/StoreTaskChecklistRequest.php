<?php

namespace App\Http\Requests;

use App\Models\TaskChecklist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 業務の投稿（F-04・F-09） */
class StoreTaskChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ログインしていれば誰でも投稿できる（auth ミドルウェアで確認）
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(TaskChecklist::STATUSES))],
            // 画像は1枚、jpg / png / webp、2MB まで（F-09）
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'description' => '説明',
            'due_date' => '期限',
            'status' => 'ステータス',
            'image' => '画像',
        ];
    }
}
