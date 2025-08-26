<?php
/**
 * Template: offers.php
 * Toont een lijst van offertes voor het huidige event.
 * Gebruikt in de shortcode [dj_offers].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_offers';
// Voor nu een vaste event_id. In de toekomst te vervangen door dynamische context.
$event_id = 1;
$offers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id = %d ORDER BY created_at DESC", $event_id ) );

if ( $offers ) :
    echo '<h2>' . esc_html__( 'Offertes', 'dj-srm' ) . '</h2>';
    echo '<table class="dj-offers"><thead><tr>';
    echo '<th>' . esc_html__( 'Klant', 'dj-srm' ) . '</th>';
    echo '<th>' . esc_html__( 'Totaal', 'dj-srm' ) . '</th>';
    echo '<th>' . esc_html__( 'Status', 'dj-srm' ) . '</th>';
    echo '<th>' . esc_html__( 'Acties', 'dj-srm' ) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ( $offers as $offer ) {
        echo '<tr>';
        echo '<td>' . esc_html( $offer->client_name ) . '</td>';
        echo '<td>€ ' . esc_html( number_format( (float) $offer->total, 2, ',', '.' ) ) . '</td>';
        echo '<td>' . esc_html( $offer->status ) . '</td>';
        // Link naar detailweergave. We gebruiken de slug van de pagina 'offers' plus ID.
        $link = home_url( '/offers/' . intval( $offer->id ) );
        echo '<td><a href="' . esc_url( $link ) . '">' . esc_html__( 'Bekijken', 'dj-srm' ) . '</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
else :
    echo '<p>' . esc_html__( 'Geen offertes gevonden.', 'dj-srm' ) . '</p>';
endif;

// Einde template offers.php