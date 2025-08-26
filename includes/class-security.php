<?php
/**
 * Class: DJ_SRM_Security
 * Doel: Beheer van veiligheidsinstellingen zoals logs en IP-blocklist.
 * Waarom: Biedt admins inzicht in acties en stelt hen in staat misbruik te voorkomen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DJ_SRM_Security {
    public function __construct() {
        // Registreer admin menu items
        add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );
    }

    /**
     * Registreert twee submenu's: Logs en IP Blocklist
     */
    public function register_admin_menus() {
        add_submenu_page(
            'dj-srm-dashboard',
            __( 'Logs', 'dj-srm' ),
            __( 'Logs', 'dj-srm' ),
            'manage_options',
            'dj-srm-logs',
            [ $this, 'logs_page' ]
        );
        add_submenu_page(
            'dj-srm-dashboard',
            __( 'IP Blocklist', 'dj-srm' ),
            __( 'IP Blocklist', 'dj-srm' ),
            'manage_options',
            'dj-srm-ip-blocklist',
            [ $this, 'ip_blocklist_page' ]
        );
    }

    /**
     * Toont een overzicht van de logs met actie, gebruiker, IP en datum.
     */
    public function logs_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_logs';
        $logs  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 200" );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Actie Log', 'dj-srm' ) . '</h1>';
        if ( $logs ) {
            echo '<table class="widefat"><thead><tr>';
            echo '<th>' . esc_html__( 'Datum', 'dj-srm' ) . '</th>';
            echo '<th>' . esc_html__( 'Actie', 'dj-srm' ) . '</th>';
            echo '<th>' . esc_html__( 'Gebruiker', 'dj-srm' ) . '</th>';
            echo '<th>' . esc_html__( 'IP-adres', 'dj-srm' ) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ( $logs as $log ) {
                echo '<tr>';
                echo '<td>' . esc_html( $log->created_at ) . '</td>';
                echo '<td>' . esc_html( $log->action ) . '</td>';
                echo '<td>' . esc_html( $log->user ) . '</td>';
                echo '<td>' . esc_html( $log->ip ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__( 'Geen logitems gevonden.', 'dj-srm' ) . '</p>';
        }
        echo '</div>';
    }

    /**
     * Beheerpagina voor de IP-blocklist. Toont geblokkeerde IP-adressen en formuliertje om IP toe te voegen.
     */
    public function ip_blocklist_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'dj_srm_ip_blocklist';
        // Verwerken van formulier voor toevoegen
        if ( isset( $_POST['new_block_ip'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'dj_srm_add_block_ip' ) ) {
            $new_ip = sanitize_text_field( $_POST['new_block_ip'] );
            $reason = sanitize_text_field( $_POST['reason'] );
            if ( filter_var( $new_ip, FILTER_VALIDATE_IP ) ) {
                $wpdb->insert( $table, [ 'ip' => $new_ip, 'reason' => $reason ] );
                echo '<div class="notice notice-success"><p>' . esc_html__( 'IP-adres toegevoegd aan blocklist.', 'dj-srm' ) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Ongeldig IP-adres.', 'dj-srm' ) . '</p></div>';
            }
        }
        // Verwijderen van IP-adres
        if ( isset( $_GET['delete_ip'] ) ) {
            $delete_id = intval( $_GET['delete_ip'] );
            $wpdb->delete( $table, [ 'id' => $delete_id ] );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'IP-adres verwijderd van blocklist.', 'dj-srm' ) . '</p></div>';
        }
        // Ophalen van blocklist
        $ips = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'IP Blocklist', 'dj-srm' ) . '</h1>';
        // Formulier voor toevoegen
        echo '<form method="post" action="">';
        wp_nonce_field( 'dj_srm_add_block_ip' );
        echo '<table class="form-table"><tr><th><label for="new_block_ip">' . esc_html__( 'IP-adres', 'dj-srm' ) . '</label></th>';
        echo '<td><input type="text" name="new_block_ip" id="new_block_ip" class="regular-text">';
        echo '<p class="description">' . esc_html__( 'Voeg een IP-adres toe dat geen verzoeken of stemmen mag doen.', 'dj-srm' ) . '</p></td></tr>';
        echo '<tr><th><label for="reason">' . esc_html__( 'Reden', 'dj-srm' ) . '</label></th>';
        echo '<td><input type="text" name="reason" id="reason" class="regular-text"></td></tr></table>';
        submit_button( __( 'Toevoegen', 'dj-srm' ) );
        echo '</form>';
        // Tabel met geblokkeerde IP's
        if ( $ips ) {
            echo '<h2>' . esc_html__( 'Geblokkeerde IP-adressen', 'dj-srm' ) . '</h2>';
            echo '<table class="widefat"><thead><tr>';
            echo '<th>' . esc_html__( 'IP-adres', 'dj-srm' ) . '</th>';
            echo '<th>' . esc_html__( 'Reden', 'dj-srm' ) . '</th>';
            echo '<th>' . esc_html__( 'Datum', 'dj-srm' ) . '</th>';
            echo '<th>' . esc_html__( 'Actie', 'dj-srm' ) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ( $ips as $row ) {
                echo '<tr>';
                echo '<td>' . esc_html( $row->ip ) . '</td>';
                echo '<td>' . esc_html( $row->reason ) . '</td>';
                echo '<td>' . esc_html( $row->created_at ) . '</td>';
                $delete_url = esc_url( add_query_arg( [ 'delete_ip' => $row->id ] ) );
                echo '<td><a href="' . $delete_url . '" class="button-link delete-ip">' . esc_html__( 'Verwijderen', 'dj-srm' ) . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__( 'Er staan geen IP-adressen op de blocklist.', 'dj-srm' ) . '</p>';
        }
        echo '</div>';
    }
}

// Instantieer de beveiligingsklasse
new DJ_SRM_Security();