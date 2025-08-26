<?php
/**
 * Template: request-form.php
 * Toont een formulier waarop gasten een verzoeknummer kunnen indienen.
 * Wordt gebruikt in de shortcode [dj_request_form].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Bepaal de huidige event ID. In een echte implementatie zou dit dynamisch bepaald worden (bijv. via queryvars of pagina-meta).
$current_event_id = 1;

?>
<form id="dj-request-form" class="dj-request-form dj-srm-form">
    <label><?php echo esc_html__( 'Naam', 'dj-srm' ); ?>:</label>
    <input type="text" name="requester" required>

    <label><?php echo esc_html__( 'E-mail (optioneel)', 'dj-srm' ); ?>:</label>
    <input type="email" name="email">

    <label><?php echo esc_html__( 'Artiest', 'dj-srm' ); ?>:</label>
    <input type="text" name="artist" required>

    <label><?php echo esc_html__( 'Nummer', 'dj-srm' ); ?>:</label>
    <input type="text" name="song" required>

    <label><?php echo esc_html__( 'Bericht (optioneel)', 'dj-srm' ); ?>:</label>
    <textarea name="message"></textarea>

    <input type="hidden" name="event_id" value="<?php echo esc_attr( $current_event_id ); ?>">

    <button type="submit"><?php echo esc_html__( 'Nummer aanvragen', 'dj-srm' ); ?></button>
</form>
<div id="dj-request-response"></div>

<script>
// Gebruik jQuery om het formulier via AJAX te verzenden
jQuery(document).ready(function($){
    $('#dj-request-form').on('submit', function(e){
        e.preventDefault();
        var data = $(this).serialize();
        data += '&action=dj_srm_add_request';
        $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function(response){
            if(response.success) {
                $('#dj-request-response').text(response.data.message);
                $('#dj-request-form')[0].reset();
            } else {
                $('#dj-request-response').text(response.data ? response.data.message : 'Er ging iets mis.');
            }
        });
    });
});
</script>

<!-- Einde template request-form.php -->