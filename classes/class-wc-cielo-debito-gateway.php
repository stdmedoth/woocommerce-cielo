<?php


defined( 'ABSPATH' ) or exit;

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

use Cielo\API30\Merchant;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\RecurrentPayment;
use Cielo\API30\Ecommerce\Payment;
use Cielo\API30\Ecommerce\CreditCard;
use Cielo\API30\Ecommerce\Request\CieloRequestException;

class WC_DebCielo_Gateway extends WC_Payment_Gateway{
  public function __construct(){
    $this->id = 'cielodebito';
    $this->method_title = 'Cielo Débito';
    $this->method_description = 'Integração de cartão de débito com woocommerce';
    $this->title        = $this->get_option( 'title' );
    $this->description  = $this->get_option( 'description' );
    $this->instructions = $this->get_option( 'instructions', $this->description );
    $this->enviroment  = $this->get_option( 'enviroment' );
    $this->merchant_id = $this->get_option( 'merchant_id' );
    $this->merchant_key = $this->get_option( 'merchant_key' );

    $this->has_fields = true;

    $this->init_form_fields();
    $this->init_settings();

    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

  }

  public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
      echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
    }
  }


  public function payment_fields(){
    cielo_debtpay_form();
  }

  public function validate_fields() {
    if( empty( $_POST[ 'cielo_nome_cartaodeb' ]) ) {
      wc_add_notice(  'Nome impresso no cartão é requerido!', 'error' );
      return false;
    }

    if( empty( $_POST[ 'cielo_num_cartaodeb' ]) ) {
      wc_add_notice(  'Número do cartão é requerido!', 'error' );
      return false;
    }

    if( empty( $_POST[ 'cartao_exp_cartaodeb' ]) ) {
      wc_add_notice(  'Data de Expiração do cartão é requerida!', 'error' );
      return false;
    }

    if( empty( $_POST[ 'cartao_segrcode_cartaodeb' ]) ) {
      wc_add_notice(  'Código de segurança do cartão é requerida!', 'error' );
      return false;
    }

    return true;
  }


  public function init_form_fields() {
    $this->form_fields = apply_filters( 'wc_cielo_debito_form_fields', array(
        'enabled' => array(
            'title'   => __( 'Ativar/Desativar', 'wc-cielo-debito-gateway' ),
            'type'    => 'checkbox',
            'label'   => __( 'Ativar cartão débito cielo', 'wc-cielo-debito-gateway' ),
            'default' => 'no'
        ),
        'title' => array(
            'title'       => __( 'Cielo Crédito', 'wc-cielo-debito-gateway' ),
            'type'        => 'text',
            'description' => __( 'Isso controla o título da forma de pagamento que o cliente vê durante a finalização da compra.', 'wc-cielo-debito-gateway' ),
            'default'     => __( 'Cartão de débito', 'wc-cielo-debito-gateway' ),
            'desc_tip'    => true,
        ),
        'description' => array(
            'title'       => __( 'Descrição', 'wc-cielo-debito-gateway' ),
            'type'        => 'textarea',
            'description' => __( 'Descrição da forma de pagamento que o cliente verá em sua finalização da compra.', 'wc-cielo-debito-gateway' ),
            'default'     => __( 'Efetue o pagamento no cartão de débito.', 'wc-cielo-debito-gateway' ),
            'desc_tip'    => true,
        ),
        'enviroment' => array(
          'title'       => __( 'Ambiente', 'wc-cielo-debito-gateway' ),
          'type'        => 'select',
          'options'     => array(
            'producao' => 'Produção',
            'testes'   => 'Testes'
          ),
          'description' => __( 'Ambiente utilizado na integração com API cielo', 'wc-cielo-debito-gateway' ),
          'desc_tip'    => true,
        ),

        'merchant-id' => array(
          'title'       => __( 'Merchant-ID', 'wc-cielo-debito-gateway' ),
          'type'        => 'text',
          'description' => __( 'Merchant-ID fornecido pela Cielo.', 'wc-cielo-debito-gateway' ),
          'default'     => __( '', 'wc-cielo-credito-gateway' ),
          'desc_tip'    => true,
        ),

        'merchant-key' => array(
          'title'       => __( 'Merchant-Key', 'wc-cielo-debito-gateway' ),
          'type'        => 'text',
          'description' => __( 'Merchant-Key fornecido pela Cielo.', 'wc-cielo-debito-gateway' ),
          'default'     => __( '', 'wc-cielo-credito-gateway' ),
          'desc_tip'    => true,
        ),

        'instructions' => array(
            'title'       => __( 'Instruções', 'wc-cielo-debito-gateway' ),
            'type'        => 'textarea',
            'description' => __( 'Instruções que serão adicionadas à página de agradecimento e aos e-mails.', 'wc-cielo-debito-gateway' ),
            'default'     => 'Pão de batata',
            'desc_tip'    => true,
        ),
    ) );
  }


  public function thankyou_page() {
    if ( $this->instructions ) {
      echo wpautop( wptexturize( $this->instructions ) );
    }
  }

  public function process_payment( $order_id ) {

    switch ($this->enviroment) {
      case 'producao':
        $environment = Environment::production();
        break;

      case 'testes':
        $environment = Environment::sandbox();
        break;

      default:
        wc_add_notice( __('Ambiente de pagamento não configurado') , 'error' );
        return;
    }

    if( empty( $_POST[ 'cielo_nome_cartaodeb' ]) ) {
      wc_add_notice(  'Nome impresso no cartão é requerido!', 'error' );
      return false;
    }


    $cartao_nome = $_POST['cielo_nome_cartaodeb'];
    $cartao_num =  $_POST[ 'cielo_num_cartaodeb' ];
    $cartao_exp = date("m/Y", strtotime($_POST[ 'cartao_exp_cartaodeb' ]));
    $cartao_segcode = $_POST[ 'cartao_segrcode_cartaodeb' ];

    $order = wc_get_order( $order_id );
    $order_customer = new WC_Customer( $order->get_customer_id('view') );
    if(!$order_customer){
      wc_add_notice( __('Não foi possível receber cadastro do cliente') , 'error' );
      return;
    }

    $merchant = new Merchant($this->merchant_id, $this->merchant_key);
    if(!$merchant){
      wc_add_notice( __('Não foi possível criar Merchant') , 'error' );
      return;
    }

    $sale = new Sale($order_id);
    if(!$sale){
      wc_add_notice( __('Não foi possível criar instancia de venda') , 'error' );
      return;
    }


    $cielo_customer = $sale->customer( $cartao_nome );
    if(!$cielo_customer){
      wc_add_notice( __('Não foi possível receber cliente para pagamento') , 'error' );
      return;
    }
    // Crie uma instância de Payment informando o valor do pagamento

    $payment = $sale->payment($order->calculate_totals(true));
    if(!$payment){
      wc_add_notice( __('Não foi possível instanciar pagamento') , 'error' );
      return;
    }

    //$payment->setReturnUrl( );

    $payment->debitCard($cartao_segcode, CreditCard::VISA)
            ->setExpirationDate($cartao_exp)
            ->setCardNumber($cartao_num)
            ->setHolder($cartao_nome);

    $sale = (new CieloEcommerce($merchant, $environment))->createSale($sale);
    if(!$sale){
      wc_add_notice( __('Não foi possível instanciar venda no webservice') , 'error' );
      return;
    }

    $paymentId = $sale->getPayment()->getPaymentId();
    if(!$paymentId){
      wc_add_notice( __('Não foi possível receber id do pagamento') , 'error' );
      return;
    }

    $order->payment_complete();
    $order->update_status( 'on-hold', __( 'Aguardando pagamento', 'wc-cielo-credito-gateway' ) );
    $order->reduce_order_stock();
    WC()->cart->empty_cart();
    return array(
        'result'    => 'success',
        'redirect'  => $this->get_return_url( $order )
    );
  }
}

function wc_debcielo_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_DebCielo_Gateway';
    return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'wc_debcielo_add_to_gateways' );
