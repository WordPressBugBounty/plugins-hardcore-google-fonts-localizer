<?php

class Google_Fonts_Localizer_Settings extends AdminPageFramework
{

    public function setUp()
    {

        // Create the root menu
        $this->setRootMenuPage('Settings');

        // Add the sub menu item
        $this->addSubMenuItems(
            [
                'title'     => GOOGLE_FONTS_LOCALIZER_PLUGIN_TITLE,
                'page_slug' => 'google_fonts_localizer_settings',
            ]
        );

        $this->addSettingFields(
            [
                'field_id'    => 'font_url',
                'title'       => __('Google Fonts URL', 'google-fonts-localizer'),
                'description' => __('Place the import URL of the Google Font you want to import here.', 'google-fonts-localizer'),
                'type'        => 'text',
                'attributes'        => [
                    'size' => 100,
                ]
            ],
            [
                'field_id'    => 'rebuild_cache',
                'title'       => __('Re-Build Cache', 'google-fonts-localizer'),
                'label' => __('Delete all Files and Folders associated with the Fonts Cache', 'google-fonts-localizer'),
                'type'        => 'checkbox',
                'default_value' => true,
                'attributes'        => [
                    'size' => 100,
                ]
            ],
            [
                'field_id'    => 'deactivate_plugin_font_loaders',
                'title'       => __('Disable Fonts from', 'google-fonts-localizer'),
                'label' => [
                    'jupiter' => __('Jupiter Theme by Artbees', 'google-fonts-localizer'),
                    'jupiter_x' => __('Jupiter X Theme by Artbees', 'google-fonts-localizer'),
                    'elementor' => __('Elementor', 'google-fonts-localizer'),
                    'vc' => __('WP Bakery Page Builder', 'google-fonts-localizer'),
                    'revo' => __('Slider Revolution by Themepunch', 'google-fonts-localizer')
                ],
                'default' => [
                    'jupiter' => false,
                    'jupiter_x' => false,
                    'elementor' => false,
                    'vc' => false,
                    'revo' => false
                ],
                'type'        => 'checkbox',
                'attributes'        => [
                    'size' => 100,
                ]
            ],
            [
                'field_id' => 'save_settings',
                'value' => __('Save Settings', 'google-fonts-localizer'),
                'type' => 'submit'
            ]
        );
    }

    public function submit_after_Google_Fonts_Localizer_Settings() {

        $options = get_option('Google_Fonts_Localizer_Settings');
        if ($options) {
            $url = $options['font_url'];
            $rebuild = $options['rebuild_cache'] === '1';

            $options['rebuild_cache'] = '0';

            if (!empty($url)) {
                Google_Fonts_Localizer_Admin::cache_google_fonts($url, $rebuild);
            }

            update_option('Google_Fonts_Localizer_Settings', $options);
        }
    }

}