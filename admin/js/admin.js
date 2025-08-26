/**
 * DJ Song Request Manager – Admin JS
 * ----------------------------------
 * Doel:
 * - Zorgt dat het admin-dashboard interactief werkt
 * - Regelt tab-navigatie (desktop & mobiel)
 * - Klaar voor uitbreiding (bijv. live refresh, AJAX-data)
 */

(function($){
    'use strict';

    /**
     * 1. Functie: Tab wisselen
     * -------------------------
     * - Activeert het gekozen tabblad
     * - Synchroniseert desktop knoppen en mobiel dropdown
     */
    function activateTab(tabId) {
        // Zet knoppen juist
        $('.dj-srm-tabs button').removeClass('active');
        $('.dj-srm-tabs button[data-tab="'+tabId+'"]').addClass('active');

        // Zet content juist
        $('.dj-srm-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');

        // Sync dropdown
        $('.dj-srm-tabs-mobile').val(tabId);
    }

    /**
     * 2. Event listeners
     * -------------------
     * Klik op tab-knoppen (desktop)
     * Wissel via dropdown (mobiel)
     */
    $(document).on('click', '.dj-srm-tabs button', function(){
        activateTab($(this).data('tab'));
    });

    $(document).on('change', '.dj-srm-tabs-mobile', function(){
        activateTab($(this).val());
    });

    /**
     * 3. (Voorbereiding op uitbreiding)
     * ---------------------------------
     * Hier kun je later functies toevoegen zoals:
     * - AJAX refresh van statistieken
     * - Live binnenkomende requests
     * - Notificaties bij nieuwe polls/awards
     */
})(jQuery);
