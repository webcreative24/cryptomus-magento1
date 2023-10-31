<?php
class Fourtek_Bajaj_Helper_Data extends Mage_Core_Helper_Abstract
{
  function getPaymentGatewayUrl() 
  {
    return Mage::getUrl('bajaj/payment/gateway', array('_secure' => false));
  }
}