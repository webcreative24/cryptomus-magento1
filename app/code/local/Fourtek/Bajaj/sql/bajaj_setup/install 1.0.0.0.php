<?php
$installer = $this;
$installer->startSetup();
$installer->run("
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` 
ADD `bajaj_field_one` VARCHAR( 255 ) NOT NULL,
ADD `bajaj_field_two` VARCHAR( 255 ) NOT NULL;
  
ALTER TABLE `{$installer->getTable('sales/order_payment')}` 
ADD `bajaj_field_one` VARCHAR( 255 ) NOT NULL,
ADD `bajaj_field_two` VARCHAR( 255 ) NOT NULL;
");
$installer->endSetup();

$installer->run("
-- DROP TABLE IF EXISTS {$this->getTable('bajaj_payment')};
CREATE TABLE {$this->getTable('bajaj_payment')} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ResponseCode` varchar(255) NOT NULL,
  `ResponseDesc` varchar(255) NOT NULL,
  `OrderNo` varchar(255) NOT NULL,
  `RequestID` varchar(255) NOT NULL,
  `DealID` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 ");

$installer->endSetup(); 

?>