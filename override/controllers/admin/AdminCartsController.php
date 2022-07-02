<?php
/*
* This class overrides AdminCartsControllerCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 02.07.2022
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

/**
 * @property Cart $object
 */
class AdminCartsController extends AdminCartsControllerCore
{
    // Nicolas MAURENT - 02.07.2022 - Override displayAjaxSearchCarts to limit number of carts and orders displayed
    public function displayAjaxSearchCarts()
    {
        $id_customer = (int)Tools::getValue('id_customer');
        $carts = Cart::getCustomerCarts((int)$id_customer, true, 5);
        $orders = Order::getCustomerOrders((int)$id_customer, false, null, 5);
        $customer = new Customer((int)$id_customer);

        if (count($carts)) {
            foreach ($carts as $key => &$cart) {
                $cart_obj = new Cart((int)$cart['id_cart']);
                if ($cart['id_cart'] == $this->context->cart->id || !Validate::isLoadedObject($cart_obj) || $cart_obj->OrderExists()) {
                    unset($carts[$key]);
                }
                $currency = new Currency((int)$cart['id_currency']);
                $cart['total_price'] = Tools::displayPrice($cart_obj->getOrderTotal(), $currency);
            }
        }
        if (count($orders)) {
            foreach ($orders as &$order) {
                $order['total_paid_real'] = Tools::displayPrice($order['total_paid_real'], $currency);
            }
        }
        if ($orders || $carts) {
            $to_return = array_merge($this->ajaxReturnVars(),
                                            array('carts' => $carts,
                                                     'orders' => $orders,
                                                     'found' => true));
        } else {
            $to_return = array_merge($this->ajaxReturnVars(), array('found' => false));
        }

        echo Tools::jsonEncode($to_return);
    }
}
