<?php
    use Cielo\API30\Ecommerce\RecurrentPayment;

  function cielo_credtpay_form(){
    ?>
    <table>
      <tr>
        <td><Label>Nome Impresso no Cartão:</Label></td>
        <td><input name='cielo_nome_cartaocred' value='<?php if(isset($_POST['cielo_nome_cartaodeb'])) echo $_POST['cielo_nome_cartaodeb'];?>' type='text'> </td>
      </tr>
      <tr>
        <td><Label>Número cartão:</Label></td>
        <td><input name='cielo_num_cartaocred' value='<?php if(isset($_POST['cielo_num_cartaodeb'])) echo $_POST['cielo_num_cartaodeb'];?>' type='text'></td>
      </tr>
      <tr>
        <td><Label>Expiração:</Label></td>
        <td><input name='cartao_exp_cartaocred' class='input-text' value='<?php if(isset($_POST['cartao_exp_cartaodeb'])) echo $_POST['cartao_exp_cartaodeb'];?>' type='month'> </td>
      </tr>
      <tr>
        <td><Label>Código Segurança (CVV):</Label>
        <td><input  name='cartao_segrcode_cartaocred' value='<?php if(isset($_POST['cartao_segrcode_cartaodeb'])) echo $_POST['cartao_segrcode_cartaodeb'];?>' type='text'> </td>
      </tr>
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
    </table>
    <?php
  }
