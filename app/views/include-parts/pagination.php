<?php

/**
 * Reusable Pagination
 *
 * @package Otys\OtysPlugin
 * @since 1.0.0
 */

$args = wp_parse_args($args, []);

if (!empty($args['pagination']) && $args['pagination']['total_pages'] > 1) :
    ?>

    <nav class="pagination" aria-label="pagination">
        <ul class="pagination-items">
            <?php
            // Show first button when needed
            if ($args['pagination']['first']['show']) :
                ?>
                <li class="pagination-item">
                    <a href="<?php echo esc_attr($args['pagination']['first']['url']) ?>" class="pagination-link" rel="next">
                    <?php
                        if ($args['pagination']['first']['icon']) {
                            ?>
                            <?php echo wp_get_attachment_image($args['pagination']['first']['icon'], 'full', false); ?>
                            <?php
                        } else {
                            echo $args['pagination']['first']['text'];
                        }
                    ?>
                    </a>
                </li>
                <?php
            endif;

            // Show prev button when needed
            if ($args['pagination']['prev']['show']) :
                ?>
                <li class="pagination-item">
                    <a href="<?php echo esc_attr($args['pagination']['prev']['url']) ?>" class="pagination-link" rel="prev">
                    <?php
                        if ($args['pagination']['prev']['icon']) {
                            ?>
                            <?php echo wp_get_attachment_image($args['pagination']['prev']['icon'], 'full', false); ?>
                            <?php
                        } else {
                            echo $args['pagination']['prev']['text'];
                        }
                    ?>
                    </a>
                </li>
                <?php
            endif;

            // Show page numbers
            foreach ($args['pagination']['pages'] as $page) {
                if ($page['show']) {
                ?>
                    <li class="pagination-item <?php echo ($page['active']) ? 'active' :  '' ?>">
                        <a href="<?php echo esc_attr($page['url']) ?>" class="pagination-link">
                            <?php echo esc_html($page['page']); ?>
                        </a>
                    </li>
                <?php
                }
            }

            // Show next button when needed
            if ($args['pagination']['next']['show']) :
                ?>
                <li class="pagination-item">
                    <a href="<?php echo esc_attr($args['pagination']['next']['url']) ?>" class="pagination-link" rel="next">
                    <?php
                        if ($args['pagination']['next']['icon']) {
                            ?>
                            <?php echo wp_get_attachment_image($args['pagination']['next']['icon'], 'full', false); ?>
                            <?php
                        } else {
                            echo $args['pagination']['next']['text'];
                        }
                    ?>
                    </a>
                </li>
                <?php
            endif;

            // Show first button when needed
            if ($args['pagination']['last']['show']) :
                ?>
                <li class="pagination-item">
                    <a href="<?php echo esc_attr($args['pagination']['last']['url']) ?>" class="pagination-link" rel="next">
                        <?php
                        if ($args['pagination']['last']['icon']) {
                            ?>
                            <?php echo wp_get_attachment_image($args['pagination']['last']['icon'], 'full', false); ?>
                            <?php
                        } else {
                            echo $args['pagination']['last']['text'];
                        }
                        ?>
                    </a>
                </li>
                <?php
            endif;
            ?>
        </ul>
    </nav>

    <?php

endif;
