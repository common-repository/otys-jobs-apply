<?php

$args = wp_parse_args(
    $args,
    array(
        'logs' => false
    )
);

add_thickbox();

?>

<script>
    function otysConfirm(message) {
        let confirm = window.confirm(`<?php _e('Are you sure you want to delete', 'otys-plugin') ?> ${message}`);

        return confirm;
    }
</script>

<div class="wrap">
    <h1><?php _e('Logs', 'otys-jobs-apply'); ?></h1>

    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <a href="<?php echo admin_url('admin.php?page=otys_logs&action=delete_all') ?>" class="button" onclick="return otysConfirm('<?php _e('all logs', 'otys-jobs-apply') ?>')"><?php _e('Delete all logs', 'otys-jobs-apply') ?></a>
        </div>
    </div>

    <div class="otys-dashboard">
        <div class="otys-dashboard-w">
            <label>
                <?= __('Total data size', 'otys'); ?>
            </label>
            <div class="otys-dashboard-w-text">
                <?= $args['size']; ?>
            </div>
        </div>
    </div>

    <?php
    if ($args['logs']) {
    ?>

        <table class="wp-list-table widefat striped">
            <tbody>
                <thead>
                    <th><?php _e('ID', 'otys-jobs-apply') ?></th>
                    <th><?php _e('Method', 'otys-jobs-apply') ?></th>
                    <th><?php _e('Status', 'otys-jobs-apply') ?></th>
                    <th><?php _e('Message', 'otys-jobs-apply') ?></th>
                    <th><?php _e('Timestamp', 'otys-jobs-apply') ?></th>
                    <th><?php _e('Action', 'otys-jobs-apply') ?></th>
                </thead>

                <?php
                foreach ($args['logs'] as $key => $logs) :
                    $request = json_decode($logs['request'], JSON_OBJECT_AS_ARRAY);
                    $requestBody = json_decode($request['body'], JSON_OBJECT_AS_ARRAY);
                    $response = isset($logs['response']) ? json_decode($logs['response'], JSON_OBJECT_AS_ARRAY) : json_decode($logs, JSON_OBJECT_AS_ARRAY);
                    $responseBody = isset($response['body']) ? json_decode($response['body'], JSON_OBJECT_AS_ARRAY) : $response;
                    $error = [];

                    if (isset($responseBody['error'])) {
                        $errorCode = array_key_exists('code', $responseBody['error']) ? $responseBody['error']['code'] : 0;
                        $errorMessage = array_key_exists('message', $responseBody['error']) ? $responseBody['error']['message'] : 'Unknown error';
                        $error = new WP_Error($errorCode, $errorMessage);
                    }

                    if (isset($responseBody['errors'])) {
                        $error = new WP_Error(0, $responseBody['errors']);
                    }
                ?>
                    <tr class="code-<?php echo isset($response['response']['code']) ? esc_html($response['response']['code']) : esc_html('0'); ?>">
                        <td><?php echo wp_kses_post($logs['id']); ?></td>
                        <td>
                            <?php echo $requestBody['method'] !== 'Otys.Services.LanguageService.wrapRequest' ? esc_html($requestBody['method']) : ''; ?>
                            <?php
                            array_walk_recursive($requestBody['params'], function ($value, $key) {
                                if ($key === 'method') {
                                    echo '<div>' . wp_kses_post($value) . '</div>';
                                }
                            });
                            ?>
                        </td>
                        <td><?php echo isset($response['response']['code']) ? esc_html($response['response']['code']) : esc_html('0'); ?></td>
                        <td><?php echo is_wp_error($error) ? __('ERROR', 'otys-jobs-apply') : 'OK'; ?></td>
                        <td><?php echo esc_html(date('d/m/Y h:i:s',$logs['timestamp'])); ?></td>
                        <td>
                            <a href="#TB_inline?width=600&height=550&inlineId=modal-window-test-<?php echo esc_attr($key) ?>" class="thickbox button">
                                <?php echo __('View', 'otys-jobs-apply') ?>
                            </a>

                            <div id="modal-window-test-<?php echo esc_attr($key) ?>" style="display:none;">
                                <div>
                                    <table class="wp-list-table widefat striped">
                                        <thead>
                                            <tr>
                                                <th><?php _e('Request', 'otys-jobs-apply'); ?></th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr>
                                                <td>
                                                    <pre><?php echo esc_html(json_encode($requestBody, JSON_PRETTY_PRINT)) ?></pre>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <br>
                                    <table class="wp-list-table widefat striped">
                                        <thead>
                                            <tr>
                                                <th><?php _e('Response', 'otys-jobs-apply'); ?></th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr>
                                                <td>
                                                    <pre><?php echo esc_html(json_encode($responseBody, JSON_PRETTY_PRINT)) ?></pre>
                                                </td>
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
        $page_links = paginate_links(array(
            'base' => add_query_arg('pagenum', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;', 'text-domain'),
            'next_text' => __('&raquo;', 'text-domain'),
            'total' => $args['pagination']['total_pages'],
            'current' => $args['pagination']['current_page']
        ));
        if ($page_links) {
            echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
        }
    } else {
        _e('There are currently no logs', 'otys-jobs-apply');
    }
    ?>
</div>