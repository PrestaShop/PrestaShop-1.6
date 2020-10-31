<?php
/*
* This class overrides ProductCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@gmail.com>
*  @date 01.12.2018
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class Product extends ProductCore
{
    /**
    * Admin panel product search
    *
    * @param int $id_lang Language id
    * @param string $query Search query
    * @return array Matching products
    */
    // Nicolas MAURENT - 02.12.18 - Add orderBy argument
    public static function searchByName($id_lang, $query, Context $context = null, $orderBy = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $sql = new DbQuery();
        // Nicolas MAURENT - 16.11.2019 - Retrieve available_for_order property
        $sql->select('p.`id_product`, pl.`name`, p.`ean13`, p.`upc`, p.`active`, p.`available_for_order`, p.`reference`, m.`name` AS manufacturer_name, stock.`quantity`, product_shop.advanced_stock_management, p.`customizable`');
        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->leftJoin('product_lang', 'pl', '
			p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl')
        );
        $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');

        $where = 'pl.`name` LIKE \'%'.pSQL($query).'%\'
		OR p.`ean13` LIKE \'%'.pSQL($query).'%\'
		OR p.`upc` LIKE \'%'.pSQL($query).'%\'
		OR p.`reference` LIKE \'%'.pSQL($query).'%\'
		OR p.`supplier_reference` LIKE \'%'.pSQL($query).'%\'
		OR EXISTS(SELECT * FROM `'._DB_PREFIX_.'product_supplier` sp WHERE sp.`id_product` = p.`id_product` AND `product_supplier_reference` LIKE \'%'.pSQL($query).'%\')';
        // Nicolas MAURENT - 01.12.18 - Order by active (status) first
        // Nicolas MAURENT - 02.12.18 - Manage orderBy argument
        $sql->orderBy(((!$orderBy) ? '' : $orderBy).'pl.`name` ASC');

        if (Combination::isFeatureActive()) {
            $where .= ' OR EXISTS(SELECT * FROM `'._DB_PREFIX_.'product_attribute` `pa` WHERE pa.`id_product` = p.`id_product` AND (pa.`reference` LIKE \'%'.pSQL($query).'%\'
			OR pa.`supplier_reference` LIKE \'%'.pSQL($query).'%\'
			OR pa.`ean13` LIKE \'%'.pSQL($query).'%\'
			OR pa.`upc` LIKE \'%'.pSQL($query).'%\'))';
        }
        $sql->where($where);
        $sql->join(Product::sqlStock('p', 0));

        $result = Db::getInstance()->executeS($sql);

        if (!$result) {
            return false;
        }

        $results_array = array();
        foreach ($result as $row) {
            $row['price_tax_incl'] = Product::getPriceStatic($row['id_product'], true, null, 2);
            $row['price_tax_excl'] = Product::getPriceStatic($row['id_product'], false, null, 2);
            $results_array[] = $row;
        }
        return $results_array;
    }
}
