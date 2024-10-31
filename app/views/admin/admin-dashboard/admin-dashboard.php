<div class="wrap">
    <h1><?php _e('Admin Dashboard', 'otys-jobs-apply'); ?></h1>
    <p>
        <?php
        if (!isset($args['session']['user'])) {
            ?>
            <p>
                <?php _e('Currently the OTYS Plugin does not have a valid session. 
                Make sure to add a proper API Key', 'otys-jobs-apply'); ?>
            </p>
            <p>
                <?php echo '<a href="' . get_admin_url(null, 'admin.php?page=otys_settings') . '" class="wp-core-ui button-primary">'. __('Add API Key', 'otys-jobs-apply') . '</a>' ?>
            </p>
            <?php
        }
        ?>
    </p>
</div>