<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TEXT = [
        'terms' => [
            'match' => 'Products, Services',
            'en' => 'Some content on the platform is sponsored, including banners, featured companies and push notifications. Sponsored content is labelled and paid for by the advertiser, who is responsible for its accuracy. It is not a recommendation by SunXFinder and does not affect ratings or verification.',
            'ar' => 'بعض المحتوى في المنصة ممول، بما في ذلك البنرات والشركات المميزة والإشعارات. يُميَّز المحتوى الممول بعلامة واضحة ويدفع المعلن مقابله، وهو المسؤول عن دقته. ولا يُعد توصية من SunXFinder ولا يؤثر في التقييمات أو التوثيق.',
        ],
        'privacy' => [
            'match' => 'Your Choices',
            'en' => 'We may send sponsored notifications from SunXFinder or solar companies. You can turn these off in the app without turning off updates about your requests and projects. Sponsored notifications are sent through SunXFinder, and your contact details are not given to companies you have not dealt with. We count how often ads are viewed and tapped, and share only totals with advertisers.',
            'ar' => 'قد نرسل إشعارات ممولة من SunXFinder أو من شركات الطاقة الشمسية. يمكنك إيقافها من التطبيق دون إيقاف التحديثات الخاصة بطلباتك ومشاريعك. تُرسل الإشعارات الممولة عبر SunXFinder، ولا نعطي بيانات التواصل الخاصة بك للشركات التي لم تتعامل معها. كما نحسب عدد مرات مشاهدة الإعلانات والضغط عليها، ولا نشارك مع المعلنين سوى الإجماليات.',
        ],
    ];

    /** Adds the sponsored content wording to saved pages, keeping any edits made in admin. */
    public function up(): void
    {
        if (!Schema::hasTable('tbl_legal_sections')) {
            return;
        }

        foreach (self::TEXT as $page => $text) {
            $section = DB::table('tbl_legal_sections')
                ->where('page', $page)
                ->where('title_en', 'like', '%' . $text['match'] . '%')
                ->whereNull('deleted_at')
                ->first();

            // Already there when the section was first filled from the updated built-in text.
            if (!$section || str_contains((string) $section->body_en, $text['en'])) {
                continue;
            }

            DB::table('tbl_legal_sections')->where('id', $section->id)->update([
                'body_en' => $section->body_en . '<p>' . $text['en'] . '</p>',
                'body_ar' => $section->body_ar . '<p>' . $text['ar'] . '</p>',
                'updated_at' => now(),
            ]);

            DB::table('tbl_legal_sections')
                ->where('page', $page)
                ->where('type', 'header')
                ->whereNull('deleted_at')
                ->update(['last_updated' => '2026-11-01']);
        }
    }

    public function down(): void
    {
        // Text is left in place: admins may have edited it since.
    }
};
