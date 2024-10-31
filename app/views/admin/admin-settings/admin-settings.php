<?php

global $wp_settings_sections;

$args = wp_parse_args($args, [
    'tabs' => []
]);

// check if the user have submitted the settings
// WordPress will add the "settings-updated" $_GET parameter to the url
if (isset($_GET['settings-updated'])) {
    // add settings saved message with the class of "updated"
    add_settings_error('otys_messages', 'otys_message', __('Settings Saved', 'otys-jobs-apply'), 'updated');
}

// show error / update messsages
settings_errors('otys_messages');
?>

    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()) ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('otys_settings');
            ?>
            <div class="swiper-pagination nav-tab-wrapper"></div>
            <div class="otys-settings-tabs-container swiper">
                <div class="otys-settings-tabs swiper-wrapper">
                    <?php
                    foreach ($args['tabs'] as $tab => $section) {
                        ?>
                        <div class="otys-setting-tab swiper-slide">
                            <div class="otys-settings-tab-inner">
                                <?php
                                do_settings_sections($section['slug']);
                                submit_button(__('Save changes', 'otys-jobs-apply'));
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <input id="otys-ajax-form" type="hidden" name="ajax" value="true" />
        </form>
    </div>

<script>
    {
        'use strict';

        let otys_settings_tabs = <?php echo json_encode($args['tabs']);?>;
        let settings = {
            slidesPerView: 1,
            pagination: {
            el: '.swiper-pagination',
                clickable: true,
                renderBullet: function (index, className) {
                    let tab = otys_settings_tabs[Object.keys(otys_settings_tabs)[index]];
                    let disabled = tab.disabled ? 'disabled' : '';
                    return '<span class="nav-tab ' + className + '" '+ disabled + ' data-actions="'+ tab.actions +'">' + tab.name + '</span>';
                },
                bulletActiveClass: 'nav-tab-active'
            },
            simulateTouch: false,
        }

        let initialSlide = null;

        if ((initialSlide = window.localStorage.getItem('swiper-index')) !== null) {
            settings.initialSlide = initialSlide;
        }

        const swiper = new Swiper('.swiper', settings);

        swiper.on('slideChange', (slider) => {
            window.localStorage.setItem('swiper-index', slider.activeIndex);
        });
    }
</script>