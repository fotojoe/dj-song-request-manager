<?php
/**
 * Template: offer-single.php
 * Toont offerte details (admin dashboard vs klant shortcode)
 */

if (!isset($offer) && isset($offer_id)) {
    global $wpdb;
    $table = $wpdb->prefix . 'dj_srm_offers';
    $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $offer_id));
}

if (!$offer) {
    echo "<div class='wrap'><h2>❌ Offerte niet gevonden</h2></div>";
    return;
}

$items = json_decode($offer->items, true);
$rider = json_decode($offer->rider ?? '{}', true);
$is_admin = is_admin();

$status_class = [
    'concept'      => 'badge-blue',
    'verzonden'    => 'badge-blue',
    'geaccepteerd' => 'badge-green',
    'geweigerd'    => 'badge-red',
    'betaald'      => 'badge-yellow'
];
?>

<?php if ($is_admin): ?>
<!-- ================== ADMIN DASHBOARD VIEW ================== -->
<div class="wrap dj-srm-dashboard">
    <h1>📄 Offerte #<?php echo esc_html($offer->offer_number); ?></h1>

    <!-- Tabs -->
    <div class="dj-srm-tabs">
        <button data-tab="overview" class="active">📌 Overzicht</button>
        <button data-tab="items">📝 Items</button>
        <button data-tab="rider">🎤 Rider</button>
        <button data-tab="notes">🗒 Notities</button>
        <button data-tab="actions">⚙️ Acties</button>
    </div>

    <div class="dj-srm-tabs-content">

        <!-- Overzicht -->
        <div id="overview" class="dj-srm-tab-content active">
            <div class="dj-srm-grid">
                <div class="dj-srm-card">
                    <h2>👤 Klantgegevens</h2>
                    <p><strong><?php echo esc_html($offer->client_name); ?></strong></p>
                    <p><?php echo esc_html($offer->client_email); ?><br>
                       <?php echo esc_html($offer->client_phone); ?></p>
                </div>
                <div class="dj-srm-card">
                    <h2>🎉 Event</h2>
                    <p><strong>Type:</strong> <?php echo esc_html($offer->event_type); ?></p>
                    <p><strong>Datum:</strong> <?php echo esc_html($offer->event_date); ?></p>
                    <p><strong>Tijd:</strong> <?php echo esc_html($offer->start_time . " - " . $offer->end_time); ?></p>
                    <p><strong>Locatie:</strong> <?php echo esc_html($offer->venue_city); ?></p>
                </div>
            </div>
            <div class="dj-srm-card highlight">
                <h2>💶 Totaal</h2>
                <p>Subtotaal: <strong>€ <?php echo number_format($offer->subtotal,2,",","."); ?></strong></p>
                <p>BTW: <strong>€ <?php echo number_format($offer->vat,2,",","."); ?></strong></p>
                <p style="font-size:18px;">Totaal: <strong>€ <?php echo number_format($offer->total,2,",","."); ?></strong></p>
                <p>Status: 
                    <span class="status-badge <?php echo $status_class[$offer->status] ?? 'badge-blue'; ?>">
                        <?php echo ucfirst($offer->status); ?>
                    </span>
                </p>
            </div>
        </div>

        <!-- Items -->
        <div id="items" class="dj-srm-tab-content">
            <div class="dj-srm-card">
                <h2>📝 Offerte-items</h2>
                <table class="widefat striped">
                    <thead>
                        <tr><th>Omschrijving</th><th>Aantal</th><th>Prijs</th><th>BTW</th><th style="text-align:right;">Subtotaal</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)) : ?>
                            <?php foreach ($items as $item): 
                                $qty = intval($item['qty']);
                                $price = floatval($item['price']);
                                $vat = floatval($item['vat']);
                                $lineSubtotal = $qty * $price;
                            ?>
                            <tr>
                                <td><?php echo esc_html($item['item']); ?></td>
                                <td><?php echo $qty; ?></td>
                                <td>€ <?php echo number_format($price, 2, ",", "."); ?></td>
                                <td><?php echo $vat; ?>%</td>
                                <td style="text-align:right;">€ <?php echo number_format($lineSubtotal, 2, ",", "."); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">Geen items toegevoegd.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rider -->
        <div id="rider" class="dj-srm-tab-content">
            <div class="dj-srm-card">
                <h2>🎤 Rider</h2>
                <p><strong>Eten & drinken:</strong><br><?php echo nl2br(esc_html($rider['eten'] ?? 'Niet ingevuld')); ?></p>
                <p><strong>Techniek:</strong><br><?php echo nl2br(esc_html($rider['techniek'] ?? 'Niet ingevuld')); ?></p>
                <p><strong>Logistiek:</strong><br><?php echo nl2br(esc_html($rider['logistiek'] ?? 'Niet ingevuld')); ?></p>
            </div>
        </div>

        <!-- Notes -->
        <div id="notes" class="dj-srm-tab-content">
            <div class="dj-srm-card">
                <h2>🗒 Notities & Voorwaarden</h2>
                <p><strong>Notities:</strong><br><?php echo nl2br(esc_html($offer->notes ?? 'Geen notities')); ?></p>
                <p><strong>Voorwaarden:</strong><br><?php echo nl2br(esc_html($offer->terms ?? 'Geen voorwaarden')); ?></p>
            </div>
        </div>

        <!-- Acties -->
        <div id="actions" class="dj-srm-tab-content">
            <div class="dj-srm-card">
                <h2>⚙️ Acties</h2>
                <a href="<?php echo admin_url("admin.php?page=dj-srm-offers"); ?>" class="button">⬅ Terug</a>
                <a href="<?php echo admin_url("admin.php?page=dj-srm-offers&edit={$offer->id}"); ?>" class="button">✏ Bewerken</a>
                <button class="button delete-offer" data-id="<?php echo $offer->id; ?>" data-nonce="<?php echo wp_create_nonce('dj_srm_nonce'); ?>">🗑 Verwijderen</button>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- ================== KLANT VIEW (kort en simpel) ================== -->
<div class="dj-offer-client">
    <h2>Offerte #<?php echo esc_html($offer->offer_number); ?></h2>
    <p><strong>Klant:</strong> <?php echo esc_html($offer->client_name); ?></p>
    <p><strong>Event:</strong> <?php echo esc_html($offer->event_type); ?> op <?php echo esc_html($offer->event_date); ?></p>

    <p>Status: 
        <span class="status-badge <?php echo $status_class[$offer->status] ?? 'badge-blue'; ?>">
            <?php echo ucfirst($offer->status); ?>
        </span>
    </p>

    <table class="widefat striped">
        <thead><tr><th>Omschrijving</th><th>Aantal</th><th>Prijs</th><th>BTW</th><th>Subtotaal</th></tr></thead>
        <tbody>
        <?php if (!empty($items)) : ?>
            <?php foreach ($items as $item): 
                $qty = intval($item['qty']);
                $price = floatval($item['price']);
                $vat = floatval($item['vat']);
                $lineSubtotal = $qty * $price;
            ?>
            <tr>
                <td><?php echo esc_html($item['item']); ?></td>
                <td><?php echo $qty; ?></td>
                <td>€ <?php echo number_format($price, 2, ",", "."); ?></td>
                <td><?php echo $vat; ?>%</td>
                <td>€ <?php echo number_format($lineSubtotal, 2, ",", "."); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">Geen items toegevoegd.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p><strong>Totaal:</strong> € <?php echo number_format($offer->total,2,",","."); ?></p>

    <?php if (in_array($offer->status, ['concept','verzonden'])): ?>
        <button class="button-primary update-offer-status" data-id="<?php echo $offer->id; ?>" data-status="geaccepteerd">✅ Accepteren</button>
        <button class="button update-offer-status" data-id="<?php echo $offer->id; ?>" data-status="geweigerd">❌ Weigeren</button>
    <?php else: ?>
        <p><em>Deze offerte is al <?php echo $offer->status; ?>.</em></p>
    <?php endif; ?>
</div>
<?php endif; ?>
