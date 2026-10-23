# 練習課題3（必須）: ルーティング設計

ToDoアプリのルーティング設計書です。Blade の Web アプリとして作り、ログインには Breeze を使い、ルートは routes/web.php に書きます。

| 画面・操作               | メソッド  | URL                              | ルート名         | コントローラー@アクション  | ログイン | 権限（Policy）    |
|--------------------------|-----------|----------------------------------|------------------|----------------------------|----------|-------------------|
| トップ                   | GET       | /                                | home             | （tasks.index へリダイレクト） | 不要     | -                 |
| ToDo一覧（検索・絞り込み） | GET       | /tasks?keyword=...&status=...    | tasks.index      | TaskController@index       | 必要     | 自分のToDoだけ表示 |
| ToDo詳細                 | GET       | /tasks/{task}                    | tasks.show       | TaskController@show        | 必要     | 自分のToDoだけ    |
| ToDo作成フォーム         | GET       | /tasks/create                    | tasks.create     | TaskController@create      | 必要     | -                 |
| ToDoの保存               | POST      | /tasks                           | tasks.store      | TaskController@store       | 必要     | -                 |
| ToDo編集フォーム         | GET       | /tasks/{task}/edit               | tasks.edit       | TaskController@edit        | 必要     | 自分のToDoだけ    |
| ToDoの更新               | PUT       | /tasks/{task}                    | tasks.update     | TaskController@update      | 必要     | 自分のToDoだけ    |
| ToDoの削除               | DELETE    | /tasks/{task}                    | tasks.destroy    | TaskController@destroy     | 必要     | 自分のToDoだけ    |
| 完了・未完了の切り替え（Ajax） | PATCH | /tasks/{task}/toggle             | tasks.toggle     | TaskController@toggle      | 必要     | 自分のToDoだけ    |

※ ログイン・ログアウト・会員登録・パスワードリセットの画面は Breeze が作るルートを使う
※ ログイン後の遷移先は tasks.index にする
※ 一覧の絞り込み status は all（すべて）/ active（未完了）/ done（完了）
※ 完了・未完了の切り替え（Ajax）は画面を返さず、JSON（ToDoのIDと完了状態）を返す
※ 「自分のToDoだけ」は TaskPolicy で、ToDoの user_id がログイン中のユーザーと一致するかを確認する
