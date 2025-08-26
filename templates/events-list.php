<?php
/**
 * Template: events-list.php
 * Toont een lijst van bevestigde events op de frontend in een toegankelijke kaartlayout.
 * Gebruikt in shortcode [dj_events].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table  = $wpdb->prefix . 'dj_srm_events';
// Alleen events met status 'bevestigd' tonen
$events = $wpdb->get_results( "SELECT * FROM $table WHERE status='bevestigd' ORDER BY start_time ASC" );

if ( $events ) : ?>
    <div class="dj-events-list">
        <?php foreach ( $events as $event ) : ?>
            <div class="dj-event-card">
                <h2 class="dj-event-title"><?php echo esc_html( $event->name ); ?></h2>

                <?php if ( $event->start_time ) : ?>
                    <p class="dj-event-date">
                        <span class="label"><?php esc_html_e( 'Datum:', 'dj-srm' ); ?></span>
                        <?php echo date_i18n( 'j F Y, H:i', strtotime( $event->start_time ) ); ?>
                    </p>
                <?php endif; ?>

                <?php if ( $event->location ) : ?>
                    <p class="dj-event-location">
                        <span class="label"><?php esc_html_e( 'Locatie:', 'dj-srm' ); ?></span>
                        <?php echo esc_html( $event->location ); ?>
                    </p>
                <?php endif; ?>

                <?php if ( $event->description ) : ?>
                    <p class="dj-event-description"><?php echo esc_html( $event->description ); ?></p>
                <?php endif; ?>

                <div class="dj-event-footer">
                    <button class="dj-event-more">
                        <?php esc_html_e( 'Meer info', 'dj-srm' ); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <p class="dj-no-events"><?php esc_html_e( 'Geen bevestigde events gevonden.', 'dj-srm' ); ?></p>
<?php endif;
