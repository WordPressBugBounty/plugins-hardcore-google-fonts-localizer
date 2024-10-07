<?php

class Google_Fonts_Localizer_Filters
{

    public function __construct()
    {
        add_action('after_setup_theme', [$this, 'deactivate_font_loaders']);
    }

    public function deactivate_font_loaders()
    {
        $options = get_option('Google_Fonts_Localizer_Settings');

        if ($options && isset($options['deactivate_plugin_font_loaders'])) {
            $loader_options = $options['deactivate_plugin_font_loaders'];

            if ($loader_options['jupiter'] === '1' && defined('THEME_JS') && defined('THEME_VERSION')) {
                add_action('wp_print_scripts', [$this, 'dequeue_jupiter_font_styles'], 100);
                add_action('wp_footer', [$this, 'dequeue_jupiter_footer_font_includes'], 5);
            }

            if ($loader_options['jupiter_x'] === '1' && defined('JUPITERX_API') && class_exists('JupiterX_Fonts')) {
                add_filter( 'jupiterx_font_types', [ $this, 'add_jupiterX_font_type' ] );
                add_filter('jupiterx_custom_fonts', [$this, 'add_jupiterX_custom_fonts']);
            }

            if ($loader_options['elementor'] === '1' && class_exists(\Elementor\Plugin::class)) {
                add_filter( 'elementor/fonts/groups', [$this, 'remove_elementor_font_groups'], 1000, 1);
                add_filter('elementor/fonts/additional_fonts', [$this, 'add_elementor_fonts']);
            }

            if ($loader_options['vc'] === '1' && class_exists('Vc_Manager')) {
                add_filter('vc_google_fonts_get_fonts_filter', [$this, 'change_vc_fonts_list'], 100);
                add_filter('get_footer', [$this, 'dequeue_vc_font_styles'], 100);
            }

            if ($loader_options['revo'] === '1' && class_exists('RevSliderFront')) {
                add_filter('revslider_printCleanFontImport', [$this, 'disable_rev_slider_font_import'], 100, 6);
                add_filter('revslider_operations_getArrFontFamilys', [$this, 'change_rev_fonts_list'], 100, 1);
            }

        }
    }

    public function add_elementor_fonts()
    {
        return $this->add_jupiterX_custom_fonts();
    }

    public function remove_elementor_font_groups($font_groups)
    {
        $keep_groups = [
            'custom'
        ];
        $keep_groups = apply_filters('google-font-localizer/elementor/keepgroups', $keep_groups);

        foreach ($font_groups as $group => $label) {
            if (!in_array($group, $keep_groups)) {
                unset($font_groups[$group]);
            }
        }

        $font_groups['local'] = __('Fonts Localizer', 'google-fonts-localizer');
        return $font_groups;
    }

    public function add_jupiterX_font_type( $types ) {
        $types['local'] = 'Fonts Localizer';
        return $types;
    }

    public function add_jupiterX_custom_fonts($fonts = []) {
        $fonts_cache = get_option('google_fonts_localizer_cache');

        foreach ($fonts_cache as $family => $types) {
            $fonts[$family] = 'local';
        }

        return $fonts;
    }

    public function dequeue_jupiter_footer_font_includes() {
        if(! is_admin()) {
            global $wp_styles;
            $registered_styles = $wp_styles->registered;

            foreach ($registered_styles as $registered_style) {
                if (strpos($registered_style->src, 'fonts.googleapis.com') != false) {
                    wp_deregister_style($registered_style->handle);
                }
            }
        }
    }

    public function dequeue_jupiter_font_styles()
    {
        wp_dequeue_script('mk-webfontloader', THEME_JS . '/plugins/wp-enqueue/min/webfontloader.js', array('jquery'),
            THEME_VERSION, true);
        wp_dequeue_script('mk-webfontloader', THEME_JS . '/plugins/wp-enqueue/webfontloader.js', array('jquery'),
            THEME_VERSION, true);
    }

    public function change_vc_fonts_list($fonts_list)
    {
        $fonts_cache = get_option('google_fonts_localizer_cache');

        if ($fonts_cache) {
            $fonts_list = [];

            foreach ($fonts_cache as $family => $variants) {
                $styles = [];
                $types  = [];
                foreach ($variants as $variant => $url_pairs) {
                    $variant_parts = explode('-', $variant);
                    $weight        = $variant_parts[0];
                    $style         = $variant_parts[1];

                    $styles[] = $weight . $style === 'italic' ? 'italic' : '';
                    $types[]  = "$weight $style:$weight:$style";
                }

                $fonts_list[] = (object)[
                    'font_family' => $family,
                    'font_styles' => implode(',', $styles),
                    'font_types'  => implode(',', $types)
                ];
            }

            return $fonts_list;
        } else {
            return $fonts_list;
        }
    }

    public function dequeue_vc_font_styles()
    {
        $fonts_cache = get_option('google_fonts_localizer_cache');

        foreach ($fonts_cache as $family => $variants) {
            $family = preg_replace('/\s/', '_', $family);
            $family = strtolower($family);
            wp_dequeue_style('vc_google_fonts_' . $family);
        }
    }

    public function disable_rev_slider_font_import()
    {
        return '';
    }

    public function change_rev_fonts_list($fonts)
    {
        $fonts_cache = get_option('google_fonts_localizer_cache');

        if ($fonts_cache) {
            $fonts = [];

            foreach ($fonts_cache as $family => $variants) {
                $variations = [];
                foreach ($variants as $variant => $url_pairs) {
                    $variant_parts = explode('-', $variant);
                    $weight        = $variant_parts[0];
                    $style         = $variant_parts[1];

                    if ($style === 'normal') {
                        $style = '';
                    }

                    if ($weight === '400' && $style === 'italic') {
                        $weight = '';
                    }

                    $variations[] = $weight . $style;
                }

                $fonts[] = [
                    'type'     => 'googlefont',
                    'version'  => 'Google Fonts',
                    'label'    => $family,
                    'variants' => $variations
                ];
            }

            return $fonts;
        } else {
            return $fonts;
        }
    }
}