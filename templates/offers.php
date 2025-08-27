<?php
/**
 * Template: offers.php
 * Doel: volledige klantportal voor offertes
 *
 * Flow:
 *  - Stap 1: Login met e-mail + PIN
 *  - Stap 2: Toon offertes (volledige details per offerte)
 */

if ( ! defined('ABSPATH') ) exit;
global $wpdb;
$table = $wpdb->prefix . 'dj_srm_offers';

session_start();

// ===============================
// Reset PIN (opnieuw aanvragen)
// ===============================
if ( isset($_POST['reset_email']) ) {
    $email = sanitize_email($_POST['reset_email']);
    $offers = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE client_email=%s",$email));
    if ($offers) {
        $new_pin = rand(100000,999999);
        $wpdb->update($table, ['pincode'=>$new_pin], ['client_email'=>$email]);
        $subject = "🔑 Nieuwe inlogcode DJ’s Oostboys Offertes";
        $msg  = "<p>Beste klant,</p>";
        $msg .= "<p>Uw nieuwe pincode is: <strong>{$new_pin}</strong></p>";
        $msg .= "<p>Gebruik deze code samen met uw e-mailadres om uw offertes te bekijken.</p>";
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($email,$subject,$msg,$headers);
        echo "<div class='dj-offer-success'>Nieuwe pincode is verstuurd naar {$email}.</div>";
    } else {
        echo "<div class='dj-offer-error'>Geen offertes gevonden voor dit e-mailadres.</div>";
    }
}

// ===============================
// Login check
// ===============================
if ( isset($_POST['client_email']) && isset($_POST['client_pin']) ) {
    $email = sanitize_email($_POST['client_email']);
    $pin   = sanitize_text_field($_POST['client_pin']);

    $offers = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE client_email=%s AND pincode=%s ORDER BY created_at DESC",$email,$pin)
    );

    if ($offers) {
        $_SESSION['dj_offer_email'] = $email;
    } else {
        echo "<div class='dj-offer-error'>Ongeldige combinatie van e-mail en pincode.</div>";
    }
}

// ===============================
// Uitloggen
// ===============================
if ( isset($_GET['logout']) ) {
    unset($_SESSION['dj_offer_email']);
    wp_redirect(site_url('/offers/'));
    exit;
}

// ===============================
// Als niet ingelogd → login form
// ===============================
if ( !isset($_SESSION['dj_offer_email']) ) : ?>
    <div class="dj-offer-login-form">
        <h2>🔐 Log in om uw offertes te bekijken</h2>
        <form method="post">
            <p>
                <label>E-mailadres</label><br>
                <input type="email" name="client_email" required>
            </p>
            <p>
                <label>Pincode</label><br>
                <input type="text" name="client_pin" maxlength="6" required>
            </p>
            <p><button type="submit" class="button-primary">Inloggen</button></p>
        </form>

        <hr>
        <h3>Pincode vergeten?</h3>
        <form method="post">
            <p><input type="email" name="reset_email" placeholder="Uw e-mailadres" required></p>
            <p><button type="submit" class="button">Nieuwe pincode aanvragen</button></p>
        </form>
    </div>
<?php
return; endif;

// ===============================
// Ingelogd → toon offertes
// ===============================
$email = $_SESSION['dj_offer_email'];
$offers = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE client_email=%s ORDER BY created_at DESC",$email));

echo "<div class='dj-offer-overview'>";
echo "<h2>🎉 Welkom terug! Hier zijn uw offertes</h2>";
echo "<a href='".esc_url(add_query_arg('logout','1'))."' class='button'>Uitloggen</a><br><br>";

foreach($offers as $offer) {
    $items = json_decode($offer->items,true);
    echo "<div class='dj-offer-card' style='border:1px solid #ccc; padding:20px; margin:20px 0; border-radius:8px;'>";
    echo "<h3>Offerte #{$offer->offer_number} ({$offer->status})</h3>";
    echo "<p><strong>Event:</strong> {$offer->event_type} – {$offer->event_date}</p>";
    echo "<p><strong>Locatie:</strong> {$offer->venue_street} {$offer->venue_number}, {$offer->venue_postcode} {$offer->venue_city}</p>";
    echo "<p><strong>Gasten:</strong> {$offer->guest_count}</p>";

    // Items
    if ($items) {
        echo "<table class='widefat striped'><thead><tr><th>Omschrijving</th><th>Aantal</th><th>Prijs</th><th>Subtotaal</th></tr></thead><tbody>";
        foreach($items as $it) {
            $subtotal = $it['qty'] * $it['price'];
            echo "<tr><td>{$it['item']}</td><td>{$it['qty']}</td><td>€ ".number_format($it['price'],2,",",".")."</td><td>€ ".number_format($subtotal,2,",",".")."</td></tr>";
        }
        echo "</tbody></table>";
    }

    echo "<p><strong>Totaal:</strong> € ".number_format($offer->total,2,",",".")."</p>";
    echo "<p><em>Geldig tot {$offer->valid_until}</em></p>";

    echo "<h4>Voorwaarden</h4>";
    echo "<div>".wpautop($offer->terms)."</div>";

    if (!empty($offer->rider)) {
        echo "<h4>Rider</h4>";
        echo "<div>".wpautop($offer->rider)."</div>";
    }

    // Actie knoppen
    echo "<div class='dj-offer-actions'>";
    if ($offer->status==='verzonden' || $offer->status==='concept') {
        echo "<p>Wilt u akkoord gaan met deze offerte?</p>";
        echo "<button class='button-primary dj-offer-accept' data-id='{$offer->id}'>✅ Akkoord</button> ";
        echo "<button class='button dj-offer-decline' data-id='{$offer->id}'>❌ Niet akkoord</button>";
    } else {
        echo "<p><strong>Status:</strong> {$offer->status}</p>";
    }
    echo "</div>";

    echo "</div>";
}
echo "</div>";
?>

<script>
jQuery(function($){
    $('.dj-offer-accept, .dj-offer-decline').on('click', function(){
        var status = $(this).hasClass('dj-offer-accept') ? 'geaccepteerd' : 'geweigerd';
        var id = $(this).data('id');
        $.post(dj_srm_ajax.url, {
            action: 'dj_srm_update_offer_status',
            _ajax_nonce: dj_srm_ajax.nonce,
            id: id,
            status: status
        }, function(resp){
            if(resp.success){
                alert(resp.data.message);
                location.reload();
            } else {
                alert('Er ging iets mis.');
            }
        });
    });
});
</script>
