<?php
/*
* This class overrides PDFGeneratorCore of Prestashop
*
*  @author Nicolas MAURENT <nbowlinger@yahoo.com>
*  @date 23.11.2017
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class PDFGenerator extends PDFGeneratorCore
{
	 // Nicolas MAURENT - 08.04.17 - Overriding default font to get benefit of more special character for pdf
	const DEFAULT_FONT = 'dejavusans';
}