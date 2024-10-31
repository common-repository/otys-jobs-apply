<?php

$option = get_option($args['option_name']);

?>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php _e('Select documents', 'otys-jobs-apply') ?></span>
    </legend>

    <select name="<?php echo esc_attr($args['option_name']) ?>">
        <option value="0"><?php echo __('Disabled', 'otys-jobs-apply'); ?></option>
    <?php
    foreach ($args['documents'] as $document) {
        ?>
            <option value="<?php echo esc_attr($document['uid']) ?>" <?php echo ($option == $document['uid'] ? esc_attr('selected') : '') ?>><?php echo esc_html($document['name']) ?></option>
        <?php
    }
    ?>
    </select>

    <?php
    if (isset($args['description'])) {
        echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
    }
    ?>
</fieldset>
