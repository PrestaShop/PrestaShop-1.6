<?php
/*
* This class overrides OrderDetail of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 12.04.2018
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class OrderDetail extends OrderDetailCore
{
    /**
     * Check the order status
     * @param array $product
     * @param int $id_order_state
     */
    protected function checkProductStock($product, $id_order_state)
    {
        if ($id_order_state != Configuration::get('PS_OS_CANCELED') && $id_order_state != Configuration::get('PS_OS_ERROR')) {
            // Nicolas MAURENT - 12.04.18 - Bug fix: Fix stock available after add product in order #8233
            //$update_quantity = true;
            //if (!StockAvailable::dependsOnStock($product['id_product'])) {
            //    $update_quantity = StockAvailable::updateQuantity($product['id_product'], $product['id_product_attribute'], -(int)$product['cart_quantity']);
            //}
            $update_quantity = StockAvailable::updateQuantity($product['id_product'], $product['id_product_attribute'], -(int)$product['cart_quantity']);
            // Nicolas MAURENT - 12.04.18 - End

            if ($update_quantity) {
                $product['stock_quantity'] -= $product['cart_quantity'];
            }

            if ($product['stock_quantity'] < 0 && Configuration::get('PS_STOCK_MANAGEMENT')) {
                $this->outOfStock = true;
            }
            Product::updateDefaultAttribute($product['id_product']);
        }
    }
}