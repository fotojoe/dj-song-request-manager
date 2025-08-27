<?php
/**
 * Template: offer-form.php (Admin Wizard)
 * Alleen voor beheerders
 */
if ( ! current_user_can('manage_options') ) {
    wp_die(__('Je hebt geen rechten om offertes aan te maken.', 'dj-srm'));
}

// AJAX nonce voor veiligheid
$offer_nonce = wp_create_nonce('dj_srm_nonce');
?>

<div class="dj-srm-offer-form wrap">
    <h1>Nieuwe Offerte Wizard</h1>

    <!-- Progress bar -->
    <div id="offer-progress" class="dj-progressbar">
        <div class="step active" data-step="1">1<br><span>Klant</span></div>
        <div class="step" data-step="2">2<br><span>Event</span></div>
        <div class="step" data-step="3">3<br><span>Locatie</span></div>
        <div class="step" data-step="4">4<br><span>Items</span></div>
        <div class="step" data-step="5">5<br><span>Rider</span></div>
        <div class="step" data-step="6">6<br><span>Bevestigen</span></div>
    </div>

    <form id="offerWizard">
        <!-- AJAX action + nonce -->
        <input type="hidden" name="action" value="dj_srm_add_offer">
        <input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr($offer_nonce); ?>">

        <!-- Stap 1: Klant -->
        <fieldset data-step="1">
            <h2>👤 Klantgegevens</h2>
            <label>Naam</label>
            <input type="text" name="client_name" required>
            <label>Email</label>
            <input type="email" name="client_email" required>
            <label>Telefoon</label>
            <input type="text" name="client_phone" placeholder="+31 6 ...">
            <div class="nav-buttons">
                <button type="button" class="next button-primary">Volgende ➡</button>
            </div>
        </fieldset>

        <!-- Stap 2: Event -->
        <fieldset data-step="2" style="display:none;">
            <h2>🎉 Eventdetails</h2>
            <label>Soort Event</label>
            <select name="event_type" required>
                <option value="">-- Kies --</option>
                <option value="Bruiloft">💍 Bruiloft</option>
                <option value="Verjaardag">🎂 Verjaardag</option>
                <option value="Zakelijk">🏢 Zakelijk feest</option>
                <option value="Festival">🎪 Festival</option>
                <option value="Kinderfeest">🎈 Kinderfeest</option>
                <option value="Anders">❓ Anders</option>
            </select>
            <label>Datum</label><input type="date" name="event_date">
            <label>Starttijd</label><input type="time" name="start_time">
            <label>Eindtijd</label><input type="time" name="end_time">
            <label>Aantal gasten</label><input type="text" name="guest_count" placeholder="bijv. 50-75">
            <div class="nav-buttons">
                <button type="button" class="prev button">⬅ Vorige</button>
                <button type="button" class="next button-primary">Volgende ➡</button>
            </div>
        </fieldset>

        <!-- Stap 3: Locatie -->
        <fieldset data-step="3" style="display:none;">
            <h2>📍 Locatie</h2>
            <label>Type locatie</label>
            <select name="venue_type">
                <option value="commercieel">Commercieel</option>
                <option value="prive">Privé</option>
            </select>
            <label>Plaats</label><input type="text" name="venue_city">
            <label>Straat</label><input type="text" name="venue_street">
            <label>Huisnummer</label><input type="text" name="venue_number">
            <label>Postcode</label><input type="text" name="venue_postcode">
            <div class="nav-buttons">
                <button type="button" class="prev button">⬅ Vorige</button>
                <button type="button" class="next button-primary">Volgende ➡</button>
            </div>
        </fieldset>

        <!-- Stap 4: Items -->
        <fieldset data-step="4" style="display:none;">
            <h2>📝 Offerte-items</h2>
            <table id="offer-items" class="widefat striped">
                <thead>
                    <tr><th>Omschrijving</th><th>Aantal</th><th>Prijs (€)</th><th>BTW %</th><th>Subtotaal</th><th>Actie</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="items[0][item]" required></td>
                        <td><input type="number" name="items[0][qty]" value="1" min="1"></td>
                        <td><input type="number" step="0.01" name="items[0][price]" value="0.00"></td>
                        <td>
                            <select name="items[0][vat]">
                                <option value="21">21%</option>
                                <option value="9">9%</option>
                                <option value="0">0%</option>
                            </select>
                        </td>
                        <td class="subtotal">€ 0.00</td>
                        <td><button type="button" class="remove-item button">X</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" id="add-offer-item" class="button">+ Item toevoegen</button>

            <div class="totals">
                <p>Subtotaal: <span id="subtotal">€ 0.00</span></p>
                <p>BTW: <span id="vat">€ 0.00</span></p>
                <p><strong>Totaal: <span id="total">€ 0.00</span></strong></p>
            </div>

            <div class="nav-buttons">
                <button type="button" class="prev button">⬅ Vorige</button>
                <button type="button" class="next button-primary">Volgende ➡</button>
            </div>
        </fieldset>

        <!-- Stap 5: Rider -->
        <fieldset data-step="5" style="display:none;">
            <h2>🎤 Rider (techniek / hospitality)</h2>
            <textarea name="rider" rows="6" placeholder="Beschrijf techniek, eten/drinken, logistiek..."></textarea>
            <div class="nav-buttons">
                <button type="button" class="prev button">⬅ Vorige</button>
                <button type="button" class="next button-primary">Volgende ➡</button>
            </div>
        </fieldset>

        <!-- Stap 6: Bevestiging -->
        <fieldset data-step="6" style="display:none;">
            <h2>✅ Bevestig & Verstuur</h2>
            <label>Notities</label><textarea name="notes"></textarea>
            <label>Voorwaarden</label><textarea name="terms"></textarea>
            <label>Korting (€)</label><input type="number" step="0.01" name="discount" value="0.00">
            <label>Geldig tot</label><input type="date" name="valid_until">

            <div class="nav-buttons">
                <button type="button" class="prev button">⬅ Vorige</button>
                <button type="submit" class="button-primary">Opslaan & Versturen</button>
            </div>
        </fieldset>
    </form>

    <!-- Meldingen -->
    <div id="offer-message" style="margin-top:20px;"></div>
</div>

<script>
jQuery(function($){
    let currentStep = 1;

    function showStep(step){
        $("fieldset").hide();
        $("fieldset[data-step="+step+"]").show();
        $(".dj-progressbar .step").removeClass("active");
        $(".dj-progressbar .step[data-step="+step+"]").addClass("active");
        currentStep = step;
    }

    $(".next").on("click", function(){ showStep(currentStep+1); });
    $(".prev").on("click", function(){ showStep(currentStep-1); });

    // Items toevoegen/verwijderen
    $("#add-offer-item").on("click", function(){
        let rowCount = $("#offer-items tbody tr").length;
        let row = `<tr>
            <td><input type="text" name="items[${rowCount}][item]" required></td>
            <td><input type="number" name="items[${rowCount}][qty]" value="1" min="1"></td>
            <td><input type="number" step="0.01" name="items[${rowCount}][price]" value="0.00"></td>
            <td>
                <select name="items[${rowCount}][vat]">
                    <option value="21">21%</option>
                    <option value="9">9%</option>
                    <option value="0">0%</option>
                </select>
            </td>
            <td class="subtotal">€ 0.00</td>
            <td><button type="button" class="remove-item button">X</button></td>
        </tr>`;
        $("#offer-items tbody").append(row);
    });

    $(document).on("click",".remove-item", function(){
        $(this).closest("tr").remove();
        recalcTotals();
    });

    // Recalc totals
    $(document).on("input change","#offer-items input, #offer-items select", function(){
        recalcTotals();
    });

    function recalcTotals(){
        let subtotal=0, vat=0;
        $("#offer-items tbody tr").each(function(){
            let qty   = parseFloat($(this).find("input[name*='[qty]']").val()) || 0;
            let price = parseFloat($(this).find("input[name*='[price]']").val()) || 0;
            let vatRate = parseFloat($(this).find("select[name*='[vat]']").val()) || 0;
            let line = qty*price;
            let lineVat = line*(vatRate/100);
            subtotal += line;
            vat += lineVat;
            $(this).find(".subtotal").text("€ "+line.toFixed(2));
        });
        $("#subtotal").text("€ "+subtotal.toFixed(2));
        $("#vat").text("€ "+vat.toFixed(2));
        $("#total").text("€ "+(subtotal+vat).toFixed(2));
    }

    // AJAX Submit
    $("#offerWizard").on("submit", function(e){
        e.preventDefault();
        let formData = $(this).serialize();
        $("#offer-message").html("<div class='notice notice-info'><p>Bezig met opslaan...</p></div>");
        $.post(ajaxurl, formData, function(resp){
            if(resp.success){
                $("#offer-message").html("<div class='notice notice-success'><p>"+resp.data.message+"</p></div>");
                if(resp.data.redirect){ window.location.href = resp.data.redirect; }
            } else {
                $("#offer-message").html("<div class='notice notice-error'><p>Fout: "+resp.data.message+"</p></div>");
            }
        });
    });
});
</script>
