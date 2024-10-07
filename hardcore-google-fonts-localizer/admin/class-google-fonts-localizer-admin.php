<?php

class Google_Fonts_Localizer_Admin
{
    private static $fontTypes = ['woff2', 'woff', 'ttf'];
    private static $gFontURL  = 'http://fonts.googleapis.com/css?family=';

    private static $uaFonts = [
    'woff2' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
    'woff'  => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/4.0; GTB7.4; InfoPath.3; SV1; .NET CLR 3.1.76908; WOW64; en-US)',
    'ttf'   => 'Mozilla/5.0 (Linux; U; Android 2.2.1; en-ca; LG-P505R Build/FRG83) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
    'svg'   => 'Mozilla/5.0(iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B314 Safari/531.21.10',
    'eot'   => 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)'
    ];

    public function __construct()
    {
        $this->load_dependencies();
    }

    private function load_dependencies()
    {
        require_once(plugin_dir_path(GOOGLE_FONTS_LOCALIZER_PLUGIN_FILE) . 'lib/apf/admin-page-framework.php');
        require_once(plugin_dir_path(GOOGLE_FONTS_LOCALIZER_PLUGIN_FILE) . 'admin/class-google-fonts-localizer-admin-settings.php');

        new Google_Fonts_Localizer_Settings();
    }

    public static function cache_google_fonts(string $url, bool $rebuild) {
        if (preg_match('/^https:\/\/fonts.googleapis.com\/css2/', $url)) {
            self::$gFontURL = 'https://fonts.googleapis.com/css2?family=';
        }

        $urlParts = parse_url($url);
        $queryParts = self::parseQuery($urlParts['query']);

        foreach ($queryParts['family'] as $family) {
            $fonts = self::get_google_fonts_urls($family);
            $fonts = self::sort_fonts($fonts);
            self::download_google_fonts($fonts, $rebuild);

            if($rebuild) {
                $rebuild = false;
            }
        }
    }

    private static function get_google_fonts_urls(string $family)
    {
        $parse_url = self::$gFontURL . urlencode($family);
        $fontsDownloadLinks = [];

        foreach (self::$fontTypes as $fontType) {
            $content = self::curlGoogleFont($parse_url, self::$uaFonts[$fontType]);
            $fontsDownloadLinks[$fontType] = self::parseCss($content, $family);
        }

        return $fontsDownloadLinks;
    }

    private static function parseQuery($query)
    {
        $query  = explode('&', $query);
        $params = [];
        foreach ($query as $param) {
            // prevent notice on explode() if $param has no '='
            if (strpos($param, '=') === false) {
                $param .= '=';
            }

            list($name, $value) = explode('=', $param, 2);
            $params[urldecode($name)][] = urldecode($value);
        }
        return $params;
    }

    private static function curlGoogleFont(string $url, string $ua)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER         => 1,
            CURLOPT_VERBOSE        => 1,
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => $ua
        ]);

        if (($response = curl_exec($curl))) {
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header      = substr($response, 0, $header_size);

            if (strrpos($header, 'Content-Type: text/css')) {
                return $response;
            }
        }

        return '';
    }

    private static function parseCss(string $css, string $font_families) {
        $fonts = [];
        $fontName = null;
        $fontStyle = null;
        $fontWeight = null;
        $url = null;

        $font_families = explode('|', $font_families);
        $font_family_names = [];

        foreach ($font_families as $font_family) {
            $name = explode(':', $font_family);
            $font_family_names[] = $name[0];
        }

        foreach (preg_split('/\r\n|\n|\r/', $css) as $cssLine) {
            // We save the font-family name for EOT
            if (strpos($cssLine, 'font-family')) {
                preg_match("/'(.*?)'/i", $cssLine, $data);
                $fontName = $data[1];
            }
            if (strpos($cssLine, 'url')) {
                preg_match('/local\((.*?)\)/i', $cssLine, $data);
                if (count($data)) {
                    $fontName = str_replace('\'', '', $data[1]);
                }
            }
            if (strpos($cssLine, 'src')) {
                preg_match('/url\((.*?)\)/i', $cssLine, $data);
                $url = $data[1];
            }
            if (strpos($cssLine, 'font-style')) {
                preg_match("/\: '?(.*?)'?;$/i", $cssLine, $data);
                $fontStyle = $data[1];
            }
            if (strpos($cssLine, 'font-weight')) {
                preg_match("/\: '?(.*?)'?;$/i", $cssLine, $data);
                $fontWeight = $data[1];
            }

            if ($fontName !== null && $url !== null ) {
                $current_name = null;
                foreach ($font_family_names as $current_name) {
                    if (strpos($fontName, $current_name) !== false) {
                        $fonts[$current_name][implode('-', [$fontWeight, $fontStyle])] = $url;
                    }
                }
                $fontStyle = null;
                $fontWeight = null;
                $fontName = null;
                $url = null;
            }
        }

        return $fonts;
    }

    private static function sort_fonts(array $fonts) {
        $sorted_fonts = [];

        foreach ($fonts as $file_type => $font_families) {
            foreach ($font_families as $font_family => $urls) {
                $sorted_fonts[$font_family][$file_type] = $urls;
            }
        }

        return $sorted_fonts;
    }

    private static function download_google_fonts(array $fonts, bool $rebuild) {
        $cached_fonts = get_option('google_fonts_localizer_cache');
        if (!$cached_fonts || $rebuild) {
            $cached_fonts = [];
        }

        $uploads_path = (wp_get_upload_dir())['basedir'] . '/fonts_cache';
        $uploads_url = (wp_get_upload_dir())['baseurl'] . '/fonts_cache';


        if (is_dir($uploads_path) && $rebuild) {
            self::rrmdir($uploads_path);
        }

        if (! is_dir($uploads_path)) {
            mkdir($uploads_path);
        }

        foreach ($fonts as $font_family => $file_type) {
            foreach ($file_type as $type => $font_variants) {
                foreach ($font_variants as $font_variant => $url) {
                    $font_file = file_get_contents($url);
                    $relative_dir = implode('/' , [sanitize_file_name($font_family), $font_variant]);
                    $uploads_dir = $uploads_path . '/' . $relative_dir;

                    if (! is_dir($uploads_dir) ) {
                        mkdir($uploads_dir, 0777, true);
                    }

                    file_put_contents($uploads_dir . '/' . $font_variant . '.' . $type, $font_file);
                    $url = $uploads_url . '/' . $relative_dir . '/' . $font_variant . '.' . $type;

                    $cached_fonts[$font_family][$font_variant][$type] = $url;
                }
            }
        }

        if (get_option('google_fonts_localizer_cache') !== false) {
            update_option('google_fonts_localizer_cache', $cached_fonts);
        } else {
            add_option('google_fonts_localizer_cache', $cached_fonts);
        }
    }

    private static function rrmdir($path) {
        if (is_dir($path)) {
            array_map( [Google_Fonts_Localizer_Admin::class, 'rrmdir'], glob($path . DIRECTORY_SEPARATOR . '{,.[!.]}*', GLOB_BRACE) );
            @rmdir($path);
        }
        else {
            @unlink($path);
        }
    }

}