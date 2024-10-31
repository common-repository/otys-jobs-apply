<?php
/**
 * Template used for [otys-vacancies-geo-search]
 *
 * @since 1.0.0
 */
 ?>
<div class="vacancies-geo-search-section">
    <h2>
        <?php echo _e('Maximum distance', 'otys-jobs-apply') ?>
    </h2>
    <form class="owp-geo-search-form" action="<?php echo esc_url($args['action']) ?>" method="GET">
        <?php
        foreach ($args['selectedParameters'] as $paramName => $paramValue) {
            ?>
            <input type="hidden" name="<?php echo esc_attr($paramName) ?>" value="<?php echo esc_attr($paramValue) ?>" />
            <?php
        }
        ?>

        <?php
        if ($args['search']) {
            ?>
            <input type="hidden" name="search" value="<?php echo esc_attr($args['search']) ?>" />
            <?php
        }
        ?>

        <input class="owp-geo-search-input" type="text" name="pc"
            placeholder="<?php _e('Postal code', 'otys-jobs-apply'); ?>"
            value="<?php echo esc_attr($args['postalCode']) ?>" required />

        <div class="owp-geo-search-range owp-range-slider-container">
            <input type="range" min="<?php echo esc_attr($args['minDistance']); ?>"
                max="<?php echo esc_attr($args['maxDistance']); ?>" value="<?php echo esc_attr($args['distance']); ?>"
                step="<?php echo esc_attr($args['steps']); ?>" class="owp-range-slider" id="owp-geo-search-range"
                name="pcm" required />
            <span class="owp-range-slider-value">
                <?php echo esc_html($args['distance']); ?>
            </span>
        </div>

        <?php if ($args['useCountrySelect'] === true) { ?>
            <div class="owp-geo-search-country">
                <select name="plo">
                    <?php foreach ($args['countries'] as $countryCode => $countryName) { ?>
                        <option value="<?php echo $countryCode; ?>" <?php echo ($args['country'] === $countryCode ? 'selected' : ''); ?>>
                            <?php echo $countryName; ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        <?php } ?>

        <div class="owp-buttoncontainer">
            <?php
            if ($args['postalCode'] != '') {
                ?>
                <a href="<?php echo $args['resetUrl']; ?>" class="owp-search-remove"><?php echo __('Reset', 'otys-jobs-apply'); ?></a>
                <?php
            }
            ?>
            <button class="button owp-search-button" type="submit">
                <?php _e('Search', 'otys-jobs-apply'); ?>
            </button>
        </div>
    </form>
</div>