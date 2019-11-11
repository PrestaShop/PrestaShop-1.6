<?php
/*
* This class overrides AdminSupplyOrdersControllerCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 21.11.2017
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

/**
 * @since 1.5.0
 * @property SupplyOrder $object
 */
class AdminSupplyOrdersController extends AdminSupplyOrdersControllerCore
{
    /**
     * Helper function for AdminSupplyOrdersController::postProcess()
     *
     * @see AdminSupplyOrdersController::postProcess()
     */
    // Nicolas MAURENT - 28.02.17 - Bug fix on price calculation for added product from supply order
    protected function postProcessUpdateReceipt()
    {	
	// gets all box selected
        $rows = Tools::getValue('supply_order_detailBox');
        if (!$rows) {
            $this->errors[] = Tools::displayError('You did not select any products to update.');
            return;
        }

        // final array with id_supply_order_detail and value to update
        $to_update = array();
        // gets quantity for each id_order_detail
        foreach ($rows as $row) {
            if (Tools::getValue('quantity_received_today_'.$row)) {
                $to_update[$row] = (int)Tools::getValue('quantity_received_today_'.$row);
            }
        }

        // checks if there is something to update
        if (!count($to_update)) {
            $this->errors[] = Tools::displayError('You did not select any products to update.');
            return;
        }

        $supply_order = new SupplyOrder((int)Tools::getValue('id_supply_order'));

        foreach ($to_update as $id_supply_order_detail => $quantity) {
            $supply_order_detail = new SupplyOrderDetail($id_supply_order_detail);

            if (Validate::isLoadedObject($supply_order_detail) && Validate::isLoadedObject($supply_order)) {
                // checks if quantity is valid
                // It's possible to receive more quantity than expected in case of a shipping error from the supplier
                if (!Validate::isInt($quantity) || $quantity <= 0) {
                    $this->errors[] = sprintf(Tools::displayError('Quantity (%d) for product #%d is not valid'),
                        (int)$quantity, (int)$id_supply_order_detail);
                } else {
                    // everything is valid :  updates

                    // creates the history
                    $supplier_receipt_history = new SupplyOrderReceiptHistory();
                    $supplier_receipt_history->id_supply_order_detail = (int)$id_supply_order_detail;
                    $supplier_receipt_history->id_employee = (int)$this->context->employee->id;
                    $supplier_receipt_history->employee_firstname = pSQL($this->context->employee->firstname);
                    $supplier_receipt_history->employee_lastname = pSQL($this->context->employee->lastname);
                    $supplier_receipt_history->id_supply_order_state = (int)$supply_order->id_supply_order_state;
                    $supplier_receipt_history->quantity = (int)$quantity;

                    // updates quantity received
                    $supply_order_detail->quantity_received += (int)$quantity;

                    // if current state is "Pending receipt", then we sets it to "Order received in part"
                    if (3 == $supply_order->id_supply_order_state) {
                        $supply_order->id_supply_order_state = 4;
                    }

                    // Adds to stock
                    $warehouse = new Warehouse($supply_order->id_warehouse);
                    if (!Validate::isLoadedObject($warehouse)) {
                        $this->errors[] = Tools::displayError('The warehouse could not be loaded.');
                        return;
                    }

                    // Nicolas MAURENT - 28.02.17 - Bug fix: variable must be float
                    $price = (float)$supply_order_detail->unit_price_te;
					
                    // converts the unit price to the warehouse currency if needed
                    if ($supply_order->id_currency != $warehouse->id_currency) {
                        // first, converts the price to the default currency
			// Nicolas MAURENT - 28.02.17 - Bug fix: passing $price as float
                        $price_converted_to_default_currency = Tools::convertPrice($price,
                            $supply_order->id_currency, false);

                        // then, converts the newly calculated pri-ce from the default currency to the needed currency
                        $price = Tools::ps_round(Tools::convertPrice($price_converted_to_default_currency,
                            $warehouse->id_currency, true), 6);
                    }

                    $manager = StockManagerFactory::getManager();
                    $res = $manager->addProduct($supply_order_detail->id_product,
                        $supply_order_detail->id_product_attribute,    $warehouse, (int)$quantity,
                        Configuration::get('PS_STOCK_MVT_SUPPLY_ORDER'), $price, true, $supply_order->id);

                    $location = Warehouse::getProductLocation($supply_order_detail->id_product,
                        $supply_order_detail->id_product_attribute, $warehouse->id);

                    $res = Warehouse::setProductlocation($supply_order_detail->id_product,
                        $supply_order_detail->id_product_attribute, $warehouse->id, $location ? $location : '');

                    if ($res) {
                        $supplier_receipt_history->add();
                        $supply_order_detail->save();
                        $shops = $warehouse->getShops();
                        foreach ($shops as $shop) {
                            StockAvailable::updateQuantity($supply_order_detail->id_product, $supply_order_detail->id_product_attribute, (int)$quantity, (int)$shop['id_shop']);
                        }
                    } else {
                        $this->errors[] = Tools::displayError('Something went wrong when setting warehouse on product record');
                    }
                }
            }
        }

        $supply_order->id_supply_order_state = ($supply_order->id_supply_order_state == 4 && $supply_order->getAllPendingQuantity() > 0) ? 4 : 5;
        $supply_order->save();

        if (!count($this->errors)) {
            // display confirm message
            $token = Tools::getValue('token') ? Tools::getValue('token') : $this->token;
            $redirect = self::$currentIndex.'&token='.$token;
            $this->redirect_after = $redirect.'&conf=4';
        }
    }
}