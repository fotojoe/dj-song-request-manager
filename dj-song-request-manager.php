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
define('DJ_SRM_PLUGIN_FILE', __FILE__); // ✅ toegevoegd
define('DJ_SRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DJ_SRM_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!defined('DJ_SRM_GITHUB_REPO'))  define('DJ_SRM_GITHUB_REPO', 'fotojoe/dj-song-request-manager');
if (!defined('DJ_SRM_GITHUB_TOKEN')) define('DJ_SRM_GITHUB_TOKEN', '');

// ============================================================================
// ACTIVATION / DEACTIVATION
// ============================================================================
register_activation_hook(__FILE__, 'dj_srm_activate');
register_deactivation_hook(__FILE__, 'dj_srm_deactivate');

function dj_srm_activate() {
    require_once DJ_SRM_PLUGIN_DIR . 'includes/db-install.php';
    if (function_exists('dj_srm_create_tables')) dj_srm_create_tables();

    require_once DJ_SRM_PLUGIN_DIR . 'includes/pages-install.php';
    if (function_exists('dj_srm_create_pages')) dj_srm_create_pages();

    flush_rewrite_rules();
}
function dj_srm_deactivate() {
    flush_rewrite_rules();
}

// ============================================================================
// INITIALISATIE
// ============================================================================
add_action('init', function() {
    load_plugin_textdomain('dj-srm', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('plugins_loaded', function() {
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
});

// ============================================================================
// FRONTEND ASSETS
// ============================================================================
add_action('wp_enqueue_scripts', function() {
    $css_url = DJ_SRM_PLUGIN_URL . 'public/css/frontend.css';
    $js_url  = DJ_SRM_PLUGIN_URL . 'public/js/frontend.js';
    wp_enqueue_style('dj-srm-frontend', $css_url, [], DJ_SRM_VERSION);
    wp_enqueue_script('dj-srm-frontend', $js_url, ['jquery'], DJ_SRM_VERSION, true);
    wp_localize_script('dj-srm-frontend', 'dj_srm_ajax', [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dj_srm_nonce'),
    ]);
});

// ============================================================================
// ADMIN ASSETS
// ============================================================================
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'dj-srm') === false) return;

    wp_enqueue_style('dj-srm-admin', DJ_SRM_PLUGIN_URL.'admin/css/admin.css', [], DJ_SRM_VERSION);
    wp_enqueue_script('dj-srm-admin', DJ_SRM_PLUGIN_URL.'admin/js/admin.js', ['jquery'], DJ_SRM_VERSION, true);

    wp_localize_script('dj-srm-admin', 'dj_srm_ajax', [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dj_srm_nonce'),
    ]);
});

// ============================================================================
// ADMIN MENU
// ============================================================================
add_action('admin_menu', function() {
    // Hoofdmenu
    add_menu_page(
        __('DJ SRM', 'dj-srm'),
        __('DJ SRM', 'dj-srm'),
        'manage_options',
        'dj-srm-dashboard',
        'dj_srm_dashboard_page',
        'dashicons-format-audio',
        6
    );

    // Submenu’s
    add_submenu_page(
        'dj-srm-dashboard',
        __('Dashboard', 'dj-srm'),
        __('Dashboard', 'dj-srm'),
        'manage_options',
        'dj-srm-dashboard',
        'dj_srm_dashboard_page'
    );

    add_submenu_page(
        'dj-srm-dashboard',
        __('Instellingen', 'dj-srm'),
        __('Instellingen', 'dj-srm'),
        'manage_options',
        'dj-srm-settings',
        'dj_srm_settings_page'
    );
});

// Dashboardpagina
function dj_srm_dashboard_page() {
    $file = DJ_SRM_PLUGIN_DIR . 'admin/dashboard.php';
    if (file_exists($file)) {
        include $file;
    } else {
        echo '<div class="wrap"><h1>DJ SRM</h1><p>Dashboardbestand niet gevonden.</p></div>';
    }
}

// Instellingenpagina
function dj_srm_settings_page() {
    $file = DJ_SRM_PLUGIN_DIR . 'admin/settings/general-settings.php';
    if (file_exists($file)) {
        include $file;
    } else {
        echo '<div class="wrap"><h1>Instellingen</h1><p>Instellingenbestand niet gevonden.</p></div>';
    }
}

// ============================================================================
// GITHUB UPDATER (ongewijzigd)
// ============================================================================
if (is_admin() && DJ_SRM_GITHUB_REPO !== 'OWNER/REPO') :
class DJ_SRM_GitHub_Updater {
    const API_RELEASE_LATEST = 'https://api.github.com/repos/%s/releases/latest';
    const TRANSIENT          = 'dj_srm_github_release';

    public static function plugin_data(){
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $file = DJ_SRM_PLUGIN_FILE;
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
