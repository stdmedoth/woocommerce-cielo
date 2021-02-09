 <?php
 /*
  * Plugin Name: WooCommerce Cielo
  * Description: Uma integração do WooCommerce com API Cielo
  * Version: 1.0.0
  * Author: João Calisto
*/

defined( 'ABSPATH' ) || exit;

require 'vendor/autoload.php';

add_action( 'woocommerce_init', 'wc_cielo_load_plugin'  );

add_action('wp_enqueue_scripts', 'wc_cielo_load_js');

function wc_cielo_load_plugin(){

  require __DIR__ . '/tools.php';

  require __DIR__ . '/classes/class-wc-cielo-debito-gateway.php';
  require __DIR__ . '/classes/class-wc-cielo-credito-gateway.php';

  require __DIR__ . '/forms/cielo-credito-payment-forms.php';
  require __DIR__ . '/forms/cielo-debito-payment-forms.php';

}

function wc_cielo_load_js(){
  wp_enqueue_script('forms', plugin_dir_url( __FILE__ ) . 'assets/js/forms.js');
}


function disable_footer_widgets( $sidebars_widgets )
{
    //if (is_single())
    //{
        $sidebars_widgets['sidebar'] = false;
    //}
    return $sidebars_widgets;
}

add_filter( 'sidebars_widgets', 'disable_footer_widgets' );
