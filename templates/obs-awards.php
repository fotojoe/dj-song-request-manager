<?php
/**
 * Template: obs-awards.php
 * Toont awardwinnaars in een simpel format voor OBS overlay.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_awards';
$event_id = 1;
$awards = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id=%d ORDER BY created_at DESC", $event_id ) );

if ( $awards ) :
    echo '<div class="obs-awards">';
    echo '<h2>' . esc_html__( 'Awards', 'dj-srm' ) . '</h2>';
    foreach ( $awards as $award ) {
        echo '<p><strong>' . esc_html( $award->name ) . '</strong> – ' . esc_html( $award->receiver ) . '</p>';
    }
    echo '</div>';
else :
    echo '<p>' . esc_html__( 'Nog geen awards uitgereikt.', 'dj-srm' ) . '</p>';
endif;

// Einde template obs-awards.php