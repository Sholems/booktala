<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans rich text written in the admin editors (blog posts, CMS pages) before
 * it reaches the public site. Formatting survives; scripts, event handlers,
 * iframes and javascript: links do not, so a compromised or careless admin
 * account cannot inject code into every visitor's browser.
 */
class SafeHtml
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                // The editors and seeded pages style content with classes and inline styles.
                ->allowAttribute('class', '*')
                ->allowAttribute('style', '*')
                ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->allowMediaSchemes(['https', 'http'])
                ->allowRelativeMedias()
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->withMaxInputLength(500000)
        );
    }
}
