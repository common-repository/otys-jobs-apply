<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()) ?></h1>
<?php

if ($args['is_enabled'] === false) {
    ?>
        <p><?php _e('Job alert is not enabled for your client. If you want to use job alerts please contact support.', 'otys-jobs-apply') ?></p>
    <?php
} else {
?>
    <form action="options.php" method="post">
        <?php
        settings_fields('otys_jobalert');

        do_settings_sections('otys_jobalert');
        submit_button(__('Save changes', 'otys-jobs-apply'));
        ?>
    </form>
    
    <?php
}
?>
</div>