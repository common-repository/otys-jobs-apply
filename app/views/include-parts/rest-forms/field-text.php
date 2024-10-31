<?php
$args['question'] = wp_parse_args(
    $args['question'],
    [
        'type' => 'text',
        'errors' => []
    ]
);

if (isset($args['question']['name'])) :
    ?>

    <input 
        id="<?php echo esc_attr($args['identifier']); ?>" 
        type="<?php echo esc_attr($args['question']['type']) ?>" 
        name="<?php echo esc_attr($args['question']['name']) ?>" 
        autocomplete="<?php echo esc_attr($args['question']['autoCompleteName']) ?>"
        <?php echo (!empty($args['question']['value']) ? 'value="' . esc_attr($args['question']['value']) . '"' : ''); ?>
        <?php echo ($args['question']['validation']['mandatory']) ? esc_attr('required') : '' ?>
    />

    <?php
endif;
?>