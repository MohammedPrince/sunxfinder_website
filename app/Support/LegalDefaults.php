<?php

namespace App\Support;

use App\Helpers\Helper;
use App\Models\LegalSection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reads the built-in legal pages (resources/views/visitors) into tbl_legal_sections rows,
 * so the blade files stay the only copy of the default text.
 */
class LegalDefaults
{
    public const FILES = ['privacy' => 'privacy-policy', 'terms' => 'terms'];

    /** Saves the built-in text of a page. Returns the number of rows. */
    public static function seed(string $page): int
    {
        $rows = self::rows($page);
        LegalSection::insert($rows);

        return count($rows);
    }

    /** The built-in text in the same shape as MainRepository::getLegalPage(), without saving it. */
    public static function page(string $page): array
    {
        $blocks = collect(self::rows($page))->map(fn(array $row) => (new LegalSection())->forceFill($row));

        return [
            'header' => $blocks->firstWhere('type', 'header'),
            'sections' => $blocks->where('type', 'section')->values(),
            'contacts' => $blocks->where('type', 'contact')->values(),
        ];
    }

    public static function rows(string $page): array
    {
        $file = self::FILES[$page];
        $en = self::parse($file);
        $ar = self::parse($file . '-ar');

        if (count($en) !== count($ar)) {
            throw new RuntimeException("The English and Arabic {$file} pages have a different number of blocks.");
        }

        $now = now();
        $order = ['section' => 0, 'contact' => 0];
        $rows = [];

        foreach ($en as $index => $block) {
            $type = $block['type'];

            $rows[] = [
                'page' => $page,
                'type' => $type,
                'eyebrow_en' => $block['eyebrow'],
                'eyebrow_ar' => $ar[$index]['eyebrow'],
                'title_en' => $block['title'],
                'title_ar' => $ar[$index]['title'],
                'body_en' => $block['body'],
                'body_ar' => $ar[$index]['body'],
                'last_updated' => $block['last_updated'],
                'sort_order' => $type === 'header' ? 0 : ++$order[$type],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    private static function parse(string $file): array
    {
        $source = file_get_contents(resource_path("views/visitors/{$file}.blade.php"));

        if (!preg_match("/@section\('content'\)(.*)@endsection/s", $source, $match)) {
            throw new RuntimeException("No content section in {$file}.");
        }

        $html = preg_replace('/\{\{--.*?--\}\}|<style\b.*?<\/style>/s', '', $match[1]);
        // Route links become site paths, which work on every domain. Both names match their path.
        $html = preg_replace("/\{\{\s*route\('([a-z-]+)',\s*\['lang'\s*=>\s*'(en|ar)'\]\)\s*\}\}/", '/$1?lang=$2', $html);

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $hasClass = fn(string $class) => "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";

        $hero = $xpath->query("//header[{$hasClass('legal-hero')}]")->item(0);
        if (!$hero) {
            throw new RuntimeException("No page header in {$file}.");
        }

        $text = fn(string $query, \DOMNode $context) => self::collapse((string) $xpath->evaluate("string({$query})", $context));
        $updated = $text("span[{$hasClass('legal-updated')}]", $hero);

        $blocks = [[
            'type' => 'header',
            'eyebrow' => $text("span[{$hasClass('legal-eyebrow')}]", $hero),
            'title' => $text('h1', $hero),
            'body' => $text('p', $hero),
            // Only the English date can be parsed; both languages share this field.
            'last_updated' => Str::startsWith($updated, 'Last updated')
                ? Carbon::parse(Str::after($updated, ':'))->toDateString()
                : null,
        ]];

        foreach ($xpath->query("//section[{$hasClass('legal-card')} or {$hasClass('legal-contact')}]") as $section) {
            /** @var \DOMElement $section */
            $heading = $xpath->query('h2', $section)->item(0);
            $title = preg_replace('/^\d+\.\s*/', '', self::collapse((string) $heading?->textContent));
            $heading?->parentNode->removeChild($heading);

            $body = '';
            foreach ($section->childNodes as $child) {
                $body .= $dom->saveHTML($child);
            }

            $blocks[] = [
                'type' => str_contains($section->getAttribute('class'), 'legal-contact') ? 'contact' : 'section',
                'eyebrow' => null,
                'title' => $title,
                'body' => self::tidy(Helper::sanitizeRichText($body, true)),
                'last_updated' => null,
            ];
        }

        return $blocks;
    }

    private static function collapse(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /** One clean line of HTML, without the source file's indentation around block tags. */
    private static function tidy(string $html): string
    {
        $html = self::collapse($html);

        return preg_replace('#\s*(</?(?:p|ul|ol|li|h3|h4|blockquote)>)\s*#i', '$1', $html);
    }
}
