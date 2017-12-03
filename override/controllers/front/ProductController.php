<?php
/*
* This class overrides ProductControllerCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 17.11.2017
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class ProductController extends ProductControllerCore
{
	/**
     * Assign template vars related to page content
     * @see ProductControllerCore::initContent()
     */
    public function initContent()
    {
		parent::initContent();
		
		if (!$this->errors) {
			// Adding supplier to smarty
			$this->context->smarty->assign('product_supplier', new Supplier((int)$this->product->id_supplier, $this->context->language->id));		
		}
	}
}