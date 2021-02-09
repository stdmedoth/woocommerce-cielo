<?php

defined( 'ABSPATH' ) or exit;

use Cielo\API30\Ecommerce\RecurrentPayment;

function cielo_credtpay_form($tipo_pagamento){
  ?>
  <table>
    <tr>
      <td><Label>Nome Impresso no Cartão:</Label></td>
      <td><input name='cielo_nome_cartaocred' type='text' class="input-text wc-credit-card-form-name" maxlength="100" value='<?php if(isset($_POST['cielo_nome_cartaodeb'])) echo $_POST['cielo_nome_cartaocred'];?>'> </td>
    </tr>
    <tr>

      <td><Label for='ccNo'>Número cartão:</Label></td>
      <td><input name='cielo_num_cartaocred' type='text' class="input-text wc-credit-card-form-card-number validate-required " onkeyup="return checkDigit(this, event, value)"  maxlength="20"  placeholder="•••• •••• •••• ••••"  value='<?php if(isset($_POST['cielo_num_cartaocred'])) echo $_POST['cielo_num_cartaocred'];?>'></td>
    </tr>
    <tr>
      <td><Label>Expiração:</Label></td>
      <td><input name='cartao_exp_cartaocred' type='month' class='input-text' autocomplete="cc-exp" value='<?php if(isset($_POST['cartao_exp_cartaocred'])) echo $_POST['cartao_exp_cartaocred'];?>'> </td>
    </tr>
    <tr>
      <td><Label>Código Segurança (CVV):</Label>
      <td><input  name='cartao_segrcode_cartaocred' autocomplete="cc-csc" maxlength="3" value='<?php if(isset($_POST['cartao_segrcode_cartaocred'])) echo $_POST['cartao_segrcode_cartaocred'];?>' type='text'> </td>
    </tr>
    <?php if($tipo_pagamento == 'recorrente'){  ?>
    <tr>
      <td>
        <select name='cartao_recur_cartaocred' class='input-text'>
          <option value='<?php echo RecurrentPayment::INTERVAL_MONTHLY?>'>
            Mensalmente
          </option>
          <option value='<?php echo RecurrentPayment::INTERVAL_BIMONTHLY?>'>
            Bimestral
          </option>
          <option value='<?php echo RecurrentPayment::INTERVAL_QUARTERLY?>'>
            Trimestral
          </option>
          <option value='<?php echo RecurrentPayment::INTERVAL_SEMIANNUAL?>'>
            Semestral
          </option>
          <option value='<?php echo RecurrentPayment::INTERVAL_ANNUAL?>'>
            Anual
          </option>
        </select>
      <td>
    </tr>
  <?php } ?>
  </table>
  <?php
}
