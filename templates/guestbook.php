<?php
/**
 * Template: guestbook.php
 * Toont het gastenboek met bestaande berichten en een formulier om een nieuw bericht te plaatsen.
 * Gebruikt in shortcode [dj_guestbook].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_guestbook';
$event_id = 1;
$entries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id=%d AND approved=1 ORDER BY created_at DESC", $event_id ) );
?>
<div class="dj-guestbook">
    <h2><?php echo esc_html__( 'Gastenboek', 'dj-srm' ); ?></h2>
    <!-- Lijst met goedgekeurde berichten -->
    <?php if ( $entries ) : ?>
        <ul class="guestbook-list">
            <?php foreach ( $entries as $entry ) : ?>
                <li><strong><?php echo esc_html( $entry->name ); ?>:</strong> <?php echo esc_html( $entry->message ); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p><?php echo esc_html__( 'Nog geen berichten. Schrijf de eerste!', 'dj-srm' ); ?></p>
    <?php endif; ?>
    <!-- Formulier voor nieuw bericht -->
    <form id="dj-guestbook-form" class="dj-srm-form">
        <label><?php echo esc_html__( 'Naam', 'dj-srm' ); ?>:</label>
        <input type="text" name="name" required>
        <label><?php echo esc_html__( 'Bericht', 'dj-srm' ); ?>:</label>
        <textarea name="message" required></textarea>
        <input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
        <button type="submit"><?php echo esc_html__( 'Verstuur bericht', 'dj-srm' ); ?></button>
    </form>
    <div id="dj-guestbook-response"></div>
</div>
<script>
jQuery(document).ready(function($){
    $('#dj-guestbook-form').on('submit', function(e){
        e.preventDefault();
        var data = $(this).serialize();
        data += '&action=dj_srm_add_guestbook';
        $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function(response){
            if(response.success) {
                $('#dj-guestbook-response').text(response.data.message);
                $('#dj-guestbook-form')[0].reset();
            } else {
                $('#dj-guestbook-response').text(response.data ? response.data.message : 'Er ging iets mis.');
            }
        });
    });
});
</script>

<!-- Einde template guestbook.php -->