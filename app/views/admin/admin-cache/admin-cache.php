<?php

$args = wp_parse_args(
    $args,
    array(
        'plugin_cache' => false
    )
);

?>

<script>
    function otysConfirmCacheDelete(message) {
        let confirm = window.confirm(`<?php _e('Are you sure you want to delete', 'otys-plugin') ?> ${message}`);

        return confirm;
    }
</script>

<div class="wrap">
    <h1><?php _e('Admin cache', 'otys-jobs-apply'); ?></h1>

    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <a href="<?php echo admin_url('admin.php?page=otys_cache&delete_cache=all') ?>" class="button" onclick="return otysConfirmCacheDelete('<?php _e('all cache', 'otys-jobs-apply') ?>')"><?php _e('Delete all cache', 'otys-jobs-apply') ?></a>
            <a href="<?php echo admin_url('admin.php?page=otys_cache&delete_cache=expired') ?>" class="button" onclick="return otysConfirmCacheDelete('<?php _e('all cache', 'otys-jobs-apply') ?>')"><?php _e('Delete expired cache', 'otys-jobs-apply') ?></a>
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
    if ($args['plugin_cache']) {
        ?>
        <table class="wp-list-table widefat striped">
            <tbody>
                <thead>
                    <th><?php _e('Transient name', 'otys-jobs-apply') ?></th>
                    <th><?php _e('Autoload', 'otys-jobs-apply') ?></th>
                    <th><?php _e('Expire date', 'otys-jobs-apply') ?></th>
                    <th><?php _e('Action', 'otys-jobs-apply') ?></th>
                </thead>

                <?php
                foreach ($args['plugin_cache'] as $cache) :
                    $timeLeftInSeconds = $cache['expire'] - time();
                    $date = date('d/m/Y H:i:s', $cache['expire']);
                ?>

                    <tr>
                        <td><?php echo esc_html($cache['name']); ?></td>
                        <td><?php echo esc_html($cache['autoload']); ?></td>
                        <td><?php echo ($timeLeftInSeconds <= 0) ? 'expired' : esc_html($date); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=otys_cache&delete_cache=' . esc_attr($cache['name'])) ?>" class="button" onclick="return otysConfirmCacheDelete('<?php echo esc_attr($cache['name']) ?>')"><?php _e('Delete', 'otys-jobs-apply') ?></a>
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
        _e('Currently there is no cache', 'otys-jobs-apply');
    }
    ?>
</div>