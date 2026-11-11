<?php

namespace Tests\Unit;

use App\Models\ProjectMilestone;
use Tests\TestCase;

/** Default stages are saved in English and translated when shown. No database. */
class ProjectMilestoneTest extends TestCase
{
    public function test_default_stage_is_translated(): void
    {
        $milestone = new ProjectMilestone([
            'title' => 'Equipment procurement',
            'description' => 'Order and receive solar panels, inverter, and batteries.',
        ]);

        $this->assertSame('توفير المعدات', $milestone->displayTitle('ar'));
        $this->assertSame('طلب واستلام الألواح الشمسية والمحول والبطاريات.', $milestone->displayDescription('ar'));
        $this->assertSame('Equipment procurement', $milestone->displayTitle('en'));
        $this->assertSame('توفير المعدات', $milestone->toArray()['ar_title']);
        // The stored English is untouched (the handover check reads it).
        $this->assertSame('Equipment procurement', $milestone->title);
    }

    public function test_custom_text_is_shown_as_written(): void
    {
        $custom = new ProjectMilestone(['title' => 'Roof repair', 'description' => 'Fix the roof first.']);
        $this->assertSame('Roof repair', $custom->displayTitle('ar'));
        $this->assertSame('Fix the roof first.', $custom->displayDescription('ar'));

        // A default title whose description the company changed keeps that description.
        $edited = new ProjectMilestone(['title' => 'System testing', 'description' => 'Test with the customer present.']);
        $this->assertSame('اختبار النظام', $edited->displayTitle('ar'));
        $this->assertSame('Test with the customer present.', $edited->displayDescription('ar'));
    }
}
