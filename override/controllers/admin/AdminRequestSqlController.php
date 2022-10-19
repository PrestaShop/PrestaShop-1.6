<?php
/*
* This class overrides AdminRequestSqlControllerCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 19.10.2022
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

/**
 * @property RequestSql $object
 */
class AdminRequestSqlController extends AdminRequestSqlControllerCore
{

    /**
     * Genrating a export file
     */
    public function generateExport()
    {
        $id = Tools::getValue($this->identifier);
        $export_dir = defined('_PS_HOST_MODE_') ? _PS_ROOT_DIR_.'/export/' : _PS_ADMIN_DIR_.'/export/';
        if (!Validate::isFileName($id)) {
            die(Tools::displayError());
        }
        $file = 'request_sql_'.$id.'.csv';
        if ($csv = fopen($export_dir.$file, 'w')) {
            $sql = RequestSql::getRequestSqlById($id);

            if ($sql) {
                $results = Db::getInstance()->executeS($sql[0]['sql']);
                foreach (array_keys($results[0]) as $key) {
                    $tab_key[] = $key;
                    fputs($csv, $key.';');
                }
                foreach ($results as $result) {
                    fputs($csv, "\n");
                    foreach ($tab_key as $name) {
                        fputs($csv, strip_tags($result[$name]).';');
                    }
                }
                if (file_exists($export_dir.$file)) {
                    $filesize = filesize($export_dir.$file);
                    // Nicolas MAURENT - 19.10.2022 - Pickup right charset name to set Content-Type
                    $charset = self::$encoding_file[0]['name'];
                    if (Configuration::get('PS_ENCODING_FILE_MANAGER_SQL')) {
                        //$charset = Configuration::get('PS_ENCODING_FILE_MANAGER_SQL');
                        $PS_ENCODING_FILE_MANAGER_SQL = Configuration::get('PS_ENCODING_FILE_MANAGER_SQL');
                        for($x = 0; $x < count(self::$encoding_file); $x++) {
                          if (self::$encoding_file[$x]['value'] == $PS_ENCODING_FILE_MANAGER_SQL) {
                            $charset = self::$encoding_file[$x]['name'];
                          }
                        }
                    }
                    // } else {
                    //     $charset = self::$encoding_file[0]['name'];
                    // }

                    header('Content-Type: text/csv; charset='.$charset);
                    header('Cache-Control: no-store, no-cache');
                    header('Content-Disposition: attachment; filename="'.$file.'"');
                    // Nicolas MAURENT - 19.10.2022 - encode it as UTF-8 with Byte Order Mark
                    if ($charset == 'utf-8') {
                        header('Content-Length: '.($filesize+3));
                        echo "\xEF\xBB\xBF";
                    } else {
                        header('Content-Length: '.$filesize);
                    }
                    readfile($export_dir.$file);
                    die();
                }
            }
        }
    }
}
