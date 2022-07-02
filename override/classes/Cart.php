<?php
/*
* This class overrides CartCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@gmail.com>
*  @date 02.07.2022
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class Cart extends CartCore
{
  /**
   * Get customer carts
   *
   * @param int $id_customer Customer id
   * @param bool $with_order Display or not carts with order
   * @param int $limit Limit the number of carts displayed
   * @return array Customer carts
   */
   // Nicolas MAURENT - 02.07.2022 - Override getCustomerCarts to eventually limit number of carts displayed
  public static function getCustomerCarts($id_customer, $with_order = true, $limit = false)
    {
        //var_dump("getCustomerCarts entered with limit= ".$limit);
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT *
		FROM '._DB_PREFIX_.'cart c
		WHERE c.`id_customer` = '.(int)$id_customer.'
		'.(!$with_order ? 'AND NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'orders o WHERE o.`id_cart` = c.`id_cart`)' : '').'
		ORDER BY c.`date_add` DESC'.($limit ? (' LIMIT '.$limit) : ''));
    }
}
