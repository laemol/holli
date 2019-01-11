<?php
/**
 * Plugin Name:       Holli
 * Description:       Plugin for the Holli API
 * Version:           1.1.0
 * Author:            Talpaq
 * Author URI:        https://talpaq.com
 * Text Domain:       talpaq
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/laemol/holli
 */

/** Allow for cross-domain requests (from the front end). */
send_origin_headers();

/*
* Holli constants
*/
if (!defined('HOLLI_PLUGIN_VERSION')) {
    define('HOLLI_PLUGIN_VERSION', '1.1.0');
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
    define('HOLLI_LINK', 'https://www.tickets-tours.com/tour/details/?pid=');
}
if (!defined('HOLLI_VERSION')) {
    define('HOLLI_VERSION', 'v3');
}

/*
 * Holli stylesheet
 */

function load_plugin_css() {
    wp_enqueue_style( 'holli-style', HOLLI_URL . 'assets/css/holli.css' );
}
add_action( 'wp_enqueue_scripts', 'load_plugin_css' );

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
        add_action( 'admin_print_styles', [$this, 'utm_user_scripts'] );
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
    function utm_user_scripts() {
        $plugin_url = plugin_dir_url( __FILE__ );

    wp_enqueue_style( 'style',  HOLLI_URL. "assets/css/holli.css");
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
     * Make an API call to the Holli API and returns the response
     *
     * @return array
     */
    private function getData($resource)
    {
        $options = $this->getOptions();

        $data = [];

        $wp_request_headers = [ 
            'x-authorization:' . $options['api_key'],
            'x-authorization' => $options['api_key'],
            'Content-Type' => 'application/json'];

        $url = HOLLI_DOMAIN . '/api/' . HOLLI_VERSION . '/' . $resource ;

        $response = wp_remote_get($url, [
            'headers' => $wp_request_headers
        ]);

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

        $api_response = $this->getData('customers');

        $not_ready = (empty($data['api_key']) || empty($api_response) || isset($api_response['error']));
         ?>

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
	                <?php if ($not_ready): ?> 
                            <?php _e('You can find your api key on your <a href="https://backend.holliapp.com/profile#api" target="_blank">profile page</a>.', 'holli'); ?>
                    <?php endif; ?>
                    </p><br>

                    <label style="padding-right:20px"><?php _e('API key', 'holli'); ?>:</label>
                    <input name="holli_api_key"
                                           id="holli_api_key"
                                           class="regular-text"
                                           type="text"
                                           value="<?php echo (isset($data['api_key'])) ? $data['api_key'] : ''; ?>"/>
                    <?php echo $this->getStatusIcon(!$not_ready); ?>
                </div>

	            <?php if (!empty($data['api_key']) ): ?>

                    <?php
                    // if we don't even have a response from the API
                    if (empty($api_response)) : ?>
                        <p class="notice notice-error">
                            <?php _e('An error happened on the WordPress side. Make sure your server allows remote calls.', 'holli'); ?>
                        </p>

                    <?php
                    // If we have an error returned by the API
                    elseif (isset($api_response['error'])): ?>

                        <p class="notice notice-error">

                            <span><?php echo $api_response['error']['message'] ?></span>
                        </p>
                   
                    <?php endif; ?>

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
                <p><code>limit</code> Sets the number of products that will be displayed. Default value is <code>4</code></p>
                <p><code>recommended</code> Shows only recommended products in random order if set to 1. Default is <code>0</code></p>
                <p><code>button</code> Sets the text on the button. Default value is <code>Buy Now</code></p>
                <p><code>lang</code> Sets the language. Default value is <code>EN</code></p>
                <p><code>area</code> Display products in a certain area. Default all areas are available. Possible values: </p>

                <ul>
                <?php
                $zones = array_shift($this->getData('zones'));
                foreach($zones as $zone){
                    echo '<li>' . $zone['name'] . '<code>area=' . $zone['id'] . '</code></li>';
                }
                ?>
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
            'limit' => 4,
            'button' => 'Buy Now',
            'recommended' => 0,
            'lang' => 'en',
            'area' => '', 
            'partner_id' => null // Only used for iframe solution 
        ], $atts);

        $data = $this->getData('products?&limit=' . $value['limit'] . '&zone_id=' . $value['area'] . '&recommended=' . $value['recommended'] . '&lang=' . $value['lang']);

        if (!$options['api_key']) { 
            echo '<i>Please set your API key in the plugin settings</i>';
        }elseif(!$data){
            echo '<i>No data found</i>';
        }elseif($data['data']){
            $output = '<div class="card-container">';
        foreach ($data['data'] as $product) {
            $offset++;
            $partnerId = !is_null($value['partner_id']) ? $value['partner_id'] : $product['partnerId'];

            $output .= '<div class="card">';
            $output .= '<div class="card-inner">';
            $output .=  '<a class="card-image" href="' . HOLLI_LINK  . $partnerId . '&partnerId=' . $product['partnerId'] . '">';
            $output .=  '<img src="' . $product['media'][0]['imageUrl'] . '" alt="' . $product['name'] . '"/>';
            $output .= '<div class="card-price">';
            if ($product['prices'][0]['originalPrice'] > $product['prices'][0]['currentPrice']) {
                $output .=  '<span class="discount">&euro; ' . $product['prices'][0]['originalPrice'] . '</span>';
            }
            $output .=  '&euro; ' . $product['prices'][0]['currentPrice'] . '</div></a>';
            $output .=  '<div class="card-content">';
            $output .=  '<a class="card-title" href="' . HOLLI_LINK. $product['productId'] . '"><h4>' . $product['name'] . '</h4></a>';
            $output .=  '<p>' . ucfirst($product['type']) . ', ' . $product['category'] . '</p>';
            $output .=  '<a href="' . HOLLI_LINK  . $product['productId'] . '&partnerId=' . $partnerId  . '" class="button">' . $value['button'] . '</a>';
            $output .=  '</div></div></div>'; 
        }
        $output .=  '</div>';
        }else{
            echo '<i>Error</i>';
        }
        return $output;

        ob_get_clean(); 
    }

    /**
     * Add the Product details code to the page
     *
     * This contains the code for the Product details
     *
     * @param $force boolean
     *
     * @return void
     */
    public function addProductCode()
    {
        $data = $this->getData('products/' . $_GET['pid']);

        return($product);
    }
}

/*
 * Starts our plugin class, easy!
 */
new Holli();
