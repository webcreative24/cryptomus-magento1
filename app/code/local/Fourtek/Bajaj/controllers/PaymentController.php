<?php
require_once Mage::getBaseDir() . DS . 'lib/cryptomus/src/Payment.php';

class Fourtek_Bajaj_PaymentController extends Mage_Core_Controller_Front_Action
{
    const CRYPTOMUS_ERROR_STATUSES = [
        'fail',
        'system_fail',
        'wrong_amount',
        'cancel',
    ];

    const CRYPTOMUS_PENDING_STATUSES = [
        'process',
        'check',
    ];

    const PAID_STATUSES = [
        'paid',
        'paid_over',
    ];


    public function succesorderAction()
    {

        $param = $this->getRequest()->getRawBody();
        if ($param) {
            $response = json_decode($param, true);
            $order = Mage::getModel('sales/order')->loadByIncrementId($response['order_id']);
            if ($order->getEntityId()) {
                $orderId = $response['order_id'];
                $cryptomusOrderStatus = $response['status'];

                $ocOrderStatus = null;
                if (in_array($cryptomusOrderStatus, self::CRYPTOMUS_ERROR_STATUSES)) {
                    $ocOrderStatus = 'payment_cryptomus_invalid_status_id';
                } elseif (in_array($cryptomusOrderStatus, self::CRYPTOMUS_PENDING_STATUSES)) {
                    $ocOrderStatus = 'payment_cryptomus_pending_status_id';
                } elseif (in_array($cryptomusOrderStatus, self::PAID_STATUSES)) {
                    $ocOrderStatus = 'payment_cryptomus_paid_status_id';
                }

                if ($ocOrderStatus && $ocOrderStatus === 'payment_cryptomus_paid_status_id') {
                    $this->createInvoice($order);
                } elseif ($ocOrderStatus === 'payment_cryptomus_pending_status_id') {
                    Mage::log("Cryptomus status : $cryptomusOrderStatus; Cryptomus order: $orderId");
                } elseif ($ocOrderStatus === 'payment_cryptomus_invalid_status_id') {
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
                    $order->save();
                    Mage::log("Cryptomus status : $cryptomusOrderStatus; Cryptomus order: $orderId");
                }

                echo json_encode(['success' => true]);
                exit();
            }
        }
    }

    public function redirectAction()
    {
        $lastRealOrder = Mage::getSingleton('checkout/session')->setLastRealOrder();
        $incrementId = $lastRealOrder->getData('last_real_order_id');
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        $merchantId = Mage::getStoreConfig('payment/bajaj/merchant_id');
        $lifetime = Mage::getStoreConfig('payment/bajaj/lifetime');
        $successUrl = Mage::getUrl('checkout/onepage/success', array('_secure' => true));
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        $paymentData = [
            'amount' => (string)round($order->getGrandTotal(), 2),
            'currency' => $currencyCode,
            'merchant' => $merchantId,
            'order_id' => (string)$incrementId,
            'url_return' => $successUrl,
            'url_callback' => Mage::getUrl('bajaj/payment/succesorder', array('_secure' => true)),
            'lifetime' => $lifetime,
        ];
        try {
            $paymentClient = $this->initPaymentClient();
            $response = $paymentClient->create($paymentData);;
            Mage::app()->getResponse()->setRedirect($response['url'])->sendResponse();
        } catch (RequestBuilderException $e) {
            if ($errors = $e->getErrors()) {
                foreach ($errors as $error) {
                    Mage::log('Cryptomus payment error: ' . $error, '');
                }
            }
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
        }

    }

    /**
     * @return Payment
     */
    private function initPaymentClient()
    {
        $apiKey = Mage::getStoreConfig('payment/bajaj/api_key');
        $merchantId = Mage::getStoreConfig('payment/bajaj/merchant_id');

        return new Payment($apiKey, $merchantId);
    }

    private function createInvoice($order)
    {
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        $invoice->register()->pay();
        $invoice->getOrder()->setIsInProcess(true);

        $history = $invoice->getOrder()->addStatusHistoryComment(
            'Cryptomus invoice created', false
        );

        $history->setIsCustomerNotified(true);

        $order->save();

        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();
        $invoice->save();
        $invoice->sendEmail(true, '');
        $order->setData('state', "complete");
        $order->setStatus("complete");
        $history = $order->addStatusHistoryComment('Order marked as complete automatically.', false);
        $history->setIsCustomerNotified(false);
        $order->save();
    }
}
