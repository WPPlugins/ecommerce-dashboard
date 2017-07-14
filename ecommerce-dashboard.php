<?php
/*
Plugin Name: eCommerce Dashboard
Plugin URI: http://club.orbisius.com/products/wordpress-plugins/ecommerce-dashboard/
Description: This plugin allows you to see your sale stats on your mobile device. Currently, it supports WooCommerce. Calculates and shows daily, weekly, monthly and all sales.
Version: 1.0.8
Author: Svetoslav Marinov (Slavi)
Author URI: http://orbisius.com
*/

/*  Copyright 2012-2050 Svetoslav Marinov (Slavi) <slavi@orbisius.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Setup plugin
add_action( 'init', 'ecommerce_dashboard_handle_mobile', 0 );
add_action( 'init', 'ecommerce_dashboard_init' );
add_action( 'plugins_loaded', 'ecommerce_dashboard_setup_deps' );
add_action( 'admin_init', 'ecommerce_dashboard_register_settings');
add_action( 'admin_notices', 'ecommerce_dashboard_admin_notice');
add_action( 'admin_menu', 'ecommerce_dashboard_setup_admin', 10);
add_action( 'wp_footer', 'ecommerce_dashboard_add_plugin_credits', 1000); // be the last in the footer

add_filter( 'wc_order_types', 'ecommerce_dashboard_order_type_fix', 10, 2 );

/**
 * This loads the required WooCommerce libararies.
 */
function ecommerce_dashboard_setup_deps() {
    if (function_exists('WC')) {
        $wc = WC();

        $class = 'WC_Report_Sales_By_Date';
        $name = 'sales-by-date';

        include_once( apply_filters( 'wc_admin_reports_path', $wc->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' ) );
        include_once( apply_filters( 'wc_admin_reports_path', $wc->plugin_path() . '/includes/admin/class-wc-admin-reports.php' ) );
        include_once( apply_filters( 'wc_admin_reports_path', $wc->plugin_path() . '/includes/admin/reports/class-wc-report-' . $name . '.php', $name, $class ) );

        // This class is intented subclasses WC_Report_Sales_By_Date so I can set the chart colours in
        // case there are new ones added in the future and/or if the modificator changes from public to private or soemthing.
        if (class_exists('WC_Report_Sales_By_Date')) {
            class eCommerce_Dashboard_Platform_WooCommerce_Sales_By_Date extends WC_Report_Sales_By_Date {
                public $my_chart_colours = array(
                    'sales_amount' => '#3498db',
                    'average'      => '#75b9e7',
                    'order_count'  => '#b8c0c5',
                    'item_count'   => '#d4d9dc',
                    'coupon_amount' => '#e67e22',
                    'shipping_amount' => '#1abc9c',
                    'refund_amount' => '#c0392b',
                );

                public function __construct() {
                    // PHP classes don't have default constructors!
                    // so the parent class doesn't have one so we won't call it.
                    // OR we'll get a fatal error.
                    // parent::__construct();

                    if (empty($this->chart_colours)) {
                        $this->chart_colours = array();
                    }

                    $this->chart_colours = array_merge($this->my_chart_colours, $this->chart_colours);
                }
            }
        }
    }
}

/**
 * For some weird reason (?) on the public side the 'shop_order' parameter is missing.
 * This makes all the stats to show 0 which is not cool
 *
 * @param array $order_types
 * @param str $for reports text
 * @return array
 */
function ecommerce_dashboard_order_type_fix( $order_types, $for ) {
    if (isset($_REQUEST['ed_stats']) && !in_array('shop_order', $order_types)) {
        $order_types[] = 'shop_order';
    }

    return $order_types;
}

/**
 * 
 * @return string
 */
function ecommerce_dashboard_get_settings_link() {
    // when using options
    /*$link = admin_url('options-general.php?page=' . plugin_basename(__FILE__));
    $dashboard_link = "<a href=\"{$link}\">Settings</a>";*/
    $link = admin_url('admin.php?page=' . plugin_basename(__FILE__));

    return $link;
}

/**
 * Adds the action link to settings. That's from Plugins. It is a nice thing.
 * @param type $links
 * @param type $file
 * @return type
 */
function ecommerce_dashboard_add_quick_settings_link($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        // Top level settings page.
        $link = ecommerce_dashboard_get_settings_link();
        $dashboard_link = "<a href=\"{$link}\">Settings</a>";

        array_unshift($links, $dashboard_link);
    }

    return $links;
}

/**
 * Setups loading of assets (css, js).
 * for live servers we'll use the minified versions e.g. main.min.js otherwise .js or .css (dev)
 * @see http://jscompress.com/ - used for JS compression
 * @see http://refresh-sf.com/yui/ - used for CSS compression
 * @return type
 */
function ecommerce_dashboard_init() {
    $dev = empty($_SERVER['DEV_ENV']) ? 0 : 1;
    $suffix = $dev ? '' : '.min';

    wp_register_style('ecommerce_dashboard', plugins_url("/assets/main{$suffix}.css", __FILE__), false,
            filemtime( plugin_dir_path( __FILE__ ) . "/assets/main{$suffix}.css" ) );
    wp_enqueue_style('ecommerce_dashboard');

	if (0) {
		wp_enqueue_script( 'jquery' );
		wp_register_script( 'ecommerce_dashboard', plugins_url("/assets/main{$suffix}.js", __FILE__), array('jquery', ),
				filemtime( plugin_dir_path( __FILE__ ) . "/assets/main{$suffix}.js" ), true);
		wp_enqueue_script( 'ecommerce_dashboard' );
	}
}

/**
 *
 */
function ecommerce_dashboard_handle_mobile_page($content = '') {
    $buff = <<<BUFF_EOF
<!DOCTYPE html>
<html>
    <head>
        <title>eCommerce Dashboard</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="http://code.jquery.com/mobile/1.2.1/jquery.mobile-1.2.1.min.css" />
        <style>
            /* This will stop truncating the app title */
            .ui-header .ui-title {
                margin-right: 10%;
                margin-left: 10%;
            }
        </style>
    </head>
<body>
    <div data-role="page">

        <div data-role="header">
            <h1>eCommerce Dashboard</h1>
        </div><!-- /header -->

        <div data-role="content">
            <p>$content</p>
        </div><!-- /content -->

    </div><!-- /page -->

    <script src="http://code.jquery.com/jquery-1.8.3.min.js"></script>
    <script src="http://code.jquery.com/mobile/1.2.1/jquery.mobile-1.2.1.min.js"></script>

    <script>
        $(function() {
            $("form:not(.filter) :input:visible:enabled:first").focus();
        });
    </script>
</body>
</html>
BUFF_EOF;

    return $buff;
}

/**
 *
 */
function ecommerce_dashboard_handle_mobile() {
    if (isset($_REQUEST['ed_stats'])) {
        $opt = ecommerce_dashboard_get_options();

        // is the plugin enabled?
        if (empty($opt['status'])) {
            return ;
        }

        $pwd = empty($_REQUEST['stats_password']) ? '' : $_REQUEST['stats_password'];

        if (empty($pwd) || $opt['stats_pass'] != $pwd) {
            $msg = '';
            $esc_pwd = esc_attr($pwd);

            if (!empty($pwd) && $opt['stats_pass'] != $pwd) {
                $msg = '<span style="color:red;">Invalid password</span>';
            }

            $page_content = <<<PAGE_BUFF
<form method="POST">
    $msg

    <input type="hidden" name="ed_stats" value="" />
    <div data-role="fieldcontain" class="ui-hide-label">
        <label for="stats_password">Enter Stats Password:</label>
        <input type="password" name="stats_password" id="stats_password" value="$esc_pwd" placeholder="Stats Password" autocomplete='off' />
    </div>
    <input type="submit" value="Submit" data-theme='a' />
</form>
PAGE_BUFF;
        } else {
            $stats = ecommerce_dashboard_get_stats();
            $page_content = ecommerce_dashboard_render_stats($stats);

            $page_content .= "<h3>Feedback</h3>";
            $page_content .= "<a href='mailto:help+ecommerce+dashboard+wp@orbisius.com?subject=idea'>Suggest an idea</a>";
        }

        echo ecommerce_dashboard_handle_mobile_page($page_content);
        exit;
    }
}

/**
 * Set up administration
 *
 * @package eCommerce Dashboard
 * @since 0.1
 */
function ecommerce_dashboard_setup_admin() {
	// Main page
	add_menu_page(__('eCommerce Dashboard', 'ecommerce_dashboard'),
		__('eCommerce Dashboard', 'ecommerce_dashboard'), 'manage_options',
		__FILE__, 'ecommerce_dashboard_options_page', plugins_url('/assets/icon.png', __FILE__) );

    // Settings > eCommerce Dashboard
	/*add_options_page( 'eCommerce Dashboard', 'eCommerce Dashboard', 'manage_options', __FILE__,
            'ecommerce_dashboard_options_page' );*/

    // Plugins > Action Links
    add_filter('plugin_action_links', 'ecommerce_dashboard_add_quick_settings_link', 10, 2);
}

/**
 * Checks for found e-commerce platforms.
 *
 * @staticvar array $active_plugins
 * @staticvar array $found_platforms
 * @return array
 */
function ecommerce_dashboard_get_platforms() {
    static $active_plugins = null;
    static $found_platforms = null;

    if (!is_null($found_platforms)) {
        return $found_platforms;
    }

    if (is_null($active_plugins)) {
        $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
    }

    if ( in_array( 'woocommerce/woocommerce.php',  $active_plugins) ) {
        $found_platforms['woocommerce'] = 'WooCommerce';
    }

    return $found_platforms;
}

/**
 * Shows a notice if WooCommerce is not enabled.
 * @global array $pagenow
 */
function ecommerce_dashboard_admin_notice() {
    global $pagenow;

	// Check if WooCommerce is active
    if ( 0 && $pagenow == 'plugins.php' ) {
         echo '<div class="updated">
             <p>Quick Order will not work because WooCommerce is not installed or activated.</p>
         </div>';
    }
}

/**
 * Sets the setting variables
 */
function ecommerce_dashboard_register_settings() { // whitelist options
    register_setting('ecommerce_dashboard_settings', 'ecommerce_dashboard_options',
            'ecommerce_dashboard_validate_settings');
}

/**
 * This is called by WP after the user hits the submit button.
 * The variables are trimmed first and then passed to the who ever wantsto filter them.
 * @param array the entered data from the settings page.
 * @return array the modified input array
 */
function ecommerce_dashboard_validate_settings($input) { // whitelist options
    $input = array_map('trim', $input);

    // let extensions do their thing
    $input_filtered = apply_filters('ecommerce_dashboard_filter_settings', $input);

    // did the extension break stuff?
    $input = is_array($input_filtered) ? $input_filtered : $input;

    // Don't leave an empty pwd
    $input['stats_pass'] = empty($input['stats_pass']) ? mt_rand(1000, 9999) : $input['stats_pass'];

    return $input;
}

/**
 * Retrieves the plugin options. It inserts some defaults.
 * The saving is handled by the settings page. Basically, we submit to WP and it takes
 * care of the saving.
 *
 * @return array
 */
function ecommerce_dashboard_get_options() {
    $defaults = array(
        'status' => 0,
        'stats_pass' => mt_rand(1000, 9999),
    );

    $opts = get_option('ecommerce_dashboard_options');

    $opts = (array) $opts;
    $opts = array_merge($defaults, $opts);

    return $opts;
}

/**
 * Gets stats total sales.
 *
 * supports WooCommerce for now
 */
function ecommerce_dashboard_get_stats() {
    $stats = array();

    $platforms = ecommerce_dashboard_get_platforms();

    $ecommerce_dashboard_stats = get_transient( 'ecommerce_dashboard_stats' );

    // Don't cache results on development environment OR if the total sales is 0
    if ( empty($_SERVER['DEV_ENV'])
            && empty($_REQUEST['clear_stats_cache']) // the user wants fresh data
            && (false !== $ecommerce_dashboard_stats)
            && !empty($ecommerce_dashboard_stats['total_sales']) ) {
        return $ecommerce_dashboard_stats;
    }

    // initialize
    foreach (array('daily_sales', 'weekly_sales', 'monthly_sales', 'yearly_sales', 'total_sales') as $key) {
        $stats[$key . '_fmt'] = 'n/a';
    }

    if (!empty($platforms['woocommerce'])) {
        $woo_platform = new eCommerce_Dashboard_Platform_WooCommerce();

        $stats['daily_sales_fmt'] = $woo_platform->get_sales('today');
        $stats['weekly_sales_fmt'] = $woo_platform->get_sales('7day');
        $stats['monthly_sales_fmt'] = $woo_platform->get_sales('month');
        $stats['yearly_sales_fmt'] = $woo_platform->get_sales('year');
        $stats['total_sales_fmt'] = $woo_platform->get_sales('total');
    }

    set_transient( 'ecommerce_dashboard_stats', $stats, 3600 ); // 1h expiration

    return $stats;
}

class eCommerce_Dashboard_Util {
    /**
     * Loads news from Club Orbsius Site.
     * <?php eCommerce_Dashboard_Util::output_orb_widget(); ?>
     */
    public static function output_orb_widget($obj = '', $return = 0) {
        $buff = '';
        ?>
        <!-- Orbisius JS Widget -->
            <?php
                $naked_domain = !empty($_SERVER['DEV_ENV']) ? 'orbclub.com.clients.com' : 'club.orbisius.com';

                if (!empty($_SERVER['DEV_ENV']) && is_ssl()) {
                    $naked_domain = 'ssl.orbisius.com/club';
                }

				// obj could be 'author'
                $obj = empty($obj) ? str_replace('.php', '', basename(__FILE__)) : sanitize_title($obj);
                $obj_id = 'orb_widget_' . sha1($obj);

                $params = '?' . http_build_query(array('p' => $obj, 't' => $obj_id, 'layout' => 'plugin', ));
                $buff .= "<div id='$obj_id' class='$obj_id orbisius_ext_content'></div>\n";
                $buff .= "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://$naked_domain/wpu/widget/$params';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'orbsius-js-$obj_id');</script>";
            ?>
            <!-- /Orbisius JS Widget -->
        <?php

        if ($return) {
            return $buff;
        } else {
            echo $buff;
        }
    }
}

class eCommerce_Dashboard_Platform_WooCommerce {
    /**
     * Accepts 2 optional params. If both are omitted the 2 dates will become today and tomorrow's dates.
	 * If the dates are passed e.g. in weekly sales the dates will be used as is.
	 * The orders include the first date. e.g. >= start date
	 *
     * @param str $start_date YYYY-MM-DD
     * @param str $end_date YYYY-MM-DD
     * @return float
     */
    public function get_sales($current_range = null, $end_date = null) {
        if (!class_exists('eCommerce_Dashboard_Platform_WooCommerce_Sales_By_Date')) {
            return __METHOD__ . ": Cannot find eCommerce_Dashboard_Platform_WooCommerce_Sales_By_Date class. This is a dealbreaker.";
        }

        $report = new eCommerce_Dashboard_Platform_WooCommerce_Sales_By_Date();
       
        $_GET['range'] = ''; // woo reports are looking for this
        
        // this sets some internal vars. we don't about the output
        /*ob_start();
        $report->output_report();
        ob_end_clean();*/

        if ($current_range == 'today') {
            $current_range = 'custom';

            // This file looks for a GET variable woocommerce\includes\admin\reports\class-wc-admin-report.php
            $_GET['start_date'] = date( 'Y-m-d', current_time('timestamp') );
            $_GET['end_date'] = ''; // make WC calculate it
        } elseif ($current_range == 'total') {
            $current_range = 'custom';
            // let's put a date far far back.
            $_GET['start_date'] = '1981-04-19'; // Slavi's birthday :)
            $_GET['end_date'] = ''; // make WC calculate it
        }

        if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) ) {
            $current_range = '7day';
        }

        $report->calculate_current_range( $current_range );
        ob_start();
        ?>
        <?php if ( $legends = $report->get_chart_legend() ) : ?>
            <ul class="chart-legend">
                <?php foreach ( $legends as $legend ) : ?>
                    <li style="border-color: <?php echo $legend['color']; ?>"
                        <?php if ( isset( $legend['highlight_series'] ) )
                            echo 'class="highlight_series" data-series="' . esc_attr( $legend['highlight_series'] ) . '"'; ?>>
                        <?php echo $legend['title']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            Nothing found.
        <?php endif; ?>
        <?php

       $content = ob_get_contents();
       ob_end_clean();
    
       return $content;
    }
}

/**
 * Generates the HTML markup and then returns it.
 * TODO: output JSON
 *
 */
function ecommerce_dashboard_render_stats($stats = array()) {
    $buff = <<<BUFF_EOF
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Daily Sales:</th>
            <td>
                {$stats['daily_sales_fmt']}
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Weekly Sales:</th>
            <td>
                {$stats['weekly_sales_fmt']}
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Monthly Sales:</th>
            <td>
                {$stats['monthly_sales_fmt']}
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Yearly Sales:</th>
            <td>
                {$stats['yearly_sales_fmt']}
            </td>
        </tr>		
        <tr valign="top">
            <th scope="row">Total Sales:</th>
            <td>
                {$stats['total_sales_fmt']}
            </td>
        </tr>
    </table>
BUFF_EOF;

    return $buff;
}

/**
 * Options page and this is shown under Products.
 * For some reason the saved message doesn't show up on Products page
 * that's why I had to display the message for edit.php page specifically.
 *
 * @package eCommerce Dashboard
 * @since 1.0
 * @see http://stackoverflow.com/questions/5943368/dynamically-generating-a-qr-code-with-php
 * @see https://developers.google.com/chart/infographics/docs/qr_codes?csw=1
 * @see http://phpqrcode.sourceforge.net/
 */
function ecommerce_dashboard_options_page() {
    $opts = ecommerce_dashboard_get_options();
    $detected_platforms = ecommerce_dashboard_get_platforms();
    $stats = ecommerce_dashboard_get_stats();

	?>
	<div class="wrap ecommerce_dashboard_admin_wrapper">
        <h2>eCommerce Dashboard</h2>

        <?php if ( empty($detected_platforms) ) : ?>
            <div class="error app_error"><p>No e-commerce plugin was detected or it hasn't been activated.</p></div>
        <?php endif; ?>

        <div class="updated"><p>
            <?php if (!empty($_REQUEST['settings-updated'])) : ?>
               <strong>Settings saved.</strong>
            <?php else : ?>
                This plugin allows you to see your sale stats on your mobile device.
                Currently, it supports WooCommerce. Calculates and shows daily, weekly, monthly and all sales.
            <?php endif; ?>
        </p></div>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <!-- main content -->
                <div id="post-body-content">

                    <div class="meta-box-sortables ui-sortable">

                        <div class="postbox">

                            <h3><span>Settings</span></h3>
                            <div class="inside">
                                <form method="post" action="options.php">
                                    <?php settings_fields('ecommerce_dashboard_settings'); ?>
                                    <table class="form-table">
                                        <tr valign="top">
                                            <th scope="row">Plugin Status</th>
                                            <td>
                                                <label for="radio1">
                                                    <input type="radio" id="radio1" name="ecommerce_dashboard_options[status]"
                                                        value="1" <?php echo empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Enabled
                                                </label>
                                                <br/>
                                                <label for="radio2">
                                                    <input type="radio" id="radio2" name="ecommerce_dashboard_options[status]"
                                                        value="0" <?php echo!empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Disabled
                                                </label>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">Stats Password</th>
                                            <td>
                                                <label for="ecommerce_dashboard_options_stats_pass">
                                                    <input type="text" id="ecommerce_dashboard_options_stats_pass"
                                                           name="ecommerce_dashboard_options[stats_pass]"
                                                        value='<?php echo esc_attr($opts['stats_pass']); ?>' />
                                                </label>
                                                <div>You will be prompted to enter this password when you access the stats. Use letters, numbers etc.</div>
                                            </td>
                                        </tr>
                                    </table>

                                    <p class="submit">
                                        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                                    </p>
                                </form>
                            </div> <!-- .inside -->

                        </div> <!-- .postbox -->

                        <div class="postbox">
                            <h3><span>Info</span></h3>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Detected eCommerce Platform(s):</th>
                                        <td valign="top">
                                            <?php if ( empty($detected_platforms) ) : ?>
                                                <div class="app_error">No e-commerce plugin was detected or it hasn't been activated.</div>
                                            <?php else: ?>
                                                <?php echo join(", ", $detected_platforms); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">Stats Access Link:</th>
                                        <td>
                                            <a href="<?php echo site_url('/?ed_stats'); ?>" target="_blank"><?php echo site_url('/?ed_stats'); ?></a>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">Access Stats via QR code:</th>
                                        <td>
                                            <div class="inside">
                                                <?php if (!empty($opts['status'])) : ?>
                                                    <img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&choe=UTF-8&chl=<?php echo site_url('/?ed_stats');?>" title="Access Stats via QR" />
                                                <?php else : ?>
                                                    <div class="app_error">Please Enable the plugin to see the QR code.</div>
                                                <?php endif; ?>
                                            </div> <!-- .inside -->
                                        </td>
                                    </tr>
                                </table>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                        <div class="postbox">
                            <h3><span>Sale Stats</span></h3>
                            <div class="inside">
                                <?php echo ecommerce_dashboard_render_stats($stats); ?>
                                
                                <br/>Note: Stats are refreshed every hour. Click on the button if it has been more than an hour
                                        and you're not seeing any changes but you know that you have completed orders
                                        
                                <p class="submit">
                                    <a href='<?php echo add_query_arg( 'clear_stats_cache', 1 ); ?>' class="button-primary"><?php _e('Refresh Stats') ?></a>
                                </p>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                    </div> <!-- .meta-box-sortables .ui-sortable -->

                </div> <!-- post-body-content -->

                <!-- sidebar -->
                <div id="postbox-container-1" class="postbox-container">

                    <div class="meta-box-sortables">

                        <!-- Hire Us -->
                        <div class="postbox">
                            <h3><span>Hire Us</span></h3>
                            <div class="inside">
                                Hire us to create a plugin/web/mobile app
                                <br/><a href="http://orbisius.com/page/free-quote/?utm_source=<?php echo str_replace('.php', '', basename(__FILE__));?>&utm_medium=plugin-settings&utm_campaign=product"
                                   title="If you want a custom web/mobile app/plugin developed contact us. This opens in a new window/tab"
                                    class="button-primary" target="_blank">Get a Free Quote</a>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->
                        <!-- /Hire Us -->

                        <!-- Newsletter-->
                        <div class="postbox">
                            <h3><span>Newsletter</span></h3>
                            <div class="inside">
                                <!-- Begin MailChimp Signup Form -->
                                <div id="mc_embed_signup">
                                    <?php
                                        $current_user = wp_get_current_user();
                                        $email = empty($current_user->user_email) ? '' : $current_user->user_email;
                                    ?>

                                    <form action="http://WebWeb.us2.list-manage.com/subscribe/post?u=005070a78d0e52a7b567e96df&amp;id=1b83cd2093" method="post"
                                          id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
                                        <input type="hidden" value="settings" name="SRC2" />
                                        <input type="hidden" value="<?php echo str_replace('.php', '', basename(__FILE__));?>" name="SRC" />

                                        <span>Get notified about cool plugins we release</span>
                                        <!--<div class="indicates-required"><span class="app_asterisk">*</span> indicates required
                                        </div>-->
                                        <div class="mc-field-group">
                                            <label for="mce-EMAIL">Email</label>
                                            <input type="email" value="<?php echo esc_attr($email); ?>" name="EMAIL" class="required email" id="mce-EMAIL">
                                        </div>
                                        <div id="mce-responses" class="clear">
                                            <div class="response" id="mce-error-response" style="display:none"></div>
                                            <div class="response" id="mce-success-response" style="display:none"></div>
                                        </div>	<div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button-primary"></div>
                                    </form>
                                </div>
                                <!--End mc_embed_signup-->
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->
                        <!-- /Newsletter-->

                        <?php eCommerce_Dashboard_Util::output_orb_widget(); ?>

                        <!-- Support options -->
                        <div class="postbox">
                            <h3>
                                <?php
                                        $plugin_data = get_plugin_data(__FILE__);
                                        $product_name = trim($plugin_data['Name']);
                                        $product_page = trim($plugin_data['PluginURI']);
                                        $product_descr = trim($plugin_data['Description']);
                                        $product_descr_short = substr($product_descr, 0, 50) . '...';

                                        $base_name_slug = basename(__FILE__);
                                        $base_name_slug = str_replace('.php', '', $base_name_slug);
                                        $product_page .= (strpos($product_page, '?') === false) ? '?' : '&';
                                        $product_page .= "utm_source=$base_name_slug&utm_medium=plugin-settings&utm_campaign=product";

                                        $product_page_tweet_link = $product_page;
                                        $product_page_tweet_link = str_replace('plugin-settings', 'tweet', $product_page_tweet_link);
                                    ?>
                                <!-- Twitter: code -->
                                <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="http://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
                                <!-- /Twitter: code -->

                                <!-- Twitter: Orbisius_Follow:js -->
                                    <a href="https://twitter.com/orbisius" class="twitter-follow-button"
                                       data-align="right" data-show-count="false">Follow @orbisius</a>
                                <!-- /Twitter: Orbisius_Follow:js -->

                                &nbsp;

                                <!-- Twitter: Tweet:js -->
                                <a href="https://twitter.com/share" class="twitter-share-button"
                                   data-lang="en" data-text="Checkout <?php echo $product_name;?> #WordPress #plugin.<?php echo $product_descr_short; ?>"
                                   data-count="none" data-via="orbisius" data-related="orbisius"
                                   data-url="<?php echo $product_page_tweet_link;?>">Tweet</a>
                                <!-- /Twitter: Tweet:js -->

                                <br/>
                                <span>
                                    <a href="<?php echo $product_page; ?>" target="_blank" title="[new window]">Product Page</a>
                                    |
                                    <a href="http://club.orbisius.com/forums/forum/community-support-forum/wordpress-plugins/<?php echo $base_name_slug;?>/?utm_source=<?php echo $base_name_slug;?>&utm_medium=plugin-settings&utm_campaign=product"
                                    target="_blank" title="[new window]">Support Forums</a>

                                     <!-- |
                                     <a href="http://docs.google.com/viewer?url=https%3A%2F%2Fdl.dropboxusercontent.com%2Fs%2Fwz83vm9841lz3o9%2FOrbisius_LikeGate_Documentation.pdf" target="_blank">Documentation</a>-->
                                </span>
                            </h3>
                        </div> <!-- .postbox -->
                        <!-- /Support options -->

                        <div class="postbox">
                            <h3><span>Tell Your Friends</span></h3>
                            <div class="inside">
                                <?php
                                    $plugin_data = get_plugin_data(__FILE__);

                                    $app_link = urlencode($plugin_data['PluginURI']);
                                    $app_title = urlencode($plugin_data['Name']);
                                    $app_descr = urlencode($plugin_data['Description']);
                                ?>
                                <p>
                                    <!-- AddThis Button BEGIN -->
                                    <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                                        <a class="addthis_button_facebook" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_twitter" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_google_plusone" g:plusone:count="false" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_linkedin" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_email" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <!--<a class="addthis_button_myspace" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_google" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_digg" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_delicious" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_stumbleupon" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_tumblr" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>
                                        <a class="addthis_button_favorites" addthis:url="<?php echo $app_link?>" addthis:title="<?php echo $app_title?>" addthis:description="<?php echo $app_descr?>"></a>-->
                                        <a class="addthis_button_compact"></a>
                                    </div>
                                    <!-- The JS code is in the footer -->

                                    <script type="text/javascript">
                                    var addthis_config = {"data_track_clickback":true};
                                    var addthis_share = {
                                      templates: { twitter: 'Check out {{title}} at {{lurl}} (from @orbisius)' }
                                    }
                                    </script>
                                    <!-- AddThis Button START part2 -->
                                    <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=lordspace"></script>
                                    <!-- AddThis Button END part2 -->
                                </p>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                        <div class="postbox"> <!-- quick-contact -->
                            <?php
                            $current_user = wp_get_current_user();
                            $email = empty($current_user->user_email) ? '' : $current_user->user_email;
                            $quick_form_action = is_ssl()
                                    ? 'https://ssl.orbisius.com/apps/quick-contact/'
                                    : 'http://apps.orbisius.com/quick-contact/';

                            if (!empty($_SERVER['DEV_ENV'])) {
                                $quick_form_action = 'http://localhost/projects/quick-contact/';
                            }
                            ?>
                            <h3><span>Quick Question or Suggestion</span></h3>
                            <div class="inside">
                                <div>
                                    <form method="post" action="<?php echo $quick_form_action; ?>" target="_blank">
                                        <?php
                                            global $wp_version;
                                            $plugin_data = get_plugin_data(__FILE__);

                                            $hidden_data = array(
                                                'site_url' => site_url(),
                                                'wp_ver' => $wp_version,
                                                'first_name' => $current_user->first_name,
                                                'last_name' => $current_user->last_name,
                                                'product_name' => $plugin_data['Name'],
                                                'product_ver' => $plugin_data['Version'],
                                                'woocommerce_ver' => defined('WOOCOMMERCE_VERSION') ? WOOCOMMERCE_VERSION : 'n/a',
                                            );
                                            $hid_data = http_build_query($hidden_data);
                                            echo "<input type='hidden' name='data[sys_info]' value='$hid_data' />\n";
                                        ?>
                                        <textarea class="widefat" id='ecommerce_dashboard_msg' name='data[msg]' required="required"></textarea>
                                        <br/>Your Email: <input type="text" class=""
                                               name='data[sender_email]' placeholder="Email" required="required"
                                               value="<?php echo esc_attr($email); ?>"
                                               />
                                        <br/><input type="submit" class="button-primary" value="<?php _e('Send Feedback') ?>"
                                                    onclick="try { if (jQuery('#ecommerce_dashboard_msg').val().trim() == '') { alert('Enter your message.'); jQuery('#ecommerce_dashboard_msg').focus(); return false; } } catch(e) {};" />
                                        <br/>
                                        What data will be sent
                                        <a href='javascript:void(0);'
                                            onclick='jQuery(".ecommerce_dashboard_data_to_be_sent").toggle();'>(show/hide)</a>
                                        <div class="hide-if-js hide ecommerce_dashboard_data_to_be_sent">
                                            <textarea class="widefat" rows="4" readonly="readonly" disabled="disabled"><?php
                                            foreach ($hidden_data as $key => $val) {
                                                if (is_array($val)) {
                                                    $val = var_export($val, 1);
                                                }

                                                echo "$key: $val\n";
                                            }
                                            ?></textarea>
                                        </div>
                                    </form>
                                </div>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox --> <!-- /quick-contact -->
                        
                    </div> <!-- .meta-box-sortables -->

                </div> <!-- #postbox-container-1 .postbox-container -->

            </div> <!-- #post-body .metabox-holder .columns-2 -->

            <br class="clear">
        </div> <!-- #poststuff -->

       <?php eCommerce_Dashboard_Util::output_orb_widget(); ?>
       <?php eCommerce_Dashboard_Util::output_orb_widget('author'); ?>
	</div>
	<?php
}

function ecommerce_dashboard_get_plugin_data() {
    // pull only these vars
    $default_headers = array(
		'Name' => 'Plugin Name',
		'PluginURI' => 'Plugin URI',
	);

    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');

    $url = $plugin_data['PluginURI'];
    $name = $plugin_data['Name'];

    $data['name'] = $name;
    $data['url'] = $url;

    return $data;
}

/**
* adds some HTML comments in the page so people would know that this plugin powers their site.
*/
function ecommerce_dashboard_add_plugin_credits() {
    // pull only these vars
    $default_headers = array(
		'Name' => 'Plugin Name',
		'PluginURI' => 'Plugin URI',
	);

    $plugin_data = get_file_data(__FILE__, $default_headers, 'plugin');

    $url = $plugin_data['PluginURI'];
    $name = $plugin_data['Name'];

    printf(PHP_EOL . PHP_EOL . '<!-- ' . "Powered by $name | URL: $url " . '-->' . PHP_EOL . PHP_EOL);
}
