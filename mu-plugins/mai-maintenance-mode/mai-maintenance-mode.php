<?php
/**
 * MU Plugin: MAI Maintenance Mode
 * Description: Вкл/выкл режим обслуживания с выбором фона, логотипа, центральным заголовком и подписью. Автогенерация wp-content/maintenance/index.php.
 * Author: DeVolaris / medicalassistanceintl.com
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) { exit; }

define('MAI_MM_VERSION', '1.0.0');
define('MAI_MM_OPTION',  'mai_mm_settings');
define('MAI_MM_SLUG',    'mai-maintenance-mode');
define('MAI_MM_DIR',     WP_CONTENT_DIR . '/maintenance');
define('MAI_MM_FILE',    MAI_MM_DIR . '/index.php');

/** -------- Helpers -------- */

function mai_mm_defaults(): array {
    return [
        'enabled'           => 0,
        'title'             => 'Maintenance mode is on',
        'subtitle'          => 'Сайт буде доступний найближчим часом. Дякуємо за ваше терпіння!',
        'bg_id'             => 0,   // attachment ID
        'logo_id'           => 0,   // attachment ID
        'retry_after'       => 3600,
        'bypass_logged_in'  => 1,
        'whitelist_ips'     => '',  // CSV or newline
    ];
}

function mai_mm_get_settings(): array {
    $opts = get_option(MAI_MM_OPTION, []);
    return wp_parse_args($opts, mai_mm_defaults());
}

function mai_mm_is_whitelisted_ip(string $ip, string $list): bool {
    if (!$list) return false;
    $parts = preg_split('/[\s,]+/', $list);
    $parts = array_filter(array_map('trim', (array)$parts));
    return in_array($ip, $parts, true);
}

function mai_mm_current_host(): string {
    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    return $host ?: get_bloginfo('name');
}

/** -------- Front (intercept) -------- */

add_action('template_redirect', function () {
    $s = mai_mm_get_settings();

    if (empty($s['enabled'])) return;

    // Bypasses
    if (defined('WP_CLI') && WP_CLI) return;
    if (is_admin()) return;
    if (is_user_logged_in() && !empty($s['bypass_logged_in']) && current_user_can('manage_options')) return;
    if (wp_doing_ajax() || wp_doing_cron()) return;

    global $pagenow;
    if (in_array($pagenow, ['wp-login.php', 'wp-register.php'], true)) return;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip && mai_mm_is_whitelisted_ip($ip, (string)$s['whitelist_ips'])) return;

    // Build data
    $bg_url   = $s['bg_id']   ? wp_get_attachment_image_url((int)$s['bg_id'], 'full')   : '';
    $logo_url = $s['logo_id'] ? wp_get_attachment_image_url((int)$s['logo_id'], 'medium') : '';
    $title    = $s['title']   ?: mai_mm_defaults()['title'];
    $subtitle = $s['subtitle']?: mai_mm_defaults()['subtitle'];
    $host     = esc_html(mai_mm_current_host());
    $year     = (string) current_time('Y');

    // Headers
    status_header(503);
    header('Retry-After: ' . (int)$s['retry_after']);
    header('X-Robots-Tag: noindex, nofollow');
    nocache_headers();

    // Simple, self-contained HTML (no external CSS/JS)
    $bg_css = $bg_url ? "background-image: url('".esc_url($bg_url)."');" : "background: #111;";
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html($title); ?></title>
<style>
  :root{--overlay:rgba(0,0,0,.45);--txt:#fff;--muted:rgba(255,255,255,.85)}
  *{box-sizing:border-box}
  html,body{height:100%}
  body{
    margin:0;color:var(--txt);
    <?php echo $bg_css; ?>
    background-size:cover;background-position:center center;background-attachment:fixed;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,"Helvetica Neue",Arial,"Apple Color Emoji","Segoe UI Emoji";
  }
  .overlay{position:fixed;inset:0;background:var(--overlay)}
  .wrap{position:relative;z-index:2;display:flex;flex-direction:column;min-height:100vh}
  header,footer{text-align:center;padding:18px 16px;font-weight:600;letter-spacing:.3px}
  header{font-size:18px;text-shadow:0 1px 2px rgba(0,0,0,.35)}
  .center{
    flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;
    text-align:center;padding:24px 18px;gap:18px
  }
  .badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(0,0,0,.35);font-size:13px}
  .badge .lock{display:inline-block;width:9px;height:12px;border:2px solid #fff;border-radius:2px;position:relative}
  .badge .lock:before{content:"";position:absolute;left:50%;transform:translateX(-50%);top:-8px;width:12px;height:9px;border:2px solid #fff;border-bottom:none;border-radius:10px 10px 0 0}
  h1{margin:0;font-size:clamp(24px,4vw,40px);line-height:1.2;text-shadow:0 2px 8px rgba(0,0,0,.45)}
  p{margin:0;font-size:clamp(14px,2.2vw,18px);color:var(--muted);max-width:min(720px,90vw)}
  .logo{max-width:min(220px,60vw);height:auto;display:block;filter:drop-shadow(0 2px 8px rgba(0,0,0,.35))}
  footer{opacity:.9;font-size:13px}
</style>
</head>
<body>
<div class="overlay" aria-hidden="true"></div>
<div class="wrap">
  <header><?php echo $host; ?></header>
  <div class="center" role="main">
    <?php if ($logo_url): ?>
      <img class="logo" src="<?php echo esc_url($logo_url); ?>" alt="Logo">
    <?php endif; ?>
    <div class="badge" aria-hidden="true"><span class="lock"></span><span>Maintenance</span></div>
    <h1><?php echo esc_html($title); ?></h1>
    <p><?php echo esc_html($subtitle); ?></p>
  </div>
  <footer>© <?php echo $host . ' ' . esc_html($year); ?></footer>
</div>
</body>
</html>
<?php
    exit;
});

/** -------- Admin page -------- */

add_action('admin_menu', function () {
    add_options_page(
        'Maintenance Mode',
        'Maintenance Mode',
        'manage_options',
        MAI_MM_SLUG,
        'mai_mm_render_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting(MAI_MM_SLUG, MAI_MM_OPTION, [
        'sanitize_callback' => 'mai_mm_sanitize',
    ]);
});

// Media scripts for uploader
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'settings_page_' . MAI_MM_SLUG) return;
    wp_enqueue_media();
    wp_add_inline_script('jquery', <<<JS
(function($){
    function bindPicker(btnSel, inputSel, previewSel){
        $(document).on('click', btnSel, function(e){
            e.preventDefault();
            var frame = wp.media({ title: 'Выберите изображение', multiple: false, library:{type:'image'} });
            frame.on('select', function(){
                var a = frame.state().get('selection').first().toJSON();
                $(inputSel).val(a.id);
                $(previewSel).attr('src', a.url).show();
            });
            frame.open();
        });
    }
    bindPicker('#mai-mm-pick-bg','#mai-mm-bg','#mai-mm-bg-preview');
    bindPicker('#mai-mm-pick-logo','#mai-mm-logo','#mai-mm-logo-preview');
})(jQuery);
JS
    );
});

/** -------- Settings page renderer -------- */

function mai_mm_render_settings_page() {
    if (!current_user_can('manage_options')) return;
    $s = mai_mm_get_settings();
    $bg  = $s['bg_id']   ? wp_get_attachment_image_url((int)$s['bg_id'], 'large')   : '';
    $logo= $s['logo_id'] ? wp_get_attachment_image_url((int)$s['logo_id'], 'medium') : '';
    ?>
    <div class="wrap">
        <h1>Maintenance Mode</h1>
        <form method="post" action="options.php">
            <?php settings_fields(MAI_MM_SLUG); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Статус</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr(MAI_MM_OPTION); ?>[enabled]" value="1" <?php checked($s['enabled'], 1); ?>>
                            Включить режим обслуживания
                        </label>
                        <p class="description">Незалогиненные пользователи увидят страницу 503 с указанным фоном/логотипом.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Заголовок по центру</th>
                    <td>
                        <input type="text" class="regular-text" name="<?php echo esc_attr(MAI_MM_OPTION); ?>[title]" value="<?php echo esc_attr($s['title']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Доп. текст (под заголовком)</th>
                    <td>
                        <textarea class="large-text" rows="3" name="<?php echo esc_attr(MAI_MM_OPTION); ?>[subtitle]"><?php echo esc_textarea($s['subtitle']); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Фоновое изображение</th>
                    <td>
                        <input type="hidden" id="mai-mm-bg" name="<?php echo esc_attr(MAI_MM_OPTION); ?>[bg_id]" value="<?php echo (int)$s['bg_id']; ?>">
                        <button type="button" class="button" id="mai-mm-pick-bg">Выбрать / Загрузить</button>
                        <br><br>
                        <img id="mai-mm-bg-preview" src="<?php echo esc_url($bg); ?>" style="max-width:420px;height:auto;<?php echo $bg ? '' : 'display:none'; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Логотип</th>
                    <td>
                        <input type="hidden" id="mai-mm-logo" name="<?php echo esc_attr(MAI_MM_OPTION); ?>[logo_id]" value="<?php echo (int)$s['logo_id']; ?>">
                        <button type="button" class="button" id="mai-mm-pick-logo">Выбрать / Загрузить</button>
                        <br><br>
                        <img id="mai-mm-logo-preview" src="<?php echo esc_url($logo); ?>" style="max-width:220px;height:auto;<?php echo $logo ? '' : 'display:none'; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Retry-After (сек.)</th>
                    <td>
                        <input type="number" min="60" step="60" name="<?php echo esc_attr(MAI_MM_OPTION); ?>[retry_after]" value="<?php echo (int)$s['retry_after']; ?>">
                        <p class="description">Заголовок HTTP Retry-After для поисковиков и клиентов.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Обход для админов</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr(MAI_MM_OPTION); ?>[bypass_logged_in]" value="1" <?php checked($s['bypass_logged_in'], 1); ?>>
                            Не показывать страницу администраторам (manage_options)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Whitelist IP</th>
                    <td>
                        <textarea class="large-text code" rows="3" name="<?php echo esc_attr(MAI_MM_OPTION); ?>[whitelist_ips]" placeholder="1.2.3.4, 5.6.7.8 или с новой строки"><?php echo esc_textarea($s['whitelist_ips']); ?></textarea>
                        <p class="description">Этим IP страница не будет показываться.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Сохранить настройки'); ?>
        </form>
        <hr>
        <p><strong>Статическая заглушка:</strong> плагин генерирует файл <code>wp-content/maintenance/index.php</code>. Можно отдать напрямую веб-сервером как fallback.</p>
    </div>
    <?php
}

/** -------- Sanitize & static writer -------- */

function mai_mm_sanitize($input) {
    $def = mai_mm_defaults();
    $out = [];
    $out['enabled']          = empty($input['enabled']) ? 0 : 1;
    $out['title']            = sanitize_text_field($input['title'] ?? $def['title']);
    $out['subtitle']         = sanitize_textarea_field($input['subtitle'] ?? $def['subtitle']);
    $out['bg_id']            = isset($input['bg_id'])   ? (int)$input['bg_id']   : 0;
    $out['logo_id']          = isset($input['logo_id']) ? (int)$input['logo_id'] : 0;
    $out['retry_after']      = max(60, (int)($input['retry_after'] ?? $def['retry_after']));
    $out['bypass_logged_in'] = empty($input['bypass_logged_in']) ? 0 : 1;
    $out['whitelist_ips']    = trim((string)($input['whitelist_ips'] ?? ''));

    // После сохранения — обновим статическую страницу
    add_action('updated_option', function ($opt_name, $old, $new) {
        if ($opt_name === MAI_MM_OPTION) {
            try { mai_mm_write_static($new); } catch (\Throwable $e) { /* ignore */ }
        }
    }, 10, 3);

    return $out;
}

/**
 * Генерирует wp-content/maintenance/index.php со статичным HTML
 */
function mai_mm_write_static(array $s = null): void {
    if (null === $s) { $s = mai_mm_get_settings(); }
    if (!function_exists('wp_mkdir_p')) require_once ABSPATH . 'wp-admin/includes/file.php';
    wp_mkdir_p(MAI_MM_DIR);

    $bg   = $s['bg_id']   ? wp_get_attachment_image_url((int)$s['bg_id'], 'full')   : '';
    $logo = $s['logo_id'] ? wp_get_attachment_image_url((int)$s['logo_id'], 'medium') : '';
    $title    = esc_html($s['title'] ?: mai_mm_defaults()['title']);
    $subtitle = esc_html($s['subtitle'] ?: mai_mm_defaults()['subtitle']);
    $host     = esc_html(mai_mm_current_host());
    $year     = esc_html(current_time('Y'));
    $bg_css   = $bg ? "background-image:url('".esc_url($bg)."');" : "background:#111;";

    $php = <<<PHP
<?php
http_response_code(503);
header('Retry-After: '.intval({$s['retry_after']}));
header('X-Robots-Tag: noindex, nofollow');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title}</title>
<style>
  :root{--overlay:rgba(0,0,0,.45);--txt:#fff;--muted:rgba(255,255,255,.85)}
  *{box-sizing:border-box}html,body{height:100%}
  body{margin:0;color:var(--txt);{$bg_css}background-size:cover;background-position:center;background-attachment:fixed;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,"Helvetica Neue",Arial}
  .overlay{position:fixed;inset:0;background:var(--overlay)}
  .wrap{position:relative;z-index:2;display:flex;flex-direction:column;min-height:100vh}
  header,footer{text-align:center;padding:18px 16px;font-weight:600;letter-spacing:.3px}
  header{font-size:18px;text-shadow:0 1px 2px rgba(0,0,0,.35)}
  .center{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:24px 18px;gap:18px}
  .badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(0,0,0,.35);font-size:13px}
  .badge .lock{display:inline-block;width:9px;height:12px;border:2px solid #fff;border-radius:2px;position:relative}
  .badge .lock:before{content:"";position:absolute;left:50%;transform:translateX(-50%);top:-8px;width:12px;height:9px;border:2px solid #fff;border-bottom:none;border-radius:10px 10px 0 0}
  h1{margin:0;font-size:clamp(24px,4vw,40px);line-height:1.2;text-shadow:0 2px 8px rgba(0,0,0,.45)}
  p{margin:0;font-size:clamp(14px,2.2vw,18px);color:var(--muted);max-width:min(720px,90vw)}
  .logo{max-width:min(220px,60vw);height:auto;display:block;filter:drop-shadow(0 2px 8px rgba(0,0,0,.35))}
  footer{opacity:.9;font-size:13px}
</style>
</head>
<body>
<div class="overlay" aria-hidden="true"></div>
<div class="wrap">
  <header>{$host}</header>
  <div class="center" role="main">
PHP;

    if ($logo) {
        $php .= "\n    <img class=\"logo\" src=\"".esc_url($logo)."\" alt=\"Logo\">\n";
    }

    $php .= <<<HTML
    <div class="badge" aria-hidden="true"><span class="lock"></span><span>Maintenance</span></div>
    <h1>{$title}</h1>
    <p>{$subtitle}</p>
  </div>
  <footer>© {$host} {$year}</footer>
</div>
</body>
</html>
HTML;

    // Ensure index.php exists with our content
    file_put_contents(MAI_MM_FILE, $php);

    // Create silent index.php inside maintenance dir (anti-listing) if not already this file
    $index_silencer = "<?php // Silence is golden.\n";
    if (!file_exists(MAI_MM_DIR . '/index-silence.php')) {
        file_put_contents(MAI_MM_DIR . '/index-silence.php', $index_silencer);
    }
}

/** -------- Generate static on first load (if missing) -------- */
add_action('init', function () {
    if (!file_exists(MAI_MM_FILE)) {
        try { mai_mm_write_static(); } catch (\Throwable $e) { /* ignore */ }
    }
});

/** -------- Admin bar quick toggle -------- */
add_action('admin_bar_menu', function($wp_admin_bar){
    if (!current_user_can('manage_options')) return;
    $s = mai_mm_get_settings();
    $state = $s['enabled'] ? 'ON' : 'OFF';
    $color = $s['enabled'] ? '#0abf53' : '#d63638';
    $wp_admin_bar->add_node([
        'id'    => 'mai-mm-toggle',
        'title' => '<span class="ab-item" style="font-weight:700;color:'.$color.'">Maintenance: '.$state.'</span>',
        'href'  => admin_url('options-general.php?page='.MAI_MM_SLUG),
    ]);
}, 100);

/* ---- End of file ---- */
