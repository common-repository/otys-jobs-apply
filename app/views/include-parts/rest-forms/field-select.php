<?php
$args['question'] = wp_parse_args(
    $args['question'],
    [
        'multiple' => false,
        'answers' => []
    ]
);

$multiple = ($args['question']['type'] === "multiselect") ? true : false;

if (isset($args['question']['name']) && !empty($args['question']['answers'])) :
    if ($multiple) :
    ?>
    
    <select id="<?php echo esc_attr($args['identifier']); ?>" name="<?php echo esc_attr($args['question']['name']) ?>[]" multiple <?php echo ($args['question']['validation']['mandatory']) ? esc_attr('required') : '' ?> autocomplete="<?php echo $args['question']['autoCompleteName']; ?>">
        <?php
        foreach ($args['question']['answers'] as $answerId => $answer) :
            ?>

            <option value="<?php echo esc_attr($answerId) ?>" <?php echo (isset($args['question']['value']) && is_array($args['question']['value']) ? (in_array($answerId, $args['question']['value']) ? esc_attr('selected') : '') : '') ?>><?php echo esc_attr($answer['answer']) ?></option>

            <?php
        endforeach;
        ?>
    </select>
    
    <?php
    else :
    ?>

    <select id="<?php echo esc_attr($args['identifier']) ?>"  name="<?php echo esc_attr($args['question']['name']) ?>" <?php echo ($multiple) ? esc_attr('multiple') : '' ?> <?php echo ($args['question']['validation']['mandatory']) ? esc_attr('required') : '' ?> autocomplete="<?php echo $args['question']['autoCompleteName']; ?>">
        <?php
        foreach ($args['question']['answers'] as $answerId => $answer) :
            ?>

            <option value="<?php echo esc_attr($answerId) ?>" <?php echo (isset($args['question']['value']) && $args['question']['value'] == $answerId ? esc_attr('selected') : '') ?>><?php echo esc_html($answer['answer']) ?></option>

            <?php
        endforeach;
        ?>
    </select>

    <?php
    endif;
endif;
?>