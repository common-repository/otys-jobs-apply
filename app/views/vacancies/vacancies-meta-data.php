<?php

if ($args['meta_description'] ?? false) {
    echo '<meta name="description" content="' . $args['meta_description'] . '">';
}