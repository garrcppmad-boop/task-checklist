<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * すべてのコントローラーの親クラス
 * AuthorizesRequests を入れると、各コントローラーで $this->authorize() が使える
 * （権限管理 F-15：投稿者以外が編集・削除の URL を開いたら 403 にする）
 */
abstract class Controller
{
    use AuthorizesRequests;
}
