<?php
  function cielo_credtpay_form(){
    ?>
    <table>
      <tr>
        <td><Label>Nome Impresso no Cartão:</Label></td>
        <td><input name='cielo_nome_cartaocred' type='text'> </td>
      </tr>
      <tr>
        <td><Label>Número cartão:</Label></td>
        <td><input name='cielo_num_cartaocred' type='text'></td>
      </tr>
      <tr>
        <td><Label>Expiração:</Label></td>
        <td><input name='cartao_exp_cartaocred' type='month'> </td>
      </tr>
      <tr>
        <td><Label>Código Segurança (CVV):</Label>
        <td><input  name='cartao_segrcode_cartaocred' type='text'> </td>
      </tr>
    </table>
    <?php
  }
