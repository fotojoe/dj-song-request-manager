<?php
/**
 * Mailtemplate: guestbook-notification.php
 * Wordt naar de organisator gestuurd wanneer er een nieuw gastenboekbericht is geplaatst.
 */
?>
<p>Hallo {{organizer_name}},</p>
<p>Er is een nieuw bericht geplaatst in het gastenboek van <strong>{{event_name}}</strong>:</p>
<blockquote>
    <strong>{{guest_name}}:</strong> {{guest_message}}
</blockquote>
<p>Je kunt het bericht modereren in het dashboard.</p>
<p>Met vriendelijke groet,<br>DJ’s Oostboys</p>