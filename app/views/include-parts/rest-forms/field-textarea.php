<?php
$args['question'] = wp_parse_args(
    $args['question'],
    array(
    )
);

if (isset($args['question']['name'])) :
    ?>

    <textarea id="<?php echo esc_attr($args['identifier']); ?>" type="textarea" name="<?php echo esc_attr($args['question']['name']) ?>" <?php echo ($args['question']['validation']['mandatory']) ? esc_attr('required') : '' ?> autocomplete="<?php echo $args['question']['autoCompleteName']; ?>"><?php echo (!empty($args['question']['value']) ?  esc_html($args['question']['value']) : ''); ?></textarea>

    <?php
endif;
?>