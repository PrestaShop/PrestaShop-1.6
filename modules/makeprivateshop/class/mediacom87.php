<?php
/*
* @author Mediacom87 <support@mediacom87.net>
* @copyright  2007-2014 Mediacom87
*/
class MakePrivateShopClass extends Module
{
	var $addons_id;
    
        /**
	 * addJs function.
	 *
	 * @access protected
	 * @param mixed $file
	 * @return void
	 */
	protected function addJs($file)
	{
		if (version_compare(_PS_VERSION_, '1.5.0.0', '>='))
			$this->context->controller->addJS($this->_path.$file);
		elseif (version_compare(_PS_VERSION_, '1.4.0.0', '>='))
			Tools::addJS($this->_path.$file);
		else
			return '<script type="text/javascript" src="'.$this->_path.$file.'"></script>';
	}

	/**
	 * addCss function.
	 *
	 * @access protected
	 * @param mixed $file
	 * @return void
	 */
	protected function addCss($file)
	{
		if (version_compare(_PS_VERSION_, '1.5.0.0', '>='))
			$this->context->controller->addCSS($this->_path.$file, 'all');
		elseif (version_compare(_PS_VERSION_, '1.4.0.0', '>='))
			Tools::addCSS($this->_path.$file, 'all');
		else
			return '<link href="'.$this->_path.$file.'" rel="stylesheet" type="text/css" media="all" />';
	}

	/**
	 * radioButton function.
	 *
	 * @access protected
	 * @param mixed $name
	 * @param bool $title (default: false)
	 * @return void
	 */
	protected function radioButton($name, $title = false)
	{
		$test = Configuration::get($name);
		return '
			<input type="radio" name="'.$name.'" id="'.$name.'_on" value="1" '.($test ? 'checked="checked" ' : '').'/>
			<label class="t" for="'.$name.'_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" /></label>
			<input type="radio" name="'.$name.'" id="'.$name.'_off" value="0" '.(!$test ? 'checked="checked" ' : '').'/>
			<label class="t" for="'.$name.'_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" /></label>
			'.($title ? '<img src="'.$this->_path.'img/information.png" class="tips" style="cursor:help" alt="help" title="'.$title.'." />' : '');
	}

	/**
	 * aboutUsTab function.
	 *
	 * @access protected
	 * @return void
	 */
	protected function aboutUsTab()
	{
		return (version_compare(_PS_VERSION_, '1.6.0.0', '<') ?
			'<span class="tab"><img src="'.$this->_path.'img/get_info.png" /> '.$this->l('About', 'mediacom87').'</span>' : '').'
			<p style="font-size: 1.5em; font-weight: bold; padding-bottom: 0">'.$this->displayName.'</p>
			<p style="clear: both">'.$this->l('Thanks for installing this module on your website.', 'mediacom87').'</p>
			<p>'.$this->description.'
			<p>'.$this->l('Developped by', 'mediacom87').' <strong>Mediacom87</strong>'.$this->l(', which helps you to grow your business.', 'mediacom87').'</p>'
			.($this->addons_id ?
				'<p>'.$this->l('If you need support on this module:', 'mediacom87').' <a href="http://addons.prestashop.com/contact-community.php?id_product='.$this->addons_id.'" class="redLink" target="_blank">Support</a></p>
				<p>'.$this->l('If you like this module, you will discover our other modules on', 'mediacom87').' <a href="http://addons.prestashop.com/'.$this->isoCode().'/2_community?contributor=322" class="redLink" target="_blank">Prestashop Addons</a></p>' :
				'<p>'.$this->l('If you need support on this module:', 'mediacom87').' <a href="mailto:support@mediacom87.net?subject='.$this->l('Need help on this module:', 'mediacom87').' '.$this->name.' V.'.$this->version.' - PS.'._PS_VERSION_.'" class="redLink">support@mediacom87.net</a></p>
				<p>'.$this->l('If you like this module, you will discover our other modules on', 'mediacom87').' <a href="http://www.prestatoolbox.'.$this->isoCode(true).'/1_mediacom87" class="redLink" target="_blank">Prestatoolbox</a></p>');
	}

	/**
	 * isoCode function.
	 *
	 * @access protected
	 * @param bool $domain (default: false)
	 * @return void
	 */
	protected function isoCode($domain = false)
	{
		$cookie = Context::getContext()->cookie;
		if (version_compare(_PS_VERSION_, '1.5.0.0', '<'))
		{
			$language = new Language($cookie->id_lang);
			$iso = $language->iso_code;
		}
		else
			$iso = $this->context->language->iso_code;
		if ($iso == 'fr')
			return 'fr';
		else if ($domain)
			return 'com';
		else
			return 'en';
	}

	/**
	 * licenceTab function.
	 *
	 * @access protected
	 * @return void
	 */
	protected function licenceTab()
	{
		return (version_compare(_PS_VERSION_, '1.6.0.0', '<') ?
			'<span class="tab"><img src="'.$this->_path.'img/license_key.png" /> '.$this->l('License', 'mediacom87').'</span>' : '').'
			<h3>License Summary</h3>
			<ul class="license">
				<li>License does not expire.</li>
				<li>Can be used on 1 site, 1 server.</li>
				<li>Source-code or binary products cannot be resold or distributed.</li>
				<li>Commercial use allowed.</li>
				<li>Cannot modify source-code for any purpose (cannot create derivative works).</li>
			</ul>
			<h3>Terms and conditions</h3>
			<ol class="terms">
		        <li> <p><strong>Preamble:</strong> This Agreement, signed on Feb 11, 2014 [hereinafter: Effective Date] governs the relationship between You (hereinafter: Licensee) and '.$this->author.', a duly registered company in France whose principal place of business is 20 rue des Saulniers 17230 VILLEDOUX, France (Hereinafter: Licensor). This Agreement sets the terms, rights, restrictions and obligations on using ['.$this->name.'] (hereinafter: The Software) created and owned by Licensor, as detailed herein</p></li>
		        <li>
		            <p><strong>License Grant:</strong> Licensor hereby grants Licensee a Personal, Non-assignable &amp; non-transferable, Commercial, Royalty free, Without the rights to create derivative works, Non-exclusive license, all with accordance with the terms set forth and other legal restrictions set forth in 3rd party software used while running Software.</p>
		            <ol>
		                <li>
		                    <p><strong>Limited:</strong> Licensee may use Software for the purpose of:</p>
		                    <ol>
		                        <li><p>Running Software on Licensee’s Website[s] and Server[s];</p></li>
		                        <li><p>Allowing 3rd Parties to run Software on Licensee’s Website[s] and Server[s];</p></li>
		                        <li><p>Publishing Software’s output to Licensee and 3rd Parties;</p></li>
		                        <li><p>Distribute verbatim copies of Software’s output (including compiled binaries);</p></li>
		                        <li><p>Modify Software to suit Licensee’s needs and specifications.</p></li>
		                    </ol>
		                </li>
		                <li><p><strong>Binary Restricted:</strong> Licensee may sublicense Software as a part of a larger work containing more than Software, distributed solely in Object or Binary form under a personal, non-sublicensable, limited license. Such redistribution shall be limited to unlimited codebases.</p></li>
		                <li><p><strong>Non Assignable &amp; Non-Transferable:</strong> Licensee may not assign or transfer his rights and duties under this license.</p></li>
		                <li><p><strong>Commercial, Royalty Free:</strong> Licensee may use Software for any purpose, including paid-services, without any royalties</p></li>
		            </ol>
		        </li>
		        <li>
		            <p><strong>Term &amp; Termination:</strong>The Term of this license shall be until terminated. Licensor may terminate this Agreement, including Licensee’s license in the case where Licensee :</p>
		            <ol>
		                <li> <p>became insolvent or otherwise entered into any liquidation process; or</p></li>
		                <li><p>exported The Software to any jurisdiction where licensor may not enforce his rights under this agreements in; or</p></li>
		                <li><p>Licensee was in breach of any of this license\'s terms and conditions and such breach was not cured, immediately upon notification; or</p></li>
		                <li><p>Licensee in breach of any of the terms of clause 2 to this license; or</p></li>
		                <li><p>Licensee otherwise entered into any arrangement which caused Licensor to be unable to enforce his rights under this License.</p></li>
		            </ol>
		        </li>
		        <li><p><strong>Payment:</strong> In consideration of the License granted under clause 2, Licensee shall pay Licensor a fee, via Credit-Card, PayPal or any other mean which Licensor may deem adequate. Failure to perform payment shall construe as material breach of this Agreement.</p></li>
		        <li>
		            <p><strong>Upgrades, Updates and Fixes:</strong> Licensor may provide Licensee, from time to time, with Upgrades, Updates or Fixes, as detailed herein and according to his sole discretion. Licensee hereby warrants to keep The Software up-to-date and install all relevant updates and fixes, and may, at his sole discretion, purchase upgrades, according to the rates set by Licensor. Licensor shall provide any update or Fix free of charge; however, nothing in this Agreement shall require Licensor to provide Updates or Fixes.</p>
		            <ol>
		                <li><p><strong>Upgrades:</strong> for the purpose of this license, an Upgrade shall be a material amendment in The Software, which contains new features and or major performance improvements and shall be marked as a new version number. For example, should Licensee purchase The Software under version 1.X.X, an upgrade shall commence under number 2.0.0.</p></li>
		                <li><p><strong>Updates:</strong> for the purpose of this license, an update shall be a minor amendment in The Software, which may contain new features or minor improvements and shall be marked as a new sub-version number. For example, should Licensee purchase The Software under version 1.1.X, an upgrade shall commence under number 1.2.0.</p></li>
		                <li><p><strong>Fix:</strong> for the purpose of this license, a fix shall be a minor amendment in The Software, intended to remove bugs or alter minor features which impair the The Software\'s functionality. A fix shall be marked as a new sub-sub-version number. For example, should Licensee purchase Software under version 1.1.1, an upgrade shall commence under number 1.1.2.</p></li>
		            </ol>
		        </li>
		        <li>
		            <p><strong>Support:</strong> Software is provided under an AS-IS basis and without any support, updates or maintenance. Nothing in this Agreement shall require Licensor to provide Licensee with support or fixes to any bug, failure, mis-performance or other defect in The Software.</p>
		            <ol>
		                <li><p><strong>Bug Notification:</strong> Licensee may provide Licensor of details regarding any bug, defect or failure in The Software promptly and with no delay from such event; Licensee shall comply with Licensor\'s request for information regarding bugs, defects or failures and furnish him with information, screenshots and try to reproduce such bugs, defects or failures.</p> </li>
		                <li><p><strong>Feature Request:</strong> Licensee may request additional features in Software, provided, however, that (i) Licensee shall waive any claim or right in such feature should feature be developed by Licensor; (ii) Licensee shall be prohibited from developing the feature, or disclose such feature request, or feature, to any 3rd party directly competing with Licensor or any 3rd party which may be, following the development of such feature, in direct competition with Licensor; (iii) Licensee warrants that feature does not infringe any 3rd party patent, trademark, trade-secret or any other intellectual property right; and (iv) Licensee developed, envisioned or created the feature solely by himself.</p></li>
		            </ol>
		        </li>
		        <li><p><strong>Liability:</strong> To the extent permitted under Law, The Software is provided under an AS-IS basis. Licensor shall never, and without any limit, be liable for any damage, cost, expense or any other payment incurred by Licensee as a result of Software’s actions, failure, bugs and/or any other interaction between The Software and Licensee’s end-equipment, computers, other software or any 3rd party, end-equipment, computer or services. Moreover, Licensor shall never be liable for any defect in source code written by Licensee when relying on The Software or using The Software’s source code.</p></li>
		        <li>
		            <p><strong>Warranty:</strong></p>
		            <ol>
		                <li><p><strong>Intellectual Property:</strong> Licensor hereby warrants that The Software does not violate or infringe any 3rd party claims in regards to intellectual property, patents and/or trademarks and that to the best of its knowledge no legal action has been taken against it for any infringement or violation of any 3rd party intellectual property rights.</p></li>
		                <li><p><strong>No-Warranty:</strong> The Software is provided without any warranty; Licensor hereby disclaims any warranty that The Software shall be error free, without defects or code which may cause damage to Licensee’s computers or to Licensee, and that Software shall be functional. Licensee shall be solely liable to any damage, defect or loss incurred as a result of operating software and undertake the risks contained in running The Software on License’s Server[s] and Website[s].</p> </li>
		                <li><p><strong>Prior Inspection:</strong> Licensee hereby states that he inspected The Software thoroughly and found it satisfactory and adequate to his needs, that it does not interfere with his regular operation and that it does meet the standards and scope of his computer systems and architecture. Licensee found that The Software interacts with his development, website and server environment and that it does not infringe any of End User License Agreement of any software Licensee may use in performing his services. Licensee hereby waives any claims regarding The Software\'s incompatibility, performance, results and features, and warrants that he inspected the The Software.</p> </li>
		            </ol>
		        </li>
		        <li><p><strong>No Refunds:</strong> Licensee warrants that he inspected The Software according to clause 7(c) and that it is adequate to his needs. Accordingly, as The Software is intangible goods, Licensee shall not be, ever, entitled to any refund, rebate, compensation or restitution for any reason whatsoever, even if The Software contains material flaws.</p></li>
		        <li><p><strong>Indemnification:</strong> Licensee hereby warrants to hold Licensor harmless and indemnify Licensor for any lawsuit brought against it in regards to Licensee’s use of The Software in means that violate, breach or otherwise circumvent this license, Licensor\'s intellectual property rights or Licensor\'s title in The Software. Licensor shall promptly notify Licensee in case of such legal action and request Licensee’s consent prior to any settlement in relation to such lawsuit or claim.</p></li>
		        <li><p><strong>Governing Law, Jurisdiction:</strong> Licensee hereby agrees not to initiate class-action lawsuits against Licensor in relation to this license and to compensate Licensor for any legal fees, cost or attorney fees should any claim brought by Licensee against Licensor be denied, in part or in full.</p></li>
		    </ol>';
	}
}