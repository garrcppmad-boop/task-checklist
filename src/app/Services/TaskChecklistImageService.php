<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * 業務の添付画像の保存・差し替え・削除（F-09）【ローカルストレージ版】
 *
 * - 保存先：storage/app/public/task-checklists（public ディスク）
 *   公開環境（Render）では storage/app/public に Persistent Disk を付ける（docs/05_infrastructure）
 * - 表示：public/storage → storage/app/public のシンボリックリンク（php artisan storage:link）経由
 * - DB（task_checklists.image_path）には「task-checklists/xxxx.jpg」の形でパスだけを保存する
 * - ファイル名は Laravel が作るランダムな名前にする（元のファイル名は使わない：重複・不正なファイル名を防ぐ）
 *
 * S3 に移す（F-19）ときは DISK を 's3' に変えれば、コントローラーとビューはそのまま使える。
 */
class TaskChecklistImageService
{
    /** 保存先のディスク（config/filesystems.php） */
    public const DISK = 'public';

    /** ディスクの中の保存フォルダ */
    public const DIRECTORY = 'task-checklists';

    /** 画像を保存して、DB に入れるパスを返す */
    public function store(UploadedFile $image): string
    {
        return $image->store(self::DIRECTORY, self::DISK);
    }

    /**
     * 画像を差し替える
     * 新しい画像の保存に成功してから古い画像を消す（保存に失敗しても古い画像が残るように）
     */
    public function replace(UploadedFile $image, ?string $oldPath): string
    {
        $newPath = $this->store($image);
        $this->delete($oldPath);

        return $newPath;
    }

    /** 画像を削除する（パスが空なら何もしない） */
    public function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /** 表示用の URL（画像がなければ null） */
    public function url(?string $path): ?string
    {
        return filled($path) ? Storage::disk(self::DISK)->url($path) : null;
    }
}
