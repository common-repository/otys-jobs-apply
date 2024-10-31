<?php
/**
 * Template used for shortcode [otys-vacancies-apply] form errors.
 * 
 * @since 2.0.0
 */
?>

<div class="rest-form-page" data-type="success">
    <div class="form-intro">
        <p>
            <?php echo sprintf(
                __('Thank you for your application. You have successfully applied for the vacancy %s.', 'otys-jobs-apply'),
                $args['vacancy']['title']
            ); ?>
        </p>
    </div>
</div>

