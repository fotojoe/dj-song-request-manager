<?php
/**
 * DJ Song Request Manager – Admin Dashboard
 *
 * Functie van dit bestand:
 * -------------------------
 * - Centrale startpagina in WordPress Admin.
 * - Toont statistieken in kaarten (events, requests, polls, awards).
 * - Bevat snelkoppelingen om nieuwe items toe te voegen.
 * - Tabs / menu om verschillende onderdelen te bekijken.
 * - Responsive: tabs voor desktop, dropdown voor mobiel.
 * - Voorbereid op toekomstige uitbreidingen (Top 5 nummers, logs, enz.).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

/**
 * 1. Statistieken ophalen
 * Tel records uit tabellen (kan uitgebreid worden met meer modules).
 */
$events_count   = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dj_srm_events" );
$requests_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dj_srm_requests" );
$polls_count    = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dj_srm_polls" );
$awards_count   = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dj_srm_awards" );

/**
 * 2. Laatste verzoeken ophalen
 * Beperk tot 5 resultaten voor snelheid.
 */
$last_requests = $wpdb->get_results("
    SELECT requester, artist, song, created_at 
    FROM {$wpdb->prefix}dj_srm_requests 
    ORDER BY created_at DESC LIMIT 5
");

/**
 * 3. (Extra voorbeeld) Top 5 populairste nummers
 * Op basis van het aantal aanvragen per song.
 */
$top_songs = $wpdb->get_results("
    SELECT song, artist, COUNT(*) as total 
    FROM {$wpdb->prefix}dj_srm_requests 
    GROUP BY song, artist 
    ORDER BY total DESC 
    LIMIT 5
");
?>

<div class="wrap dj-srm-dashboard">

    <!-- Titel + introductie -->
    <h1><span class="dashicons dashicons-format-audio"></span> DJ Song Request Manager</h1>
    <p class="intro">
        Welkom bij het DJ SRM Dashboard!  
        - Gebruik de <strong>snelkoppelingen</strong> om direct nieuwe items aan te maken.  
        - Bekijk de <strong>statistieken</strong> voor een snel overzicht.  
        - Navigeer via <strong>tabbladen</strong> naar de details.  
    </p>

    <!-- Snelkoppelingen -->
    <div class="quick-links">
        <a href="?page=dj-srm-events&tab=new" class="button button-primary">+ Nieuw Event</a>
        <a href="?page=dj-srm-requests" class="button">+ Nieuw Request</a>
        <a href="?page=dj-srm-polls&tab=new" class="button">+ Nieuwe Poll</a>
        <a href="?page=dj-srm-awards&tab=new" class="button">+ Nieuw Award</a>
    </div>

    <!-- Statistiekenkaarten -->
    <div class="card-grid">
        <div class="dj-srm-card"><h3>Events</h3><div class="stat"><?php echo intval($events_count); ?></div></div>
        <div class="dj-srm-card"><h3>Requests</h3><div class="stat"><?php echo intval($requests_count); ?></div></div>
        <div class="dj-srm-card"><h3>Polls</h3><div class="stat"><?php echo intval($polls_count); ?></div></div>
        <div class="dj-srm-card"><h3>Awards</h3><div class="stat"><?php echo intval($awards_count); ?></div></div>
    </div>

    <!-- Tabs (desktop) -->
    <nav class="dj-srm-tabs">
        <button class="active" data-tab="requests">Laatste verzoeken</button>
        <button data-tab="events">Komende events</button>
        <button data-tab="polls">Actieve polls</button>
        <button data-tab="awards">Awards</button>
        <button data-tab="top-songs">Top 5 Songs</button>
    </nav>

    <!-- Tabs (mobiel = dropdown) -->
    <select class="dj-srm-tabs-mobile">
        <option value="requests">Laatste verzoeken</option>
        <option value="events">Komende events</option>
        <option value="polls">Actieve polls</option>
        <option value="awards">Awards</option>
        <option value="top-songs">Top 5 Songs</option>
    </select>

    <!-- TAB: Requests -->
    <div class="dj-srm-tab-content active" id="requests">
        <h2>Laatste verzoeken</h2>
        <table class="widefat striped">
            <thead>
                <tr><th>Aanvrager</th><th>Artiest</th><th>Nummer</th><th>Datum</th></tr>
            </thead>
            <tbody>
            <?php if ( $last_requests ) : ?>
                <?php foreach ( $last_requests as $req ) : ?>
                    <tr>
                        <td><?php echo esc_html($req->requester); ?></td>
                        <td><?php echo esc_html($req->artist); ?></td>
                        <td><?php echo esc_html($req->song); ?></td>
                        <td><?php echo date_i18n( 'd-m-Y H:i', strtotime($req->created_at) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4">Nog geen verzoeken gevonden.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TAB: Events -->
    <div class="dj-srm-tab-content" id="events">
        <h2>Komende events</h2>
        <p>Hier verschijnt binnenkort een lijst van geplande events (te vullen via <code>class-events.php</code>).</p>
    </div>

    <!-- TAB: Polls -->
    <div class="dj-srm-tab-content" id="polls">
        <h2>Actieve polls</h2>
        <p>Hier verschijnen actieve polls zodra ze aangemaakt zijn.</p>
    </div>

    <!-- TAB: Awards -->
    <div class="dj-srm-tab-content" id="awards">
        <h2>Awards</h2>
        <p>Hier verschijnen toegekende awards zodra ze beschikbaar zijn.</p>
    </div>

    <!-- TAB: Top Songs -->
    <div class="dj-srm-tab-content" id="top-songs">
        <h2>Top 5 populairste nummers</h2>
        <table class="widefat striped">
            <thead>
                <tr><th>Artiest</th><th>Nummer</th><th>Aantal aanvragen</th></tr>
            </thead>
            <tbody>
            <?php if ( $top_songs ) : ?>
                <?php foreach ( $top_songs as $song ) : ?>
                    <tr>
                        <td><?php echo esc_html($song->artist); ?></td>
                        <td><?php echo esc_html($song->song); ?></td>
                        <td><?php echo intval($song->total); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="3">Nog geen populaire nummers gevonden.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- JavaScript voor tabs -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons  = document.querySelectorAll('.dj-srm-tabs button');
    const mobile   = document.querySelector('.dj-srm-tabs-mobile');
    const contents = document.querySelectorAll('.dj-srm-tab-content');

    function activateTab(tabId) {
        buttons.forEach(b => b.classList.toggle('active', b.dataset.tab === tabId));
        contents.forEach(c => c.classList.toggle('active', c.id === tabId));
        mobile.value = tabId;
    }

    buttons.forEach(btn => btn.addEventListener('click', () => activateTab(btn.dataset.tab)));
    mobile.addEventListener('change', () => activateTab(mobile.value));
});
</script>
