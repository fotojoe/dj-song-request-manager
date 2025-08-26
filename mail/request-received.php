<?php
/**
 * Mailtemplate: request-received.php
 * Wordt gebruikt om een gast te bevestigen dat zijn/haar verzoeknummer is ontvangen.
 * Gebruik eenvoudige HTML; je kunt de header en footer inladen via DJ_SRM_Settings.
 */

?><p><?php echo esc_html__( 'Beste', 'dj-srm' ); ?> {{requester}},</p>
<p><?php echo esc_html__( 'Je verzoek voor het nummer', 'dj-srm' ); ?> <strong>{{song}}</strong> <?php echo esc_html__( 'is ontvangen!', 'dj-srm' ); ?></p>
<p><?php echo esc_html__( 'Wij doen ons best om jouw verzoek te draaien tijdens', 'dj-srm' ); ?> <strong>{{event_name}}</strong>.</p>
<p><?php echo esc_html__( 'Muzikale groet,', 'dj-srm' ); ?><br>DJ’s Oostboys</p>