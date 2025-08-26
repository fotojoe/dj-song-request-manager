<?php
/**
 * Class: DJ_SRM_Awards
 * Doel: Awards uitreiken tijdens events en tonen aan gasten.
 * Waarom: Awards geven een speelse en persoonlijke twist aan events.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DJ_SRM_Awards {
    /**
     * Constructor: registreert shortcodes en AJAX handlers.
     */
    public function __construct() {
        // Shortcode voor awards-lijst
        add_shortcode( 'dj_awards', [ $this, 'render_awards' ] );
        // Optionele AJAX handler om awards toe te voegen
        add_action( 'wp_ajax_dj_srm_add_award', [ $this, 'handle_new_award' ] );

        // Admin menu voor awards beheer
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
    }

    /**
     * Shortcode [dj_awards] callback.
     * Laadt het frontend template voor awards.
     */
    public function render_awards() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/awards.php';
        return ob_get_clean();
    }

    /**
     * AJAX handler om een nieuwe award op te slaan.
     * Verwacht event_id, name, receiver en type in $_POST.
     */
    public function handle_new_award() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_awards';
        $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
        $name     = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $receiver = isset( $_POST['receiver'] ) ? sanitize_text_field( $_POST['receiver'] ) : '';
        $type     = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
        $wpdb->insert( $table, [
            'event_id' => $event_id,
            'name'     => $name,
            'receiver' => $receiver,
            'type'     => $type
        ] );
        // Log de actie
        if ( function_exists( 'dj_srm_log_action' ) ) {
            dj_srm_log_action( 'award_created', $receiver );
        }
        wp_send_json_success( [ 'message' => __( 'Award toegevoegd!', 'dj-srm' ) ] );
    }

    /**
     * Registreert het submenu voor awards in het dashboard.
     */
    public function register_admin_menu() {
        add_submenu_page(
            'dj-srm-dashboard',
            __( 'Awards', 'dj-srm' ),
            __( 'Awards', 'dj-srm' ),
            'manage_options',
            'dj-srm-awards',
            [ $this, 'admin_page' ]
        );
    }

    /**
     * Admin pagina voor awards. Maakt aan, toont en verwijdert awards.
     */
    public function admin_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_awards';
        // Verwerking: verwijderen
        if ( isset( $_GET['action'], $_GET['award_id'] ) && $_GET['action'] === 'delete' ) {
            $id = intval( $_GET['award_id'] );
            $wpdb->delete( $table, [ 'id' => $id ] );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Award verwijderd.', 'dj-srm' ) . '</p></div>';
        }
        // Verwerking: nieuwe award
        if ( isset( $_POST['dj_srm_add_award'] ) ) {
            check_admin_referer( 'dj_srm_add_award' );
            $data = [
                'event_id' => intval( $_POST['event_id'] ),
                'name'     => sanitize_text_field( $_POST['name'] ),
                'receiver' => sanitize_text_field( $_POST['receiver'] ),
                'type'     => sanitize_text_field( $_POST['type'] ),
            ];
            $wpdb->insert( $table, $data );
            // Log de award
            if ( function_exists( 'dj_srm_log_action' ) ) {
                dj_srm_log_action( 'award_created', $data['receiver'] );
            }
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Award aangemaakt.', 'dj-srm' ) . '</p></div>';
        }
        // Ophalen awards inclusief eventnaam
        $awards = $wpdb->get_results( "SELECT a.*, e.name AS event_name FROM {$table} AS a LEFT JOIN {$wpdb->prefix}dj_srm_events AS e ON a.event_id = e.id ORDER BY a.created_at DESC" );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Awards beheren', 'dj-srm' ) . '</h1>';
        // Lijst
        echo '<table class="widefat"><thead><tr>';
        echo '<th>' . esc_html__( 'ID', 'dj-srm' ) . '</th>';
        echo '<th>' . esc_html__( 'Event', 'dj-srm' ) . '</th>';
        echo '<th>' . esc_html__( 'Naam award', 'dj-srm' ) . '</th>';
        echo '<th>' . esc_html__( 'Ontvanger', 'dj-srm' ) . '</th>';
        echo '<th>' . esc_html__( 'Type', 'dj-srm' ) . '</th>';
        echo '<th>' . esc_html__( 'Acties', 'dj-srm' ) . '</th>';
        echo '</tr></thead><tbody>';
        if ( $awards ) {
            foreach ( $awards as $award ) {
                $delete_url = esc_url( add_query_arg( [ 'action' => 'delete', 'award_id' => $award->id ] ) );
                echo '<tr>';
                echo '<td>' . esc_html( $award->id ) . '</td>';
                echo '<td>' . esc_html( $award->event_name ? $award->event_name : $award->event_id ) . '</td>';
                echo '<td>' . esc_html( $award->name ) . '</td>';
                echo '<td>' . esc_html( $award->receiver ) . '</td>';
                echo '<td>' . esc_html( $award->type ) . '</td>';
                echo '<td><a href="' . $delete_url . '" onclick="return confirm(\'' . esc_js( __( 'Weet je zeker dat je deze award wilt verwijderen?', 'dj-srm' ) ) . '\');">' . esc_html__( 'Verwijderen', 'dj-srm' ) . '</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">' . esc_html__( 'Geen awards gevonden.', 'dj-srm' ) . '</td></tr>';
        }
        echo '</tbody></table>';
        // Formulier
        echo '<h2>' . esc_html__( 'Nieuwe award', 'dj-srm' ) . '</h2>';
        echo '<form method="post">';
        wp_nonce_field( 'dj_srm_add_award' );
        echo '<table class="form-table">';
        // Event drop-down
        global $wpdb;
        $events_table = $wpdb->prefix . 'dj_srm_events';
        $events = $wpdb->get_results( "SELECT id, name FROM {$events_table} ORDER BY start_time ASC" );
        echo '<tr><th><label for="event_id">' . esc_html__( 'Event', 'dj-srm' ) . '</label></th><td><select name="event_id">';
        echo '<option value="0">' . esc_html__( 'Selecteer een event', 'dj-srm' ) . '</option>';
        if ( $events ) {
            foreach ( $events as $ev ) {
                echo '<option value="' . esc_attr( $ev->id ) . '">' . esc_html( $ev->name ) . '</option>';
            }
        }
        echo '</select></td></tr>';
        echo '<tr><th><label for="name">' . esc_html__( 'Naam award', 'dj-srm' ) . '</label></th><td><input type="text" name="name" class="regular-text" required placeholder="Bijv. Beste danser"></td></tr>';
        echo '<tr><th><label for="receiver">' . esc_html__( 'Ontvanger', 'dj-srm' ) . '</label></th><td><input type="text" name="receiver" class="regular-text" required placeholder="Naam van de ontvanger"></td></tr>';
        echo '<tr><th><label for="type">' . esc_html__( 'Type', 'dj-srm' ) . '</label></th><td><input type="text" name="type" class="regular-text" placeholder="Bijv. Publiek, Jury"></td></tr>';
        echo '</table>';
        submit_button( __( 'Award aanmaken', 'dj-srm' ), 'primary', 'dj_srm_add_award' );
        echo '</form>';
        echo '</div>';
    }
}

new DJ_SRM_Awards();

/*
 * === Einde class-awards.php ===
 * Mogelijke uitbreidingen:
 * - CRUD-functionaliteit voor awards in admin.
 * - E-mailnotificatie naar winnaar sturen.
 * - OBS overlay voor live aankondiging.
 */