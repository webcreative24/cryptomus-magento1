<?php
// app/code/local/Envato/Custompaymentmethod/Block/Form/Custompaymentmethod.php
class Fourtek_Bajaj_Block_Form_Bajaj extends Mage_Payment_Block_Form
{
  protected function _construct()
  {
    parent::_construct();
    $this->setTemplate('bajaj/form/bajaj.phtml');
  }
}