<?php
/*
Plugin Name:  Lean & Mean Google Tag Manager
Description:  Tiny plugin that prints the Google Tag Manager scripts in the head and body of a site.
Version:      1.2
Author:       Seb Jones
Author URI:   http://sebj.co.uk
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

/*
 * Activation function. Sets up options used by the plugin.
 */
if (!function_exists('lam_tagman_activation')) {
    function lam_tagman_activation() {
        add_option('lam_tagman_id', "", "", "yes");
    }
    register_activation_hook( __FILE__, 'lam_tagman_activation' );
}

/*
 * Uninstall function. Deletes options used by the plugin.
 */
if (!function_exists('lam_tagman_uninstall')) {
    function lam_tagman_uninstall() {
        delete_option('lam_tagman_id');
    }
    register_uninstall_hook( __FILE__, 'lam_tagman_uninstall' );
}

/*
 * Head function. Prints the Google Tag Manager head script in the head
 * of the document if the ID option has been set.
 */
if ( !function_exists( 'lam_tagman_head' ) ) {
    function lam_tagman_head() {
        $o = get_option('lam_tagman_id');

        if (!empty($o) && lam_tagman_is_id($o)) {
            ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo trim(esc_attr($o)); ?>');</script>
<!-- End Google Tag Manager -->
            <?php
        }
    }
    add_action('wp_head', 'lam_tagman_head', 1);
}

/*
 * Starts Buffering output, which we will then use to inject the Google Tag
 * Manager code in the 'wp_footer' hook.
 */
if ( !function_exists( 'lam_tagman_start_buffering_output' ) ) {
    function lam_tagman_start_buffering_output() {
        $o = get_option('lam_tagman_id');

        if (!empty($o) && lam_tagman_is_id($o)) {
            ob_start();
        }
    }
    add_action( 'wp_head', 'lam_tagman_start_buffering_output');
}

/* 
 * Body function. Injects the Google Tag Manager body script after the opening
 * body tag of the buffered output.
 */
if ( !function_exists( 'lam_tagman_body' ) ) {
    function lam_tagman_body() {
        $o = get_option('lam_tagman_id');

        if (!empty($o) && lam_tagman_is_id($o)) {
            $o_sanitized = trim(esc_attr($o));
            $script = <<<HERE
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=$o"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
HERE;

            $buffers  = ob_get_clean();

            $pattern ='/<[bB][oO][dD][yY]\s[A-Za-z]{2,5}[A-Za-z0-9 "_=\-\.]+>|<body>/';
            ob_start();

            if(preg_match($pattern, $buffers, $match_buffers)) {
                $new_body = $match_buffers[0] . $script;

                echo preg_replace($pattern, $new_body, $buffers);

            }

            ob_flush();
        }

    }
    add_action('wp_footer', 'lam_tagman_body', PHP_INT_MAX);
}

/*
 * Callback function that prints the input text field for the ID option.
 */
if (!function_exists('lam_tagman_id_field_cb')) {
    function lam_tagman_id_field_cb($args) {
        $o = get_option('lam_tagman_id');
?>
        <input type="text" id="lam_tagman_id" name="lam_tagman_id" value="<?php echo isset($o) ? trim(esc_attr($o)) : ''; ?>">
<?php
    }
}

/*
 * Admin init function. Registers the ID setting and adds it's field to the
 * General settings menu.
 */
if (!function_exists('lam_tagman_admin_init')) {
    function lam_tagman_admin_init() { 
        register_setting('general', 'lam_tagman_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );

        add_settings_field('lam_tagman_id', 'Google Tag Manager Container ID',
            'lam_tagman_id_field_cb', 'general');
    }
    add_action('admin_init', 'lam_tagman_admin_init');
}

/*
 * Admin Notice function. Displays a notice if the ID option has not been set.
 */
if (!function_exists('lam_tagman_admin_notice')) {
    function lam_tagman_admin_notice() {
        $o = get_option('lam_tagman_id');
        if (empty($o)) {
?>
            <div class='notice notice-info'><p>Please add your Google Tag Manager ID to the field in <a href='<?php echo admin_url("options-general.php#lam_tagman_id"); ?>'>General Settings</a> to start using <em>Lean &amp; Mean Google Tag Manager.</em></p></div>
<?php
        }
        else if (!lam_tagman_is_id($o)) {
?>
            <div class='notice notice-error'><p>The Google Tag Manager Container ID you specified does not appear to be valid. Please check that <a href='<?php echo admin_url("options-general.php#lam_tagman_id"); ?>'>your input</a> conforms to the format <strong>GTM-XXXXX</strong>.</p></div>
<?php
        }
    }
    add_action( 'admin_notices', 'lam_tagman_admin_notice' );
}

/*
 * Validation function. Checks if the given string fits the format of a
 * Google Tag Manager Container ID.
 */
if (!function_exists('lam_tagman_is_id')) {
    function lam_tagman_is_id($str) {
        $str = trim($str);
        $prefix = "GTM-";
        $len = strlen($prefix);

        return strlen($str) > $len && !strncasecmp($str, $prefix, $len);
    }
}
