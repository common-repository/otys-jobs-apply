<div class="otys-login-form logged-in">
    <p>
        <?php echo sprintf(__('You are logged in as %s'), '<a href="'. $args['candidate_portal_url'] .'">' . $args['user']['full_name'] . '</a>'); ?>
    </p>

    <p>
        <a href="<?php echo $args['logout_url_with_redirect']; ?>"><?php echo __('Logout', 'otys-jobs-apply'); ?></a>
    </p>
</div>