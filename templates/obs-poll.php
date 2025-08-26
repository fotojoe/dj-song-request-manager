<?php
/**
 * Template: obs-poll.php
 * Toont pollresultaten voor OBS overlay.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$poll_table = $wpdb->prefix . 'dj_srm_polls';
$vote_table = $wpdb->prefix . 'dj_srm_poll_votes';

// Actieve poll
$poll = $wpdb->get_row( "SELECT * FROM {$poll_table} WHERE status='open' ORDER BY created_at DESC LIMIT 1" );

if ( $poll ) :
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT choice, COUNT(*) AS total FROM {$vote_table} WHERE poll_id=%d GROUP BY choice", $poll->id ) );
    echo '<div class="obs-poll">';
    echo '<h2>' . esc_html( $poll->question ) . '</h2>';
    echo '<ul>';
    if ( $results ) {
        foreach ( $results as $row ) {
            echo '<li>' . esc_html( $row->choice ) . ': ' . esc_html( $row->total ) . '</li>';
        }
    } else {
        echo '<li>' . esc_html__( 'Nog geen stemmen.', 'dj-srm' ) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
else :
    echo '<p>' . esc_html__( 'Geen actieve poll.', 'dj-srm' ) . '</p>';
endif;

// Einde template obs-poll.php