<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

class ShortcodeBaseModel extends \Otys\OtysPlugin\Models\BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns attributes as array
     *
     * @param array $atts
     * @return array
     */
    public static function filterAtts(array $atts): array
    {
        $filteredAtts = [];

        if (is_array($atts)) {
            foreach ($atts as $att => $attValue) {
                // If key is numeric make value the key and add value as empty array
                if (is_numeric($att)) {
                    $filteredAtts[$attValue] = [];
                    continue;
                }

                // Convert values to array and add to filtered atts
                if (is_string($attValue)) {
                    $filteredAtts[$att] = explode(',', $attValue);
                }
            }
        }

        return $filteredAtts;
    }

    /**
     * getShortcodeAttributes
     * Get shortcode attributes from a shortcode within the current post. This
     * function seaches for the shortcode in the $post->post_content and extracts
     * the attributes from the shortcode and returns them as an array
     *
     * @param string $shortcode
     * @return array
     */
    public static function getShortcodeAttributes(string $shortcode = '')
    {
        global $post;

        $pattern = get_shortcode_regex([$shortcode]);

        if (
            preg_match_all('/' . $pattern . '/s', $post->post_content, $matches)
            && array_key_exists(2, $matches)
            && in_array($shortcode, $matches[2])
        ) {
            $shortcodeKey = array_search($shortcode, $matches[2]);
            $shortCodeString = $matches[3][$shortcodeKey];

            $shortcodeAtts = shortcode_parse_atts($shortCodeString);

            if (is_array($shortcodeAtts)) {
                return $shortcodeAtts;
            }

            return [$shortcodeAtts];
        }

        return [];
    }
}