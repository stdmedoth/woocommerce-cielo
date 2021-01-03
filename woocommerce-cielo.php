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
  require 'classes/class-wc-cielo-debito-gateway.php';
  require 'classes/class-wc-cielo-credito-gateway.php';

  require 'forms/cielo-credito-payment-forms.php';
  require 'forms/cielo-debito-payment-forms.php';
}

function wc_cielo_load_js(){
  wp_enqueue_script('forms', plugin_dir_url( __FILE__ ) . 'assets/js/forms.js');
}
