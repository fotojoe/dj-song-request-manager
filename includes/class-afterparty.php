<?php
/**
 * Class: DJ_SRM_Afterparty
 * Doel: Samenvatting van events na afloop maken en tonen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DJ_SRM_Afterparty {
    /**
     * Constructor: registreert shortcode en admin menu.
     */
    public function __construct() {
        // Shortcode voor recap
        add_shortcode( 'dj_afterparty', [ $this, 'render_afterparty' ] );
        // Admin submenu
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
    }

    /**
     * Shortcode callback voor [dj_afterparty]. Toont recap op frontend.
     */
    public function render_afterparty() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/afterparty.php';
        return ob_get_clean();
    }

    /**
     * Registreer Afterparty submenu in admin.
     */
    public function register_admin_menu() {
        add_submenu_page(
            'dj-srm-dashboard',
            __( 'Afterparty', 'dj-srm' ),
            __( 'Afterparty', 'dj-srm' ),
            'manage_options',
            'dj-srm-afterparty',
            [ $this, 'admin_page' ]
        );
    }

    /**
     * Content van de adminpagina voor het samenstellen van de recap.
     */
    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Afterparty Recap samenstellen', 'dj-srm' ) . '</h1>';
        echo '<p>' . esc_html__( 'Selecteer de gedraaide nummers, vul highlights in en maak een recap. Deze pagina kan in de toekomst uitgebreid worden met een builder.', 'dj-srm' ) . '</p>';
        echo '</div>';
    }
}

new DJ_SRM_Afterparty();

/*
 * === Einde class-afterparty.php ===
 * Mogelijke uitbreidingen:
 * - CRUD-functionaliteit en automatische generaties van recap.
 * - Integratie met polls, awards en guestbook voor volledige samenvatting.
 */