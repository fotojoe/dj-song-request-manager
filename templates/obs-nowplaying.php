<?php
/**
 * Template: obs-nowplaying.php
 * Toont het huidige nummer dat wordt afgespeeld. Voor gebruik in OBS overlay.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_requests';
// Het meest recente gedraaide nummer (status = 'gedraaid')
$nowplaying = $wpdb->get_row( "SELECT * FROM {$table} WHERE status='gedraaid' ORDER BY created_at DESC LIMIT 1" );

if ( $nowplaying ) :
    echo '<div class="obs-nowplaying">';
    echo '<h2>' . esc_html__( 'Now Playing', 'dj-srm' ) . '</h2>';
    echo '<p>' . esc_html( $nowplaying->artist ) . ' – ' . esc_html( $nowplaying->song ) . '</p>';
    echo '</div>';
else :
    echo '<p>' . esc_html__( 'Er speelt momenteel geen nummer.', 'dj-srm' ) . '</p>';
endif;

// Einde template obs-nowplaying.php