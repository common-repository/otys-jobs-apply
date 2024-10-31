<?php

$option = get_option($args['option_name']);


if (!empty($option)) {
    $keyed = array_combine($option, $option);
}

?>

<fieldset>
    <legend class="screen-reader-text">
        <span><?php _e('Select match criteria', 'otys-jobs-apply') ?></span>
    </legend>
    <?php
    if ($args['match_criteria']) :
        foreach ($args['match_criteria'] as $criteria_id => $criteria) :
            ?>
            <label>
                <input type="checkbox"
                value="<?php echo esc_attr($criteria_id) ?>"value="<?php echo esc_attr($criteria_id) ?>"value="<?php echo esc_attr($criteria_id) ?>"
                name="<?php echo esc_attr($args['option_name']) ?>[]" 
                <?php echo (!empty($option) && array_key_exists($criteria_id, $keyed)) ? esc_attr('checked') : '' ?>
                />

                <?php echo esc_html($criteria['name']) ?>
            </label><br>
            <?php
        endforeach;
    endif;
    
    if (isset($args['description'])) {
        echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
    }
    ?>
</fieldset>