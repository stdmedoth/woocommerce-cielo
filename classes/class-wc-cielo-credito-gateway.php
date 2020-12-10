<?php

defined( 'ABSPATH' ) or exit;

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

use Cielo\API30\Merchant;

use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\Payment;
use Cielo\API30\Ecommerce\CreditCard;
use Cielo\API30\Ecommerce\Request\CieloRequestException;


function wc_credcielo_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_CredCielo_Gateway';
    return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'wc_credcielo_add_to_gateways' );


class WC_CredCielo_Gateway extends WC_Payment_Gateway{
  public function __construct(){
    $this->id = 'cielocredito';
    $this->method_title = 'Cielo Crédito';
    $this->method_description = 'Integração de cartão de crédito com woocommerce';
    $this->title        = $this->get_option( 'title' );
    $this->description  = $this->get_option( 'description' );
    $this->instructions = $this->get_option( 'instructions', $this->description );
    $this->enviroment  = $this->get_option( 'enviroment' );

    $this->init_form_fields();
    $this->init_settings();

    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
  }

  public function init_form_fields() {

    $this->form_fields = apply_filters( 'wc_cielo_credito_form_fields', array(

        'enabled' => array(
            'title'   => __( 'Ativar/Desativar', 'wc-cielo-credito-gateway' ),
            'type'    => 'checkbox',
            'label'   => __( 'Ativar cartão crédito cielo', 'wc-cielo-credito-gateway' ),
            'default' => 'no'
        ),

        'title' => array(
            'title'       => __( 'Cielo Crédito', 'wc-cielo-credito-gateway' ),
            'type'        => 'text',
            'description' => __( 'Isso controla o título da forma de pagamento que o cliente vê durante a finalização da compra.', 'wc-cielo-credito-gateway' ),
            'default'     => __( 'Cartão de cŕedito', 'wc-cielo-credito-gateway' ),
            'desc_tip'    => true,
        ),

        'description' => array(
            'title'       => __( 'Descrição', 'wc-cielo-credito-gateway' ),
            'type'        => 'textarea',
            'description' => __( 'Descrição da forma de pagamento que o cliente verá em sua finalização da compra.', 'wc-cielo-credito-gateway' ),
            'default'     => __( 'Efetue o pagamento no cartão de crédito.', 'wc-cielo-credito-gateway' ),
            'desc_tip'    => true,
        ),
        'enviroment' => array(
          'title'       => __( 'Ambiente', 'wc-cielo-credito-gateway' ),
          'type'        => 'select',
          'options'     => array(
            'producao' => 'Produção',
            'testes'   => 'Testes'),
          'description' => __( 'Ambiente utilizado na integração com API cielo', 'wc-cielo-credito-gateway' ),
          'desc_tip'    => true,
        ),

        'merchant-id' => array(
          'title'       => __( 'Merchant-ID', 'wc-cielo-credito-gateway' ),
          'type'        => 'text',
          'description' => __( 'Merchant-ID fornecido pela Cielo.', 'wc-cielo-credito-gateway' ),
          'default'     => __( '', 'wc-cielo-credito-gateway' ),
          'desc_tip'    => true,
        ),

        'merchant-key' => array(
          'title'       => __( 'Merchant-Key', 'wc-cielo-credito-gateway' ),
          'type'        => 'text',
          'description' => __( 'Merchant-Key fornecido pela Cielo.', 'wc-cielo-credito-gateway' ),
          'default'     => __( '', 'wc-cielo-credito-gateway' ),
          'desc_tip'    => true,
        ),

        'instructions' => array(
            'title'       => __( 'Instruções', 'wc-cielo-credito-gateway' ),
            'type'        => 'textarea',
            'description' => __( 'Instruções que serão adicionadas à página de agradecimento e aos e-mails.', 'wc-cielo-credito-gateway' ),
            'default'     => '',
            'desc_tip'    => true,
        ),
    ) );
  }

  public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
      echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
    }
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

    $order = wc_get_order( $order_id );
    $order_customer = new WC_Customer( $order->get_customer_id('view') );
    if(!$order_customer){
      wc_add_notice( __('Não foi possível receber cadastro do cliente') , 'error' );
      return;
    }

    $merchant = new Merchant('MERCHANT ID', 'MERCHANT KEY');
    if(!$merchant){
      wc_add_notice( __('Não foi possível criar Merchant') , 'error' );
      return;
    }

    $sale = new Sale($order_id);
    if(!$sale){
      wc_add_notice( __('Não foi possível criar instancia de venda') , 'error' );
      return;
    }


    $cielo_customer = $sale->customer($order_customer->get_display_name( 'view' ));
    if(!$cielo_customer){
      wc_add_notice( __('Não foi possível cadastrar cliente para pagamento') , 'error' );
      return;
    }
    // Crie uma instância de Payment informando o valor do pagamento

    $payment = $sale->payment($order->calculate_totals(true));
    if(!$payment){
      wc_add_notice( __('Não foi possível instanciar pagamento') , 'error' );
      return;
    }

    $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
        ->creditCard("123", CreditCard::VISA)
        ->setCardToken("TOKEN-PREVIAMENTE-ARMAZENADO");

    // Crie o pagamento na Cielo
    try {
        // Configure o SDK com seu merchant e o ambiente apropriado para criar a venda
        $sale = (new CieloEcommerce($merchant, $environment))->createSale($sale);
        // Com a venda criada na Cielo, já temos o ID do pagamento, TID e demais
        // dados retornados pela Cielo
        $paymentId = $sale->getPayment()->getPaymentId();
    } catch (CieloRequestException $e) {
        // Em caso de erros de integração, podemos tratar o erro aqui.
        // os códigos de erro estão todos disponíveis no manual de integração.
        wc_add_notice( __('Erro no pagamento') . $e->getCieloError() , 'error' );
        return;
    }

    // Mark as on-hold (we're awaiting the payment)
    $order->update_status( 'on-hold', __( 'Aguardando pagamento', 'wc-cielo-credito-gateway' ) );

    // Reduce stock levels
    $order->reduce_order_stock();

    // Remove cart
    WC()->cart->empty_cart();

    // Return thankyou redirect
    return array(
        'result'    => 'success',
        'redirect'  => $this->get_return_url( $order )
    );
  }
}
