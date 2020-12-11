<?php
  function cielo_debtpay_form(){
    ?>
    <table>
      <tr>
        <td><Label>Nome Impresso no Cartão:</Label></td>
        <td><input name='cielo_nome_cartaodeb' type='text'> </td>
      </tr>
      <tr>
        <td><Label>Número cartão:</Label></td>
        <td><input name='cielo_num_cartaodeb' type='text'></td>
      </tr>
      <tr>
        <td><Label>Expiração:</Label></td>
        <td><input name='cartao_exp_cartaodeb' type='month'> </td>
      </tr>
      <tr>
        <td><Label>Código Segurança (CVV):</Label>
        <td><input  name='cartao_segrcode_cartaodeb' type='text'> </td>
      </tr>
    </table>    <?php
  }
