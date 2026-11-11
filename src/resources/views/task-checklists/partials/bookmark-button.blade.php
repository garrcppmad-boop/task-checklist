{{--
    ブックマークボタン（F-14・Ajax）：チームの業務一覧（No.5）と業務の詳細（No.9）で共通
    受け取る変数：$taskChecklist、$isBookmarked（今ブックマークしているか）、$class（位置合わせ用の追加クラス。任意）

    流れ：fetch で POST → BookmarkController@toggle が {"bookmarked": true/false} を返す → Alpine.js でボタンの表示を変える
    ページは再読み込みしない
    - sending：返事が来るまでは2回目を送らない（二重送信を防ぐ）
    - URL は相対パス（absolute: false）にする。localhost / 127.0.0.1 など、開き方が違っても同じサイトに送れるように
--}}
<div x-data="{
        bookmarked: @js((bool) $isBookmarked),
        sending: false,
        async toggleBookmark() {
            if (this.sending) return;
            this.sending = true;
            try {
                const res = await fetch(@js(route('task-checklists.bookmark', $taskChecklist, false)), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                        'Accept': 'application/json',
                    },
                });
                if (! res.ok) throw new Error(res.status);
                const data = await res.json();
                this.bookmarked = data.bookmarked;
            } catch (e) {
                alert('ブックマークを変更できませんでした。ページを再読み込みしてから、もう一度お試しください。');
            } finally {
                this.sending = false;
            }
        },
    }"
     class="{{ $class ?? '' }}">
    <button type="button"
            @click="toggleBookmark()"
            :disabled="sending"
            :aria-pressed="bookmarked.toString()"
            :class="bookmarked ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-gray-300 bg-white text-gray-600'"
            aria-pressed="{{ $isBookmarked ? 'true' : 'false' }}"
            {{-- 色は :class だけで付ける（ここにも書くと、切り替えたときに古い色が残る） --}}
            class="whitespace-nowrap rounded-full border px-3 py-1 text-sm">
        <span x-text="bookmarked ? '★ ブックマーク済み' : '☆ ブックマーク'">{{ $isBookmarked ? '★ ブックマーク済み' : '☆ ブックマーク' }}</span>
    </button>
</div>
