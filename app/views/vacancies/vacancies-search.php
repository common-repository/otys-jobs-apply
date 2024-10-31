<?php
/**
 * Template used for [otys-vacancies-search]
 *
 * @since 1.0.0
 */
 ?>
<div class="vacancies-search-section">
    <h2><?php echo _e('Search vacancies', 'otys-jobs-apply') ?></h2>
    <form class="owp-search-form" action="<?php echo esc_url($args['action']) ?>" method="POST"> 
        <?php
        if ($args['showKeywordSearch']) {
            ?>
            <input class="owp-search-input" type="text" name="search" placeholder="<?php _e('Keyword', 'otys-jobs-apply'); ?>" value="<?php echo esc_attr($args['search']) ?>" />
            <?php
        }
        ?>

        <?php
        foreach ($args['filters'] as $critSlug => $critData) {
            ?>
            <div class="owp-search-criteria">
                <select name="<?php echo esc_attr($critSlug); ?>[]" multiple>
                    <option disabled><?php echo $critData['name']; ?></option>
                    <?php
                    foreach ($critData['options'] as $critOptionSlug => $critOptionData) {
                        ?>

                        <option value="<?php echo esc_attr($critOptionSlug); ?>" <?php echo ($critOptionData['active']) ? 'selected' : ''?>>
                            <?php echo esc_attr($critOptionData['name']); ?>
                        </option>
 
                        <?php
                    }
                    ?>
                </select>
            </div>
            <?php
        }
        ?>

        <?php
            foreach ($args['selectedParameters'] as $paramName => $paramValue) {
            ?>
                <input type="hidden" name="<?php echo esc_attr($paramName) ?>" value="<?php echo esc_attr($paramValue) ?>" />
                <?php    
            }
        ?>

        <input type="hidden" name="action" value="otys_search" />
        
        <button class="button owp-search-button" type="submit"><?php _e('Search', 'otys-jobs-apply'); ?></button>
    </form>
</div>