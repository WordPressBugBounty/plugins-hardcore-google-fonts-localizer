<?php

class Google_Fonts_Localizer_Frontend
{
    public function __construct()
    {
        add_action('wp_head', [$this, 'inject_fonts_css'], 0);
    }

    public function inject_fonts_css() {
        $css_cache = get_option('google_fonts_localizer_cache');

        if ($css_cache) {
            ob_start();

            foreach ($css_cache as $family => $variants) {
                foreach ($variants as $variant => $url_pairs) {
                    $variant_parts = explode('-', $variant);
                    $weight = $variant_parts[0];
                    $style = $variant_parts[1];
                    $src = [];

                    foreach ($url_pairs as $type => $url) {
                        switch($type) {
                            case 'eot':
                                $url = $url . '?#iefix';
                                $format = 'embedded-opentype';
                                break;
                            case 'woff2':
                                $format = 'woff2';
                                break;
                            case 'woff':
                                $format = 'woff';
                                break;
                            case 'ttf':
                                $format = 'truetype';
                                break;
                            case 'svg':
                                $url = $url . '#' . preg_replace('/\s/', '', $family);
                                $format = 'svg';
                                break;
                            default:
                                $format = '';
                                break;
                        }

                        $src[] = "url('$url') format('$format')";
                    }

                    ?>
                    @font-face {
                    font-family: '<?php echo $family; ?>';
                    font-weight: <?php echo $weight; ?>;
                    font-style: <?php echo $style; ?>;
                    font-display: swap;
                    src: <?php echo implode(',', $src); ?>;
                    }
                    <?php
                }
            }
            
            $css = $this->minifyCss(ob_get_clean());
            echo "<style>$css</style>";
        }
    }

    /**
     * This function takes a css-string and compresses it, removing
     * unnecessary whitespace, colons, removing unnecessary px/em
     * declarations etc.
     *
     * @param string $css
     * @return string compressed css content
     * @author Steffen Becker
     */
    private function minifyCss($css) {
        // some of the following functions to minimize the css-output are directly taken
        // from the awesome CSS JS Booster: https://github.com/Schepp/CSS-JS-Booster
        // all credits to Christian Schaefer: http://twitter.com/derSchepp
        // remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // backup values within single or double quotes
        preg_match_all('/(\'[^\']*?\'|"[^"]*?")/ims', $css, $hit, PREG_PATTERN_ORDER);
        for ($i=0; $i < count($hit[1]); $i++) {
            $css = str_replace($hit[1][$i], '##########' . $i . '##########', $css);
        }
        // remove traling semicolon of selector's last property
        $css = preg_replace('/;[\s\r\n\t]*?}[\s\r\n\t]*/ims', "}\r\n", $css);
        // remove any whitespace between semicolon and property-name
        $css = preg_replace('/;[\s\r\n\t]*?([\r\n]?[^\s\r\n\t])/ims', ';$1', $css);
        // remove any whitespace surrounding property-colon
        $css = preg_replace('/[\s\r\n\t]*:[\s\r\n\t]*?([^\s\r\n\t])/ims', ':$1', $css);
        // remove any whitespace surrounding selector-comma
        $css = preg_replace('/[\s\r\n\t]*,[\s\r\n\t]*?([^\s\r\n\t])/ims', ',$1', $css);
        // remove any whitespace surrounding opening parenthesis
        $css = preg_replace('/[\s\r\n\t]*{[\s\r\n\t]*?([^\s\r\n\t])/ims', '{$1', $css);
        // remove any whitespace between numbers and units
        $css = preg_replace('/([\d\.]+)[\s\r\n\t]+(px|em|pt|%)/ims', '$1$2', $css);
        // shorten zero-values
        $css = preg_replace('/([^\d\.]0)(px|em|pt|%)/ims', '$1', $css);
        // constrain multiple whitespaces
        $css = preg_replace('/\p{Zs}+/ims',' ', $css);
        // remove newlines
        $css = str_replace(array("\r\n", "\r", "\n"), '', $css);
        // Restore backupped values within single or double quotes
        for ($i=0; $i < count($hit[1]); $i++) {
            $css = str_replace('##########' . $i . '##########', $hit[1][$i], $css);
        }
        return $css;
    }
}