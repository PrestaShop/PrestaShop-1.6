<?php
class OrderInvoice extends OrderInvoiceCore
{
    
    public function getProductsDetail()
    {
        // Nicolas MAURENT - 07.12.17 - NOT ranked by name. This ensures consistency with order display
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT *
		FROM `'._DB_PREFIX_.'order_detail` od
		LEFT JOIN `'._DB_PREFIX_.'product` p
		ON p.id_product = od.product_id
		LEFT JOIN `'._DB_PREFIX_.'product_shop` ps ON (ps.id_product = p.id_product AND ps.id_shop = od.id_shop)
		WHERE od.`id_order` = '.(int)$this->id_order.'
		'.($this->id && $this->number ? ' AND od.`id_order_invoice` = '.(int)$this->id : ''));
    }
	/*
    * module: barcode
    * date: 2018-11-12 23:30:25
    * version: 1.0
    */
    public function getProducts($products = false, $selected_products = false, $selected_qty = false)
    {
        if (!$products) {
			$products = $this->getProductsDetail();
        }

		$order = new Order($this->id_order);
		$customized_datas = Product::getAllCustomizedDatas($order->id_cart);

		$result_array = array();
		foreach ($products as $row)
		{
			$row['barcode'] = ((int)$row['product_attribute_id'] > 0)? Image::displayBarCodeCombination($row['product_attribute_id']) :
                        Image::displayBarCodeProduct($row['product_id']);
			
                        // Change qty if selected
                        if ($selected_qty) {
                            $row['product_quantity'] = 0;
                            foreach ($selected_products as $key => $id_product) {
                                if ($row['id_order_detail'] == $id_product) {
                                    $row['product_quantity'] = (int)$selected_qty[$key];
                                }
                            }
                            if (!$row['product_quantity']) {
                                continue;
                            }
                        }

			$this->setProductImageInformations($row);
			$this->setProductCurrentStock($row);
			$this->setProductCustomizedDatas($row, $customized_datas);

                        // Add information for virtual product
                        if ($row['download_hash'] && !empty($row['download_hash'])) {
                            $row['filename'] = ProductDownload::getFilenameFromIdProduct((int)$row['product_id']);
                            // Get the display filename
                            $row['display_filename'] = ProductDownload::getFilenameFromFilename($row['filename']);
			}

			$row['id_address_delivery'] = $order->id_address_delivery;
			
            /* Ecotax */
            $round_mode = $order->round_mode;

            $row['ecotax_tax_excl'] = $row['ecotax']; // alias for coherence
            $row['ecotax_tax_incl'] = $row['ecotax'] * (100 + $row['ecotax_tax_rate']) / 100;
            $row['ecotax_tax'] = $row['ecotax_tax_incl'] - $row['ecotax_tax_excl'];

            if ($round_mode == Order::ROUND_ITEM) {
                $row['ecotax_tax_incl'] = Tools::ps_round($row['ecotax_tax_incl'], _PS_PRICE_COMPUTE_PRECISION_, $round_mode);
            }

            $row['total_ecotax_tax_excl'] = $row['ecotax_tax_excl'] * $row['product_quantity'];
            $row['total_ecotax_tax_incl'] = $row['ecotax_tax_incl'] * $row['product_quantity'];

            $row['total_ecotax_tax'] = $row['total_ecotax_tax_incl'] - $row['total_ecotax_tax_excl'];

            foreach (array(
                'ecotax_tax_excl',
                'ecotax_tax_incl',
                'ecotax_tax',
                'total_ecotax_tax_excl',
                'total_ecotax_tax_incl',
                'total_ecotax_tax'
            ) as $ecotax_field) {
                $row[$ecotax_field] = Tools::ps_round($row[$ecotax_field], _PS_PRICE_COMPUTE_PRECISION_, $round_mode);
            }

            // Aliases
            $row['unit_price_tax_excl_including_ecotax'] = $row['unit_price_tax_excl'];
            $row['unit_price_tax_incl_including_ecotax'] = $row['unit_price_tax_incl'];
            $row['total_price_tax_excl_including_ecotax'] = $row['total_price_tax_excl'];
            $row['total_price_tax_incl_including_ecotax'] = $row['total_price_tax_incl'];
			
            /* Stock product */
            $result_array[(int)$row['id_order_detail']] = $row;
        }

        if ($customized_datas) {
			Product::addCustomizationPrice($result_array, $customized_datas);
        }

        return $result_array;
    }
}
