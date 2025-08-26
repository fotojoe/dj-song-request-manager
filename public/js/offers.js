jQuery(function($){

    /**
     * Wizard initialiseren (nieuw of edit)
     */
    function initWizard(formId){
        const $form = $(formId);
        const $steps = $form.find("fieldset");
        if(!$steps.length) return; // geen wizard gevonden

        let currentStep = 0;
        $steps.hide().eq(0).show();

        function showStep(i){
            $steps.hide().eq(i).fadeIn();
            $("#offer-progress .step").removeClass("active completed");
            $("#offer-progress .step").each(function(index){
                if(index < i){ $(this).addClass("completed").html("✔"); }
                if(index === i){ $(this).addClass("active"); }
            });
            currentStep = i;
        }

        // Validatie voor stap
        function validateStep(stepIndex){
            let valid = true;
            $steps.eq(stepIndex).find("input, select, textarea").each(function(){
                let $field = $(this);
                let $error = $field.next(".field-error");

                if(this.hasAttribute("required") && !$field.val()){
                    $field.css("border","2px solid red");
                    if(!$error.length){
                        $field.after("<div class='field-error' style='color:red;font-size:12px;margin-top:4px;'>Dit veld is verplicht</div>");
                    }
                    valid = false;
                } else if($field.attr("type")==="email" && $field.val()){
                    // check email regex
                    let regex = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
                    if(!regex.test($field.val())){
                        $field.css("border","2px solid red");
                        if(!$error.length){
                            $field.after("<div class='field-error' style='color:red;font-size:12px;margin-top:4px;'>Ongeldig e-mailadres</div>");
                        }
                        valid = false;
                    } else {
                        $field.css("border","");
                        $error.remove();
                    }
                } else {
                    $field.css("border","");
                    $error.remove();
                }
            });
            return valid;
        }

        // Volgende stap
        $form.on("click", ".next", function(){
            if(validateStep(currentStep)){
                if(currentStep < $steps.length-1) showStep(currentStep+1);
            }
        });

        // Vorige stap
        $form.on("click", ".prev", function(){
            if(currentStep > 0) showStep(currentStep-1);
        });
    }

    // Init wizard voor beide formulieren
    initWizard("#offerWizard");
    initWizard("#offerEditForm");

    /**
     * Items toevoegen/verwijderen
     */
    $(document).on("click", "#add-offer-item", function(e){
        e.preventDefault();
        var $tbody = $('#offer-items tbody');
        var index = $tbody.find('tr').length;
        var newRow = `
            <tr>
                <td><input type="text" name="items[${index}][item]" required></td>
                <td><input type="number" name="items[${index}][qty]" value="1" min="1" required></td>
                <td><input type="number" step="0.01" name="items[${index}][price]" value="0.00" required></td>
                <td>
                    <select name="items[${index}][vat]">
                        <option value="21">21%</option>
                        <option value="9">9%</option>
                        <option value="0">0%</option>
                    </select>
                </td>
                <td class="subtotal">€ 0.00</td>
                <td><button type="button" class="remove-item button">X</button></td>
            </tr>`;
        $tbody.append(newRow);
    });

    $(document).on("click", ".remove-item", function(e){
        e.preventDefault();
        $(this).closest('tr').remove();
        calculateTotals();
    });

    /**
     * Totals berekenen
     */
    $(document).on("input change", "#offer-items input, #offer-items select", calculateTotals);

    function calculateTotals(){
        var subtotal = 0, vatTotal = 0;
        $('#offer-items tbody tr').each(function(){
            var qty   = parseFloat($(this).find("input[name*='[qty]']").val()) || 0;
            var price = parseFloat($(this).find("input[name*='[price]']").val()) || 0;
            var vat   = parseFloat($(this).find("select[name*='[vat]']").val()) || 0;

            var lineSubtotal = qty * price;
            var lineVat = lineSubtotal * (vat/100);

            subtotal += lineSubtotal;
            vatTotal += lineVat;

            $(this).find('.subtotal').text("€ " + lineSubtotal.toFixed(2));
        });

        var total = subtotal + vatTotal;
        $('#subtotal').text("€ " + subtotal.toFixed(2));
        $('#vat').text("€ " + vatTotal.toFixed(2));
        $('#total').text("€ " + total.toFixed(2));
    }

    /**
     * AJAX submit voor nieuwe + edit formulieren
     */
    $(document).on("submit", "#offerWizard, #offerEditForm", function(e){
        e.preventDefault();

        // Controle: minstens 1 item
        if($('#offer-items tbody tr').length === 0){
            alert("Voeg minstens één item toe aan de offerte.");
            return;
        }

        var $btn = $(this).find("button[type=submit]");
        $btn.prop("disabled", true).text("Bezig met opslaan...");

        var formData = $(this).serialize();
        $.post(dj_srm_ajax.url, formData, function(response){
            $btn.prop("disabled", false).text("Opslaan");

            if(response && response.success){
                showToast(response.data.message, "success");
                if(response.data.admin_link){
                    setTimeout(function(){
                        window.location.href = response.data.admin_link;
                    }, 1200);
                }
            } else {
                showToast("Opslaan mislukt.", "error");
            }
        });
    });

    /**
     * Toast notificatie
     */
    function showToast(msg, type="info"){
        var $toast = $("<div class='dj-toast "+type+"'>"+msg+"</div>");
        $("body").append($toast);
        setTimeout(function(){ $toast.addClass("show"); }, 100);
        setTimeout(function(){ $toast.removeClass("show").fadeOut(500, ()=> $toast.remove()); }, 3000);
    }

});

jQuery(function($){
    $(document).on("click", ".delete-offer", function(e){
        e.preventDefault();
        if(!confirm("Weet je zeker dat je deze offerte wilt verwijderen?")) return;

        var id = $(this).data("id");
        var nonce = $(this).data("nonce");

        $.post(dj_srm_ajax.url, {
            action: "dj_srm_delete_offer",
            id: id,
            _ajax_nonce: nonce
        }, function(response){
            if(response.success){
                alert(response.data.message);
                window.location.href = response.data.redirect;
            } else {
                alert(response.data.message || "Verwijderen mislukt.");
            }
        });
    });
});
