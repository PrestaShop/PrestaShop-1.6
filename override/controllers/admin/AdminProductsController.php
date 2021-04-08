<?php
/*
* This class overrides AdminProductsControllerCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 16.11.2017
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

/**
 * @property Product $object
 */
class AdminProductsController extends AdminProductsControllerCore
{
    public function __construct()
    {
		parent::__construct();

		// Nicolas MAURENT - 28.10.17 - Retrieving supplier with join and select
		$this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'supplier` supp ON (supp.id_supplier = a.`id_supplier`)';
		$this->_select .= ', supp.`name` AS `supp`';

		// Nicolas MAURENT - 28.10.17 - Adding supplier into the field list to display just before 'image' field
		$image_offset = (int)array_keys(array_keys($this->fields_list),'image')[0];
		$this->fields_list = array_merge(
			array_splice($this->fields_list,0,$image_offset),
			array('supp' =>
				array(
					'title' => $this->l('Supplier'),
					'filter_key' => 'supp!name'
				)
			),
			array_slice($this->fields_list,$image_offset-1)
		);
    }

    public function processDuplicate()
    {
        if (Validate::isLoadedObject($product = new Product((int)Tools::getValue('id_product')))) {
            $id_product_old = $product->id;
            if (empty($product->price) && Shop::getContext() == Shop::CONTEXT_GROUP) {
                $shops = ShopGroup::getShopsFromGroup(Shop::getContextShopGroupID());
                foreach ($shops as $shop) {
                    if ($product->isAssociatedToShop($shop['id_shop'])) {
                        $product_price = new Product($id_product_old, false, null, $shop['id_shop']);
                        $product->price = $product_price->price;
                    }
                }
            }
            unset($product->id);
            unset($product->id_product);
            // Nicolas MAURENT - 15.11.2019 - Clear reference and ean13 bar code when duplicating product
            unset($product->reference);
            unset($product->ean13);
            // Nicolas MAURENT - 15.11.2019 - End
            $product->indexed = 0;
            $product->active = 0;
            if ($product->add()
            && Category::duplicateProductCategories($id_product_old, $product->id)
            && Product::duplicateSuppliers($id_product_old, $product->id)
            && ($combination_images = Product::duplicateAttributes($id_product_old, $product->id)) !== false
            && GroupReduction::duplicateReduction($id_product_old, $product->id)
            && Product::duplicateAccessories($id_product_old, $product->id)
            && Product::duplicateFeatures($id_product_old, $product->id)
            && Pack::duplicate($id_product_old, $product->id)
            && Product::duplicateCustomizationFields($id_product_old, $product->id)
            && Product::duplicateTags($id_product_old, $product->id)
            && Product::duplicateDownload($id_product_old, $product->id)) {
                if ($product->hasAttributes()) {
                    Product::updateDefaultAttribute($product->id);
                } else {
                    Product::duplicateSpecificPrices($id_product_old, $product->id);
                }

                if (!Tools::getValue('noimage') && !Image::duplicateProductImages($id_product_old, $product->id, $combination_images)) {
                    $this->errors[] = Tools::displayError('An error occurred while copying images.');
                } else {
                    Hook::exec('actionProductAdd', array('id_product' => (int)$product->id, 'product' => $product));
                    if (in_array($product->visibility, array('both', 'search')) && Configuration::get('PS_SEARCH_INDEXATION')) {
                        Search::indexation(false, $product->id);
                    }
                    $this->redirect_after = self::$currentIndex.(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '').'&conf=19&token='.$this->token;
                }
            } else {
                $this->errors[] = Tools::displayError('An error occurred while creating an object.');
            }
        }
    }

    public function processUpdate()
    {
        $existing_product = $this->object;

        $this->checkProduct();

        if (!empty($this->errors)) {
            $this->display = 'edit';
            return false;
        }

        $id = (int)Tools::getValue('id_'.$this->table);
        /* Update an existing product */
        if (isset($id) && !empty($id)) {
            /** @var Product $object */
            $object = new $this->className((int)$id);
            $this->object = $object;

            if (Validate::isLoadedObject($object)) {
                $this->_removeTaxFromEcotax();
                $product_type_before = $object->getType();
                $this->copyFromPost($object, $this->table);
                $object->indexed = 0;

                if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
                    $object->setFieldsToUpdate((array)Tools::getValue('multishop_check', array()));
                }

                // Duplicate combinations if not associated to shop
                if ($this->context->shop->getContext() == Shop::CONTEXT_SHOP && !$object->isAssociatedToShop()) {
                    $is_associated_to_shop = false;
                    $combinations = Product::getProductAttributesIds($object->id);
                    if ($combinations) {
                        foreach ($combinations as $id_combination) {
                            $combination = new Combination((int)$id_combination['id_product_attribute']);
                            $default_combination = new Combination((int)$id_combination['id_product_attribute'], null, (int)$this->object->id_shop_default);

                            $def = ObjectModel::getDefinition($default_combination);
                            foreach ($def['fields'] as $field_name => $row) {
                                $combination->$field_name = ObjectModel::formatValue($default_combination->$field_name, $def['fields'][$field_name]['type']);
                            }

                            $combination->save();
                        }
                    }
                } else {
                    $is_associated_to_shop = true;
                }

                if ($object->update()) {
                    // If the product doesn't exist in the current shop but exists in another shop
                    if (Shop::getContext() == Shop::CONTEXT_SHOP && !$existing_product->isAssociatedToShop($this->context->shop->id)) {
                        $out_of_stock = StockAvailable::outOfStock($existing_product->id, $existing_product->id_shop_default);
                        $depends_on_stock = StockAvailable::dependsOnStock($existing_product->id, $existing_product->id_shop_default);
                        StockAvailable::setProductOutOfStock((int)$this->object->id, $out_of_stock, $this->context->shop->id);
                        StockAvailable::setProductDependsOnStock((int)$this->object->id, $depends_on_stock, $this->context->shop->id);
                    }

                    PrestaShopLogger::addLog(sprintf($this->l('%s modification', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int)$this->object->id, true, (int)$this->context->employee->id);

                    // Nicolas MAURENT - 28.10.17 - Added logging of status change for a product
                    if((int)$this->object->active == 1 && (int)$existing_product->active == 0)
                            PrestaShopLogger::addLog($this->l('Enabling', 'AdminTab', false, false), 1, null, get_class($object), (int)$id, true, (int)$this->context->employee->id);
                    elseif((int)$this->object->active == 0 && (int)$existing_product->active == 1)
                            PrestaShopLogger::addLog($this->l('Disabling', 'AdminTab', false, false), 1, null, get_class($object), (int)$id, true, (int)$this->context->employee->id);
                    // Nicolas MAURENT - 28.10.17 - End

                    if (in_array($this->context->shop->getContext(), array(Shop::CONTEXT_SHOP, Shop::CONTEXT_ALL))) {
                        if ($this->isTabSubmitted('Shipping')) {
                            $this->addCarriers();
                        }
                        if ($this->isTabSubmitted('Associations')) {
                            $this->updateAccessories($object);
                        }
                        if ($this->isTabSubmitted('Suppliers')) {
                            $this->processSuppliers();
                        }
                        if ($this->isTabSubmitted('Features')) {
                            $this->processFeatures();
                        }
                        if ($this->isTabSubmitted('Combinations')) {
                            $this->processProductAttribute();
                        }
                        if ($this->isTabSubmitted('Prices')) {
                            $this->processPriceAddition();
                            $this->processSpecificPricePriorities();
                        }
                        if ($this->isTabSubmitted('Customization')) {
                            $this->processCustomizationConfiguration();
                        }
                        if ($this->isTabSubmitted('Attachments')) {
                            $this->processAttachments();
                        }
                        if ($this->isTabSubmitted('Images')) {
                            $this->processImageLegends();
                        }

                        $this->updatePackItems($object);
                        // Disallow avanced stock management if the product become a pack
                        if ($product_type_before == Product::PTYPE_SIMPLE && $object->getType() == Product::PTYPE_PACK) {
                            StockAvailable::setProductDependsOnStock((int)$object->id, false);
                        }
                        $this->updateDownloadProduct($object, 1);
                        $this->updateTags(Language::getLanguages(false), $object);

                        if ($this->isProductFieldUpdated('category_box') && !$object->updateCategories(Tools::getValue('categoryBox'))) {
                            $this->errors[] = Tools::displayError('An error occurred while linking the object.').' <b>'.$this->table.'</b> '.Tools::displayError('To categories');
                        }
                    }

                    if ($this->isTabSubmitted('Warehouses')) {
                        $this->processWarehouses();
                    }
                    if (empty($this->errors)) {
                        if (in_array($object->visibility, array('both', 'search')) && Configuration::get('PS_SEARCH_INDEXATION')) {
                            Search::indexation(false, $object->id);
                        }

                        // Save and preview
                        if (Tools::isSubmit('submitAddProductAndPreview')) {
                            $this->redirect_after = $this->getPreviewUrl($object);
                        } else {
                            $page = (int)Tools::getValue('page');
                            // Save and stay on same form
                            if ($this->display == 'edit') {
                                $this->confirmations[] = $this->l('Update successful');
                                $this->redirect_after = self::$currentIndex.'&id_product='.(int)$this->object->id
                                    .(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '')
                                    .'&updateproduct&conf=4&key_tab='.Tools::safeOutput(Tools::getValue('key_tab')).($page > 1 ? '&page='.(int)$page : '').'&token='.$this->token;
                            } else {
                                // Default behavior (save and back)
                                $this->redirect_after = self::$currentIndex.(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '').'&conf=4'.($page > 1 ? '&submitFilterproduct='.(int)$page : '').'&token='.$this->token;
                            }
                        }
                    }
                    // if errors : stay on edit page
                    else {
                        $this->display = 'edit';
                    }
                } else {
                    if (!$is_associated_to_shop && $combinations) {
                        foreach ($combinations as $id_combination) {
                            $combination = new Combination((int)$id_combination['id_product_attribute']);
                            $combination->delete();
                        }
                    }
                    $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> ('.Db::getInstance()->getMsgError().')';
                }
            } else {
                $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> ('.Tools::displayError('The object cannot be loaded. ').')';
            }
            return $object;
        }
    }

    // Nicolas MAURENT - 08.04.21 - Override ProductQuantity to add logging
    public function ajaxProcessProductQuantity()
    {
        if ($this->tabAccess['edit'] === '0') {
            return die(Tools::jsonEncode(array('error' => $this->l('You do not have the right permission'))));
        }
        if (!Tools::getValue('actionQty')) {
            return Tools::jsonEncode(array('error' => $this->l('Undefined action')));
        }

        $product = new Product((int)Tools::getValue('id_product'), true);
        switch (Tools::getValue('actionQty')) {
            case 'depends_on_stock':
                if (Tools::getValue('value') === false) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
                }
                if ((int)Tools::getValue('value') != 0 && (int)Tools::getValue('value') != 1) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Incorrect value'))));
                }
                if (!$product->advanced_stock_management && (int)Tools::getValue('value') == 1) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Not possible if advanced stock management is disabled. '))));
                }
                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int)Tools::getValue('value') == 1 && (Pack::isPack($product->id) && !Pack::allUsesAdvancedStockManagement($product->id)
                    && ($product->pack_stock_type == 2 || $product->pack_stock_type == 1 ||
                        ($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 1 || Configuration::get('PS_PACK_STOCK_TYPE') == 2))))) {
                    die(Tools::jsonEncode(array('error' => $this->l('You cannot use advanced stock management for this pack because').'<br />'.
                        $this->l('- advanced stock management is not enabled for these products').'<br />'.
                        $this->l('- you have chosen to decrement products quantities.'))));
                }

                StockAvailable::setProductDependsOnStock($product->id, (int)Tools::getValue('value'));
                break;

            case 'pack_stock_type':
                $value = Tools::getValue('value');
                if ($value === false) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
                }
                if ((int)$value != 0 && (int)$value != 1
                    && (int)$value != 2 && (int)$value != 3) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Incorrect value'))));
                }
                if ($product->depends_on_stock && !Pack::allUsesAdvancedStockManagement($product->id) && ((int)$value == 1
                    || (int)$value == 2 || ((int)$value == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 1 || Configuration::get('PS_PACK_STOCK_TYPE') == 2)))) {
                    die(Tools::jsonEncode(array('error' => $this->l('You cannot use this stock management option because:').'<br />'.
                        $this->l('- advanced stock management is not enabled for these products').'<br />'.
                        $this->l('- advanced stock management is enabled for the pack'))));
                }

                Product::setPackStockType($product->id, $value);
                break;

            case 'out_of_stock':
                if (Tools::getValue('value') === false) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
                }
                if (!in_array((int)Tools::getValue('value'), array(0, 1, 2))) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Incorrect value'))));
                }

                StockAvailable::setProductOutOfStock($product->id, (int)Tools::getValue('value'));
                break;

            case 'set_qty':
                if (Tools::getValue('value') === false || (!is_numeric(trim(Tools::getValue('value'))))) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
                }
                if (Tools::getValue('id_product_attribute') === false) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Undefined id product attribute'))));
                }

                StockAvailable::setQuantity($product->id, (int)Tools::getValue('id_product_attribute'), (int)Tools::getValue('value'));
                Hook::exec('actionProductUpdate', array('id_product' => (int)$product->id, 'product' => $product));
                // Nicolas MAURENT - 08.04.21 - Log new quantity
                PrestaShopLogger::addLog(sprintf($this->l('Updating stock to %d', 'AdminTab', false, false), (int)Tools::getValue('value')), 1, null, get_class($product), (int)$product->id, true, (int)$this->context->employee->id);

                // Catch potential echo from modules
                $error = ob_get_contents();
                if (!empty($error)) {
                    ob_end_clean();
                    die(Tools::jsonEncode(array('error' => $error)));
                }
                break;

            case 'advanced_stock_management' :
                if (Tools::getValue('value') === false) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
                }
                if ((int)Tools::getValue('value') != 1 && (int)Tools::getValue('value') != 0) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Incorrect value'))));
                }
                if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int)Tools::getValue('value') == 1) {
                    die(Tools::jsonEncode(array('error' =>  $this->l('Not possible if advanced stock management is disabled. '))));
                }

                $product->setAdvancedStockManagement((int)Tools::getValue('value'));
                if (StockAvailable::dependsOnStock($product->id) == 1 && (int)Tools::getValue('value') == 0) {
                    StockAvailable::setProductDependsOnStock($product->id, 0);
                }
                break;

        }
        die(Tools::jsonEncode(array('error' => false)));
    }
}
