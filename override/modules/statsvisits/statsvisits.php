<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class StatsVisitsOverride extends StatsVisits
{
	private $html = '';
        
        /**
	 * Get the details on visitors
	 *
	 * @return array(array, int) array of customers entries, number of visitors
         * @author Nicolas MAURENT <nbowlinger@yahoo.com>
         * @date 06.12.2017
	 */
        private function getVisitors()
        {
            if (Configuration::get('PS_STATSDATA_CUSTOMER_PAGESVIEWS'))
            {
             $sql = 'SELECT c.id_guest, CONCAT(LEFT(cu.firstname, 1), \'. \', cu.lastname) as customer, c.ip_address, c.date_add, c.http_referer, pt.name as page
            FROM `'._DB_PREFIX_.'connections` c
            LEFT JOIN `'._DB_PREFIX_.'connections_page` cp ON c.id_connections = cp.id_connections
            LEFT JOIN `'._DB_PREFIX_.'page` p ON p.id_page = cp.id_page
            LEFT JOIN `'._DB_PREFIX_.'page_type` pt ON p.id_page_type = pt.id_page_type
            LEFT JOIN `'._DB_PREFIX_.'guest` g ON c.id_guest = g.id_guest
            LEFT JOIN `'._DB_PREFIX_.'customer` cu ON (g.id_customer = cu.id_customer)
            WHERE c.`date_add` BETWEEN '.ModuleGraph::getDateBetween().'
             '.Shop::addSqlRestriction(false, 'c').'
            GROUP BY c.id_connections
            ORDER BY c.date_add DESC
            LIMIT 1000';
            }
            else
            {
             $sql = 'SELECT c.id_guest, CONCAT(LEFT(cu.firstname, 1), \'. \', cu.lastname) as customer, c.ip_address, c.date_add, c.http_referer, pt.name as page
            FROM `'._DB_PREFIX_.'connections` c
            LEFT JOIN `'._DB_PREFIX_.'page` p ON c.id_page = p.id_page
            LEFT JOIN `'._DB_PREFIX_.'page_type` pt ON p.id_page_type = pt.id_page_type
            LEFT JOIN `'._DB_PREFIX_.'guest` g ON c.id_guest = g.id_guest
            LEFT JOIN `'._DB_PREFIX_.'customer` cu ON (g.id_customer = cu.id_customer)
            WHERE c.`date_add` BETWEEN '.ModuleGraph::getDateBetween().'
             '.Shop::addSqlRestriction(false, 'c').'
            ORDER BY c.date_add DESC
            LIMIT 1000';
            }
            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            return array($results, Db::getInstance()->NumRows());
        }

	public function hookAdminStatsModules()
	{
            global $irow;
            $this->html = Parent::hookAdminStatsModules();
            
            list($visitors2, $total_visitors2) = $this->getVisitors();	
            
            $this->html .= '
			<h4> '.$this->l('Visitors').' (1000 max)</h4>';
		if ($total_visitors2)
		{
                        $this->html .= $this->l('Total :').' '.(int)($total_visitors2).'
                        <div>
                                <table class="table">
                                        <thead>
						<tr>
                                                        <th class="center"><span class="title_box active">'.$this->l('Guest').'</span></th>
                                                        <th class="center"><span class="title_box active">'.$this->l('Customer').'</span></th>
                                                        <th class="center"><span class="title_box active">'.$this->l('IP').'</span></th>
							<th class="center"><span class="title_box active">'.$this->l('Connection date-time').'</span></th>
							<th class="center"><span class="title_box active">'.$this->l('IP Tracer').'</span></th>
							<th class="center"><span class="title_box active">'.$this->l('IP Whois').'</span></th>
							<th class="center"><span class="title_box active">'.$this->l('Referrer').'</span></th>
                                                        <th class="center"><span class="title_box active">'.$this->l('Page').'</span></th>
						</tr>
					</thead>
					<tbody>';
                        foreach ($visitors2 as $visitor2)                     
                                $this->html .= '
                                        <tr'.($irow++ % 2 ? ' class="alt_row"' : '').'>
						<td class="center">'.$visitor2['id_guest'].'</td>
						<td class="center">'.$visitor2['customer'].'</td>
						<td class="center">'.long2ip($visitor2['ip_address']).'</td>
						<td class="center">'.$visitor2['date_add'].'</td>
						<td class="center"><a href=" http://www.localiser-IP.com/?ip='.long2ip($visitor2['ip_address']).'" target="blank"><img src="../modules/'.$this->name.'/lien_ip.png" height="30" width="30"></a></td>
						<td class="center"><a href="http://whois.domaintools.com/'.long2ip($visitor2['ip_address']).'" target="blank"><img src="../modules/'.$this->name.'/whois.png" height="30" width="30"></a></td>
                                                <td class="center">'.(empty($visitor2['http_referer']) ? $this->l('None') : parse_url($visitor2['http_referer'], PHP_URL_HOST)).'</td>
                                                <td class="center">'.$visitor2['page'].'</td>
					</tr>';
			$this->html .= '
					</tbody>
				</table>
			</div>';
		}
		else
			$this->html .= '<p class="alert alert-warning">'.$this->l('There were no visitors.').'</p>';
		$this->html .= '
			<h4>'.$this->l('Notice').'</h4>
			<p class="alert alert-info">'.$this->l('Maintenance IPs are excluded from the visitors.').'</p>
			<a class="btn btn-default" href="'.Tools::safeOutput('index.php?controller=AdminMaintenance&token='.Tools::getAdminTokenLite('AdminMaintenance')).'">
				<i class="icon-share-alt"></i> '.$this->l('Add or remove an IP address.').'
			</a>
		';
                
		return $this->html;
	}
}
