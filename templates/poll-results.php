<?php
/**
 * Template: poll-results.php
 * Toont de resultaten van de actieve poll.
 * Gebruikt in de shortcode [dj_poll_results].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$poll_table = $wpdb->prefix . 'dj_srm_polls';
$votes_table = $wpdb->prefix . 'dj_srm_poll_votes';

// Actieve poll ophalen
$poll = $wpdb->get_row( "SELECT * FROM {$poll_table} WHERE status='open' ORDER BY created_at DESC LIMIT 1" );

if ( $poll ) :
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT choice, COUNT(*) AS total FROM {$votes_table} WHERE poll_id=%d GROUP BY choice", $poll->id ) );
    ?>
    <div class="dj-poll-results">
        <h2><?php echo esc_html__( 'Resultaten:', 'dj-srm' ) . ' ' . esc_html( $poll->question ); ?></h2>
        <?php if ( $results ) : ?>
            <ul>
                <?php foreach ( $results as $row ) : ?>
                    <li><?php echo esc_html( $row->choice ); ?>: <?php echo esc_html( $row->total ); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p><?php echo esc_html__( 'Nog geen stemmen uitgebracht.', 'dj-srm' ); ?></p>
        <?php endif; ?>
    </div>
<?php else : ?>
    <p><?php echo esc_html__( 'Geen resultaten beschikbaar.', 'dj-srm' ); ?></p>
<?php endif; ?>

<!-- Einde template poll-results.php -->