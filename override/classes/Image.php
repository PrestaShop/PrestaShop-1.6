<?php
/**
* History:
*
* 1.0 - First version
*
*  @author    Vincent MASSON <contact@coeos.pro>
*  @copyright Vincent MASSON <www.coeos.pro>
*  @license   http://www.coeos.pro/boutique/fr/content/3-conditions-generales-de-ventes
*/
class Image extends ImageCore
{
	/*
    * module: barcode
    * date: 2018-11-12 23:30:24
    * version: 1.0
    */
    public static function displayBarCodeCombination($ipa, $width = false, $height = false)
	{
		$ipa = (is_array($ipa))? $ipa['ipa'] : $ipa;
		if ($ean = Db::getInstance()->getValue('SELECT `ean13` FROM `'._DB_PREFIX_.'product_attribute` WHERE `id_product_attribute`='.(int)$ipa))
			return Image::displayBarCode($ean, $width, $height);
	}
	/*
    * module: barcode
    * date: 2018-11-12 23:30:24
    * version: 1.0
    */
    public static function displayBarCodeProduct($id_product, $width = false, $height = false)
	{
		$id_product = (is_array($id_product))? $id_product['id_product'] : $id_product;
		if ($ean = Db::getInstance()->getValue('SELECT `ean13` FROM `'._DB_PREFIX_.'product` WHERE `id_product`='.(int)$id_product))
			return Image::displayBarCode($ean, $width, $height);
	}
	/*
    * module: barcode
    * date: 2018-11-12 23:30:24
    * version: 1.0
    */
    public static function displayBarCode($ean = false, $width = false, $height = false)
	{
		$largeur_img = array(8 => 272, 13 => 400, 15 => 512, 18 => 620);
		$ean = (is_array($ean))? $ean['ean'] : $ean;
		$ean = str_replace(array(' ', '-', '.', '/'), '', $ean);
		$title_alt = 'title="EAN : '.$ean.'" alt="EAN : '.$ean.'"';
		$context = new Context();
		$front = (isset($context::getContext()->controller->controller_type)
			&& $context::getContext()->controller->controller_type == 'front')? true : false;
		$php_self = (isset($context::getContext()->controller->php_self))? $context::getContext()->controller->php_self : false;
		$quality_barcode = Configuration::get('quality_barcode');
		$height_barcode = Configuration::get('height_barcode');
		$width8_barcode = Configuration::get('width8_barcode');
		$width13_barcode = Configuration::get('width13_barcode');
		$width15_barcode = Configuration::get('width15_barcode');
		$width18_barcode = Configuration::get('width18_barcode');
		if (!in_array(Tools::strlen($ean), array(8, 13, 15, 18)))
			return;
		$largeur_cab = array(8 => $width8_barcode, 13 => $width13_barcode, 15 => $width15_barcode, 18 => $width18_barcode);
		$largeur_cab[Tools::strlen($ean)] = ($width)? $width : $largeur_cab[Tools::strlen($ean)];
		$height = ($height)? $height : $height_barcode;
		$height_barcode = ($height)? $height : $height_barcode;
		$width = ($width)? $width : $largeur_img[Tools::strlen($ean)];
		if (file_exists(_THEME_CSS_DIR_.'../../../modules/barcode/cache/'.$ean.'.jpg') && $front)
			return '<img '.$title_alt.' src="'._THEME_CSS_DIR_.'../../../modules/barcode/cache/'.$ean.'.jpg" class="barcode ean_'.Tools::strlen($ean).'"
			style="height:'.$height_barcode.'px;width:'.$largeur_cab[Tools::strlen($ean)].'px"/>';
		$epaisseur = 4;
		$gauche = (Tools::strlen($ean) > 8)? 9 : 1;
		$guard = 13;
		$font_size = 5;
		$central_guard = '_Y_Y_';
		$normal_guard = 'Y_Y';
		$image = imagecreate($largeur_img[Tools::strlen($ean)], $height);
		imagecolorallocate($image, 255, 255, 255);
		$noir = imagecolorallocate($image, 0, 0, 0);
		$txtposx = (Tools::strlen($ean) > 8)? array(0, $gauche + 5 * $epaisseur, $gauche + 49 * $epaisseur + 4, $gauche + 49 * $epaisseur + 240) :
				array(1, 5 * $epaisseur,  38 * $epaisseur);
		$txtposy = $height - $guard - 1;
		$element = array();
		$element['a'] = array(
				0 => '___XX_X',
				1 => '__XX__X',
				2 => '__X__XX',
				3 => '_XXXX_X',
				4 => '_X___XX',
				5 => '_XX___X',
				6 => '_X_XXXX',
				7 => '_XXX_XX',
				8 => '_XX_XXX',
				9 => '___X_XX');
		$element['b'] = array(
				0 => '_X__XXX',
				1 => '_XX__XX',
				2 => '__XX_XX',
				3 => '_X____X',
				4 => '__XXX_X',
				5 => '_XXX__X',
				6 => '____X_X',
				7 => '__X___X',
				8 => '___X__X',
				9 => '__X_XXX');
		$element['c'] = array(
				0 => 'XXX__X_',
				1 => 'XX__XX_',
				2 => 'XX_XX__',
				3 => 'X____X_',
				4 => 'X_XXX__',
				5 => 'X__XXX_',
				6 => 'X_X____',
				7 => 'X___X__',
				8 => 'X__X___',
				9 => 'XXX_X__');
		$motifs = array(
				0 => 'aaaaaa',
				1 => 'aababb',
				2 => 'aabbab',
				3 => 'aabbba',
				4 => 'abaabb',
				5 => 'abbaab',
				6 => 'abbbaa',
				7 => 'ababab',
				8 => 'ababba',
				9 => 'abbaba');
		$motifs_ean15 = array(1 => array('a', 'a', 'b', 'b'), 2 => array('a', 'b', 'a', 'b'));
		$motifs_ean18 = array(
				0 => 'bbaaa',
				1 => 'babaa',
				2 => 'baaba',
				3 => 'baaab',
				4 => 'abbaa',
				5 => 'aabba',
				6 => 'aaabb',
				7 => 'ababa',
				8 => 'abaab',
				9 => 'aabab');
		$motif = (Tools::strlen($ean) >= 13)? $motifs[Tools::substr($ean, 0, 1)] : 'aaaaaa';
		$motif_array = str_split($motif);
		$cab = $normal_guard;
		$middle = (Tools::strlen($ean) >= 13)? 6 : 3;
		$eanarray = str_split($ean);
		$controle = ((int)Tools::substr($ean, -2)) % 4;
		foreach ($eanarray as $key => $chiffre_ean)
		{
			if ($key <= 12)
			{
				if (Tools::strlen($ean) >= 13 && $key > 0 && $key <= $middle)
					$cab .= $element[$motif_array[$key - 1]][$chiffre_ean];
				if (Tools::strlen($ean) == 8 && $key <= $middle)
					$cab .= $element[$motif_array[$key]][$chiffre_ean];
				if ($key > $middle && $key < 13)
					$cab .= $element['c'][$chiffre_ean];
				if ($key == $middle)
					$cab .= $central_guard;
			}
			if ($key == 12 || $key == 7 && Tools::strlen($ean) == 8)
				$cab .= $normal_guard;
			if (Tools::strlen($ean) == 15 && $key == 13)
			{
				$cab .= '________X_XX';
				$cab .= $element[$motifs_ean15[1][$controle]][(int)$eanarray[13]];
				$cab .= '_X';
				$cab .= $element[$motifs_ean15[2][$controle]][(int)$eanarray[14]];
			}
			if (Tools::strlen($ean) == 18 && $key == 13)
			{
				$ean4 = str_split(Tools::substr($ean, -4));
				$ean5 = str_split(Tools::substr($ean, -5));
				$z = 9 * ($ean5[1] + $ean5[3]) + 3 * ($ean5[0] + $ean5[2] + $ean5[4]);
				$controle = $z % 10;
				$motif_ean18 = str_split($motifs_ean18[$controle]);
				$cab .= '________X_XX';
				$cab .= $element[$motif_ean18[0]][(int)$eanarray[13]];
				foreach ($ean4 as $key => $chiffre)
					$cab .= '_X'.$element[$motif_ean18[$key + 1]][$chiffre];
			}
		}
		$cab = str_split($cab);
		foreach ($cab as $key => $line)
		{
			$x = $key * $epaisseur + $gauche;
			if ($line == 'X')
				for ($i = 0; $i < $epaisseur; $i++)
				{
				if ($key < 100 && Tools::strlen($ean) > 15 || Tools::strlen($ean) <= 15)
					ImageLine ($image, $x + $i, 0, $x + $i, $height - $guard, $noir);
				else
					ImageLine ($image, $x + $i, $guard, $x + $i, $height, $noir);
				}
			if ($line == 'Y')
				for ($i = 0; $i < $epaisseur; $i++)
					ImageLine ($image, $x + $i, 0, $x + $i, $height - 7, $noir);
		}
		if (Tools::strlen($ean) == 8)
		{
			foreach (str_split(Tools::substr($ean, 0, 4)) as $key => $chiffre)
				imagestring($image, $font_size, $txtposx[1] + ceil($key * $epaisseur * 7), $txtposy, $chiffre, $noir);
			foreach (str_split(Tools::substr($ean, -4)) as $key => $chiffre)
				imagestring($image, $font_size, $txtposx[2] + ceil($key * $epaisseur * 7), $txtposy, $chiffre, $noir);
		}
		else
		{
			imagestring($image, $font_size, $txtposx[0], $txtposy, Tools::substr($ean, 0, 1), $noir);
			foreach (str_split(Tools::substr($ean, 1, 6)) as $key => $chiffre)
				imagestring($image, $font_size, $txtposx[1] + ceil($key * $epaisseur * 7), $txtposy, $chiffre, $noir);
			foreach (str_split(Tools::substr($ean, 7, 6)) as $key => $chiffre)
				imagestring($image, $font_size, $txtposx[2] + ceil($key * $epaisseur * 7), $txtposy, $chiffre, $noir);
			if (Tools::strlen($ean) == 15)
				foreach (str_split(Tools::substr($ean, -2)) as $key => $chiffre)
					imagestring($image, $font_size, $txtposx[3] + ceil($key * $epaisseur * 7), $txtposy, $chiffre, $noir);
			if (Tools::strlen($ean) == 18)
				foreach (str_split(Tools::substr($ean, -5)) as $key => $chiffre)
					imagestring($image, $font_size, $txtposx[3] + ceil($key * $epaisseur * 7), -1, $chiffre, $noir);
		}
		imagejpeg($image, dirname(__FILE__).'/../../modules/barcode/cache/'.$ean.'.jpg', $quality_barcode);
		imagedestroy($image);
		if ($php_self && $php_self == 'pdf-invoice' && $front)
			return '<img '.$title_alt.' src="'.dirname(__FILE__).'/../../modules/barcode/cache/'.$ean.'.jpg" class="barcode ean_'.Tools::strlen($ean).'"
			style="height:'.$height_barcode.'px;width:'.$largeur_cab[Tools::strlen($ean)].'px"/>';
		if ($front)
			return '<img '.$title_alt.' src="'._THEME_CSS_DIR_.'../../../modules/barcode/cache/'.$ean.'.jpg" class="barcode ean_'.Tools::strlen($ean).'"
				style="height:'.$height_barcode.'px;width:'.$largeur_cab[Tools::strlen($ean)].'px"/>';
		return '<img '.$title_alt.' src="../modules/barcode/cache/'.$ean.'.jpg" class="barcode ean_'.Tools::strlen($ean).'"
			style="height:'.$height_barcode.'px;width:'.$largeur_cab[Tools::strlen($ean)].'px"/>';
	}
}