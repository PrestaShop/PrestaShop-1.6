<?php
/*
* This class overrides MailCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 11.01.2018
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

include_once(_PS_SWIFT_DIR_.'swift_required.php');

class Mail extends MailCore
{
    /**
     * Send Email
     *
     * @param int $id_lang Language ID of the email (to translate the template)
     * @param string $template Template: the name of template not be a var but a string !
     * @param string $subject Subject of the email
     * @param string $template_vars Template variables for the email
     * @param string|array $to To email
     * @param string|array $to_name To name
     * @param string $from From email
     * @param string $from_name To email
     * @param array $file_attachment Array with three parameters (content, mime and name). You can use an array of array to attach multiple files
     * @param bool $mode_smtp SMTP mode (deprecated)
     * @param string $template_path Template path
     * @param bool $die Die after error
     * @param int $id_shop Shop ID
     * @param string|array $bcc Bcc recipient(s) (email address)
     * @param string $reply_to Email address for setting the Reply-To header
     * @return bool|int Whether sending was successful. If not at all, false, otherwise amount of recipients succeeded.
     */
    public static function Send($id_lang, $template, $subject, $template_vars, $to,
        $to_name = null, $from = null, $from_name = null, $file_attachment = null, $mode_smtp = null,
        $template_path = _PS_MAIL_DIR_, $die = false, $id_shop = null, $bcc = null, $reply_to = null)
    {
        if (!$id_shop) {
            $id_shop = Context::getContext()->shop->id;
        }

        $configuration = Configuration::getMultiple(array(
            'PS_SHOP_EMAIL',
            'PS_MAIL_METHOD',
            'PS_MAIL_SERVER',
            'PS_MAIL_USER',
            'PS_MAIL_PASSWD',
            'PS_SHOP_NAME',
            'PS_MAIL_SMTP_ENCRYPTION',
            'PS_MAIL_SMTP_PORT',
            'PS_MAIL_TYPE'
        ), null, null, $id_shop);

        // Returns immediatly if emails are deactivated
        if ($configuration['PS_MAIL_METHOD'] == 3) {
            return true;
        }

        $theme_path = _PS_THEME_DIR_;

        // Get the path of theme by id_shop if exist
        if (is_numeric($id_shop) && $id_shop) {
            $shop = new Shop((int)$id_shop);
            $theme_name = $shop->getTheme();

            if (_THEME_NAME_ != $theme_name) {
                $theme_path = _PS_ROOT_DIR_.'/themes/'.$theme_name.'/';
            }
        }

        if (!isset($configuration['PS_MAIL_SMTP_ENCRYPTION']) || Tools::strtolower($configuration['PS_MAIL_SMTP_ENCRYPTION']) === 'off') {
            $configuration['PS_MAIL_SMTP_ENCRYPTION'] = false;
        }
        if (!isset($configuration['PS_MAIL_SMTP_PORT'])) {
            $configuration['PS_MAIL_SMTP_PORT'] = 'default';
        }

        // Sending an e-mail can be of vital importance for the merchant, when his password is lost for example, so we must not die but do our best to send the e-mail

        if (!isset($from) || !Validate::isEmail($from)) {
            $from = $configuration['PS_SHOP_EMAIL'];
        }

        if (!Validate::isEmail($from)) {
            $from = null;
        }

        // $from_name is not that important, no need to die if it is not valid
        if (!isset($from_name) || !Validate::isMailName($from_name)) {
            $from_name = $configuration['PS_SHOP_NAME'];
        }
        if (!Validate::isMailName($from_name)) {
            $from_name = null;
        }

        // It would be difficult to send an e-mail if the e-mail is not valid, so this time we can die if there is a problem
        if (!is_array($to) && !Validate::isEmail($to)) {
            Tools::dieOrLog(Tools::displayError('Error: parameter "to" is corrupted'), $die);
            return false;
        }

        // if bcc is not null, make sure it's a vaild e-mail
        if (!is_null($bcc) && !is_array($bcc) && !Validate::isEmail($bcc)) {
            Tools::dieOrLog(Tools::displayError('Error: parameter "bcc" is corrupted'), $die);
            $bcc = null;
        }

        if (!is_array($template_vars)) {
            $template_vars = array();
        }

        // Do not crash for this error, that may be a complicated customer name
        if (is_string($to_name) && !empty($to_name) && !Validate::isMailName($to_name)) {
            $to_name = null;
        }

        if (!Validate::isTplName($template)) {
            Tools::dieOrLog(Tools::displayError('Error: invalid e-mail template'), $die);
            return false;
        }

        if (!Validate::isMailSubject($subject)) {
            Tools::dieOrLog(Tools::displayError('Error: invalid e-mail subject'), $die);
            return false;
        }

        try {

        /* Construct multiple recipients list if needed */
        $message = Swift_Message::newInstance();
        if (is_array($to) && isset($to)) {
            foreach ($to as $key => $addr) {
                $addr = trim($addr);
                if (!Validate::isEmail($addr)) {
                    Tools::dieOrLog(Tools::displayError('Error: invalid e-mail address'), $die);
                    return false;
                }

                if (is_array($to_name) && isset($to_name[$key])) {
                    $addrName = $to_name[$key];
                } else {
                    $addrName = $to_name;
                }
                
                $addrName = (($addrName == null || $addrName == $addr || !Validate::isGenericName($addrName)) ? '' : self::mimeEncode($addrName));
                $message->addTo($addr, $addrName);
            }
            $to_plugin = $to[0];
        } else {
            /* Simple recipient, one address */
            $to_plugin = $to;
            $to_name = (($to_name == null || $to_name == $to) ? '' : self::mimeEncode($to_name));
            $message->addTo($to, $to_name);
        }

        if (isset($bcc) && is_array($bcc)) {
            foreach ($bcc as $addr) {
                $addr = trim($addr);
                if (!Validate::isEmail($addr)) {
                    Tools::dieOrLog(Tools::displayError('Error: invalid e-mail address'), $die);
                    return false;
                }
                $message->addBcc($addr);
            }
        } elseif (isset($bcc)) {
            $message->addBcc($bcc);
        }

            /* Connect with the appropriate configuration */
            if ($configuration['PS_MAIL_METHOD'] == 2) {
                if (empty($configuration['PS_MAIL_SERVER']) || empty($configuration['PS_MAIL_SMTP_PORT'])) {
                    Tools::dieOrLog(Tools::displayError('Error: invalid SMTP server or SMTP port'), $die);
                    return false;
                }

                $connection = Swift_SmtpTransport::newInstance($configuration['PS_MAIL_SERVER'], $configuration['PS_MAIL_SMTP_PORT'], $configuration['PS_MAIL_SMTP_ENCRYPTION'])
                    ->setUsername($configuration['PS_MAIL_USER'])
                    ->setPassword($configuration['PS_MAIL_PASSWD']);

            } else {
                $connection = Swift_MailTransport::newInstance();
            }

            if (!$connection) {
                return false;
            }
            $swift = Swift_Mailer::newInstance($connection);
            /* Get templates content */
            $iso = Language::getIsoById((int)$id_lang);
            if (!$iso) {
                Tools::dieOrLog(Tools::displayError('Error - No ISO code for email'), $die);
                return false;
            }
            $iso_template = $iso.'/'.$template;

            $module_name = false;
            $override_mail = false;

            // get templatePath
            if (preg_match('#'.$shop->physical_uri.'modules/#', str_replace(DIRECTORY_SEPARATOR, '/', $template_path)) && preg_match('#modules/([a-z0-9_-]+)/#ui', str_replace(DIRECTORY_SEPARATOR, '/', $template_path), $res)) {
                $module_name = $res[1];
            }

            if ($module_name !== false && (file_exists($theme_path.'modules/'.$module_name.'/mails/'.$iso_template.'.txt') ||
                    file_exists($theme_path.'modules/'.$module_name.'/mails/'.$iso_template.'.html'))) {
                $template_path = $theme_path.'modules/'.$module_name.'/mails/';
            } elseif (file_exists($theme_path.'mails/'.$iso_template.'.txt') || file_exists($theme_path.'mails/'.$iso_template.'.html')) {
                $template_path = $theme_path.'mails/';
                $override_mail  = true;
            }
            if (!file_exists($template_path.$iso_template.'.txt') && ($configuration['PS_MAIL_TYPE'] == Mail::TYPE_BOTH || $configuration['PS_MAIL_TYPE'] == Mail::TYPE_TEXT)) {
                Tools::dieOrLog(Tools::displayError('Error - The following e-mail template is missing:').' '.$template_path.$iso_template.'.txt', $die);
                return false;
            } elseif (!file_exists($template_path.$iso_template.'.html') && ($configuration['PS_MAIL_TYPE'] == Mail::TYPE_BOTH || $configuration['PS_MAIL_TYPE'] == Mail::TYPE_HTML)) {
                Tools::dieOrLog(Tools::displayError('Error - The following e-mail template is missing:').' '.$template_path.$iso_template.'.html', $die);
                return false;
            }
            $template_html = '';
            $template_txt = '';
            Hook::exec('actionEmailAddBeforeContent', array(
                'template' => $template,
                'template_html' => &$template_html,
                'template_txt' => &$template_txt,
                'id_lang' => (int)$id_lang
            ), null, true);
            $template_html .= Tools::file_get_contents($template_path.$iso_template.'.html');
            $template_txt .= strip_tags(html_entity_decode(Tools::file_get_contents($template_path.$iso_template.'.txt'), null, 'utf-8'));
            Hook::exec('actionEmailAddAfterContent', array(
                'template' => $template,
                'template_html' => &$template_html,
                'template_txt' => &$template_txt,
                'id_lang' => (int)$id_lang
            ), null, true);
            if ($override_mail && file_exists($template_path.$iso.'/lang.php')) {
                include_once($template_path.$iso.'/lang.php');
            } elseif ($module_name && file_exists($theme_path.'mails/'.$iso.'/lang.php')) {
                include_once($theme_path.'mails/'.$iso.'/lang.php');
            } elseif (file_exists(_PS_MAIL_DIR_.$iso.'/lang.php')) {
                include_once(_PS_MAIL_DIR_.$iso.'/lang.php');
            } else {
                Tools::dieOrLog(Tools::displayError('Error - The language file is missing for:').' '.$iso, $die);
                return false;
            }

            /* Create mail and attach differents parts */
            $subject = '['.Configuration::get('PS_SHOP_NAME', null, null, $id_shop).'] '.$subject;
            $message->setSubject($subject);

            $message->setCharset('utf-8');

            /* Set Message-ID - getmypid() is blocked on some hosting */
            $message->setId(Mail::generateId());

            if (!($reply_to && Validate::isEmail($reply_to))) {
                $reply_to = $from;
            }

            if (isset($reply_to) && $reply_to) {
                $message->setReplyTo($reply_to);
            }

            $template_vars = array_map(array('Tools', 'htmlentitiesDecodeUTF8'), $template_vars);
            $template_vars = array_map(array('Tools', 'stripslashes'), $template_vars);

            if (Configuration::get('PS_LOGO_MAIL') !== false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_MAIL', null, null, $id_shop))) {
                $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO_MAIL', null, null, $id_shop);
            } else {
                if (file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $id_shop))) {
                    $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $id_shop);
                } else {
                    $template_vars['{shop_logo}'] = '';
                }
            }
            ShopUrl::cacheMainDomainForShop((int)$id_shop);
            /* don't attach the logo as */
            if (isset($logo)) {
                $template_vars['{shop_logo}'] = $message->embed(Swift_Image::fromPath($logo));
            }

            if ((Context::getContext()->link instanceof Link) === false) {
                Context::getContext()->link = new Link();
            }

            $template_vars['{shop_name}'] = Tools::safeOutput(Configuration::get('PS_SHOP_NAME', null, null, $id_shop));
            // Nicolas MAURENT - 11.01.19 - Adding shop address details to smarty context when sending email
            $template_vars['{shop_addr1}'] = Tools::safeOutput(Configuration::get('PS_SHOP_ADDR1', null, null, $id_shop));
            $template_vars['{shop_addr2}'] = Tools::safeOutput(Configuration::get('PS_SHOP_ADDR2', null, null, $id_shop));
            $template_vars['{shop_code}'] = Tools::safeOutput(Configuration::get('PS_SHOP_CODE', null, null, $id_shop));
            $template_vars['{shop_city}'] = Tools::safeOutput(Configuration::get('PS_SHOP_CITY', null, null, $id_shop));
            // Nicolas MAURENT - 11.01.19 - End
            $template_vars['{shop_url}'] = Context::getContext()->link->getPageLink('index', true, Context::getContext()->language->id, null, false, $id_shop);
            $template_vars['{my_account_url}'] = Context::getContext()->link->getPageLink('my-account', true, Context::getContext()->language->id, null, false, $id_shop);
            $template_vars['{guest_tracking_url}'] = Context::getContext()->link->getPageLink('guest-tracking', true, Context::getContext()->language->id, null, false, $id_shop);
            $template_vars['{history_url}'] = Context::getContext()->link->getPageLink('history', true, Context::getContext()->language->id, null, false, $id_shop);
            $template_vars['{color}'] = Tools::safeOutput(Configuration::get('PS_MAIL_COLOR', null, null, $id_shop));
            // Get extra template_vars
            $extra_template_vars = array();
            Hook::exec('actionGetExtraMailTemplateVars', array(
                'template' => $template,
                'template_vars' => $template_vars,
                'extra_template_vars' => &$extra_template_vars,
                'id_lang' => (int)$id_lang
            ), null, true);
            $template_vars = array_merge($template_vars, $extra_template_vars);
            $swift->registerPlugin(new Swift_Plugins_DecoratorPlugin(array($to_plugin => $template_vars)));
            if ($configuration['PS_MAIL_TYPE'] == Mail::TYPE_BOTH || $configuration['PS_MAIL_TYPE'] == Mail::TYPE_TEXT) {
                $message->addPart($template_txt, 'text/plain', 'utf-8');
            }
            if ($configuration['PS_MAIL_TYPE'] == Mail::TYPE_BOTH || $configuration['PS_MAIL_TYPE'] == Mail::TYPE_HTML) {
                $message->addPart($template_html, 'text/html', 'utf-8');
            }
            if ($file_attachment && !empty($file_attachment)) {
                // Multiple attachments?
                if (!is_array(current($file_attachment))) {
                    $file_attachment = array($file_attachment);
                }

                foreach ($file_attachment as $attachment) {
                    if (isset($attachment['content']) && isset($attachment['name']) && isset($attachment['mime'])) {
                        $message->attach(Swift_Attachment::newInstance()->setFilename($attachment['name'])->setContentType($attachment['mime'])->setBody($attachment['content']));
                    }
                }
            }
            /* Send mail */
            $message->setFrom(array($from => $from_name));
            $send = $swift->send($message);

            ShopUrl::resetMainDomainCache();

            if ($send && Configuration::get('PS_LOG_EMAILS')) {
                $mail = new Mail();
                $mail->template = Tools::substr($template, 0, 62);
                $mail->subject = Tools::substr($subject, 0, 254);
                $mail->id_lang = (int)$id_lang;
                $recipients_to = $message->getTo();
                $recipients_cc = $message->getCc();
                $recipients_bcc = $message->getBcc();
                if (!is_array($recipients_to)) {
                    $recipients_to = array();
                }
                if (!is_array($recipients_cc)) {
                    $recipients_cc = array();
                }
                if (!is_array($recipients_bcc)) {
                    $recipients_bcc = array();
                }
                foreach (array_merge($recipients_to, $recipients_cc, $recipients_bcc) as $email => $recipient_name) {
                    /** @var Swift_Address $recipient */
                    $mail->id = null;
                    $mail->recipient = Tools::substr($email, 0, 126);
                    $mail->add();
                }
            }

            return $send;
        } catch (Swift_SwiftException $e) {
            PrestaShopLogger::addLog(
                'Swift Error: '.$e->getMessage(),
                3,
                null,
                'Swift_Message',
                null,
                true
            );

            return false;
        }
    }
}