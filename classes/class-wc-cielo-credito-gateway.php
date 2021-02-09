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


class WC_CieloCredito_Gateway extends WC_Payment_Gateway{

  public function __construct(){
    $this->id = 'cielocredito';
    $this->method_title = 'Cielo Crédito';
    $this->method_description = 'Integração de cartão de crédito com woocommerce';
    $this->title        = $this->get_option( 'title' );
    $this->description  = $this->get_option( 'description' );
    $this->instructions = $this->get_option( 'instructions', $this->description );
    $this->enviroment  = $this->get_option( 'enviroment' );
    $this->merchant_id = $this->get_option( 'merchant_id' );
    $this->merchant_key = $this->get_option( 'merchant_key' );
    $this->tipo_pagamento = $this->get_option( 'tipo_pagamento' );
    $this->bandeira = null;

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
    cielo_credtpay_form( $this->tipo_pagamento );
  }

  public function validate_fields() {

    if( empty( $_POST[ 'cielo_nome_cartaocred' ]) ) {
		  wc_add_notice(  'Nome impresso no cartão é requerido!', 'error' );
		  return false;
    }

    $nome = $_POST[ 'cielo_nome_cartaocred' ];
    $contagem = str_word_count($nome);
    if($contagem <= 1){
      wc_add_notice(  'Insira o nome como está no cartão!', 'error' );
		  return false;
    }

    if( empty( $_POST[ 'cielo_num_cartaocred' ] ) ) {
		  wc_add_notice(  'Número do cartão é requerido!', 'error' );
		  return false;
    }

    if( empty( $_POST[ 'cartao_exp_cartaocred' ]) ) {
      wc_add_notice(  'Data de Expiração do cartão é requerida!', 'error' );
      return false;
    }

    if( empty( $_POST[ 'cartao_segrcode_cartaocred' ]) ) {
      wc_add_notice(  'Código de segurança do cartão é requerida!', 'error' );
      return false;
    }

    if( $this->tipo_pagamento == 'recorrente' ){
      if( empty($_POST[ 'cartao_recur_cartaocred' ])){
        wc_add_notice(  'Não foi possível identificar recorrencia!', 'error' );
        return false;
      }
    }

    $cartao_num = $_POST[ 'cielo_num_cartaocred' ];

    if(startsWith($cartao_num, '38')){
      $this->bandeira = CreditCard::DINERS;
    }else
    if(startsWith($cartao_num, '35')){
      $this->bandeira = CreditCard::JCB;
    }else
    if(startsWith($cartao_num, '4')){
      $this->bandeira = CreditCard::VISA;
    }else
    if(startsWith($cartao_num, '5')){
      $this->bandeira = CreditCard::MASTERCARD;
    }else
    if(startsWith($cartao_num, '6')){
      $this->bandeira = CreditCard::DISCOVER;
    }else{
      wc_add_notice(  'Bandeira do cartão não registrada!', 'error' );
      return false;
    }

    return true;
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

        'merchant_id' => array(
          'title'       => __( 'Merchant-ID', 'wc-cielo-credito-gateway' ),
          'type'        => 'text',
          'description' => __( 'Merchant-ID fornecido pela Cielo.', 'wc-cielo-credito-gateway' ),
          'default'     => __( '', 'wc-cielo-credito-gateway' ),
          'desc_tip'    => true,
        ),

        'merchant_key' => array(
          'title'       => __( 'Merchant-Key', 'wc-cielo-credito-gateway' ),
          'type'        => 'text',
          'description' => __( 'Merchant-Key fornecido pela Cielo.', 'wc-cielo-credito-gateway' ),
          'default'     => __( '', 'wc-cielo-credito-gateway' ),
          'desc_tip'    => true,
        ),
        'tipo_pagamento' => array(
          'title'       => __( 'Tipo de Pagamento', 'wc-cielo-debito-gateway' ),
          'type'        => 'select',
          'options'     => array(
            'padrao' => 'Padrão',
            'recorrente' => 'Recorrente'
          )
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


  public function thankyou_page() {
    if ( $this->instructions ) {
      echo wpautop( wptexturize( $this->instructions ) );
    }
  }

  public function process_payment( $order_id ) {
    //$this->enviroment = 'testes';

    switch ($this->enviroment) {
      case 'producao':
        $environment = Environment::production();
        break;

      case 'testes':
        $environment = Environment::sandbox();
        break;

      default:
        wc_add_notice( 'Ambiente de pagamento não configurado', 'error' );
        return ;
    }

    $cartao_nome = $_POST['cielo_nome_cartaocred'];
    $cartao_num =  $_POST[ 'cielo_num_cartaocred' ];
    $cartao_exp = date("m/Y", strtotime($_POST[ 'cartao_exp_cartaocred' ]));
    $cartao_segcode = $_POST[ 'cartao_segrcode_cartaocred' ];
    $recorrencia = $_POST[ 'cartao_recur_cartaocred' ];

    $order = wc_get_order( $order_id );
    $order_customer = new WC_Customer( $order->get_customer_id('view') );
    if(!$order_customer){
      wc_add_notice( 'Não foi possível receber cadastro do cliente', 'error' );
      return ;
    }
    $sale = new Sale($order_id);
    if(!$sale){
      wc_add_notice( 'Não foi possível criar instancia de venda', 'error' );
      return ;
    }

    $cielo_customer = $sale->customer($order_customer->get_first_name() . " " .$order_customer->get_last_name());
    if(!$cielo_customer){
      wc_add_notice( 'Não foi possível cadastrar cliente para pagamento', 'error' );
      return ;
    }

    $merchant = new Merchant($this->merchant_id, $this->merchant_key);
    if(!$merchant){
      wc_add_notice( 'Não foi possível criar Merchant', 'error' );
      return ;
    }

    $valor = money_format('%i', $order->calculate_totals(true));
    $valor = str_replace('.', '', $valor); // remove o ponto
    $valor = str_replace(',', '', $valor); // remove a virgula

    $payment = $sale->payment($valor);
    if(!$payment){
      wc_add_notice( 'Não foi possível instanciar pagamento', 'error' );
      return ;
    }

    $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
            ->creditCard($cartao_segcode, $this->bandeira)
            ->setExpirationDate($cartao_exp)
            ->setCardNumber($cartao_num)
            ->setHolder($cartao_nome);

    $payment->recurrentPayment(true)->setInterval($recorrencia);


    try {

      $sale = (new CieloEcommerce($merchant, $environment))->createSale($sale);

      $recurrentPaymentId = $sale->getPayment()->getRecurrentPayment()->getRecurrentPaymentId();

      $pay_code = $sale->getPayment()->getRecurrentPayment()->getReasonCode();
      $pay_message = $sale->getPayment()->getRecurrentPayment()->getReasonMessage();

      if($pay_code != 0){
        wc_add_notice( 'Não foi possível instanciar venda no webservice: ' . $pay_code . ':' . $pay_message, 'error' );
        return ;
      }

      $order->update_status( 'on-hold', __( 'Pagamento efetuado', 'wc-cielo-credito-gateway' ) );

    } catch (CieloRequestException $e) {
      wc_add_notice( 'Não foi possível instanciar venda no webservice: ' . $e->getCieloError(), 'error' );
      return ;
    }

    $order->payment_complete();
    $order->update_status( 'on-hold', 'Pedido efetuado');
    $order->reduce_order_stock();

    WC()->cart->empty_cart();
    return array(
        'result'    => 'success',
        //'messages'  => 'Pedido efetuado com sucesso',
        'redirect'  => $this->get_return_url( $order )
    );
  }
}


function wc_credcielo_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_CieloCredito_Gateway';
    return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'wc_credcielo_add_to_gateways' );
