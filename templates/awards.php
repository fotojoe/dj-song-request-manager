<?php
/**
 * Template: awards.php
 * Toont een lijst van uitgereikte awards voor het huidige event.
 * Wordt gebruikt in de shortcode [dj_awards].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_awards';
// In een echte implementatie zou event_id dynamisch bepaald worden. Voor nu gebruiken we event_id=1.
$event_id = 1;
$awards = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id = %d ORDER BY created_at ASC", $event_id ) );

if ( $awards ) :
    echo '<div class="dj-awards">';
    echo '<h2>' . esc_html__( 'Awards', 'dj-srm' ) . '</h2>';
    foreach ( $awards as $award ) {
        echo '<div class="dj-award">';
        echo '<strong>' . esc_html( $award->name ) . '</strong> - ' . esc_html( $award->receiver );
        if ( $award->type ) {
            echo ' <em>(' . esc_html( $award->type ) . ')</em>';
        }
        echo '</div>';
    }
    echo '</div>';
else :
    echo '<p>' . esc_html__( 'Nog geen awards uitgereikt.', 'dj-srm' ) . '</p>';
endif;

// Einde template awards.php