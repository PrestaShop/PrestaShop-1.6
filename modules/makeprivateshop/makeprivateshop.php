<?php
/*
* @author Mediacom87 <support@mediacom87.net>
* @copyright  2007-2014 Mediacom87
*/
if (!defined('_PS_VERSION_'))
	exit;
include_once('class/mediacom87.php');

class MakePrivateShop extends MakePrivateShopClass
{
	public function __construct()
	{
		$this->name = 'makeprivateshop';
		$this->tab = version_compare(_PS_VERSION_, '1.4.0.0', '>=') ? 'administration' : 'Mediacom87';
		$this->version = '1.8.1';
		$this->author = 'Mediacom87';
		$this->need_instance = 0;
		$this->module_key = '6bebd0c5695be29f1cb29301c68246a1';

		parent::__construct();

		/* boostrap */
		if (version_compare(_PS_VERSION_, '1.6.0.0', '>='))
			$this->bootstrap = true;

		$this->displayName = $this->l('Make your shop private');
		$this->description = $this->l('Only allow access to your store to customers');

		$this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
	}

	public function install()
	{
		if (!parent::install()
			|| !$this->registerHook('header')
			|| !Configuration::updateValue('MPS_STATUT', 0)
			|| !Configuration::updateValue('MPS_CONTENT', 0)
			|| !Configuration::updateValue('MPS_CREATE', 1)
			|| !Configuration::updateValue('MPS_LOGO', 1)
			|| !Configuration::updateValue('MPS_TUNNEL', 0)
			|| !Configuration::updateValue('MPS_BREADCRUMB', 0)
			|| !Configuration::updateValue('MPS_URL', '')
			|| !Configuration::updateValue('MPS_WIDTH', 556)
			|| !Configuration::updateValue('MPS_BOT', 0))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('MPS_STATUT')
			|| !Configuration::deleteByName('MPS_CONTENT')
			|| !Configuration::deleteByName('MPS_CREATE')
			|| !Configuration::deleteByName('MPS_LOGO')
			|| !Configuration::deleteByName('MPS_TUNNEL')
			|| !Configuration::deleteByName('MPS_BREADCRUMB')
			|| !Configuration::deleteByName('MPS_URL')
			|| !Configuration::deleteByName('MPS_WIDTH')
			|| !Configuration::deleteByName('MPS_BOT')
			|| !parent::uninstall())
			return false;
		return true;
	}

	public function getContent($tab = 'AdminModules')
	{
		if (version_compare(_PS_VERSION_, '1.5.0.0', '>='))
		{
			$cookie = Context::getContext()->cookie;
			$currentIndex = AdminController::$currentIndex;
		}
		else
			global $currentIndex, $cookie;

		$token = Tools::getAdminToken($tab.(int)Tab::getIdFromClassName($tab).(int)$cookie->id_employee);
		if (Tools::isSubmit('save'))
		{
			Configuration::updateValue('MPS_STATUT', (bool)Tools::getValue('MPS_STATUT'));
			Configuration::updateValue('MPS_BOT', (bool)Tools::getValue('MPS_BOT'));
			Configuration::updateValue('MPS_CONTENT', (bool)Tools::getValue('MPS_CONTENT'));
			Configuration::updateValue('MPS_CREATE', (bool)Tools::getValue('MPS_CREATE'));
			Configuration::updateValue('MPS_LOGO', (bool)Tools::getValue('MPS_LOGO'));
			Configuration::updateValue('MPS_TUNNEL', (bool)Tools::getValue('MPS_TUNNEL'));
			Configuration::updateValue('MPS_BREADCRUMB', (bool)Tools::getValue('MPS_BREADCRUMB'));
			Configuration::updateValue('MPS_URL', Tools::getValue('MPS_URL'));
			Configuration::updateValue('MPS_WIDTH', (int)Tools::getValue('MPS_WIDTH'));
			Tools::redirectAdmin($currentIndex.'&modulename='.$this->name.'&configure='.$this->name.'&conf=6&token='.$token);
		}

		if (version_compare(_PS_VERSION_, '1.6.0.0', '>='))
			$output = $this->displayForm16(); //Prestashop 1.6.x
		elseif (version_compare(_PS_VERSION_, '1.5.0.0', '>='))
			$output = $this->displayForm15(); //Prestashop 1.5.x
		else
			$output = $this->displayForm(); //Prestashop 1.x.x

		return $output;
	}

	/**
	 * displayForm function.
	 *
	 * @access private
	 * @param mixed $token
	 * @return void
	 */
	private function displayForm()
	{
		return '
			<script src="'.$this->_path.'js/tabpane.js"></script>
			<script src="'.$this->_path.'js/tipTip.js"></script>
			<link rel="stylesheet" type="text/css" href="'.$this->_path.'css/tabpane.css" />
			<link rel="stylesheet" type="text/css" href="'.$this->_path.'css/tipTip.css" />

			<h2><img src="'.$this->_path.'logo.png" /> '.$this->displayName.'</h2>
			<div class="tab-pane" id="tab-pane-100">
				<div class="tab-page" id="tab-page-1">
					<span class="tab"><img src="'.$this->_path.'img/settings.png" /> '.$this->l('Configuration').'</span>
					<form method="post">
						<div>
							<label>'.$this->l('Privatize your store:').'</label>
							<div class="margin-form">
								'.$this->radioButton('MPS_STATUT').'
							</div>
						</div>

						<div>
							<label>'.$this->l('Let work crawlers:').'</label>
							<div class="margin-form">
								'.$this->radioButton('MPS_BOT', $this->l('You can choose to make available your shop to crawlers in order to not to block SEO')).'
							</div>
						</div>

						<div class="clear">
							<label>'.$this->l('Content only:').'</label>
							<div class="margin-form">
								'.$this->radioButton('MPS_CONTENT', $this->l('Show only form authentication')).'
							</div>
						</div>

						<div class="clear">
							<label>'.$this->l('Create Account:').'</label>
							<div class="margin-form">
								'.$this->radioButton('MPS_CREATE', $this->l('Give or not, possibility to create an account')).'
							</div>
						</div>

						<div class="clear">
							<label>'.$this->l('Display logo:').'</label>
							<div class="margin-form">
								'.$this->radioButton('MPS_LOGO', $this->l('Show the logo when you choose Content only')).'
							</div>
						</div>

						<div class="clear">
							<label>'.$this->l('Hide order steps:').'</label>
							<div class="margin-form">
								'.$this->radioButton('MPS_TUNNEL', $this->l('Hide order steps when you choose Content only')).'
							</div>
						</div>

						<div class="clear">
							<label>'.$this->l('Hide breadcrumb:').'</label>
							<div class="margin-form">
								'.$this->radioButton('MPS_BREADCRUMB', $this->l('Hide breadcrumb when you choose Content only')).'
							</div>
							<div class="margin-form">
								'.$this->l('Logo url:').'
								<input type=text" value="'.Configuration::get('MPS_URL').'" name="MPS_URL" size="30" /> <img src="'.$this->_path.'img/information.png" class="tips" style="cursor:help" alt="help" title="'.$this->l('Leave blank if you want to use the default logo of your shop').'." />
							</div>
						</div>

						<div class="clear">
							<label>'.$this->l('Width for the page:').'</label>
							<div class="margin-form" style="margin-left:5px">
							<input type=text" value="'.Configuration::get('MPS_WIDTH').'" name="MPS_WIDTH" size="4" /> px <img src="'.$this->_path.'img/information.png" class="tips" style="cursor:help" alt="help" title="'.$this->l('Define the width of your authenfication page. Often, this corresponds to the width used for the central column or large column of your template').'." />
							</div>
						</div>

						<input type="submit" class="button" name="save" value="'.$this->l('Save').'" />
					</form>
				</div>

				<div class="tab-page" id="tab-page-2">
					'.$this->aboutUsTab().'
				</div>

				<div class="tab-page" id="tab-page-3">
					'.$this->licenceTab().'
				</div>
			</div>
			<script type="text/javascript">
				'.((Tools::getValue('tabpane'))? $this->_TabPaneSelected('tab-pane-100', Tools::getValue('tabpane')) : '').'
				setupAllTabs();
				$(function(){
					$(".tips").tipTip({delay: 0, edgeOffset: 10, defaultPosition:"right"});

				});
			</script>';
	}

	/**
	 * displayForm15 function.
	 *
	 * @access private
	 * @param mixed $token
	 * @return void
	 */
	private function displayForm15()
	{
		return '<script src="'.$this->_path.'js/tabpane.js"></script>
			<link rel="stylesheet" type="text/css" href="'.$this->_path.'css/tabpane.css" />

			<h2>'.$this->displayName.'</h2>

			<div class="tab-pane" id="tab-pane-1">
				<div class="tab-page">
					<span class="tab"><img src="'.$this->_path.'img/settings.png" /> '.$this->l('Configuration').'</span>
					'.$this->renderFormCode().'
				</div>

				<div class="tab-page about" id="tab-page-2">
					'.$this->aboutUsTab().'
				</div>

				<div class="tab-page licence" id="tab-page-3">
					'.$this->licenceTab().'
				</div>
			</div>
			<script type="text/javascript">
				setupAllTabs();
			</script>';
	}

	/**
	 * displayForm16 function.
	 *
	 * @access public
	 * @return void
	 */
	public function displayForm16()
	{
		return '
		<style type="text/css">
			.tab-pane h3{color:#09F}
			.tab-pane ol li p{display: inline;font-size: inherit}
			.tab-pane > ol > li{border-top:1px solid #bababa;margin:0;padding:5px}
			.tab-pane > ol > li:first-child{border-top:none}
			.tab-pane ol {counter-reset: item;list-style: none outside none;padding-left: 20px}
			.tab-pane ol li:before {font-size:1.em;content: counters(item, ".", decimal) ". ";counter-increment: item}
			.tab-pane ul.license{padding-left: 25px;list-style: square inside}
		</style>
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" style="margin-left:1em">
		  <li class="active"><a href="#tab-page-1" data-toggle="tab"><i class="icon-cogs"></i><span class="hidden-xs"> '.$this->l('Configuration').'</span></a></li>
		  <li><a href="#tab-page-2" data-toggle="tab"><i class="icon-info-circle"></i><span class="hidden-xs"> '.$this->l('About').'</span></a></li>
		  <li><a href="#tab-page-3" data-toggle="tab"><i class="icon-legal"></i><span class="hidden-xs"> '.$this->l('License').'</span></a></li>
		</ul>

		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane active" id="tab-page-1">'.$this->renderFormCode().'</div>
			<div class="tab-pane panel" id="tab-page-2">'.$this->aboutUsTab().'
			</div>
			<div class="tab-pane panel" id="tab-page-3">'.$this->licenceTab().'</div>
		</div>
		<script type="text/javascript">
			$("#tab-page-1 a").click(function (e) {$(this).tab("show")})
			$("#tab-page-2 a").click(function (e) {$(this).tab("show")})
			$("#tab-page-3 a").click(function (e) {$(this).tab("show")})
		</script>';
	}

	/**
	 * renderForm1 function.
	 *
	 * @access public
	 * @return void
	 */
	public function renderFormCode()
	{
		$desc = (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? 'hint' : 'desc');
		$fields_form = array(
			'form' => array(
				'input' => array(
					array(
						'type' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? 'switch' : 'radio'),
						'class' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? '' : 't'),
						'label' => $this->l('Privatize your store:'),
						'name' => 'MPS_STATUT',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'type' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? 'switch' : 'radio'),
						'class' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? '' : 't'),
						'label' => $this->l('Let work crawlers:'),
						'name' => 'MPS_BOT',
						$desc => $this->l('You can choose to make available your shop to crawlers in order to not to block SEO'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'type' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? 'switch' : 'radio'),
						'class' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? '' : 't'),
						'label' => $this->l('Content only:'),
						'name' => 'MPS_CONTENT',
						$desc => $this->l('Show only form authentication'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'type' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? 'switch' : 'radio'),
						'class' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? '' : 't'),
						'label' => $this->l('Create Account:'),
						'name' => 'MPS_CREATE',
						$desc => $this->l('Give or not, possibility to create an account'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'type' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? 'switch' : 'radio'),
						'class' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? '' : 't'),
						'label' => $this->l('Display logo:'),
						'name' => 'MPS_LOGO',
						$desc => $this->l('Show the logo when you choose Content only'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'type' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? 'switch' : 'radio'),
						'class' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? '' : 't'),
						'label' => $this->l('Hide order steps:'),
						'name' => 'MPS_TUNNEL',
						$desc => $this->l('Hide order steps when you choose Content only'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'type' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? 'switch' : 'radio'),
						'class' => (version_compare(_PS_VERSION_, '1.6.0.0', '>=') ? '' : 't'),
						'label' => $this->l('Hide breadcrumb:'),
						'name' => 'MPS_BREADCRUMB',
						$desc => $this->l('Hide breadcrumb when you choose Content only'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'type' => 'text',
						'label' => $this->l('Logo url:'),
						'name' => 'MPS_URL',
						$desc => $this->l('Leave blank if you want to use the default logo of your shop'),
						'size' => '100%'
					),
					array(
						'type' => 'text',
						'label' => $this->l('Width for the page:'),
						'name' => 'MPS_WIDTH',
						$desc => $this->l('Define the width of your authenfication page. Often, this corresponds to the width used for the central column or large column of your template'),
						'class' => 'fixed-width-md',
						'suffix' => 'px',
						'size' => '100%'
					),
				),

				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-left'
					)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang =
			Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'save';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => Configuration::getMultiple(array(
						'MPS_STATUT',
						'MPS_CONTENT',
						'MPS_WIDTH',
						'MPS_CREATE',
						'MPS_LOGO',
						'MPS_TUNNEL',
						'MPS_BREADCRUMB',
						'MPS_URL',
						'MPS_BOT'
					))
		);

		return $helper->generateForm(array($fields_form));
	}

	public function hookHeader()
	{
		if (!$this->isCrawler() || !Configuration::get('MPS_BOT'))
		{
			if (version_compare(_PS_VERSION_, '1.5.0.0', '<'))
			{
				global $cookie, $smarty;
				if (version_compare(_PS_VERSION_, '1.4.0.0', '<'))
					$test = ((!strrpos($_SERVER['SCRIPT_NAME'], 'authentication') && !strrpos($_SERVER['SCRIPT_NAME'], 'password')) ? true : false );
				else
					$test = (($smarty->tpl_vars['page_name'] != 'authentication' && $smarty->tpl_vars['page_name'] != 'password') ? true : false );

				if (Configuration::get('MPS_STATUT') && !$cookie->isLogged() && $test && !Tools::isSubmit('SubmitCreate'))
					Tools::redirect('authentication.php');

				if (Configuration::get('MPS_STATUT') && Configuration::get('MPS_CONTENT') && !$cookie->isLogged())
				{
					$smarty->assign('content_only', 1);
					$_POST['content_only'] = 1;
				}

				if (version_compare(_PS_VERSION_, '1.4.0.0', '<'))
					$test2 = ((strrpos($_SERVER['SCRIPT_NAME'], 'authentication') || strrpos($_SERVER['SCRIPT_NAME'], 'password')) ? true : false );
				else
					$test2 = (($smarty->tpl_vars['page_name'] == 'authentication' || $smarty->tpl_vars['page_name'] == 'password') ? true : false );

				if (Configuration::get('MPS_STATUT') && !$cookie->isLogged() && $test2)
				{
					$smarty->assign(array(
							'width' => (int)Configuration::get('MPS_WIDTH'),
							'create' => (int)Configuration::get('MPS_CREATE'),
							'content_logo' => (int)Configuration::get('MPS_LOGO'),
							'content_tunnel' => (int)Configuration::get('MPS_TUNNEL'),
							'content_breadcrumb' => (int)Configuration::get('MPS_BREADCRUMB'),
							'url_logo_private' => Configuration::get('MPS_URL')
						));
					return $this->display(__FILE__, (version_compare(_PS_VERSION_, '1.5.0.0', '<')?'/views/templates/hook/':'').$this->name.'.tpl');
				}
			}
			else
			{
				$conf = Configuration::getMultiple(array(
						'MPS_STATUT',
						'MPS_CONTENT',
						'MPS_WIDTH',
						'MPS_CREATE',
						'MPS_LOGO',
						'MPS_TUNNEL',
						'MPS_BREADCRUMB',
						'MPS_URL',
						'MPS_URL'
					));

				$test = (($this->context->smarty->tpl_vars['page_name'] != 'authentication' && $this->context->smarty->tpl_vars['page_name'] != 'password') ? true : false);

				if ($conf['MPS_STATUT'] && !$this->context->customer->isLogged() && $test && !Tools::isSubmit('SubmitCreate'))
				{
					$link = new Link();
					$url = $link->getPageLink('authentication');
					Tools::redirect($url.'?back=index');
				}

				if ($conf['MPS_STATUT'] && $conf['MPS_CONTENT'] && !$this->context->customer->isLogged())
				{
					$this->context->smarty->assign('content_only', 1);

					if (version_compare(_PS_VERSION_, '1.6.0.0', '>='))
						$this->context->smarty->assign('center_column', true);
				}

				$test2 = (($this->context->smarty->tpl_vars['page_name'] == 'authentication' || $this->context->smarty->tpl_vars['page_name'] == 'password') ? true : false);

                                // Nicolas MAURENT - 03.07.18 - Removed check on MPS_STATUS to enable below settings even when MPS_STATUS is false
                                //if ($conf['MPS_STATUT'] && !$this->context->customer->isLogged() && $test2)
                                if (!$this->context->customer->isLogged() && $test2)
				{
					$this->context->smarty->assign(array(
							'width' => $conf['MPS_WIDTH'],
							'create' => $conf['MPS_CREATE'],
							'content_logo' => $conf['MPS_LOGO'],
							'content_tunnel' => $conf['MPS_TUNNEL'],
							'content_breadcrumb' => $conf['MPS_BREADCRUMB'],
							'url_logo_private' => $conf['MPS_URL']
						));

					return $this->display(__FILE__, (version_compare(_PS_VERSION_, '1.5.0.0', '<')?'/views/templates/hook/':'').$this->name.'.tpl');
				}
			}
		}
	}

	public function isCrawler()
	{
		$uas = urlencode($_SERVER['HTTP_USER_AGENT']);
		$url = 'http://www.useragentstring.com/?uas='.$uas.'&getJSON=agent_type';
		$raw = Tools::file_get_contents($url);
		$json = Tools::jsonDecode($raw);
		if (is_object($json) && strcasecmp($json->agent_type, 'crawler') == 0)
			return true;
		return false;
	}
}