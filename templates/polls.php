<?php
/**
 * Template: polls.php
 * Toont de actieve poll met stemopties voor gasten.
 * Wordt gebruikt in de shortcode [dj_polls].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$poll_table = $wpdb->prefix . 'dj_srm_polls';
// Haal de laatste open poll op
$poll = $wpdb->get_row( "SELECT * FROM {$poll_table} WHERE status='open' ORDER BY created_at DESC LIMIT 1" );

if ( $poll ) :
    $options = json_decode( $poll->options, true );
    ?>
    <div class="dj-poll">
        <h2><?php echo esc_html( $poll->question ); ?></h2>
        <form id="dj-poll-form" class="dj-srm-form">
            <input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll->id ); ?>">
            <?php
            if ( is_array( $options ) ) {
                foreach ( $options as $opt ) {
                    echo '<label><input type="radio" name="choice" value="' . esc_attr( $opt ) . '"> ' . esc_html( $opt ) . '</label><br>';
                }
            }
            ?>
            <input type="text" name="voter" placeholder="<?php echo esc_attr__( 'Je naam (optioneel)', 'dj-srm' ); ?>">
            <button type="submit"><?php echo esc_html__( 'Stemmen', 'dj-srm' ); ?></button>
        </form>
        <div id="dj-poll-response"></div>
    </div>
    <script>
    jQuery(document).ready(function($){
        $('#dj-poll-form').on('submit', function(e){
            e.preventDefault();
            var data = $(this).serialize();
            data += '&action=dj_srm_vote';
            $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function(response){
                if(response.success) {
                    $('#dj-poll-response').text(response.data.message);
                    // reset radio buttons
                    $('#dj-poll-form input[type=radio]').prop('checked', false);
                } else {
                    $('#dj-poll-response').text(response.data ? response.data.message : 'Er ging iets mis.');
                }
            });
        });
    });
    </script>
<?php else : ?>
    <p><?php echo esc_html__( 'Er is op dit moment geen actieve poll.', 'dj-srm' ); ?></p>
<?php endif; ?>

<!-- Einde template polls.php -->