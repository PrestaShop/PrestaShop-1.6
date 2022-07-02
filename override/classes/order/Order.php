<?php
/*
* This class overrides OrderCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@gmail.com>
*  @date 02.07.2022
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class Order extends OrderCore
{
    /**
     * Get customer orders
     *
     * @param int $id_customer Customer id
     * @param bool $show_hidden_status Display or not hidden order statuses
     * @param Context $context
     * @param int $limit Limit the number of orders displayed
     * @return array Customer orders
     */
     // Nicolas MAURENT - 02.07.2022 - Override getCustomerOrders to eventually limit number of orders displayed
    public static function getCustomerOrders($id_customer, $show_hidden_status = false, Context $context = null, $limit = false)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
        SELECT o.*, (SELECT SUM(od.`product_quantity`) FROM `'._DB_PREFIX_.'order_detail` od WHERE od.`id_order` = o.`id_order`) nb_products
        FROM `'._DB_PREFIX_.'orders` o
        WHERE o.`id_customer` = '.(int)$id_customer.
        Shop::addSqlRestriction(Shop::SHARE_ORDER).'
        GROUP BY o.`id_order`
        ORDER BY o.`date_add` DESC'.($limit ? (' LIMIT '.$limit) : ''));
        if (!$res) {
            return array();
        }

        foreach ($res as $key => $val) {
            $res2 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
				SELECT os.`id_order_state`, osl.`name` AS order_state, os.`invoice`, os.`color` as order_state_color
				FROM `'._DB_PREFIX_.'order_history` oh
				LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = oh.`id_order_state`)
				INNER JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$context->language->id.')
			WHERE oh.`id_order` = '.(int)$val['id_order'].(!$show_hidden_status ? ' AND os.`hidden` != 1' : '').'
				ORDER BY oh.`date_add` DESC, oh.`id_order_history` DESC
			LIMIT 1');

            if ($res2) {
                $res[$key] = array_merge($res[$key], $res2[0]);
            }
        }
        return $res;
    }
}
