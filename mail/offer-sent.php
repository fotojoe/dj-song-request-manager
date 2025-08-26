<?php
/**
 * Mailtemplate: offer-sent.php
 * Wordt verzonden naar de klant wanneer een offerte is opgesteld en verzonden.
 * De placeholders in double curly braces worden vervangen door dynamische waarden.
 */
?>
<p>Beste {{client_name}},</p>
<p>Hierbij sturen wij u een offerte voor uw evenement:</p>
<p><a href="{{offer_link}}">Bekijk offerte</a></p>
<p>
    Subtotaal: € {{subtotal}}<br>
    BTW: € {{vat}}<br>
    Totaal: € {{total}}
</p>
<p>Deze offerte is geldig tot {{valid_until}}.</p>
<p>Met vriendelijke groet,<br>DJ’s Oostboys</p>