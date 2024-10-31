<?php
$args['question'] = wp_parse_args(
    $args['question'],
    [
        'type' => 'text'
    ]
);

if (isset($args['question']['name'])) :
    ?>

    <input
        id="<?php echo esc_attr($args['identifier']); ?>" 
        type="date"
        name="<?php echo esc_attr($args['question']['name']) ?>"
        <?php echo (!empty($args['question']['value']) ? 'value="' . esc_attr(date('Y-m-d', strtotime($args['question']['value']))) . '"' : ''); ?>
        <?php echo ($args['question']['validation']['mandatory']) ? esc_attr('required') : '' ?>
        autocomplete="<?php echo $args['question']['autoCompleteName']; ?>"
    />

    <?php
endif;