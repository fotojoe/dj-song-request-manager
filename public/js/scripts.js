/**
 * DJ Song Request Manager – Scripts
 * Hier GEEN offertes meer, alleen algemene dashboard functies
 */
jQuery(function($){

    // Status wijziging requests (DJ-portal)
    $(document).on('change', '.dj-request-status-select', function(){
        var id = $(this).data('id');
        var status = $(this).val();
        $.post(dj_srm_ajax.url, {
            action: 'dj_srm_update_request_status',
            id: id,
            status: status
        }, function(response){
            if(response.success){ alert(response.data.message); location.reload(); }
        });
    });

    // Dashboard tabbladen
    function activateTab(tabId){
        $('.dj-srm-tabs button').removeClass('active');
        $('.dj-srm-tabs button[data-tab="'+tabId+'"]').addClass('active');
        $('.dj-srm-tab-content').removeClass('active');
        $('#'+tabId).addClass('active');
        $('.dj-srm-tabs-mobile').val(tabId);
    }
    $(document).on('click', '.dj-srm-tabs button', function(){ activateTab($(this).data('tab')); });
    $(document).on('change', '.dj-srm-tabs-mobile', function(){ activateTab($(this).val())); });
});
