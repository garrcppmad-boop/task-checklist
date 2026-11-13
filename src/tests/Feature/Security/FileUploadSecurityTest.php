<?php

namespace Tests\Feature\Security;

use App\Models\TaskChecklist;
use App\Models\User;
use App\Services\TaskChecklistImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * セキュリティ：ファイルアップロードのバリデーション
 * 拡張子だけを偽った危険なファイルを弾くこと、保存時のファイル名を利用者に決めさせないことを確かめる
 * （通常の形式・サイズの確認は tests/Feature/TaskChecklist/ImageUploadTest）
 */
class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(TaskChecklistImageService::DISK);
        $this->user = User::factory()->create();
    }

    private function upload(UploadedFile $file)
    {
        return $this->actingAs($this->user)->post(route('task-checklists.store'), [
            'title' => 'アップロード',
            'due_date' => today()->toDateString(),
            'status' => 'todo',
            'image' => $file,
        ]);
    }

    private function jpegBytes(): string
    {
        ob_start();
        imagejpeg(imagecreatetruecolor(10, 10));

        return ob_get_clean();
    }

    /**
     * 本番と同じ判定をさせるためのアップロードファイル
     * UploadedFile::fake() は MIME タイプをファイル名から決めてしまうため、
     * 実際のファイルを作り、中身（finfo）で MIME タイプが判定されるようにする
     */
    private function realUpload(string $clientName, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $content);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return new UploadedFile($path, $clientName, null, null, true);
    }

    private function assertNothingStored(): void
    {
        $this->assertDatabaseCount('task_checklists', 0);
        $this->assertEmpty(Storage::disk(TaskChecklistImageService::DISK)->allFiles());
    }

    public function test_php_script_disguised_as_jpg_is_rejected(): void
    {
        // 拡張子は .jpg だが中身は PHP（中身の MIME タイプで判定されるので弾かれる）
        $this->upload($this->realUpload('shell.jpg', '<?php system($_GET["c"]); ?>'))
            ->assertSessionHasErrors('image');

        $this->assertNothingStored();
    }

    public function test_php_extension_is_rejected_even_with_image_content(): void
    {
        $this->upload($this->realUpload('shell.php', $this->jpegBytes()))
            ->assertSessionHasErrors('image');

        $this->assertNothingStored();
    }

    public function test_svg_is_rejected(): void
    {
        // SVG は中に <script> を書けるため、許可しない
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->upload($this->realUpload('image.svg', $svg))
            ->assertSessionHasErrors('image');

        $this->assertNothingStored();
    }

    public function test_html_file_is_rejected(): void
    {
        $this->upload($this->realUpload('page.html', '<html><script>alert(1)</script></html>'))
            ->assertSessionHasErrors('image');

        $this->assertNothingStored();
    }

    public function test_stored_file_name_is_random_and_has_safe_extension(): void
    {
        // 二重拡張子・パストラバーサルを含むファイル名でも、保存名は Laravel が決める
        $this->upload($this->realUpload('../../public/shell.php.jpg', $this->jpegBytes()))
            ->assertSessionHasNoErrors();

        $path = TaskChecklist::sole()->image_path;

        $this->assertMatchesRegularExpression('#^task-checklists/[A-Za-z0-9]{40}\.jpg$#', $path);
        $this->assertStringNotContainsString('php', $path);
        $this->assertStringNotContainsString('..', $path);
        Storage::disk(TaskChecklistImageService::DISK)->assertExists($path);
    }

    public function test_image_path_cannot_be_set_from_request(): void
    {
        // image_path を直接送って、サーバー上の別のファイルを指させることはできない
        $this->actingAs($this->user)->post(route('task-checklists.store'), [
            'title' => 'パス指定',
            'due_date' => today()->toDateString(),
            'status' => 'todo',
            'image_path' => '../.env',
        ])->assertSessionHasNoErrors();

        $this->assertNull(TaskChecklist::sole()->image_path);
    }

    public function test_multiple_files_are_rejected(): void
    {
        // 画像は1枚だけ（配列で送っても受け付けない）
        $this->actingAs($this->user)->post(route('task-checklists.store'), [
            'title' => '複数枚',
            'due_date' => today()->toDateString(),
            'status' => 'todo',
            'image' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasErrors('image');

        $this->assertNothingStored();
    }
}
