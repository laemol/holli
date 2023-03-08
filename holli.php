<?php
/**
 * Plugin Name:       Holli
 * Description:       Plugin for the Holli API
 * Version:           1.5.1
 * Author:            Talpaq
 * Author URI:        https://talpaq.com
 * Text Domain:       talpaq
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/laemol/holli
 */

 defined( 'ABSPATH' ) || exit;

if( ! class_exists( 'updateChecker' ) ) {

	class updateChecker{

		public $plugin_slug;
		public $version;
		public $cache_key;
		public $cache_allowed;

		public function __construct() {

			$this->plugin_slug = plugin_basename( __DIR__ );
			$this->version = '1.0';
			$this->cache_key = 'holli_custom_upd';
			$this->cache_allowed = false;

			add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
			add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
			add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

		}

		public function request(){

			$remote = get_transient( $this->cache_key );

			if( false === $remote || ! $this->cache_allowed ) {

				$remote = wp_remote_get(
					'https://talpaq.com/holli-wp/info.json',
					array(
						'timeout' => 10,
						'headers' => array(
							'Accept' => 'application/json'
						)
					)
				);

				if(
					is_wp_error( $remote )
					|| 200 !== wp_remote_retrieve_response_code( $remote )
					|| empty( wp_remote_retrieve_body( $remote ) )
				) {
					return false;
				}

				set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ) );

			return $remote;

		}

		function info( $res, $action, $args ) {

			// print_r( $action );
			// print_r( $args );

			// do nothing if you're not getting plugin information right now
			if( 'plugin_information' !== $action ) {
				return $res;
			}

			// do nothing if it is not our plugin
			if( $this->plugin_slug !== $args->slug ) {
				return $res;
			}

			// get updates
			$remote = $this->request();

			if( ! $remote ) {
				return $res;
			}

			$res = new stdClass();

			$res->name = $remote->name;
			$res->slug = $remote->slug;
			$res->version = $remote->version;
			$res->tested = $remote->tested;
			$res->requires = $remote->requires;
			$res->author = $remote->author;
			$res->author_profile = $remote->author_profile;
			$res->download_link = $remote->download_url;
			$res->trunk = $remote->download_url;
			$res->requires_php = $remote->requires_php;
			$res->last_updated = $remote->last_updated;

			$res->sections = array(
				'description' => $remote->sections->description,
				'installation' => $remote->sections->installation,
				'changelog' => $remote->sections->changelog
			);

			if( ! empty( $remote->banners ) ) {
				$res->banners = array(
					'low' => $remote->banners->low,
					'high' => $remote->banners->high
				);
			}

			return $res;

		}

		public function update( $transient ) {

			if ( empty($transient->checked ) ) {
				return $transient;
			}

			$remote = $this->request();

			if(
				$remote
				&& version_compare( $this->version, $remote->version, '<' )
				&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
				&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
			) {
				$res = new stdClass();
				$res->slug = $this->plugin_slug;
				$res->plugin = plugin_basename( __FILE__ );
				$res->new_version = $remote->version;
				$res->tested = $remote->tested;
				$res->package = $remote->download_url;

				$transient->response[ $res->plugin ] = $res;

	    }

			return $transient;

		}

		public function purge( $upgrader, $options ){

			if (
				$this->cache_allowed
				&& 'update' === $options['action']
				&& 'plugin' === $options[ 'type' ]
			) {
				// just clean the cache when new plugin version is installed
				delete_transient( $this->cache_key );
			}

		}

	}

	new updateChecker();

}

/** Allow for cross-domain requests (from the front end). */
send_origin_headers();

/*
* Holli constants
*/
if (!defined('HOLLI_PLUGIN_VERSION')) {
    define('HOLLI_PLUGIN_VERSION', '1.3.3');
}
if (!defined('HOLLI_URL')) {
    define('HOLLI_URL', plugin_dir_url(__FILE__));
}
if (!defined('HOLLI_PATH')) {
    define('HOLLI_PATH', plugin_dir_path(__FILE__));
}
if (!defined('HOLLI_DOMAIN')) {
    define('HOLLI_DOMAIN', 'https://backend.holliapp.com');
}
if (!defined('HOLLI_LINK')) {
    define('HOLLI_LINK', 'https://tickets-tours.com');
}
if (!defined('HOLLI_PAGE')) {
    define('HOLLI_PAGE', 'activity');
}
if (!defined('HOLLI_VERSION')) {
    define('HOLLI_VERSION', 'v4');
}

/*
 * Holli stylesheet
 */

function load_plugin_css()
{
    wp_enqueue_style('holli-style', HOLLI_URL . 'assets/css/holli.css', [], HOLLI_PLUGIN_VERSION);
}
add_action('wp_enqueue_scripts', 'load_plugin_css');

/**
 * Main Holli Class
 *
 * This class creates the option page and add the web app scripts
 */
class Holli
{
    /**
     * The security nonce
     *
     * @var string
     */
    private $_nonce = 'holli-admin';

    /**
     * The option name
     *
     * @var string
     */
    private $option_name = 'holli_data';

    /**
     * Holli constructor.
     *
     * The main plugin actions registered for WordPress
     */
    public function __construct()
    {
        // Register Shortcode
        add_shortcode('products', [$this, 'addProductListCode']);

        // Admin page calls
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('wp_ajax_store_admin_data', [$this, 'storeAdminData']);
        add_action('admin_enqueue_scripts', [$this, 'addAdminScripts']);
        add_action('wp_enqueue_style', [$this, 'addStyleScripts']);
        add_action('admin_print_styles', [$this, 'utm_user_scripts']);
        register_activation_hook( __FILE__, [$this, 'plugin_activate'] );
    }

    /**
     * Runs on plugin activation
     *
     * @return array
     */
    function plugin_activate() {

         // Delete cache to prevent invalid results
         delete_transient('holli_api_results');
    }
    
    /**
     * Returns the saved options data as an array
     *
     * @return array
     */
    private function getOptions()
    {
        return get_option($this->option_name, []);
    }

    /**
     * Callback for the Ajax request
     *
     * Updates the options data
     *
     * @return void
     */
    public function storeAdminData()
    {
        if (wp_verify_nonce($_POST['security'], $this->_nonce) === false) {
            die('Invalid Request! Reload your page please.');
        }

        $data = $this->getOptions();

        foreach ($_POST as $field => $value) {
            if (substr($field, 0, 6) !== 'holli_') {
                continue;
            }

            if (empty($value)) {
                unset($data[$field]);
            }

            $field = substr($field, 6);

            $data[$field] = esc_attr__($value);
        }

        update_option($this->option_name, $data);

        echo __('Saved!', 'holli');
        die();
    }

    /**
     * Admin Scripts for the Ajax call
     */
    public function addAdminScripts()
    {
        wp_enqueue_script('holli-admin', HOLLI_URL . 'assets/js/admin.js', [], 1.0);

        $admin_options = [
            'ajax_url' => admin_url('admin-ajax.php'),
            '_nonce' => wp_create_nonce($this->_nonce)
        ];

        wp_localize_script('holli-admin', 'holli_exchanger', $admin_options);
    }

    /**
     * Plugin Stylesheets
     */
    public function utm_user_scripts()
    {
        $plugin_url = plugin_dir_url(__FILE__);

        wp_enqueue_style('style', HOLLI_URL . 'assets/css/holli.css');
    }

    /**
     * Adds the Holli label to the WordPress Admin Sidebar Menu
     */
    public function addAdminMenu()
    {
        add_menu_page(
            __('Holli API', 'holli'),
            __('Holli API', 'holli'),
            'manage_options',
            'holli',
            [$this, 'adminLayout'],
            'dashicons-admin-generic'
        );
    }

    /**
     * Make an API call to the Holli API and returns the (cached) response
     *
     * @return array
     */
    private function getData($resource, $key)
    {
        $options = $this->getOptions();

        $data = [];

        $wp_request_headers = [
            'x-authorization:' . $options['api_key'],
            'x-authorization' => $options['api_key'],
            'Content-Type' => 'application/json'
        ];

        $url = HOLLI_DOMAIN . '/api/' . HOLLI_VERSION . '/' . $resource;

        $cached = get_transient($key);

        if (false !== $cached) {
            $response = $cached;
        } else {
            $response = wp_remote_get($url, [
                'headers' => $wp_request_headers
            ]);

            // Cache the response
            set_transient($key, $response, 24 * HOUR_IN_SECONDS);
        }

        if (is_array($response) && !is_wp_error($response)) {
            $data = json_decode($response['body'], true);
        }

        return $data;
    }

    /**
     * Get a Dashicon for a given status
     *
     * @param $valid boolean
     *
     * @return string
     */
    private function getStatusIcon($valid)
    {
        return ($valid) ? '<span class="dashicons dashicons-yes success-message" style="color:green;font-size:24px"></span>' : '<span class="dashicons dashicons-no-alt error-message" style="font-size:24px"></span>';
    }

    /**
     * Outputs the Admin Dashboard layout containing the form with all its options
     *
     * @return void
     */
    public function adminLayout()
    {
        $data = $this->getOptions();

        // Delete cache to prevent invalid results
        delete_transient('holli_whoami');

        $api_response = $this->getData('whoami', 'holli_whoami');

        $not_ready = (empty($data['api_key']) || empty($api_response) || isset($api_response['error'])); ?>

    <div class="wrap">

        <h1><?php _e('Holli API Settings', 'holli'); ?></h1>

        <form id="holli-admin-form" class="postbox">

            <div class="form-group inside">
                <?php
                /*
                     * --------------------------
                     * API Settings
                     * --------------------------
                     */

                ?>

                <p>
                    <?php if ($not_ready) : ?>
                        <?php _e('You can find your api key on your <a href="https://www.holli-daytickets.com/profile#api" target="_blank">profile page</a>.', 'holli'); ?>
                    <?php endif; ?>
                </p><br>

                <label style="padding-right:20px"><?php _e('API key', 'holli'); ?>:</label>
                <input name="holli_api_key" id="holli_api_key" class="regular-text" type="text" value="<?php echo (isset($data['api_key']) ? $data['api_key'] : null); ?>" />

                <?php echo $this->getStatusIcon(!$not_ready); ?>

            </div>

            <?php if (!empty($data['api_key'])) : ?>

                <?php
                // if we don't even have a response from the API
                if (empty($api_response)) : ?>
                    <p class="notice notice-error">
                        <?php _e('An error happened on the WordPress side. Make sure your server allows remote calls.', 'holli'); ?>
                    </p>

                <?php
            // If we have an error returned by the API
            elseif (isset($api_response['error'])) : ?>

                    <p class="notice notice-error">

                        <span><?php echo $api_response['error']['message'] ?></span>
                    </p>

                    <?php

                    delete_option($this->option_name);

                endif; ?>

            <?php endif; ?>

            <hr>

            <div class="inside">

                <button class="button button-primary" id="holli-admin-save" type="submit">
                    <?php _e('Save', 'holli'); ?>
                </button>

            </div>
        </form>
    </div>

    <?php
    // if we have a good response from the API
    if (!isset($api_response['error']) && !empty($api_response)) : ?>
        <div class="wrap">
            <form id="holli-admin-option" class="postbox">
                <div class="form-group inside">
                <h3><?php _e('Shortcode', 'holli'); ?></h3>
                The shortcode <code>[products]</code> displays the Holli products

                <h3>Options</h3>
                <p><code>id</code> Give a (unique) id for the shortcode if you want to use multiple times. Default value is <code>1</code></p>
                <p><code>limit</code> Sets the number of products that will be displayed. Default value is <code>4</code></p>
                <p><code>recommended</code> Shows only recommended products in random order if set to 1. Default is <code>0</code></p>
                <p><code>color</code> Sets the background color on the price tag and button. Default value from stylesheet</p>
                <p><code>button</code> Sets the text on the button. Default value is <code>Buy Now</code></p>
                <p><code>lang</code> Sets the language. Default value is <code>EN</code></p>
                <p><code>cat</code> Display products from a specific category. Default all categories are available. Possible values: </p>
                <ul>
                <?php
                        $cats = array_shift($this->getData('product/categories', 'holli_categories'));

                        foreach ($cats as $cat) {
                            echo '<li style="padding-left:30px;">' . $cat['name'] . '<code>cat=' . $cat['id'] . '</code></li>';
                        } ?>
                </ul>
                <p><code>area</code> Display products in a certain area. Default all areas are available. Possible values: </p>
                
                <ul>
                <?php

                        // Save GUID
                        update_option($this->option_name, ['api_guid' => $api_response['guid'], 'api_key' => $data['api_key']]);

                        $zones = array_shift($this->getData('zones', 'holli_zones'));

                        foreach ($zones as $zone) {
                            echo '<li style="padding-left:30px;">' . $zone['name'] . '<code>area=' . $zone['id'] . '</code></li>';
                        } ?>
                </ul>
                
                </div>
            </form>
        </div>

    <?php endif; ?>

<?php
}

/**
 * Add the web app code to the page
 *
 * This contains the shortcode markup used by the web app and the widget API call on the frontend
 *
 * @param $force boolean
 *
 * @return void
 */
public function addProductListCode($atts = '')
{
    ob_start();

    $options = $this->getOptions();

    $value = shortcode_atts([
        'id' => '',
        'limit' => 4,
        'color' => '',
        'button' => 'Buy Now',
        'recommended' => 0,
        'lang' => 'en',
        'area' => '',
        'partner_id' => null, // Only used for iframe solution
        'cat' => '',
    ], $atts);

    $data = $this->getData('products?&limit=' . $value['limit'] . '&zone_id=' . $value['area'] . '&recommended=' . $value['recommended'] . '&lang=' . $value['lang'] . '&category_id=' . $value['cat'], 'holli_api_tickets' . $value['id'] . $value['limit'] . $value['area']  . $value['recommended'] . $value['lang'] . $value['cat']);

    if (!$options['api_key']) {
        echo '<i>Please set your API key in the plugin settings</i>';
    } elseif (!$data) {
        echo '<i>No data found</i>';
    } elseif ($data['data']) {
        $output = '<div class="card-container">';
        foreach ($data['data'] as $product) {
            $link = HOLLI_LINK . ($value['lang'] === "en" ? '/' : '/' . $value['lang'] . '/') . HOLLI_PAGE . '/' . $product['slug'] . '-' . $product['id']  . '?partnerId=' . $options['api_guid'];
            $output .= '<div class="card">';
            $output .= '<div class="card-inner">';
            $output .= '<a class="card-image" href="' . $link . '" target="_blank">';
            $output .= '<img src="' . $product['media'][0]['imageUrl'] . '" style="height:140px" alt="' . $product['name'] . '"/>';
            $output .= '<div class="card-price" style="background-color:' . $value['color'] . '">';
            if ($product['originalPrice'] > $product['currentPrice']) {
                $output .= '<span class="discount">&euro; ' . $product['originalPrice'] . '</span>';
            }
            $output .= '&euro; ' . $product['currentPrice'] . '</div></a>';
            $output .= '<div class="card-content">';
            $output .= '<a class="card-title" href="' . $link . '" target="_blank"><h4>' . $product['name'] . '</h4></a>';
            $output .= '<p>' . ucfirst($product['type']) . ', ' . $product['category'] . '</p>';
            $output .= '<a href="' . $link . '" class="button" target="_blank" style="background-color:' . $value['color'] . '">' . $value['button'] . '</a>';
            $output .= '</div></div></div>';
        }
        $output .= '</ul>';
    } else {
        echo '<i>Oops, something is wrong</i>';
        var_dump($data);
    }
    return $output;

    ob_get_clean();
}
}

/*
 * Starts our plugin class, easy!
 */
new Holli();
