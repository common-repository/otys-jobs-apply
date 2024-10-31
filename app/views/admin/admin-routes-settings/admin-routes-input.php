<?php

$args = wp_parse_args(
    $args,
    [
        'class' => 'regular-text',
        'value' => [],
        'description' => ''
    ]
);

$installedLanguages = wp_get_installed_translations('core');
$availableLanguagesCore = is_array($installedLanguages) && isset($installedLanguages['default']) ? array_keys($installedLanguages['default']) : [];
?>

<?php
foreach ($args['module_list'] as $moduleSlug => $module) {
    ?>

    <?php if (isset($module['description'])) {
    ?>
        <p>
            <?php echo esc_html($module['description']); ?>
        </p>
    <?php
    } ?>

    <div id="route-list-<?php echo esc_attr($moduleSlug) ?>" class="repeating-list">
        <div id="repeating-list-items-<?php echo esc_attr($moduleSlug) ?>">
            <?php
            if (!empty($args['value'][$moduleSlug])) {
            ?>
                <?php
                foreach ($args['value'][$moduleSlug] as $key => $route) {
                    ?>

                    <div id="<?php echo esc_attr($moduleSlug) ?>-item-<?php echo esc_attr($key) ?>" class="form-input repeating-item" data-key="<?php echo esc_attr($key) ?>">
                        <div class="repeating-item-col repeating-item-col--url">
                            <?= home_url('/'); ?>
                     
                            <input type="text" name="<?= $args['option_name'] . '[' . $moduleSlug . '][' . $key . '][slug]' ?>" value="<?= $route['slug']; ?>" />
                   
                            <?php 
                            if ($moduleSlug === 'vacancy') {
                                echo _x('/vacancy-slug', 'vacancy detail route example', 'otys-jobs-apply');
                            } 
                            ?>
                        </div>


                        <div class="repeating-item-col">
                            <?php
                            wp_dropdown_languages([
                                'name' => $args['option_name'] . '[' . $moduleSlug . '][' . $key . '][locale]',
                                'languages' => $availableLanguagesCore,
                                'selected' => (isset($route['locale'])) ? $route['locale'] : '',
                                'show_available_translations' => false
                            ]);
                            ?>
    
                            <button class="button action delete" data-key="<?php echo esc_attr($key) ?>"><?php _e('Delete', 'otys-jobs-apply') ?></button>
                        </div>
                    </div>

                <?php
                }
                ?>
            <?php
            } else {
            ?>
                <div class="otys-error-message">
                    <?php
                    if ($moduleSlug === 'vacancy-apply-thank-you') {
                        echo __('Optionally you can set the application thank you url. By default the thank you message will be shown on the same page as the application form.', 'otys-jobs-apply');
                    } else {
                        printf(
                            __('%s page is not set and is required for your the plugin to work properly.', 'otys-jobs-apply'),
                            $module['name']
                        );
                    }
                    ?>
                </div>
            <?php
            }
            ?>
            <template id="route-list-<?php echo esc_attr($moduleSlug) ?>-template">
                <div class="form-input repeating-item" data-key="${key}">
                    <div class="repeating-item-col repeating-item-col--url">
                        <?= home_url('/'); ?>

                        <?php

                        if ($moduleSlug === 'vacancy-apply-thank-you') {
                            ?>
                            <input type="text" name="<?= esc_attr($args['option_name']) . '[' . esc_attr($moduleSlug) . '][${key}][slug]' ?>" placeholder="thank-you" />
                            <?php
                        } else {
                            ?>

                            <input type="text" name="<?= esc_attr($args['option_name']) . '[' . esc_attr($moduleSlug) . '][${key}][slug]' ?>" placeholder="en/vacancies" />
                        
                            <?php 
                                if ($moduleSlug === 'vacancy') {
                                    echo _x('/vacancy-slug', 'vacancy detail route example', 'otys-jobs-apply');
                                } 
                            ?>
                        <?php
                        }
                        ?>
                    </div>

                    <div class="repeating-item-col">
                        <?php
                        wp_dropdown_languages([
                            'name' => esc_html($args['option_name']) . '[' . esc_html($moduleSlug) . '][${key}][locale]',
                            'languages' => $availableLanguagesCore,
                            'show_available_translations' => false
                        ]);
                        ?>

                        <button class="button action delete" data-key="${key}"><?php _e('Delete', 'otys-jobs-apply') ?></button>
                    </div>
                </div>
            </template>
        </div>

        <?php if ($args['description'] !== '') { ?>
            <p class="description">
                <?php echo $args['description']; ?>
            </p>
        <?php } ?>

        <div class="tablenav bottom">
            <button class="route-list-add button action" data-module="<?php echo esc_attr($moduleSlug) ?>"><?php _e('Add url', 'otys-jobs-apply') ?></button>
        </div>
    </div>
    <?php
}

?>