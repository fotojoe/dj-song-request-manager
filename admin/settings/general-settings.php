<?php
if ( ! defined('ABSPATH') ) exit;

function dj_srm_render_settings_page(){
    if ( ! current_user_can('manage_options') ) wp_die('Geen rechten.');
    $S = DJ_SRM_Settings::instance();

    if ($_SERVER['REQUEST_METHOD']==='POST' && check_admin_referer('dj_srm_save_settings','dj_srm_nonce')) {
        $colors = [
            'ui.color.primary','ui.color.secondary','ui.color.accent','ui.color.surface',
            'ui.color.text','ui.color.link','ui.color.success','ui.color.warning','ui.color.danger'
        ];
        foreach($colors as $k){
            $v = isset($_POST[$k]) ? sanitize_hex_color($_POST[$k]) : '';
            $S->set($k, $v ?: '');
        }
        $S->set('ui.font.body',     sanitize_text_field($_POST['ui.font.body'] ?? 'system-ui'));
        $S->set('ui.font.headings', sanitize_text_field($_POST['ui.font.headings'] ?? 'inherit'));
        $S->set('ui.mode',          in_array($_POST['ui.mode'] ?? 'auto',['auto','light','dark'],true) ? $_POST['ui.mode'] : 'auto');
        $S->set('ui.radius',        max(0, (int)($_POST['ui.radius'] ?? 12)));
        $S->set('ui.container.max', sanitize_text_field($_POST['ui.container.max'] ?? '1100px'));

        $logo_id = isset($_POST['ui.logo_id']) ? (int)$_POST['ui.logo_id'] : 0;
        $S->set('ui.logo_id', $logo_id);

        $S->set('ui.header.html', wp_kses_post($_POST['ui.header.html'] ?? ''));
        $S->set('ui.footer.html', wp_kses_post($_POST['ui.footer.html'] ?? ''));
        $S->set('email.header.html', wp_kses_post($_POST['email.header.html'] ?? ''));
        $S->set('email.footer.html', wp_kses_post($_POST['email.footer.html'] ?? ''));

        $S->set('org.name',   sanitize_text_field($_POST['org.name'] ?? ''));
        $S->set('org.phone',  sanitize_text_field($_POST['org.phone'] ?? ''));
        $S->set('org.email',  sanitize_email($_POST['org.email'] ?? ''));
        $S->set('org.site',   esc_url_raw($_POST['org.site'] ?? ''));
        $S->set('social.facebook', esc_url_raw($_POST['social.facebook'] ?? ''));
        $S->set('social.instagram', esc_url_raw($_POST['social.instagram'] ?? ''));
        $S->set('social.tiktok',    esc_url_raw($_POST['social.tiktok'] ?? ''));

        $S->log('settings_saved', ['by'=>get_current_user_id()]);
        echo '<div class="updated"><p>Instellingen opgeslagen.</p></div>';
    }

    $get = fn($k,$d='') => $S->get($k,$d);
    $logo_id  = (int)$get('ui.logo_id', 0);
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id,'medium') : '';
    ?>
    <div class="wrap dj-srm-dashboard">
        <h1>DJ SRM • Instellingen</h1>

        <select class="dj-srm-tabs-mobile">
            <option value="tab-branding">Branding</option>
            <option value="tab-colors">Kleuren & Theme</option>
            <option value="tab-typography">Lettertypes</option>
            <option value="tab-header">Header & Footer</option>
            <option value="tab-emails">E-mail / PDF</option>
        </select>

        <div class="dj-srm-tabs">
            <button class="active" data-tab="tab-branding">Branding</button>
            <button data-tab="tab-colors">Kleuren & Theme</button>
            <button data-tab="tab-typography">Lettertypes</button>
            <button data-tab="tab-header">Header & Footer</button>
            <button data-tab="tab-emails">E-mail / PDF</button>
        </div>

        <form method="post">
            <?php wp_nonce_field('dj_srm_save_settings','dj_srm_nonce'); ?>

            <div id="tab-branding" class="dj-srm-tab-content active">
                <h2>Branding</h2>
                <table class="form-table">
                    <tr>
                        <th>Logo</th>
                        <td>
                            <div style="display:flex;gap:12px;align-items:center;">
                                <img id="dj-logo-preview" src="<?php echo esc_url($logo_url); ?>" style="max-height:60px;<?php echo $logo_url?'':'display:none'; ?>">
                                <input type="hidden" name="ui.logo_id" id="dj-logo-id" value="<?php echo esc_attr($logo_id); ?>">
                                <button class="button" id="dj-upload-logo">Kies/Upload</button>
                                <button class="button" id="dj-remove-logo" <?php echo $logo_id?'':'style="display:none"'; ?>>Verwijderen</button>
                            </div>
                        </td>
                    </tr>
                    <tr><th>Naam organisatie</th><td><input type="text" name="org.name" value="<?php echo esc_attr($get('org.name','DJ’s Oostboys')); ?>" class="regular-text"></td></tr>
                    <tr><th>Telefoon</th><td><input type="text" name="org.phone" value="<?php echo esc_attr($get('org.phone','')); ?>" class="regular-text"></td></tr>
                    <tr><th>E-mail</th><td><input type="email" name="org.email" value="<?php echo esc_attr($get('org.email','')); ?>" class="regular-text"></td></tr>
                    <tr><th>Website</th><td><input type="url" name="org.site" value="<?php echo esc_attr($get('org.site', home_url() )); ?>" class="regular-text"></td></tr>
                    <tr><th>Facebook</th><td><input type="url" name="social.facebook" value="<?php echo esc_attr($get('social.facebook','')); ?>" class="regular-text"></td></tr>
                    <tr><th>Instagram</th><td><input type="url" name="social.instagram" value="<?php echo esc_attr($get('social.instagram','')); ?>" class="regular-text"></td></tr>
                    <tr><th>TikTok</th><td><input type="url" name="social.tiktok" value="<?php echo esc_attr($get('social.tiktok','')); ?>" class="regular-text"></td></tr>
                </table>
            </div>

            <div id="tab-colors" class="dj-srm-tab-content">
                <h2>Kleuren & Theme</h2>
                <table class="form-table">
                    <?php
                    $color_fields = [
                        'ui.color.primary'   => 'Primair (knoppen/links)',
                        'ui.color.secondary' => 'Secundair',
                        'ui.color.accent'    => 'Accent',
                        'ui.color.surface'   => 'Achtergrond (surface)',
                        'ui.color.text'      => 'Tekstkleur',
                        'ui.color.link'      => 'Linkkleur',
                        'ui.color.success'   => 'Succes',
                        'ui.color.warning'   => 'Waarschuwing',
                        'ui.color.danger'    => 'Fout/Gevaren'
                    ];
                    foreach($color_fields as $k=>$label){
                        $v = $get($k, '');
                        echo '<tr><th>'.$label.'</th><td><input type="text" class="dj-color" name="'.$k.'" value="'.esc_attr($v).'" data-default-color="" /></td></tr>';
                    }
                    ?>
                    <tr>
                        <th>Modus</th>
                        <td>
                            <?php $mode = $get('ui.mode','auto'); ?>
                            <label><input type="radio" name="ui.mode" value="auto"  <?php checked($mode,'auto'); ?>> Auto</label>&nbsp;&nbsp;
                            <label><input type="radio" name="ui.mode" value="light" <?php checked($mode,'light'); ?>> Licht</label>&nbsp;&nbsp;
                            <label><input type="radio" name="ui.mode" value="dark"  <?php checked($mode,'dark'); ?>> Donker</label>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="tab-typography" class="dj-srm-tab-content">
                <h2>Lettertypes</h2>
                <table class="form-table">
                    <tr><th>Body-font</th><td><input type="text" name="ui.font.body" value="<?php echo esc_attr($get('ui.font.body','Roboto')); ?>" class="regular-text"></td></tr>
                    <tr><th>Koppen-font</th><td><input type="text" name="ui.font.headings" value="<?php echo esc_attr($get('ui.font.headings','Poppins')); ?>" class="regular-text"></td></tr>
                    <tr><th>Hoekradius (px)</th><td><input type="number" min="0" name="ui.radius" value="<?php echo esc_attr($get('ui.radius', 12)); ?>" style="width:90px"></td></tr>
                    <tr><th>Max. breedte</th><td><input type="text" name="ui.container.max" value="<?php echo esc_attr($get('ui.container.max','1100px')); ?>" class="regular-text"></td></tr>
                </table>
            </div>

            <div id="tab-header" class="dj-srm-tab-content">
                <h2>Header & Footer (frontend)</h2>
                <table class="form-table">
                    <tr><th>Header HTML</th><td>
<textarea name="ui.header.html" rows="6" class="large-text"><?php
echo esc_textarea($get('ui.header.html','<div class="dj-header"><a href="{site_url}">[logo] {site_name}</a></div>'));
?></textarea></td></tr>
                    <tr><th>Footer HTML</th><td>
<textarea name="ui.footer.html" rows="6" class="large-text"><?php
echo esc_textarea($get('ui.footer.html','<div class="dj-footer">&copy; '.date('Y').' {site_name} • <a href="mailto:{org_email}">{org_email}</a></div>'));
?></textarea></td></tr>
                </table>
            </div>

            <div id="tab-emails" class="dj-srm-tab-content">
                <h2>E-mail / PDF</h2>
                <table class="form-table">
                    <tr><th>E-mail header</th><td>
<textarea name="email.header.html" rows="5" class="large-text"><?php
echo esc_textarea($get('email.header.html','<table width="100%" cellpadding="0" cellspacing="0" style="background:{primary};color:#fff;"><tr><td style="padding:16px"><strong>{site_name}</strong></td></tr></table>'));
?></textarea></td></tr>
                    <tr><th>E-mail footer</th><td>
<textarea name="email.footer.html" rows="5" class="large-text"><?php
echo esc_textarea($get('email.footer.html','<p style="font-size:12px;color:#666">Volg ons: <a href="{facebook}">Facebook</a> • <a href="{instagram}">Instagram</a></p>'));
?></textarea></td></tr>
                </table>
            </div>

            <p><button type="submit" class="button button-primary">Opslaan</button></p>
        </form>
    </div>
    <?php
}

add_action('admin_menu', function(){
    add_submenu_page(
        'dj-srm-dashboard',
        'Instellingen',
        'Instellingen',
        'manage_options',
        'dj-srm-settings',
        'dj_srm_render_settings_page'
    );
});

add_action('admin_enqueue_scripts', function(){
    if ( isset($_GET['page']) && $_GET['page']==='dj-srm-settings' ){
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();
        wp_enqueue_script('wp-color-picker');
        wp_add_inline_script('wp-color-picker', "
            jQuery(function($){
                function showTab(id){ $('.dj-srm-tabs button').removeClass('active'); $('.dj-srm-tabs button[data-tab='+id+']').addClass('active'); $('.dj-srm-tab-content').removeClass('active'); $('#'+id).addClass('active'); }
                $('.dj-srm-tabs button').on('click', function(e){ e.preventDefault(); showTab($(this).data('tab')); $('.dj-srm-tabs-mobile').val($(this).data('tab')); });
                $('.dj-srm-tabs-mobile').on('change', function(){ showTab($(this).val()); });
                $('.dj-color').wpColorPicker();

                var frame;
                $('#dj-upload-logo').on('click', function(e){
                    e.preventDefault();
                    if(frame){ frame.open(); return; }
                    frame = wp.media({ title:'Kies logo', button:{text:'Gebruik logo'}, multiple:false });
                    frame.on('select', function(){
                        var att = frame.state().get('selection').first().toJSON();
                        $('#dj-logo-id').val(att.id);
                        $('#dj-logo-preview').attr('src', att.url).show();
                        $('#dj-remove-logo').show();
                    });
                    frame.open();
                });
                $('#dj-remove-logo').on('click', function(e){ e.preventDefault(); $('#dj-logo-id').val('0'); $('#dj-logo-preview').hide(); $(this).hide(); });
            });
        ");
    }
});
