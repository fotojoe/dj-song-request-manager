<?php
/**
 * Template: requests-list.php
 * Toont een tabel met alle verzoeknummers voor de DJ.
 * Wordt gebruikt in de shortcode [dj_requests].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_requests';
$requests = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at ASC" );

if ( $requests ) :
    echo '<table class="dj-requests"><thead><tr>';
    echo '<th>' . esc_html__( 'Gast', 'dj-srm' ) . '</th>';
    echo '<th>' . esc_html__( 'Artiest', 'dj-srm' ) . '</th>';
    echo '<th>' . esc_html__( 'Nummer', 'dj-srm' ) . '</th>';
    echo '<th>' . esc_html__( 'Bericht', 'dj-srm' ) . '</th>';
    echo '<th>' . esc_html__( 'Status', 'dj-srm' ) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ( $requests as $req ) {
        $status_class = 'status-' . $req->status;
        echo '<tr class="' . esc_attr( $status_class ) . '">';
        echo '<td>' . esc_html( $req->requester ) . '</td>';
        echo '<td>' . esc_html( $req->artist ) . '</td>';
        echo '<td>' . esc_html( $req->song ) . '</td>';
        echo '<td>' . esc_html( $req->message ) . '</td>';
        // Status met dropdown voor DJ om te wijzigen
        echo '<td>';
        echo '<select class="dj-request-status-select" data-id="' . esc_attr( $req->id ) . '">';
        $statuses = [ 'nieuw' => __( 'nieuw', 'dj-srm' ), 'goedgekeurd' => __( 'goedgekeurd', 'dj-srm' ), 'geweigerd' => __( 'geweigerd', 'dj-srm' ), 'gedraaid' => __( 'gedraaid', 'dj-srm' ) ];
        foreach ( $statuses as $key => $label ) {
            $selected = selected( $req->status, $key, false );
            echo '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
else :
    echo '<p>' . esc_html__( 'Geen verzoeken gevonden.', 'dj-srm' ) . '</p>';
endif;

// Einde template requests-list.php