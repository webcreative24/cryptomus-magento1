<?php

class Fourtek_Bajaj_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'bajaj';
    protected $_formBlockType = 'bajaj/form_bajaj';
    protected $_infoBlockType = 'bajaj/info_bajaj';

    public function assignData($data)
    {
        $info = $this->getInfoInstance();

        if ($data->getCustomFieldOne()) {
            $info->setCustomFieldOne($data->getCustomFieldOne());
        }

        if ($data->getCustomFieldTwo()) {
            $info->setCustomFieldTwo($data->getCustomFieldTwo());
        }

        return $this;
    }

    public function validate()
    {
        parent::validate();
        $info = $this->getInfoInstance();

        return $this;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('bajaj/payment/redirect', array('_secure' => true));
    }
}
