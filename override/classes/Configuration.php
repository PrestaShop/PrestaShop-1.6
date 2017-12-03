<?php
/*
* This class overrides ConfigurationCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 20.11.2017
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class Configuration extends ConfigurationCore
{
    /**
     * Update configuration key and value into database (automatically insert if key does not exist)
     *
     * Values are inserted/updated directly using SQL, because using (Configuration) ObjectModel
     * may not insert values correctly (for example, HTML is escaped, when it should not be).
     * @TODO Fix saving HTML values in Configuration model
     *
     * @param string $key Key
     * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
     * @param bool $html Specify if html is authorized in value
     * @param int $id_shop_group
     * @param int $id_shop
     * @return bool Update result
     */
    public static function updateValue($key, $values, $html = false, $id_shop_group = null, $id_shop = null)
    {
        if (!Validate::isConfigName($key)) {
            // Nicolas MAURENT - 01.12.17 - Alignment with PS 1.6.1.17 code
            die(sprintf(Tools::displayError('[%s] is not a valid configuration key'), Tools::htmlentitiesUTF8($key)));
        }

        if ($id_shop === null || !Shop::isFeatureActive()) {
            $id_shop = Shop::getContextShopID(true);
        }
        if ($id_shop_group === null || !Shop::isFeatureActive()) {
            $id_shop_group = Shop::getContextShopGroupID(true);
        }

        if (!is_array($values)) {
            $values = array($values);
        }

        if ($html) {
            foreach ($values as &$value) {
                $value = Tools::purifyHTML($value);
            }
            unset($value);
        }

        $result = true;
        foreach ($values as $lang => $value) {
            $stored_value = Configuration::get($key, $lang, $id_shop_group, $id_shop);
            // if there isn't a $stored_value, we must insert $value
            if ((!is_numeric($value) && $value === $stored_value) || (is_numeric($value) && $value == $stored_value && Configuration::hasKey($key, $lang))) {
                continue;
            }

            // If key already exists, update value
            if (Configuration::hasKey($key, $lang, $id_shop_group, $id_shop)) {
                if (!$lang) {
                    // Update config not linked to lang
                    $result &= Db::getInstance()->update(self::$definition['table'], array(
                        'value' => pSQL($value, $html),
                        'date_upd' => date('Y-m-d H:i:s'),
                    ), '`name` = \''.pSQL($key).'\''.Configuration::sqlRestriction($id_shop_group, $id_shop), 1, true);
                } else {
                    // Update multi lang
                    $sql = 'UPDATE `'._DB_PREFIX_.bqSQL(self::$definition['table']).'_lang` cl
                            SET cl.value = \''.pSQL($value, $html).'\',
                                cl.date_upd = NOW()
                            WHERE cl.id_lang = '.(int)$lang.'
                                AND cl.`'.bqSQL(self::$definition['primary']).'` = (
                                    SELECT c.`'.bqSQL(self::$definition['primary']).'`
                                    FROM `'._DB_PREFIX_.bqSQL(self::$definition['table']).'` c
                                    WHERE c.name = \''.pSQL($key).'\''
                                        .Configuration::sqlRestriction($id_shop_group, $id_shop)
                                .')';
                    $result &= Db::getInstance()->execute($sql);
                }
            }
            // If key does not exists, create it
            else {
                if (!$configID = Configuration::getIdByName($key, $id_shop_group, $id_shop)) {
                    $now = date('Y-m-d H:i:s');
                    $data = array(
                        'id_shop_group' => $id_shop_group ? (int)$id_shop_group : null,
                        'id_shop'       => $id_shop ? (int)$id_shop : null,
                        'name'          => pSQL($key),
                        'value'         => $lang ? null : pSQL($value, $html),
                        'date_add'      => $now,
                        'date_upd'      => $now,
                    );
                    
					// Nicolas MAURENT - 28.10.17 - Bug fix to avoid unexpected insert into Configuration table
					//$result &= Db::getInstance()->insert('configuration', $data, true);
					$result &= Db::getInstance()->insert(self::$definition['table'], $data, true);
					// Nicolas MAURENT - 28.10.17 - End
					
                    $configID = Db::getInstance()->Insert_ID();
                }

                if ($lang) {
                    $result &= Db::getInstance()->insert(self::$definition['table'].'_lang', array(
                        self::$definition['primary'] => $configID,
                        'id_lang' => (int)$lang,
                        'value' => pSQL($value, $html),
                        'date_upd' => date('Y-m-d H:i:s'),
                    ));
                }
            }
        }

        Configuration::set($key, $values, $id_shop_group, $id_shop);

        return $result;
    }
}