<?php
/**
 * UA Transliteration (similar to rutranslit) — single-file PHP library
 * Version: 1.0.0
 * License: MIT
 *
 * Features
 * - Schemes: "passport" (KMU 2010-like), "seo" (ASCII for URLs), "custom" (pass your own map)
 * - Word-boundary rules for Є/Ї/Ю/Я, Й; "Zgh" rule; removes ь and apostrophes
 * - Helper: slugify(); WordPress integration (optional)
 *
 * Usage (plain PHP):
 *   require 'ua_translit.php';
 *   echo UATranslit::translit('Привіт, світе!', 'seo'); // pryvit-svite
 *
 * WordPress (drop into mu-plugins or plugins):
 *   // Add this at the bottom (already included): add_filter('sanitize_title', ...);
 */
class UATranslit
{
    /** @var array<string,string> base map for Ukrainian letters (position-independent) */
    private static array $base = [
        'А'=>'A','а'=>'a','Б'=>'B','б'=>'b','В'=>'V','в'=>'v','Г'=>'H','г'=>'h','Ґ'=>'G','ґ'=>'g',
        'Д'=>'D','д'=>'d','Е'=>'E','е'=>'e','Є'=>'Ie','є'=>'ie', // will be overridden by position rules
        'Ж'=>'Zh','ж'=>'zh','З'=>'Z','з'=>'z','И'=>'Y','и'=>'y', // "y" as in "my"
        'І'=>'I','і'=>'i','Ї'=>'I','ї'=>'i', // will be overridden by position rules
        'Й'=>'Y','й'=>'y', // may be adjusted in some schemes
        'К'=>'K','к'=>'k','Л'=>'L','л'=>'l','М'=>'M','м'=>'m','Н'=>'N','н'=>'n',
        'О'=>'O','о'=>'o','П'=>'P','п'=>'p','Р'=>'R','р'=>'r','С'=>'S','с'=>'s',
        'Т'=>'T','т'=>'t','У'=>'U','у'=>'u','Ф'=>'F','ф'=>'f','Х'=>'Kh','х'=>'kh',
        'Ц'=>'Ts','ц'=>'ts','Ч'=>'Ch','ч'=>'ch','Ш'=>'Sh','ш'=>'sh','Щ'=>'Shch','щ'=>'shch',
        'Ь'=>'','ь'=>'','Ю'=>'Iu','ю'=>'iu','Я'=>'Ia','я'=>'ia','’'=>'','ʼ'=>'','\''=>'',
        'Ы'=>'Y','ы'=>'y','Э'=>'E','э'=>'e', // rarely used in UA texts but mapped
        'Ъ'=>'','ъ'=>''
    ];

    /** Vowels and signs used for boundary rules */
    private static string $vowelsAndSigns = 'АаЕеЄєИиІіЇїОоУуЮюЯяЬь\'’ʼ';

    /**
     * Transliterate a string.
     *
     * @param string $text        Input text (UTF-8)
     * @param string $scheme      "passport" | "seo" | "custom"
     * @param array  $options     Options:
     *                            - 'lower' (bool) : force lowercase (default true for 'seo')
     *                            - 'upper' (bool) : force uppercase (default false)
     *                            - 'replace' (string): replacement for spaces in 'seo' (default '-')
     *                            - 'map' (array): custom map for scheme 'custom' (overrides base/rules)
     * @return string
     */
    public static function translit(string $text, string $scheme = 'seo', array $options = []): string
    {
        $scheme = strtolower($scheme);
        if ($scheme !== 'passport' && $scheme !== 'seo' && $scheme !== 'custom') {
            $scheme = 'seo';
        }

        $lowerDefault = $scheme === 'seo';
        $replace = $options['replace'] ?? '-';
        $forceLower = $options['lower'] ?? $lowerDefault;
        $forceUpper = $options['upper'] ?? false;

        // Custom map if provided
        $map = self::$base;
        if ($scheme === 'custom' && !empty($options['map']) && is_array($options['map'])) {
            $map = array_merge($map, $options['map']);
        }

        // Step 1: Handle "Zgh" rule for зг / Зг (before vowels -> Zgh/zgh)
        $text = preg_replace_callback('/Зг|зг/u', function ($m) {
            return $m[0] === 'Зг' ? 'Zgh' : 'zgh';
        }, $text);

        // Step 2: Split into characters and apply boundary rules for Є/Ї/Ю/Я, Й
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $result = '';
        $len = count($chars);

        for ($i = 0; $i < $len; $i++) {
            $ch = $chars[$i];

            // Apostrophes are removed
            if ($ch === '’' || $ch === 'ʼ' || $ch === '\'') {
                continue;
            }

            // Word-start or after vowel/sign?
            $prev = $i > 0 ? $chars[$i-1] : null;
            $isStart = ($i === 0) || ($prev !== null && mb_strpos(self::$vowelsAndSigns, $prev) !== false) || ($prev === '-' || $prev === '_');

            // Boundary-sensitive letters
            switch ($ch) {
                case 'Є': $out = $isStart ? 'Ye' : 'Ie'; break;
                case 'є': $out = $isStart ? 'ye' : 'ie'; break;
                case 'Ї': $out = $isStart ? 'Yi' : 'I';  break;
                case 'ї': $out = $isStart ? 'yi' : 'i';  break;
                case 'Ю': $out = $isStart ? 'Yu' : 'Iu'; break;
                case 'ю': $out = $isStart ? 'yu' : 'iu'; break;
                case 'Я': $out = $isStart ? 'Ya' : 'Ia'; break;
                case 'я': $out = $isStart ? 'ya' : 'ia'; break;
                case 'Й': $out = $isStart ? 'Y'  : 'i';  break; // Common practice: 'Y' at start, 'i' inside (e.g., Майя -> Maiia)
                case 'й': $out = $isStart ? 'y'  : 'i';  break;
                default:
                    $out = $map[$ch] ?? $ch;
            }

            $result .= $out;
        }

        // Step 3: Scheme-specific post-processing
        if ($scheme === 'passport') {
            // Keep mixed case but normalize some edge cases:
            // Remove soft sign (handled), apostrophes removed, hyphens preserved
            // Nothing special beyond base rules
        } elseif ($scheme === 'seo') {
            // ASCII only: lowercase, spaces to hyphens, strip non [a-z0-9-_]
            $result = self::toAscii($result);
            $result = preg_replace('/[ \t\pZ]+/u', $replace, $result); // spaces -> replace
            $result = preg_replace('/[^A-Za-z0-9\-_]+/', '', $result);
            $result = preg_replace('/' . preg_quote($replace, '/') . '+/', $replace, $result);
            $result = trim($result, $replace . '_');
            $forceLower = true;
        }

        if ($forceUpper) {
            $result = mb_strtoupper($result);
        } elseif ($forceLower) {
            $result = mb_strtolower($result);
        }

        return $result;
    }

    /** Create a URL-friendly slug from UA text. */
    public static function slugify(string $text, string $sep = '-'): string
    {
        return self::translit($text, 'seo', ['replace' => $sep, 'lower' => true]);
    }

    /** Keep ASCII only (strip diacritics if any slipped in). */
    private static function toAscii(string $s): string
    {
        // Basic translit for any stray non-ASCII (fallback)
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($s === false) { $s = ''; }
        return $s;
    }
}

// ---------- CLI usage ----------
// php ua_translit.php "Привіт, світе!" [scheme]
if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
    $in = $argv[1] ?? 'Привіт, світе! Україна — це Європа.';
    $scheme = $argv[2] ?? 'seo';
    fwrite(STDOUT, UATranslit::translit($in, $scheme) . PHP_EOL);
}

// ---------- WordPress integration (optional) ----------
if (function_exists('add_filter')) {
    /**
     * Use UA transliteration when WP creates slugs.
     * You can disable by removing this filter in your theme/plugin.
     */
    add_filter('sanitize_title', function ($title, $raw_title = '', $context = 'save') {
        return UATranslit::slugify($title);
    }, 10, 3);

    /**
     * Helper: provide wp_ua_translit() for general use.
     */
    if (!function_exists('wp_ua_translit')) {
        function wp_ua_translit(string $text, string $scheme = 'seo', array $options = []): string {
            return UATranslit::translit($text, $scheme, $options);
        }
    }
}
?>
