<?php
/*
* This class overrides OrderInvoiceCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 20.11.2017
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class OrderInvoice extends OrderInvoiceCore
{
    /**
     * Retrieve from database the list of products of current order.
     * List of products is NOT re-order by product name.
     *
     * @return array|false|null|mysqli_result|PDOStatement|resource
     * @author Nicolas MAURENT <nbowlinger@yahoo.com>
     * @date 07.12.2017
     */
    public function getProductsDetail()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT *
		FROM `'._DB_PREFIX_.'order_detail` od
		LEFT JOIN `'._DB_PREFIX_.'product` p
		ON p.id_product = od.product_id
		LEFT JOIN `'._DB_PREFIX_.'product_shop` ps ON (ps.id_product = p.id_product AND ps.id_shop = od.id_shop)
		WHERE od.`id_order` = '.(int)$this->id_order.'
		'.($this->id && $this->number ? ' AND od.`id_order_invoice` = '.(int)$this->id : ''));
    }
}
