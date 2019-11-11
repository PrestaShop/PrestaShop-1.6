<?php
/*
* This class overrides AdminControllerCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 20.11.2017
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class AdminController extends AdminControllerCore
{
	protected function processBulkStatusSelection($status)
    {
		$result = true;
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $id) {
                /** @var ObjectModel $object */                
                $object = new $this->className((int)$id);

                // Nicolas MAURENT - 28.10.17 - Added logging of status change e.g. a product
                $existing_object = new $this->className((int)$id);
                // Nicolas MAURENT - 28.10.17 - End

                $object->setFieldsToUpdate(array('active' => true));
                $object->active = (int)$status;
                $result &= $object->update();

                // Nicolas MAURENT - 28.10.17 - Added logging of status change e.g. a product
                if((int)$object->active == 1 && (int)$existing_object->active == 0)
                        PrestaShopLogger::addLog($this->l('Enabling', 'AdminTab', false, false), 1, null, get_class($object), (int)$id, true, (int)$this->context->employee->id);
                elseif((int)$object->active == 0 && (int)$existing_object->active == 1)
                        PrestaShopLogger::addLog($this->l('Disabling', 'AdminTab', false, false), 1, null, get_class($object), (int)$id, true, (int)$this->context->employee->id);
                // Nicolas MAURENT - 28.10.17 - End		
            }
        }
        return $result;
    }
}