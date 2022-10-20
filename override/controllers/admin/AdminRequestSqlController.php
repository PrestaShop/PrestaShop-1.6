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

  // Nicolas MAURENT - 20.10.2022 - Introduce csv separator configuration - Start
  // PS_CSV_SEPARATOR_MANAGER_SQL to be added into ps_configuration table
  /**
   * @var array : List of separator for a csv file
   */
   public static $csv_separator = array(
      array('value' => 1, 'name' => ';'),
      array('value' => 2, 'name' => ',')
  );

  public function __construct()
  {
      $this->bootstrap = true;
      $this->table = 'request_sql';
      $this->className = 'RequestSql';
      $this->lang = false;
      $this->export = true;

      $this->context = Context::getContext();

      parent::__construct();

      $this->fields_list = array(
          'id_request_sql' => array('title' => $this->l('ID'), 'class' => 'fixed-width-xs'),
          'name' => array('title' => $this->l('SQL query Name')),
          'sql' => array('title' => $this->l('SQL query'))
      );

      $this->fields_options = array(
          'general' => array(
              'title' =>    $this->l('Settings'),
              'fields' =>    array(
                  'PS_ENCODING_FILE_MANAGER_SQL' => array(
                      'title' => $this->l('Select your default file encoding'),
                      'cast' => 'intval',
                      'type' => 'select',
                      'identifier' => 'value',
                      'list' => self::$encoding_file,
                      'visibility' => Shop::CONTEXT_ALL
                  ),
                  'PS_CSV_SEPARATOR_MANAGER_SQL' => array(
                      'title' => $this->l('Select your default CSV seperator'),
                      'cast' => 'intval',
                      'type' => 'select',
                      'identifier' => 'value',
                      'list' => static::$csv_separator,
                      'visibility' => Shop::CONTEXT_ALL
                  )
              ),
              'submit' => array('title' => $this->l('Save'))
          )
      );

      $this->bulk_actions = array(
          'delete' => array(
              'text' => $this->l('Delete selected'),
              'confirm' => $this->l('Delete selected items?'),
              'icon' => 'icon-trash'
          )
      );
  }
  // Nicolas MAURENT - 20.10.2022 - Introduce csv separator configuration - End

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
                // Nicolas MAURENT - 20.10.2022 - Use csv seperator accoring to configuration - Start
                $separator_name = static::$csv_separator[0]['name'];
                if (Configuration::get('PS_CSV_SEPARATOR_MANAGER_SQL')) {
                    $PS_CSV_SEPARATOR_MANAGER_SQL = Configuration::get('PS_CSV_SEPARATOR_MANAGER_SQL');
                    for($x = 0; $x < count(static::$csv_separator); $x++) {
                      if (static::$csv_separator[$x]['value'] == $PS_CSV_SEPARATOR_MANAGER_SQL) {
                        $separator_name = static::$csv_separator[$x]['name'];
                      }
                    }
                }

                foreach (array_keys($results[0]) as $key) {
                    $tab_key[] = $key;
                    //fputs($csv, $key.';');
                    fputs($csv, $key.$separator_name);
                }
                foreach ($results as $result) {
                    fputs($csv, "\n");
                    foreach ($tab_key as $name) {
                        //fputs($csv, strip_tags($result[$name]).';');
                        fputs($csv, strip_tags($result[$name]).$separator_name);
                    }
                }
                // Nicolas MAURENT - 20.10.2022 - Use csv seperator accoring to configuration - End

                if (file_exists($export_dir.$file)) {
                    $filesize = filesize($export_dir.$file);
                    // Nicolas MAURENT - 19.10.2022 - Pickup right charset name to set Content-Type - Start
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
                    // Nicolas MAURENT - 19.10.2022 - Pickup right charset name to set Content-Type - End

                    readfile($export_dir.$file);
                    die();
                }
            }
        }
    }
}
