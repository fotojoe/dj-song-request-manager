<?php
/**
 * Plugin Name: DJ Song Request Manager
 * Description: Beheer events, verzoeknummers, polls, awards, offertes, gastenboek, afterparty, OBS en meer voor feesten en festivals.
 * Version:     6.0.0
 * Author:      DJ’s Oostboys
 * Text Domain: dj-srm
 * Domain Path: /languages
 * Update URI:  https://github.com/fotojoe/dj-song-request-manager
 * Requires at least: 6.1
 * Requires PHP: 7.2
 */

if ( ! defined('ABSPATH') ) exit;

// ============================================================================
// CONSTANTS
// ============================================================================
define('DJ_SRM_VERSION',    '6.0.0');
define('DJ_SRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DJ_SRM_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!defined('DJ_SRM_GITHUB_REPO'))  define('DJ_SRM_GITHUB_REPO', 'fotojoe/dj-song-request-manager');
if (!defined('DJ_SRM_GITHUB_TOKEN')) define('DJ_SRM_GITHUB_TOKEN', '');

// ============================================================================
// HELPER FUNCTIES
// ============================================================================
if (!function_exists('dj_srm_asset_url')) {
    function dj_srm_asset_url($primary_rel_path, $fallback_rel_path) {
        $primary  = DJ_SRM_PLUGIN_DIR . ltrim($primary_rel_path,  '/');
        $fallback = DJ_SRM_PLUGIN_DIR . ltrim($fallback_rel_path, '/');
        if (file_exists($primary))  return DJ_SRM_PLUGIN_URL . ltrim($primary_rel_path,  '/');
        if (file_exists($fallback)) return DJ_SRM_PLUGIN_URL . ltrim($fallback_rel_path, '/');
        return DJ_SRM_PLUGIN_URL . ltrim($primary_rel_path, '/');
    }
}

// ============================================================================
// ACTIVATION / DEACTIVATION
// ============================================================================
register_activation_hook(__FILE__, 'dj_srm_activate');
register_deactivation_hook(__FILE__, 'dj_srm_deactivate');

if (!function_exists('dj_srm_activate')) {
    function dj_srm_activate() {
        // Settings
        $settings_file = DJ_SRM_PLUGIN_DIR . 'includes/class-settings.php';
        if (file_exists($settings_file)) {
            require_once $settings_file;
            if (class_exists('DJ_SRM_Settings')) {
                DJ_SRM_Settings::install();
            }
        }

        // Database tabellen
        $db_file = DJ_SRM_PLUGIN_DIR . 'includes/db-install.php';
        if (file_exists($db_file)) {
            require_once $db_file;
            if (function_exists('dj_srm_create_tables')) {
                dj_srm_create_tables();
            }
        }

        // Pagina's
        $pages_file = DJ_SRM_PLUGIN_DIR . 'includes/pages-install.php';
        if (file_exists($pages_file)) {
            require_once $pages_file;
            if (function_exists('dj_srm_create_pages')) {
                dj_srm_create_pages();
            }
        }

        flush_rewrite_rules();
    }
}

if (!function_exists('dj_srm_deactivate')) {
    function dj_srm_deactivate() {
        flush_rewrite_rules();
    }
}

// ============================================================================
// INITIALISATIE
// ============================================================================
add_action('init', 'dj_srm_load_textdomain');
if (!function_exists('dj_srm_load_textdomain')) {
    function dj_srm_load_textdomain() {
        load_plugin_textdomain('dj-srm', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

add_action('plugins_loaded', 'dj_srm_init');
if (!function_exists('dj_srm_init')) {
    function dj_srm_init() {
        $functions_file = DJ_SRM_PLUGIN_DIR . 'includes/functions.php';
        if (file_exists($functions_file)) {
            require_once $functions_file;
        }

        $modules = [
            'class-events.php',
            'class-requests.php',
            'class-polls.php',
            'class-awards.php',
            'class-offers.php',
            'class-guestbook.php',
            'class-afterparty.php',
            'class-obs.php',
            'class-settings.php',
            'class-security.php',
        ];
        foreach ($modules as $file) {
            $path = DJ_SRM_PLUGIN_DIR . 'includes/' . $file;
            if (file_exists($path)) require_once $path;
        }
    }
}

// ============================================================================
// FRONTEND ASSETS
// ============================================================================
add_action('wp_enqueue_scripts', 'dj_srm_enqueue_frontend_assets');
if (!function_exists('dj_srm_enqueue_frontend_assets')) {
    function dj_srm_enqueue_frontend_assets() {
        $css_url = dj_srm_asset_url('public/css/frontend.css', 'styles.css');
        $js_url  = dj_srm_asset_url('public/js/frontend.js',   'scripts.js');

        wp_enqueue_style('dj-srm-frontend', $css_url, [], DJ_SRM_VERSION);
        wp_enqueue_script('dj-srm-frontend', $js_url, ['jquery'], DJ_SRM_VERSION, true);

        if (function_exists('dj_srm_print_theme_vars')) {
            dj_srm_print_theme_vars('dj-srm-frontend');
        }

        wp_localize_script('dj-srm-frontend', 'dj_srm_ajax', [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dj_srm_nonce'),
        ]);
    }
}

// ============================================================================
// ADMIN ASSETS
// ============================================================================
add_action('admin_enqueue_scripts', 'dj_srm_enqueue_admin_assets');
if (!function_exists('dj_srm_enqueue_admin_assets')) {
    function dj_srm_enqueue_admin_assets($hook) {
        if (strpos($hook, 'dj-srm') === false) return;

        $admin_css = dj_srm_asset_url('admin/css/admin.css', 'admin.css');
        $admin_js  = dj_srm_asset_url('admin/js/admin.js',   'admin.js');
        $offers_js = dj_srm_asset_url('public/js/offers.js', 'offers.js');

        wp_enqueue_style('dj-srm-admin', $admin_css, [], DJ_SRM_VERSION);
        wp_enqueue_script('dj-srm-admin', $admin_js, ['jquery'], DJ_SRM_VERSION, true);
        wp_enqueue_script('dj-srm-offers', $offers_js, ['jquery'], DJ_SRM_VERSION, true);

        if (function_exists('dj_srm_print_theme_vars')) {
            dj_srm_print_theme_vars('dj-srm-admin');
        }

        $ajax = [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dj_srm_nonce'),
        ];
        wp_localize_script('dj-srm-admin',  'dj_srm_ajax', $ajax);
        wp_localize_script('dj-srm-offers', 'dj_srm_ajax', $ajax);
    }
}

// ============================================================================
// ADMIN MENU + DASHBOARD
// ============================================================================
if (!function_exists('dj_srm_register_menu')) {
    add_action('admin_menu', 'dj_srm_register_menu');
    function dj_srm_register_menu() {
        add_menu_page(
            __('DJ SRM', 'dj-srm'),
            __('DJ SRM', 'dj-srm'),
            'manage_options',
            'dj-srm-dashboard',
            'dj_srm_dashboard_page',
            'dashicons-format-audio',
            6
        );
    }
}

if (!function_exists('dj_srm_dashboard_page')) {
    function dj_srm_dashboard_page() {
        $file = DJ_SRM_PLUGIN_DIR . 'admin/dashboard.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>DJ SRM</h1><p>Dashboardbestand niet gevonden.</p></div>';
        }
    }
}

// ============================================================================
// THEME VARIABELEN (kleuren/lettertypes) + HEADER / FOOTER
// ============================================================================
if (!function_exists('dj_srm_print_theme_vars')) {
   if (!function_exists('dj_srm_print_theme_vars')) {
    function dj_srm_print_theme_vars($handle) {
        if (!class_exists('DJ_SRM_Settings')) return;

        $S = DJ_SRM_Settings::instance();
        $C = function($k,$d='') use ($S){ return $S->get($k,$d); };

        // === Kleuren ===
        $primary   = $C('ui.color.primary',   '#AEB92D');
        $secondary = $C('ui.color.secondary', '#2C3E50');
        $accent    = $C('ui.color.accent',    '#FF5299');
        $surface   = $C('ui.color.surface',   '#BDC3C7');
        $text      = $C('ui.color.text',      '#4F4F4F');
        $link      = $C('ui.color.link',      $primary);

        // === Fonts & layout ===
        $bodyFont  = trim($C('ui.font.body','Roboto')) ?: 'system-ui';
        $headFont  = trim($C('ui.font.headings','Poppins')) ?: 'inherit';
        $radius    = (int)$C('ui.radius', 12);
        $maxw      = $C('ui.container.max','1100px');

        // === Logo (optioneel uit WP Media) ===
        $logo_id   = (int)$C('ui.logo_id', 0);
        $logo_url  = $logo_id ? wp_get_attachment_url($logo_id) : '';

        // === Header & Footer HTML ===
        $header_html = $C('ui.header.html','');
        $footer_html = $C('ui.footer.html','');

        // Variabelen in teksten vervangen
        $repl = [
            '{site_name}' => get_bloginfo('name'),
            '{org_email}' => $C('org.email',''),
            '{org_phone}' => $C('org.phone',''),
            '{org_address}' => $C('org.address',''),
            '[logo]'      => $logo_url ? '<img src="'.esc_url($logo_url).'" alt="Logo" class="dj-srm-logo" />' : '',
        ];
        $header_html = strtr($header_html, $repl);
        $footer_html = strtr($footer_html, $repl);

        // === CSS output ===
        $css = "
:root{
  --dj-primary: {$primary};
  --dj-secondary: {$secondary};
  --dj-accent: {$accent};
  --dj-surface: {$surface};
  --dj-text: {$text};
  --dj-link: {$link};
  --dj-radius: {$radius}px;
  --dj-container-max: {$maxw};
  --dj-font-body: '{$bodyFont}', system-ui, sans-serif;
  --dj-font-head: '{$headFont}', var(--dj-font-body);
}
.dj-srm-header, .dj-srm-footer {
  padding: 10px;
  text-align:center;
}
.dj-srm-header img.dj-srm-logo {
  max-height:40px;
  vertical-align:middle;
  margin-right:10px;
}
";

        wp_add_inline_style($handle, $css);

        // === Extra: header & footer direct toevoegen in frontend ===
        add_action('wp_footer', function() use ($footer_html){
            if ($footer_html) echo '<div class="dj-srm-footer">'.$footer_html.'</div>';
        });
        add_action('wp_head', function() use ($header_html){
            if ($header_html) echo '<div class="dj-srm-header">'.$header_html.'</div>';
        });
    }
}

}

if (!function_exists('dj_srm_render_header')) {
    function dj_srm_render_header($title = '') {
        echo '<header class="dj-srm-header" style="background:var(--dj-primary);padding:15px;border-radius:var(--dj-radius);color:#fff;">';
        echo '<h1 style="margin:0;font-family:var(--dj-font-head);">' . esc_html($title) . '</h1>';
        echo '<nav><a href="https://djsoostboys.nl" style="color:#fff;margin-right:10px;">Website</a>';
        echo '<a href="https://www.facebook.com/djsoostboys" style="color:#fff;">Facebook</a></nav>';
        echo '</header>';
    }
}

if (!function_exists('dj_srm_render_footer')) {
    function dj_srm_render_footer() {
        echo '<footer class="dj-srm-footer" style="margin-top:20px;padding:10px;background:var(--dj-secondary);color:#fff;text-align:center;border-radius:var(--dj-radius);">';
        echo '&copy; ' . date('Y') . ' DJ’s Oostboys – Powered by DJ Song Request Manager';
        echo '</footer>';
    }
}

// ============================================================================
// GITHUB UPDATER
// ============================================================================
if (is_admin() && DJ_SRM_GITHUB_REPO !== 'OWNER/REPO') :
class DJ_SRM_GitHub_Updater {
    const API_RELEASE_LATEST = 'https://api.github.com/repos/%s/releases/latest';
    const TRANSIENT          = 'dj_srm_github_release';

    public static function plugin_data(){
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $file = __FILE__;
        $data = get_plugin_data($file, false, false);
        return [
            'file'     => $file,
            'basename' => plugin_basename($file),
            'slug'     => dirname(plugin_basename($file)),
            'version'  => $data['Version'] ?? '0.0.0',
            'name'     => $data['Name'] ?? 'DJ Song Request Manager',
        ];
    }

    protected static function api_get($url){
        $headers = [
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'dj-srm-updater',
        ];
        if (DJ_SRM_GITHUB_TOKEN) {
            $headers['Authorization'] = 'token '.DJ_SRM_GITHUB_TOKEN;
        }
        $res = wp_remote_get($url, ['timeout'=>15, 'headers'=>$headers]);
        if (is_wp_error($res)) return null;
        if (wp_remote_retrieve_response_code($res) !== 200) return null;
        $json = json_decode(wp_remote_retrieve_body($res), true);
        return (json_last_error()===JSON_ERROR_NONE) ? $json : null;
    }

    public static function get_latest_release(){
        $cached = get_site_transient(self::TRANSIENT);
        if (is_array($cached)) return $cached;
        $release = self::api_get(sprintf(self::API_RELEASE_LATEST, DJ_SRM_GITHUB_REPO));
        if (!$release) {
            set_site_transient(self::TRANSIENT, ['no_release'=>true], 6 * HOUR_IN_SECONDS);
            return null;
        }
        set_site_transient(self::TRANSIENT, $release, 6 * HOUR_IN_SECONDS);
        return $release;
    }

    public static function inject_update($transient){
        if (empty($transient->checked)) return $transient;
        $pd   = self::plugin_data();
        $base = $pd['basename'];
        $curr = $pd['version'];

        $rel = self::get_latest_release();
        if (!$rel || empty($rel['tag_name'])) return $transient;

        $tag = ltrim($rel['tag_name'], 'vV');
        if (version_compare($tag, $curr, '<=')) return $transient;

        $package = !empty($rel['zipball_url'])
            ? $rel['zipball_url']
            : 'https://github.com/' . DJ_SRM_GITHUB_REPO . '/archive/refs/tags/' . $rel['tag_name'] . '.zip';

        $update = (object)[
            'slug'        => $pd['slug'],
            'plugin'      => $base,
            'new_version' => $tag,
            'url'         => $rel['html_url'] ?? ('https://github.com/' . DJ_SRM_GITHUB_REPO),
            'package'     => $package,
            'tested'      => get_bloginfo('version'),
            'requires'    => '6.0',
        ];
        $transient->response[$base] = $update;
        return $transient;
    }

    public static function plugins_api($res, $action, $args){
        $pd = self::plugin_data();
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== $pd['slug']) return $res;

        $rel = self::get_latest_release();
        $ver = $rel['tag_name'] ?? $pd['version'];

        return (object)[
            'name'          => $pd['name'],
            'slug'          => $pd['slug'],
            'version'       => ltrim($ver,'vV'),
            'author'        => 'DJ’s Oostboys',
            'homepage'      => 'https://github.com/' . DJ_SRM_GITHUB_REPO,
            'sections'      => [
                'description' => !empty($rel['body']) ? wp_kses_post(nl2br($rel['body'])) : 'DJ Song Request Manager – GitHub powered updates.',
                'changelog'   => !empty($rel['body']) ? wp_kses_post(nl2br($rel['body'])) : 'Zie GitHub Releases.',
            ],
            'download_link' => $rel['zipball_url'] ?? '',
            'requires'      => '6.0',
            'tested'        => get_bloginfo('version'),
        ];
    }

    public static function clear_cache(){
        delete_site_transient(self::TRANSIENT);
    }
}
add_filter('pre_set_site_transient_update_plugins', ['DJ_SRM_GitHub_Updater','inject_update']);
add_filter('plugins_api', ['DJ_SRM_GitHub_Updater','plugins_api'], 10, 3);
add_action('wp_update_plugins', ['DJ_SRM_GitHub_Updater','clear_cache']);
endif;

// ============================================================================
// EINDE
// ============================================================================
