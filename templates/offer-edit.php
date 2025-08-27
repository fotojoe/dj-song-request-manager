<?php
/**
 * Template: offer-edit.php (Admin Wizard - Bestaande offerte)
 */

if ( ! current_user_can('manage_options') ) {
    wp_die(__('Je hebt geen rechten om offertes te bewerken.', 'dj-srm'));
}

if ( ! isset($offer) ) {
    echo "<div class='wrap'><h2>Offerte niet gevonden</h2></div>";
    return;
}

$items = json_decode($offer->items, true);
$offer_nonce = wp_create_nonce('dj_srm_nonce');
?>

<div class="dj-srm-offer-form wrap">
    <h1>✏ Offerte bewerken (#<?php echo esc_html($offer->offer_number); ?>)</h1>

    <!-- Progress bar -->
    <div id="offer-progress-edit" class="dj-progressbar">
        <div class="step active" data-step="1">1<br><span>Klant</span></div>
        <div class="step" data-step="2">2<br><span>Event</span></div>
        <div class="step" data-step="3">3<br><span>Locatie</span></div>
        <div class="step" data-step="4">4<br><span>Items</span></div>
        <div class="step" data-step="5">5<br><span>Rider</span></div>
        <div class="step" data-step="6">6<br><span>Bevestigen</span></div>
    </div>

    <form id="offerEditForm" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
        <input type="hidden" name="action" value="dj_srm_update_offer">
        <input type="hidden" name="offer_id" value="<?php echo (int) $offer->id; ?>">
        <input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr($offer_nonce); ?>">

        <!-- Stap 1: Klant -->
        <fieldset data-step="1">
            <h2>👤 Klantgegevens</h2>
            <label>Volledige naam *</label>
            <input type="text" name="client_name" value="<?php echo esc_attr($offer->client_name); ?>" required>
            <label>Email *</label>
            <input type="email" name="client_email" value="<?php echo esc_attr($offer->client_email); ?>" required>
            <label>Telefoon</label>
            <input type="text" name="client_phone" value="<?php echo esc_attr($offer->client_phone); ?>">
            <button type="button" class="next button-primary">Volgende ➡</button>
        </fieldset>

        <!-- Stap 2: Event -->
        <fieldset data-step="2" style="display:none;">
            <h2>🎉 Eventdetails</h2>
            <label>Soort Event</label>
            <select name="event_type" required>
                <option value="">-- Kies --</option>
                <option value="Bruiloft" <?php selected($offer->event_type,'Bruiloft'); ?>>💍 Bruiloft</option>
                <option value="Verjaardag" <?php selected($offer->event_type,'Verjaardag'); ?>>🎂 Verjaardag</option>
                <option value="Zakelijk" <?php selected($offer->event_type,'Zakelijk'); ?>>🏢 Zakelijk feest</option>
                <option value="Festival" <?php selected($offer->event_type,'Festival'); ?>>🎪 Festival</option>
                <option value="Kinderfeest" <?php selected($offer->event_type,'Kinderfeest'); ?>>🎈 Kinderfeest</option>
                <option value="Anders" <?php selected($offer->event_type,'Anders'); ?>>❓ Anders</option>
            </select>
            <label>Datum</label><input type="date" name="event_date" value="<?php echo esc_attr($offer->event_date); ?>">
            <label>Starttijd</label><input type="time" name="start_time" value="<?php echo esc_attr($offer->start_time); ?>">
            <label>Eindtijd</label><input type="time" name="end_time" value="<?php echo esc_attr($offer->end_time); ?>">
            <label>Aantal gasten</label><input type="text" name="guest_count" value="<?php echo esc_attr($offer->guest_count); ?>">
            <button type="button" class="prev button">⬅ Vorige</button>
            <button type="button" class="next button-primary">Volgende ➡</button>
        </fieldset>

        <!-- Stap 3: Locatie -->
        <fieldset data-step="3" style="display:none;">
            <h2>📍 Locatie</h2>
            <label>Type locatie</label>
            <select name="venue_type">
                <option value="commercieel" <?php selected($offer->venue_type,'commercieel'); ?>>Commercieel</option>
                <option value="prive" <?php selected($offer->venue_type,'prive'); ?>>Privé</option>
            </select>
            <label>Plaats</label><input type="text" name="venue_city" value="<?php echo esc_attr($offer->venue_city); ?>">
            <label>Straat</label><input type="text" name="venue_street" value="<?php echo esc_attr($offer->venue_street); ?>">
            <label>Huisnummer</label><input type="text" name="venue_number" value="<?php echo esc_attr($offer->venue_number); ?>">
            <label>Postcode</label><input type="text" name="venue_postcode" value="<?php echo esc_attr($offer->venue_postcode); ?>">
            <button type="button" class="prev button">⬅ Vorige</button>
            <button type="button" class="next button-primary">Volgende ➡</button>
        </fieldset>

        <!-- Stap 4: Items -->
        <fieldset data-step="4" style="display:none;">
            <h2>📝 Offerte-items</h2>
            <table id="offer-items" class="widefat dj-offer-table">
                <thead>
                    <tr><th>Omschrijving</th><th>Aantal</th><th>Prijs (€)</th><th>BTW %</th><th>Subtotaal</th><th>Actie</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): foreach ($items as $i => $it): ?>
                    <tr>
                        <td><input type="text" name="items[<?php echo $i; ?>][item]" value="<?php echo esc_attr($it['item']); ?>" required></td>
                        <td><input type="number" name="items[<?php echo $i; ?>][qty]" value="<?php echo esc_attr($it['qty']); ?>" min="1"></td>
                        <td><input type="number" step="0.01" name="items[<?php echo $i; ?>][price]" value="<?php echo esc_attr($it['price']); ?>"></td>
                        <td>
                            <select name="items[<?php echo $i; ?>][vat]">
                                <option value="21" <?php selected($it['vat'],21); ?>>21%</option>
                                <option value="9" <?php selected($it['vat'],9); ?>>9%</option>
                                <option value="0" <?php selected($it['vat'],0); ?>>0%</option>
                            </select>
                        </td>
                        <td class="subtotal">€ 0.00</td>
                        <td><button type="button" class="remove-item button">X</button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <button type="button" id="add-offer-item" class="button">+ Item toevoegen</button>
            <div class="totals">
                <p>Subtotaal: <span id="subtotal">€ <?php echo number_format($offer->subtotal,2,",","."); ?></span></p>
                <p>BTW: <span id="vat">€ <?php echo number_format($offer->vat,2,",","."); ?></span></p>
                <p><strong>Totaal: <span id="total">€ <?php echo number_format($offer->total,2,",","."); ?></span></strong></p>
            </div>
            <button type="button" class="prev button">⬅ Vorige</button>
            <button type="button" class="next button-primary">Volgende ➡</button>
        </fieldset>

        <!-- Stap 5: Rider -->
        <fieldset data-step="5" style="display:none;">
            <h2>🎤 Rider</h2>
            <textarea name="rider" rows="6"><?php echo esc_textarea($offer->rider); ?></textarea>
            <button type="button" class="prev button">⬅ Vorige</button>
            <button type="button" class="next button-primary">Volgende ➡</button>
        </fieldset>

        <!-- Stap 6: Bevestigen -->
        <fieldset data-step="6" style="display:none;">
            <h2>✅ Bevestigen</h2>
            <label>Notities</label><textarea name="notes"><?php echo esc_textarea($offer->notes); ?></textarea>
            <label>Voorwaarden</label><textarea name="terms"><?php echo esc_textarea($offer->terms); ?></textarea>
            <label>Korting (€)</label><input type="number" step="0.01" name="discount" value="<?php echo esc_attr($offer->discount); ?>">
            <label>Geldig tot</label><input type="date" name="valid_until" value="<?php echo esc_attr($offer->valid_until); ?>">
            <button type="button" class="prev button">⬅ Vorige</button>
            <button type="submit" class="button-primary">💾 Opslaan</button>
        </fieldset>
    </form>
</div>
