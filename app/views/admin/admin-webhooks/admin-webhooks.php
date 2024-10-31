<?php

global $wp;

$args = wp_parse_args(
    $args,
    array(
        'webhooks_list' => [],
        'webhooks_logs' => false,
        'session' => false
    )
);

add_thickbox();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    if (WP_DEBUG) {
        echo '<form action="options.php" method="post">';
            settings_fields('otys_webhooks');
            do_settings_sections('otys_webhooks');
            submit_button('Save');
        echo '</form>';
    }
    ?>

    <h2><?php echo __('Registered webhooks', 'otys-jobs-apply'); ?></h2>
    
    <?php if (WP_DEBUG) : ?>
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <a href="<?php echo admin_url('admin.php?page=otys_webhooks&register_hooks=1') ?>" class="button"><?php _e('Register hooks', 'otys-jobs-apply'); ?></a>
        </div>
    </div>
    <?php endif; ?>
    <table class="wp-list-table widefat striped">
        <tbody>
            <thead>
                <th>uid</th>
                <th>service call</th>
                <th>webhook url</th>
                <th>callback method</th>
                <th>klant global</th>
            </thead>
            <?php
            if (!empty($args['webhooks_list'])) {
                foreach ($args['webhooks_list'] as $webhook) :
                    ?>

                    <tr>
                        <td><?php echo esc_html($webhook['uid']); ?></td>
                        <td><?php echo esc_html($webhook['serviceCall']); ?></td>
                        <td><?php echo esc_html($webhook['webhookUrl']); ?></td>
                        <td><?php echo ($webhook['registered']) ? esc_html(implode('::', $webhook['registered'])) : __('Not registered', 'otys-jobs-apply'); ?></td>
                        <td><?php echo ($webhook['klantGlobal']) ? 'true' : 'false'; ?></td>
                    </tr>

                    <?php
                endforeach;
            }
            ?>
        </tobdy>
    </table>

    <?php
    if (!empty($args['webhooks_logs']) && is_array($args['webhooks_logs'])) {
        ?>
        <h2><?php echo __('Logs', 'otys-jobs-apply') ?></h2>
        <table class="wp-list-table widefat striped">
            <tbody>
                <thead>
                    <th>id</th>
                    <th>method</th>
                    <th>url</th>
                    <th>response code</th>
                    <th>is processed</th>
                    <th>date added</th>
                    <td>webhook data</td>
                </thead>
                <?php
                    foreach ($args['webhooks_logs'] as $key => $webhook_log) :
                        $request = json_decode($webhook_log['request_data'], JSON_OBJECT_AS_ARRAY );
                        $response = json_decode($webhook_log['response_data'], JSON_OBJECT_AS_ARRAY );
                        ?>

                        <tr>
                            <td><?php echo esc_html($webhook_log['id']); ?></td>
                            <td><?php echo esc_html($request['request']['method']); ?></td>
                            <td><?php echo esc_html($webhook_log['webhook_url']); ?></td>
                            <td><?php echo esc_html($webhook_log['response_code']); ?></td>
                            <td><?php echo ($webhook_log['is_processed']) ? 'true' : 'false'; ?></td>
                            <td><?php echo esc_html($webhook_log['added']); ?></td>
                            <td>
                                <a href="#TB_inline?width=600&height=550&inlineId=modal-window-test-<?php echo esc_attr($key) ?>" class="thickbox button">
                                    <?php echo __('View', 'otys-jobs-apply') ?>
                                </a>
                            
                                <div id="modal-window-test-<?php echo esc_attr($key) ?>" style="display:none;">
                                    <div>
                                        <table class="wp-list-table widefat striped">
                                            <thead>
                                                <tr>
                                                    <th><?php _e('Response', 'otys-jobs-apply'); ?></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr>
                                                    <td><pre><?php echo esc_html(json_encode($response, JSON_PRETTY_PRINT)) ?></pre></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <br>
                                        <table class="wp-list-table widefat striped">
                                            <thead>
                                                <tr>
                                                    <th><?php _e('Request', 'otys-jobs-apply'); ?></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr>
                                                    <td><pre><?php echo esc_html(json_encode($request, JSON_PRETTY_PRINT)) ?></pre></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <?php
                    endforeach;
                ?>
            </tobdy>
        </table>
        <?php 
    }
    ?>
</div>