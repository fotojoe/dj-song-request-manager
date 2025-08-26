<?php
/**
 * Class: DJ_SRM_OBS
 * Doel: Live overlays genereren voor gebruik in OBS of op schermen.
 * Waarom: Maakt een event visueel aantrekkelijk en interactief.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DJ_SRM_OBS {
    /**
     * Constructor: registreer shortcodes en admin submenu.
     */
    public function __construct() {
        // Shortcodes voor verschillende overlays
        add_shortcode( 'dj_obs_nowplaying', [ $this, 'render_nowplaying' ] );
        add_shortcode( 'dj_obs_poll', [ $this, 'render_poll' ] );
        add_shortcode( 'dj_obs_awards', [ $this, 'render_awards' ] );
        add_shortcode( 'dj_obs_qr', [ $this, 'render_qr' ] );
        // Admin instellingenpagina
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
    }

    /**
     * Registreer OBS submenu.
     */
    public function register_admin_menu() {
        add_submenu_page(
            'dj-srm-dashboard',
            __( 'OBS Instellingen', 'dj-srm' ),
            __( 'OBS', 'dj-srm' ),
            'manage_options',
            'dj-srm-obs',
            [ $this, 'admin_page' ]
        );
    }

    /**
     * Content van de adminpagina voor OBS instellingen.
     */
    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'OBS Instellingen', 'dj-srm' ) . '</h1>';
        echo '<p>' . esc_html__( 'Configureer hier de kleuren, fonts en animaties voor de overlays.', 'dj-srm' ) . '</p>';
        echo '</div>';
    }

    /**
     * Shortcode [dj_obs_nowplaying] callback.
     * Toont het huidig gedraaide nummer op basis van requests met status "gedraaid".
     */
    public function render_nowplaying() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/obs-nowplaying.php';
        return ob_get_clean();
    }

    /**
     * Shortcode [dj_obs_poll] callback.
     * Toont pollresultaten voor gebruik in OBS.
     */
    public function render_poll() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/obs-poll.php';
        return ob_get_clean();
    }

    /**
     * Shortcode [dj_obs_awards] callback.
     * Toont awards in een eenvoudig formaat voor OBS.
     */
    public function render_awards() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/obs-awards.php';
        return ob_get_clean();
    }

    /**
     * Shortcode [dj_obs_qr] callback.
     * Toont een QR-code naar een meegegeven link.
     * @param array $atts Attributen zoals link (pagina).
     */
    public function render_qr( $atts ) {
        $atts = shortcode_atts( [ 'link' => '/event-manager/' ], $atts );
        ob_start();
        $url = home_url( $atts['link'] );
        echo '<div class="dj-obs-qr">';
        echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . esc_attr( $url ) . '" alt="QR">';
        echo '</div>';
        return ob_get_clean();
    }
}

new DJ_SRM_OBS();

/*
 * === Einde class-obs.php ===
 * Uitbreidingsmogelijkheden:
 * - Realtime updates en animaties.
 * - Grafische weergaves (bar/pie charts) van polls.
 * - Websocket koppeling met OBS.
 */