<?php
/**
 * Class: DJ_SRM_Requests
 * Doel: Verzoeknummers ontvangen en tonen aan DJ.
 * Waarom: Maakt interactie mogelijk tussen gasten en DJ tijdens een event.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DJ_SRM_Requests {
    /**
     * Constructor. Registreert shortcodes en AJAX handlers.
     */
    public function __construct() {
        // Shortcode voor het gastenformulier
        add_shortcode( 'dj_request_form', [ $this, 'render_request_form' ] );
        // Shortcode voor de DJ-lijst van verzoeknummers
        add_shortcode( 'dj_requests', [ $this, 'render_requests_list' ] );
        // AJAX handlers voor het indienen van een verzoek
        add_action( 'wp_ajax_dj_srm_add_request', [ $this, 'handle_new_request' ] );
        add_action( 'wp_ajax_nopriv_dj_srm_add_request', [ $this, 'handle_new_request' ] );

        // AJAX handler voor het bijwerken van de status van een verzoek. Alleen ingelogde gebruikers (DJ of admin) hebben toegang.
        add_action( 'wp_ajax_dj_srm_update_request_status', [ $this, 'handle_update_status' ] );
    }

    /**
     * Shortcode [dj_request_form] callback.
     * Laadt het frontend formulier voor gasten om een verzoeknummer in te sturen.
     *
     * @return string Gebufferde HTML van het formulier.
     */
    public function render_request_form() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/request-form.php';
        return ob_get_clean();
    }

    /**
     * Shortcode [dj_requests] callback.
     * Laadt de lijst van alle verzoeken voor de DJ-portal.
     *
     * @return string Gebufferde HTML van de verzoeklijst.
     */
    public function render_requests_list() {
        ob_start();
        include DJ_SRM_PLUGIN_DIR . 'templates/requests-list.php';
        return ob_get_clean();
    }

    /**
     * AJAX handler voor het indienen van een nieuw verzoeknummer.
     * Valideert en sanitiseert invoer, slaat het verzoek op en stuurt een JSON respons terug.
     */
    public function handle_new_request() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_requests';

        // Sanitize input
        $event_id  = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
        $song      = isset( $_POST['song'] ) ? sanitize_text_field( $_POST['song'] ) : '';
        $artist    = isset( $_POST['artist'] ) ? sanitize_text_field( $_POST['artist'] ) : '';
        $requester = isset( $_POST['requester'] ) ? sanitize_text_field( $_POST['requester'] ) : '';
        $message   = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

        // Controleer of IP geblokkeerd is
        if ( function_exists( 'dj_srm_is_ip_blocked' ) && dj_srm_is_ip_blocked() ) {
            wp_send_json_error( [ 'message' => __( 'Je mag momenteel geen verzoeken indienen.', 'dj-srm' ) ] );
        }

        // Sla het verzoek op
        $wpdb->insert( $table, [
            'event_id'  => $event_id,
            'song'      => $song,
            'artist'    => $artist,
            'requester' => $requester,
            'message'   => $message,
            'status'    => 'nieuw'
        ] );

        // Log de actie
        if ( function_exists( 'dj_srm_log_action' ) ) {
            dj_srm_log_action( 'request_submitted', $requester );
        }

        // Verstuur e-mail naar gast indien e-mail aanwezig is
        $event_name = '';
        if ( $event_id ) {
            $event_table = $wpdb->prefix . 'dj_srm_events';
            $event_name  = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$event_table} WHERE id=%d", $event_id ) );
        }
        if ( ! empty( $_POST['email'] ) && is_email( $_POST['email'] ) ) {
            $to = sanitize_email( $_POST['email'] );
            $subject = sprintf( __( 'Bevestiging verzoek voor %s', 'dj-srm' ), $song );
            $body = '';
            if ( function_exists( 'dj_srm_get_email_template' ) ) {
                $body = dj_srm_get_email_template( 'mail/request-received.php', [
                    'requester' => $requester,
                    'song'      => $song,
                    'event_name'=> $event_name
                ] );
            }
            $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
            wp_mail( $to, $subject, $body, $headers );
        }

        wp_send_json_success( [ 'message' => __( 'Bedankt voor je verzoek!', 'dj-srm' ) ] );
    }

    /**
     * AJAX handler voor het wijzigen van de status van een verzoeknummer.
     * Wordt gebruikt in de DJ-portal om snel verzoeken goed te keuren, weigeren of markeren als gedraaid.
     */
    public function handle_update_status() {
        // Controleer rechten – alleen gebruikers met manage_options mogen dit uitvoeren
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Onvoldoende rechten.', 'dj-srm' ) ] );
        }
        global $wpdb;
        $table  = $wpdb->prefix . 'dj_srm_requests';
        $id     = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
        // Valideer statuswaarde
        $allowed_statuses = [ 'nieuw', 'goedgekeurd', 'geweigerd', 'gedraaid' ];
        if ( ! in_array( $status, $allowed_statuses, true ) ) {
            wp_send_json_error( [ 'message' => __( 'Ongeldige status.', 'dj-srm' ) ] );
        }
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => __( 'Ongeldig verzoek-ID.', 'dj-srm' ) ] );
        }
        // Update uitvoeren
        $updated = $wpdb->update( $table, [ 'status' => $status ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
        if ( false === $updated ) {
            wp_send_json_error( [ 'message' => __( 'Kon status niet updaten.', 'dj-srm' ) ] );
        }
        // Loggen van de actie
        if ( function_exists( 'dj_srm_log_action' ) ) {
            $current_user = wp_get_current_user();
            dj_srm_log_action( 'request_status_' . $status, $current_user ? $current_user->user_login : '' );
        }
        wp_send_json_success( [ 'message' => __( 'Verzoekstatus bijgewerkt.', 'dj-srm' ) ] );
    }
}

// Instantieer de class
new DJ_SRM_Requests();

/*
 * === Einde class-requests.php ===
 * Mogelijke uitbreidingen:
 * - Nonce en IP-rate limiting toevoegen in handle_new_request.
 * - CRUD-functionaliteit voor verzoeken in admin.
 * - Mailbevestiging versturen naar gast.
 */