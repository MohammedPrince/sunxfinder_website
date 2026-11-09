<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic notifications: a schedule sends its messages one by one, in order, on the
 * chosen weekdays (Sat..Fri), every N days, at a set local time. Admin manages it from
 * Notifications → Automatic. Sent by the "notifications:send-scheduled" command.
 *
 * No foreign keys on purpose: they behave differently on the MyISAM dev database and the
 * live one. Plain indexes only.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tbl_notification_schedules')) {
            Schema::create('tbl_notification_schedules', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->boolean('is_active')->default(false);
                // Carbon dayOfWeek numbers: 0 = Sunday ... 6 = Saturday.
                $table->json('days');
                $table->unsignedSmallInteger('every_days')->default(1);
                $table->string('send_time', 5)->default('10:00');      // HH:MM, local time
                $table->string('timezone', 64)->default('Africa/Khartoum');
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->string('notification_type', 30)->default('general');
                $table->string('audience_type', 20)->default('all');   // all | city
                $table->string('audience_city', 120)->nullable();
                $table->string('after_last', 10)->default('stop');     // stop | repeat
                // Progress: the last message sent and when.
                $table->unsignedBigInteger('last_message_id')->nullable();
                $table->integer('last_message_order')->nullable();
                $table->date('last_sent_on')->nullable();
                $table->timestamp('last_sent_at')->nullable();
                // One attempt per day, even when it failed (no recipients, push error).
                $table->date('last_run_on')->nullable();
                $table->string('last_error', 255)->nullable();
                $table->unsignedInteger('sent_count')->default(0);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('tbl_notification_schedule_messages')) {
            Schema::create('tbl_notification_schedule_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('schedule_id');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('title_en', 180);
                $table->string('title_ar', 180)->nullable();
                $table->text('message_en');
                $table->text('message_ar')->nullable();
                $table->unsignedInteger('sent_count')->default(0);
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamps();

                $table->index(['schedule_id', 'sort_order']);
            });
        }

        Schema::table('tbl_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_notifications', 'notification_schedule_id')) {
                $table->unsignedBigInteger('notification_schedule_id')->nullable()->index();
            }
        });

        $this->seedTesterSchedule();
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_notifications', 'notification_schedule_id')) {
            Schema::table('tbl_notifications', function (Blueprint $table) {
                $table->dropIndex(['notification_schedule_id']);
                $table->dropColumn('notification_schedule_id');
            });
        }

        Schema::dropIfExists('tbl_notification_schedule_messages');
        Schema::dropIfExists('tbl_notification_schedules');
    }

    /** The 14 tester messages, every 2 days, switched off until the admin turns it on. */
    private function seedTesterSchedule(): void
    {
        if (DB::table('tbl_notification_schedules')->exists()) {
            return;
        }

        $now = now();

        $scheduleId = DB::table('tbl_notification_schedules')->insertGetId([
            'name' => 'App testers: 14 messages',
            'is_active' => false,
            'days' => json_encode([6, 0, 1, 2, 3, 4, 5]),
            'every_days' => 2,
            'send_time' => '10:00',
            'timezone' => 'Africa/Khartoum',
            'notification_type' => 'general',
            'audience_type' => 'all',
            'after_last' => 'stop',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $messages = [
            ['☀️ Welcome to SunXFinder!', 'Discover a smarter way to find solar companies! Explore SunXFinder today and help us make your solar journey easier. Share your feedback with us!',
             '☀️ مرحباً بك في SunXFinder!', 'اكتشف الطريقة الأذكى للوصول إلى شركات الطاقة الشمسية! جرّب SunXFinder اليوم وساعدنا في تحسين تجربتك. نرحب بملاحظاتك!'],
            ['🔍 Discover Solar Companies', 'Looking for solar solutions? Explore companies on SunXFinder and discover how easy it is to find solar providers.',
             '🔍 اكتشف شركات الطاقة الشمسية', 'تبحث عن حلول الطاقة الشمسية؟ استكشف الشركات على SunXFinder واكتشف سهولة الوصول إلى مزودي خدمات الطاقة الشمسية.'],
            ['🏢 Explore Company Profiles', 'Take a closer look at solar companies! Browse company profiles and tell us what information you would like to see.',
             '🏢 استكشف ملفات الشركات', 'ألقِ نظرة أقرب على شركات الطاقة الشمسية! تصفح ملفات الشركات وأخبرنا بالمعلومات التي ترغب في رؤيتها.'],
            ['☀️ Find Your Solar Solution', 'Explore solar solutions on SunXFinder. Is it easy to find what you need? Try it today and share your experience.',
             '☀️ اكتشف الحل الشمسي المناسب', 'استكشف حلول الطاقة الشمسية عبر SunXFinder. هل تجد ما تحتاج إليه بسهولة؟ جرّب التطبيق اليوم وشاركنا تجربتك.'],
            ['⚖️ Compare Solar Options', 'Smart solar decisions start with comparison! Explore your options on SunXFinder and let us know how we can improve.',
             '⚖️ قارن قبل أن تختار', 'القرارات الذكية تبدأ بالمقارنة! استكشف خياراتك عبر SunXFinder وأخبرنا كيف يمكننا تحسين تجربتك.'],
            ['🧭 Help Us Improve Search', 'Try searching and navigating through SunXFinder today. Found something confusing or difficult? Send us your feedback!',
             '🧭 ساعدنا في تحسين البحث', 'جرّب البحث والتنقل داخل SunXFinder اليوم. هل واجهت شيئاً غير واضح أو صعب؟ أرسل لنا ملاحظاتك!'],
            ['🎉 One Week with SunXFinder', 'Thank you for testing SunXFinder! Explore your favorite features again and tell us what works well and what needs improvement.',
             '🎉 أسبوع مع SunXFinder!', 'شكراً لاختبار SunXFinder! جرّب ميزاتك المفضلة مرة أخرى وأخبرنا بما يعمل جيداً وما يحتاج إلى تحسين.'],
            ['💬 Your Feedback Makes a Difference', 'Every suggestion helps! Use SunXFinder today and share your ideas, feedback, or any issues you encounter.',
             '💬 ملاحظاتك تصنع الفرق', 'كل اقتراح يساعدنا! استخدم SunXFinder اليوم وشاركنا أفكارك وملاحظاتك أو أي مشكلة تواجهك.'],
            ['🚀 Explore More on SunXFinder', 'There is more to discover! Explore different sections of SunXFinder and help us ensure everything works smoothly.',
             '🚀 اكتشف المزيد في SunXFinder', 'هناك المزيد لاكتشافه! استكشف أقسام SunXFinder المختلفة وساعدنا في التأكد من أن كل شيء يعمل بسلاسة.'],
            ['👤 Check Your SunXFinder Account', 'Visit your profile and test your account features. Is everything clear and easy to use? Let us know!',
             '👤 تحقق من حسابك في SunXFinder', 'زر ملفك الشخصي واختبر ميزات حسابك. هل كل شيء واضح وسهل الاستخدام؟ أخبرنا!'],
            ['🌞 Help Us Make Solar Easier', 'SunXFinder is built to simplify finding solar companies. Test the app today and tell us how we can make it even better.',
             '🌞 ساعدنا في تسهيل الوصول للطاقة الشمسية', 'صُمم SunXFinder لتسهيل الوصول إلى شركات الطاقة الشمسية. اختبر التطبيق اليوم وأخبرنا كيف نجعله أفضل.'],
            ['🛠️ Help Us Fix & Improve', 'Found a bug, loading issue, or unexpected behavior? Test SunXFinder today and report anything that needs attention.',
             '🛠️ ساعدنا في إصلاح وتحسين التطبيق', 'هل وجدت خطأ أو مشكلة في التحميل أو سلوكاً غير متوقع؟ اختبر SunXFinder اليوم وأبلغنا بأي مشكلة.'],
            ['🌟 Almost at the Finish Line', 'Thank you for being part of SunXFinder testing! Give the app another try and share your final suggestions before testing ends.',
             '🌟 اقتربنا من النهاية!', 'شكراً لكونك جزءاً من اختبار SunXFinder! جرّب التطبيق مرة أخرى وشاركنا اقتراحاتك الأخيرة قبل انتهاء الاختبار.'],
            ['🎉 Thank You for Testing SunXFinder', 'You made a difference! Thank you for helping us improve SunXFinder. Your feedback brings us closer to a better solar experience for everyone.',
             '🎉 شكراً لكم على اختبار SunXFinder', 'لقد صنعتم الفرق! شكراً لمساعدتنا في تحسين SunXFinder. ملاحظاتكم تقربنا من تجربة أفضل للطاقة الشمسية للجميع.'],
        ];

        $rows = [];

        foreach ($messages as $index => [$titleEn, $messageEn, $titleAr, $messageAr]) {
            $rows[] = [
                'schedule_id' => $scheduleId,
                'sort_order' => $index + 1,
                'is_active' => true,
                'title_en' => $titleEn,
                'title_ar' => $titleAr,
                'message_en' => $messageEn,
                'message_ar' => $messageAr,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('tbl_notification_schedule_messages')->insert($rows);
    }
};
