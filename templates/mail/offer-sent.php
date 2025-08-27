<?php
/**
 * Template: offer-sent.php
 * HTML mail voor klanten bij versturen van offerte
 *
 * Variabelen:
 * $offer = offerte object (uit DB)
 */
$offer_link = site_url("/offers/"); // algemene loginpagina
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>🎶 Offerte DJ’s Oostboys</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f7f9; padding:20px; color:#2C3E50;">
  <div style="max-width:600px; margin:0 auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1);">

    <!-- Header -->
    <div style="background:#A3E4D7; text-align:center; padding:20px;">
      <img src="<?php echo plugins_url('public/img/oostboys-logo.png', DJ_SRM_PLUGIN_FILE); ?>" alt="DJ’s Oostboys" style="max-height:80px;">
      <h1 style="margin:10px 0; color:#2C3E50;">🎉 Jouw Offerte staat klaar!</h1>
    </div>

    <!-- Body -->
    <div style="padding:20px;">
      <p>Hoi <strong><?php echo esc_html($offer->client_name); ?></strong>,</p>

      <p>We hebben je persoonlijke <strong>Oostboys Party-offerte</strong> klaargezet!  
         Tijd om je feestplannen werkelijkheid te maken 🥳.</p>

      <h2 style="color:#AEB92D;">📧 Zo log je in</h2>
      <p>
        Gebruik je <strong>e-mailadres</strong>: <em><?php echo esc_html($offer->client_email); ?></em><br>
        En je persoonlijke <strong>PIN-code</strong>: <em><?php echo esc_html($offer->pincode); ?></em>
      </p>

      <p>Met deze combinatie kun je inloggen op onze offertepagina en jouw feestdeal bekijken.</p>

      <!-- Knop -->
      <p style="text-align:center; margin:30px 0;">
        <a href="<?php echo esc_url($offer_link); ?>" style="background:#FF5299; color:#fff; padding:15px 25px; text-decoration:none; border-radius:30px; font-size:18px;">
          👉 Ga naar mijn Offerte
        </a>
      </p>

      <h2 style="color:#AEB92D;">📅 Eventdetails</h2>
      <p>
        <strong>Type:</strong> <?php echo esc_html($offer->event_type); ?><br>
        <strong>Datum:</strong> <?php echo esc_html($offer->event_date); ?><br>
        <strong>Locatie:</strong> <?php echo esc_html($offer->venue_city); ?><br>
        <strong>Aantal gasten:</strong> <?php echo esc_html($offer->guest_count); ?>
      </p>

      <h2 style="color:#AEB92D;">💰 Samenvatting</h2>
      <p>
        <strong>Totaal:</strong> € <?php echo number_format($offer->total,2,",","."); ?><br>
        <em>Geldig tot: <?php echo esc_html($offer->valid_until); ?></em>
      </p>

      <p>Met je login kun je de volledige offerte bekijken én meteen aangeven of je <strong>JA zegt tegen een knalfeest met DJ’s Oostboys 🎶</strong> of niet.</p>

      <p>Groetjes,<br><strong>DJ Dirk & DJ Pim</strong><br>DJ’s Oostboys</p>
    </div>

    <!-- Footer -->
    <div style="background:#2C3E50; color:#fff; text-align:center; padding:15px;">
      Give your party a voice with DJ’s Oostboys!<br>
      🌐 <a href="https://djsoostboys.nl" style="color:#A3E4D7; text-decoration:none;">djsoostboys.nl</a>  
      | 📧 info@djsoostboys.nl
    </div>
  </div>
</body>
</html>
