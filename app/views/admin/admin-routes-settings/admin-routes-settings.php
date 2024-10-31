<?php

// check if the user have submitted the settings
// WordPress will add the "settings-updated" $_GET parameter to the url
if (isset($_GET['settings-updated'])) {
    // add settings saved message with the class of "updated"
    add_settings_error('otys_messages', 'otys_message', __('Settings Saved', 'otys-jobs-apply'), 'updated');
}

// show error / update messsages
settings_errors('otys_messages');

echo '
    <div class="wrap">
        <h1>' . esc_html(get_admin_page_title()) . '</h1>
        <form action="options.php" method="post">';
            settings_fields('otys_routes_settings');
            do_settings_sections('otys_routes_settings');
            submit_button(__('Save changes', 'otys-jobs-apply'));
        echo '</form>
    </div>
';
