<?php
/**
 * Template: offer-single.php
 * Weergave offerte (admin + klantvriendelijk)
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'dj_srm_offers';

$offer_id = isset($offer_id) ? intval($offer_id) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $offer_id));

if(!$offer){
    echo "<div class='wrap'><h2>Offerte niet gevonden</h2></div>";
    return;
}

$is_admin = current_user_can('manage_options');
$nonce    = wp_create_nonce('dj_srm_nonce');

// Decode items
$items = json_decode($offer->items, true);
if(!is_array($items)) $items = [];

// Helper
function dj_srm_euro($val){ return "€ " . number_format((float)$val,2,",","."); }
?>

<div class="dj-offer-wrapper dj-offer-single wrap">

    <!-- Header -->
    <header class="dj-offer-header">
        <h1>📑 Offerte #<?php echo esc_html($offer->offer_number); ?></h1>
        <div class="meta">
            <span class="status-<?php echo esc_attr($offer->status); ?>">
                Status: <?php echo ucfirst(esc_html($offer->status)); ?>
            </span>
            <span class="valid">Geldig tot: <?php echo esc_html($offer->valid_until); ?></span>
        </div>

        <?php if($is_admin): ?>
        <div class="admin-actions">
            <a href="<?php echo esc_url(admin_url("post.php?post={$offer->id}&action=edit")); ?>" class="button">✏ Bewerken</a>
            <a href="<?php echo esc_url(admin_url("admin-ajax.php?action=dj_srm_offer_pdf&offer_id={$offer->id}&_wpnonce={$nonce}")); ?>" class="button">⬇ Download PDF</a>
            <a href="<?php echo esc_url(admin_url("admin-ajax.php?action=dj_srm_offer_email&offer_id={$offer->id}&_wpnonce={$nonce}")); ?>" class="button">📧 Verstuur per mail</a>
        </div>
        <?php endif; ?>
    </header>

    <!-- Blokken -->
    <section class="dj-blocks grid-2">
        <div class="dj-card">
            <h2>👤 Klant</h2>
            <p><strong>Naam:</strong> <?php echo esc_html($offer->client_name); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($offer->client_email); ?></p>
            <p><strong>Tel:</strong> <?php echo esc_html($offer->client_phone); ?></p>
        </div>

        <div class="dj-card">
            <h2>🎉 Event</h2>
            <p><strong>Type:</strong> <?php echo esc_html($offer->event_type); ?></p>
            <p><strong>Datum:</strong> <?php echo esc_html($offer->event_date); ?></p>
            <p><strong>Tijd:</strong> <?php echo esc_html($offer->start_time . " - " . $offer->end_time); ?></p>
            <p><strong>Gasten:</strong> <?php echo esc_html($offer->guest_count); ?></p>
            <p><strong>Locatie:</strong> <?php echo esc_html($offer->venue_street . " " . $offer->venue_number . ", " . $offer->venue_postcode . " " . $offer->venue_city); ?></p>
        </div>
    </section>

    <!-- Items -->
    <section class="dj-card">
        <h2>📝 Specificatie</h2>
        <table class="dj-offer-table widefat">
            <thead><tr><th>Omschrijving</th><th>Aantal</th><th>Prijs</th><th>BTW</th><th>Subtotaal</th></tr></thead>
            <tbody>
            <?php if($items): foreach($items as $row): 
                $line = $row['qty'] * $row['price']; ?>
                <tr>
                    <td data-label="Item"><?php echo esc_html($row['item']); ?></td>
                    <td data-label="Aantal"><?php echo esc_html($row['qty']); ?></td>
                    <td data-label="Prijs"><?php echo dj_srm_euro($row['price']); ?></td>
                    <td data-label="BTW"><?php echo esc_html($row['vat']); ?>%</td>
                    <td data-label="Subtotaal"><?php echo dj_srm_euro($line); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5">Geen items toegevoegd.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!-- Totals -->
    <section class="dj-offer-totals">
        <p><span>Subtotaal:</span> <?php echo dj_srm_euro($offer->subtotal); ?></p>
        <p><span>BTW:</span> <?php echo dj_srm_euro($offer->vat); ?></p>
        <?php if($offer->discount > 0): ?>
        <p><span>Korting:</span> -<?php echo dj_srm_euro($offer->discount); ?></p>
        <?php endif; ?>
        <p><strong>Totaal: <?php echo dj_srm_euro($offer->total); ?></strong></p>
    </section>

    <!-- Rider -->
    <?php if(!empty($offer->rider)): ?>
    <section class="dj-card">
        <h2>🎤 Rider</h2>
        <div class="rider-content">
            <?php echo wpautop(wp_kses_post($offer->rider)); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Notes & Terms -->
    <?php if(!empty($offer->notes) || !empty($offer->terms)): ?>
    <section class="dj-card">
        <h2>ℹ Extra informatie</h2>
        <?php if($offer->notes): ?>
            <h3>Notities</h3>
            <p><?php echo wpautop(wp_kses_post($offer->notes)); ?></p>
        <?php endif; ?>
        <?php if($offer->terms): ?>
            <h3>Voorwaarden</h3>
            <p><?php echo wpautop(wp_kses_post($offer->terms)); ?></p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- Klant-acties -->
    <section class="dj-offer-actions">
        <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" class="inline">
            <input type="hidden" name="action" value="dj_srm_update_offer_status">
            <input type="hidden" name="offer_id" value="<?php echo (int)$offer->id; ?>">
            <input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr($nonce); ?>">
            <button type="submit" name="status" value="geaccepteerd" class="button-primary">✅ Accepteren</button>
            <button type="submit" name="status" value="geweigerd" class="button">❌ Weigeren</button>
        </form>
    </section>

</div>
