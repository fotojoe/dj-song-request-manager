<?php
/**
 * Class: DJ_SRM_Settings
 * Doel: Instellingen beheren (opslaan, ophalen, defaults).
 */

if ( ! defined('ABSPATH') ) exit;

class DJ_SRM_Settings {
    private static $instance = null;
    private $table;

    private function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'dj_srm_settings';
    }

    /**
     * Singleton instance
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Installatie: vult defaults
     */
    public static function install() {
        $S = self::instance();

        $defaults = [
            // Algemene gegevens
            'org.phone'   => '',
            'org.email'   => '',
            'org.address' => '',

            // UI Kleuren
            'ui.color.primary'   => '#AEB92D',
            'ui.color.secondary' => '#2C3E50',
            'ui.color.accent'    => '#FF5299',
            'ui.color.surface'   => '#BDC3C7',
            'ui.color.text'      => '#4F4F4F',
            'ui.color.link'      => '#AEB92D',

            // Fonts
            'ui.font.body'     => 'Roboto',
            'ui.font.headings' => 'Poppins',

            // Layout
            'ui.radius'        => '12',
            'ui.container.max' => '1100px',

            // Header/Footer HTML
            'ui.header.html' => '<div class="dj-srm-header">[logo] <strong>{site_name}</strong></div>',
            'ui.footer.html' => '<div class="dj-srm-footer">&copy; {site_name} - Contact: {org_email}</div>',

            // Logo ID (WordPress media ID)
            'ui.logo_id' => 0,

            // Socials
            'social.facebook'  => '',
            'social.instagram' => '',
            'social.tiktok'    => '',
        ];

        foreach ($defaults as $key => $value) {
            if ($S->get($key, null) === null) {
                $S->set($key, $value);
            }
        }
    }

    /**
     * Ophalen waarde
     */
    public function get($key, $default = '') {
        global $wpdb;
        $val = $wpdb->get_var($wpdb->prepare(
            "SELECT value FROM {$this->table} WHERE option_name = %s",
            $key
        ));
        return ($val !== null) ? maybe_unserialize($val) : $default;
    }

    /**
     * Opslaan waarde
     */
    public function set($key, $value) {
        global $wpdb;
        $wpdb->replace(
            $this->table,
            [
                'option_name' => $key,
                'value'       => maybe_serialize($value),
                'autoload'    => 1,
            ],
            ['%s','%s','%d']
        );
    }
}
