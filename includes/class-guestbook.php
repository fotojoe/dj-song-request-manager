<?php
/**
 * Class: DJ_SRM_Guestbook
 * Doel: Gastenboek voor events om felicitaties en berichten te verzamelen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DJ_SRM_Guestbook {
    /**
     * Constructor: registreert shortcode en AJAX handler.
     */
    public function __construct() {
        // Shortcode voor gastenboek
        add_shortcode( 'dj_guestbook', [ $this, 'render_guestbook' ] );
        // AJAX handler voor nieuw bericht
        add_action( 'wp_ajax_dj_srm_add_guestbook', [ $this, 'handle_new_entry' ] );
        add_action( 'wp_ajax_nopriv_dj_srm_add_guestbook', [ $this, 'handle_new_entry' ] );

        // Admin submenu voor gastenboek moderatie
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
    }

    /**
     * Shortcode [dj_guestbook] callback.
     * Toont het gastenboek (lijst + formulier).
     */
    public function render_guestbook() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/guestbook.php';
        return ob_get_clean();
    }

    /**
     * AJAX handler om een nieuw gastenboekbericht op te slaan.
     */
    public function handle_new_entry() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_guestbook';
        $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
        $name     = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $message  = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
        // Controleer of IP is geblokkeerd
        if ( function_exists( 'dj_srm_is_ip_blocked' ) && dj_srm_is_ip_blocked() ) {
            wp_send_json_error( [ 'message' => __( 'Je mag momenteel geen bericht achterlaten.', 'dj-srm' ) ] );
        }
        // Sla bericht op (standaard nog niet goedgekeurd)
        $wpdb->insert( $table, [
            'event_id' => $event_id,
            'name'     => $name,
            'message'  => $message,
            'approved' => 0
        ] );
        // Log de actie
        if ( function_exists( 'dj_srm_log_action' ) ) {
            dj_srm_log_action( 'guestbook_entry', $name );
        }

        // Stuur notificatie naar organisator (admin_email) zodat zij het bericht kunnen modereren
        $event_name     = '';
        $organizer_name = get_bloginfo( 'name' );
        if ( $event_id ) {
            $events_table = $wpdb->prefix . 'dj_srm_events';
            $event_name   = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$events_table} WHERE id=%d", $event_id ) );
        }
        $admin_email = get_option( 'admin_email' );
        if ( $admin_email && function_exists( 'dj_srm_get_email_template' ) ) {
            $subject = sprintf( __( 'Nieuw gastenboekbericht voor %s', 'dj-srm' ), $event_name );
            $body    = dj_srm_get_email_template( 'mail/guestbook-notification.php', [
                'organizer_name' => $organizer_name,
                'event_name'     => $event_name,
                'guest_name'     => $name,
                'guest_message'  => $message,
            ] );
            $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
            wp_mail( $admin_email, $subject, $body, $headers );
        }
        wp_send_json_success( [ 'message' => __( 'Bedankt voor je bericht!', 'dj-srm' ) ] );
    }

    /**
     * Registreer het submenu voor het gastenboek in het dashboard.
     */
    public function register_admin_menu() {
        add_submenu_page(
            'dj-srm-dashboard',
            __( 'Gastenboek', 'dj-srm' ),
            __( 'Gastenboek', 'dj-srm' ),
            'manage_options',
            'dj-srm-guestbook',
            [ $this, 'admin_page' ]
        );
    }

    /**
     * Adminpagina voor het modereren van het gastenboek. Gebruikt code uit admin/guestbook.php.
     */
    public function admin_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_guestbook';
        // Acties via GET: goedkeuren of verwijderen
        if ( isset( $_GET['approve'] ) ) {
            $id = intval( $_GET['approve'] );
            $wpdb->update( $table, [ 'approved' => 1 ], [ 'id' => $id ] );
            echo '<div class="updated notice"><p>' . esc_html__( 'Bericht goedgekeurd.', 'dj-srm' ) . '</p></div>';
        }
        if ( isset( $_GET['delete'] ) ) {
            $id = intval( $_GET['delete'] );
            $wpdb->delete( $table, [ 'id' => $id ] );
            echo '<div class="updated notice"><p>' . esc_html__( 'Bericht verwijderd.', 'dj-srm' ) . '</p></div>';
        }
        $entries = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Gastenboek beheren', 'dj-srm' ) . '</h1>';
        echo '<table class="widefat"><thead><tr>';
        echo '<th>' . esc_html__( 'Naam', 'dj-srm' ) . '</th>';
        echo '<th>' . esc_html__( 'Bericht', 'dj-srm' ) . '</th>';
        echo '<th>' . esc_html__( 'Status', 'dj-srm' ) . '</th>';
        echo '<th>' . esc_html__( 'Acties', 'dj-srm' ) . '</th>';
        echo '</tr></thead><tbody>';
        if ( $entries ) {
            foreach ( $entries as $entry ) {
                $status = $entry->approved ? __( 'Goedgekeurd', 'dj-srm' ) : __( 'In afwachting', 'dj-srm' );
                echo '<tr>';
                echo '<td>' . esc_html( $entry->name ) . '</td>';
                echo '<td>' . esc_html( $entry->message ) . '</td>';
                echo '<td>' . esc_html( $status ) . '</td>';
                echo '<td>';
                if ( ! $entry->approved ) {
                    echo '<a href="' . esc_url( add_query_arg( [ 'approve' => $entry->id ] ) ) . '">' . esc_html__( 'Goedkeuren', 'dj-srm' ) . '</a> | ';
                }
                echo '<a href="' . esc_url( add_query_arg( [ 'delete' => $entry->id ] ) ) . '">' . esc_html__( 'Verwijderen', 'dj-srm' ) . '</a>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4">' . esc_html__( 'Geen berichten gevonden.', 'dj-srm' ) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}

new DJ_SRM_Guestbook();

/*
 * === Einde class-guestbook.php ===
 * Mogelijke uitbreidingen:
 * - Moderatiepagina in admin (goedkeuren/verwijderen).
 * - IP-rate limiting en anti-spam.
 * - Notificatie naar DJ/Admin bij nieuw bericht.
 */