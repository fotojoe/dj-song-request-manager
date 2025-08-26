<?php
/**
 * Template: afterparty.php
 * Toont de afterparty recap van het event.
 * Gebruikt in shortcode [dj_afterparty].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_afterparty';
$event_id = 1;
$recap = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id=%d", $event_id ) );

if ( $recap ) :
    $playlist   = json_decode( $recap->playlist );
    $highlights = $recap->highlights;
    $notes      = $recap->notes;
    ?>
    <div class="dj-afterparty">
        <h2><?php echo esc_html__( 'Afterparty Recap', 'dj-srm' ); ?></h2>
        <?php if ( $playlist ) : ?>
            <h3><?php echo esc_html__( 'Gedraaide nummers', 'dj-srm' ); ?></h3>
            <ul>
                <?php foreach ( $playlist as $track ) : ?>
                    <li><?php echo esc_html( $track ); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ( $highlights ) : ?>
            <h3><?php echo esc_html__( 'Hoogtepunten', 'dj-srm' ); ?></h3>
            <p><?php echo esc_html( $highlights ); ?></p>
        <?php endif; ?>
        <?php if ( $notes ) : ?>
            <h3><?php echo esc_html__( 'Extra notities', 'dj-srm' ); ?></h3>
            <p><?php echo esc_html( $notes ); ?></p>
        <?php endif; ?>
    </div>
<?php else : ?>
    <p><?php echo esc_html__( 'Er is nog geen afterparty recap beschikbaar.', 'dj-srm' ); ?></p>
<?php endif; ?>

<!-- Einde template afterparty.php -->