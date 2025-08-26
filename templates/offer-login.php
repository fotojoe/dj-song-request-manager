<?php
/**
 * Template: offer-login.php
 * Doel: frontend login + overzicht offertes voor klant
 */

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_offers';

// Stap 1: Reset pincode aanvragen
if ( isset($_POST['reset_email']) ) {
    $email = sanitize_email($_POST['reset_email']);
    $offers = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE client_email = %s", $email) );

    if ($offers) {
        $new_pin = wp_generate_password(6, false, false);

        // Update alle offertes met deze email
        $wpdb->update($table, ['client_pin' => $new_pin], ['client_email' => $email]);

        // Stuur mail
        $subject = "Nieuwe inlogcode voor uw DJ Offertes";
        $message = "<p>Beste klant,</p>";
        $message .= "<p>U heeft een nieuwe pincode aangevraagd. Uw nieuwe code is:</p>";
        $message .= "<h2>{$new_pin}</h2>";
        $message .= "<p>Gebruik deze code samen met uw e-mailadres om uw offertes te bekijken.</p>";
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($email, $subject, $message, $headers);

        echo "<div class='dj-offer-success'>Nieuwe pincode is verstuurd naar $email.</div>";
    } else {
        echo "<div class='dj-offer-error'>Geen offertes gevonden voor dit e-mailadres.</div>";
    }
}

// Stap 2: Inloggen met e-mail + pincode
if ( isset($_POST['client_email']) && isset($_POST['client_pin']) ) {
    $email = sanitize_email($_POST['client_email']);
    $pin   = sanitize_text_field($_POST['client_pin']);

    $offers = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE client_email = %s AND client_pin = %s ORDER BY created_at DESC", $email, $pin)
    );

    if ( $offers ) {
        echo "<div class='dj-offer-overview'>";
        echo "<h2>Uw offertes</h2>";
        echo "<table class='widefat striped'>";
        echo "<thead><tr><th>Nummer</th><th>Event</th><th>Datum</th><th>Status</th><th>Totaal</th><th>Bekijk</th></tr></thead><tbody>";

        foreach ($offers as $offer) {
            $link = site_url("/offers/?id={$offer->id}");
            echo "<tr>
                    <td>{$offer->offer_number}</td>
                    <td>{$offer->event_type}</td>
                    <td>{$offer->event_date}</td>
                    <td>{$offer->status}</td>
                    <td>€ " . number_format($offer->total, 2, ",", ".") . "</td>
                    <td><a class='button' href='{$link}'>Bekijk</a></td>
                 </tr>";
        }

        echo "</tbody></table></div>";
        return;
    } else {
        echo "<div class='dj-offer-error'>Ongeldige combinatie van e-mail en pincode.</div>";
    }
}
?>

<!-- Login Form -->
<div class="dj-offer-login-form">
    <h2>Log in om uw offertes te bekijken</h2>
    <form method="post">
        <p>
            <label for="client_email">E-mailadres</label><br>
            <input type="email" name="client_email" id="client_email" required>
        </p>
        <p>
            <label for="client_pin">Pincode</label><br>
            <input type="text" name="client_pin" id="client_pin" maxlength="10" required>
        </p>
        <p>
            <button type="submit" class="button-primary">Inloggen</button>
        </p>
    </form>

    <hr>

    <!-- Reset Form -->
    <h3>Pincode vergeten?</h3>
    <form method="post">
        <p>
            <label for="reset_email">Vul uw e-mailadres in</label><br>
            <input type="email" name="reset_email" id="reset_email" required>
        </p>
        <p>
            <button type="submit" class="button">Nieuwe pincode aanvragen</button>
        </p>
    </form>
</div>
