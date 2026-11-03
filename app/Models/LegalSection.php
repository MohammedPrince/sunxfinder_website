<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One block of a legal page (Admin → System → Privacy & Terms). */
class LegalSection extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tbl_legal_sections';

    public const PAGES = ['privacy' => 'Privacy Policy', 'terms' => 'Terms & Conditions'];

    /** Public route of each page. */
    public const ROUTES = ['privacy' => 'privacy-policy', 'terms' => 'terms'];

    protected $fillable = [
        'page', 'type', 'eyebrow_en', 'eyebrow_ar', 'title_en', 'title_ar', 'body_en', 'body_ar',
        'last_updated', 'sort_order', 'is_active', 'created_by',
    ];

    protected $casts = [
        'last_updated' => 'date',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /** Page blocks in display order: header, numbered sections, then contact boxes. */
    public function scopeForPage($query, string $page)
    {
        return $query->where('page', $page)
            ->orderByRaw("CASE type WHEN 'header' THEN 0 WHEN 'section' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function isHeader(): bool
    {
        return $this->type === 'header';
    }

    public function text(string $field, string $lang): ?string
    {
        return $this->getAttribute($field . '_' . ($lang === 'ar' ? 'ar' : 'en'));
    }
}
