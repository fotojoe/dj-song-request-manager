<?php
/**
 * PDF Template: Offerte
 * Wordt geladen in generate_offer_pdf()
 */
?>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size:12px; color:#2C3E50; margin:0; padding:0; }
    header { background:#A3E4D7; padding:15px; text-align:center; }
    header img { max-height:80px; }
    h1 { color:#2C3E50; margin:10px 0; }
    h2 { color:#AEB92D; border-bottom:2px solid #BDC3C7; padding-bottom:4px; margin-top:20px; }
    table { width:100%; border-collapse: collapse; margin-top:10px; }
    th, td { border:1px solid #BDC3C7; padding:6px; text-align:left; }
    th { background:#F8F8F8; }
    .totals { margin-top:20px; }
    .totals td { font-weight:bold; }
    footer { background:#2C3E50; color:#fff; text-align:center; padding:15px; margin-top:30px; }
    .highlight { color:#FF5299; }
  </style>
</head>
<body>

<header>
  <img src="<?php echo DJ_SRM_PLUGIN_DIR; ?>public/img/oostboys-logo.png" alt="DJ's Oostboys Logo">
  <h1>Offerte DJ’s Oostboys</h1>
  <p class="highlight">Give your party a voice with DJ’s Oostboys!</p>
</header>

<main style="padding:20px;">

  <h2>👤 Klantgegevens</h2>
  <p>
    <strong>Naam:</strong> <?php echo esc_html($offer->client_name); ?><br>
    <strong>Email:</strong> <?php echo esc_html($offer->client_email); ?><br>
    <strong>Telefoon:</strong> <?php echo esc_html($offer->client_phone); ?>
  </p>

  <h2>🎉 Eventdetails</h2>
  <p>
    <strong>Type:</strong> <?php echo esc_html($offer->event_type); ?><br>
    <strong>Datum:</strong> <?php echo esc_html($offer->event_date); ?><br>
    <strong>Tijd:</strong> <?php echo esc_html($offer->start_time . " - " . $offer->end_time); ?><br>
    <strong>Locatie:</strong> <?php echo esc_html($offer->venue_street." ".$offer->venue_number.", ".$offer->venue_postcode." ".$offer->venue_city); ?><br>
    <strong>Gasten:</strong> <?php echo esc_html($offer->guest_count); ?>
  </p>

  <h2>📝 Specificatie</h2>
  <table>
    <thead>
      <tr>
        <th>Omschrijving</th><th>Aantal</th><th>Prijs</th><th>BTW</th><th>Subtotaal</th>
      </tr>
    </thead>
    <tbody>
      <?php $items = json_decode($offer->items,true); if($items): foreach($items as $row): 
        $line = $row['qty'] * $row['price']; ?>
      <tr>
        <td><?php echo esc_html($row['item']); ?></td>
        <td><?php echo esc_html($row['qty']); ?></td>
        <td>€ <?php echo number_format($row['price'],2,",","."); ?></td>
        <td><?php echo esc_html($row['vat']); ?>%</td>
        <td>€ <?php echo number_format($line,2,",","."); ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5">Geen items toegevoegd.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <table class="totals">
    <tr><td>Subtotaal</td><td>€ <?php echo number_format($offer->subtotal,2,",","."); ?></td></tr>
    <tr><td>BTW</td><td>€ <?php echo number_format($offer->vat,2,",","."); ?></td></tr>
    <?php if($offer->discount>0): ?>
    <tr><td>Korting</td><td>- € <?php echo number_format($offer->discount,2,",","."); ?></td></tr>
    <?php endif; ?>
    <tr><td><strong>Totaal</strong></td><td><strong>€ <?php echo number_format($offer->total,2,",","."); ?></strong></td></tr>
  </table>

  <?php if($offer->rider): ?>
  <h2>🎤 Rider</h2>
  <?php echo wpautop(wp_kses_post($offer->rider)); ?>
  <?php endif; ?>

  <?php if($offer->notes): ?>
  <h2>ℹ Notities</h2>
  <?php echo wpautop(wp_kses_post($offer->notes)); ?>
  <?php endif; ?>

  <?php if($offer->terms): ?>
  <h2>📜 Voorwaarden</h2>
  <?php echo wpautop(wp_kses_post($offer->terms)); ?>
  <?php endif; ?>

</main>

<footer>
  <p><strong>DJ’s Oostboys</strong> | www.djsoostboys.nl | info@djsoostboys.nl</p>
  <p>Bedrijfsfeesten • Bruiloften • Kinderentertainment</p>
</footer>

</body>
</html>
