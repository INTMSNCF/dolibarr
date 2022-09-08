<?php
/* Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/core/modules/export/export_Tac3.modules.php
 *		\ingroup    export
 *		\brief      File of class to build exports with Tac3 format
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';

// avoid timeout for big export
set_time_limit(0);

/**
 *	Class to build export files with format Tac3
 */
class ExportTac3 extends ModeleExports
{
	/**
	 * @var string ID ex: Tac3, tsv, excel...
	 */
	public $id;

	/**
	 * @var string export files label
	 */
	public $label;

	public $extension;

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	public $label_lib;

	public $version_lib;

	public $separator;

	public $handle; // Handle fichier


	/**
	 *	Constructor
	 *
	 *	@param	    DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;
		$langs->loadLangs(array("tac3@tac3"));

		$this->db = $db;

		$this->separator = ';';
		if (!empty($conf->global->EXPORT_TAC3_SEPARATOR_TO_USE)) {
			$this->separator = $conf->global->EXPORT_TAC3_SEPARATOR_TO_USE;
		}
		$this->escape = '"';
		$this->enclosure = '"';

		$this->id = 'tac3'; // Same value then xxx in file name export_xxx.modules.php
		$this->label = 'TAC3'; // Label of driver
		$this->desc = $langs->trans("Tac3FormatDesc", $this->separator, $this->enclosure, $this->escape);
		$this->extension = 'tac3'; // Extension for generated file by this driver
		$this->picto = 'mime/other'; // Picto
		$this->version = '1.0'; // Driver version

		// If driver use an external library, put its name here
		$this->label_lib = 'Dolibarr API';
		$this->version_lib = DOL_VERSION;
	}

	/**
	 * getDriverId
	 *
	 * @return string
	 */
	public function getDriverId()
	{
		return $this->id;
	}

	/**
	 * getDriverLabel
	 *
	 * @return 	string			Return driver label
	 */
	public function getDriverLabel()
	{
		return $this->label;
	}

	/**
	 * getDriverDesc
	 *
	 * @return string
	 */
	public function getDriverDesc()
	{
		return $this->desc;
	}

	/**
	 * getDriverExtension
	 *
	 * @return string
	 */
	public function getDriverExtension()
	{
		return $this->extension;
	}

	/**
	 * getDriverVersion
	 *
	 * @return string
	 */
	public function getDriverVersion()
	{
		return $this->version;
	}

	/**
	 * getLabelLabel
	 *
	 * @return string
	 */
	public function getLibLabel()
	{
		return $this->label_lib;
	}

	/**
	 * getLibVersion
	 *
	 * @return string
	 */
	public function getLibVersion()
	{
		return $this->version_lib;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Open output file
	 *
	 *	@param		string		$file			Path of filename to generate
	 * 	@param		Translate	$outputlangs	Output language object
	 *	@return		int							<0 if KO, >=0 if OK
	 */
	public function open_file($file, $outputlangs)
	{
		// phpcs:enable
		global $langs;

		dol_syslog("ExportTac3::open_file file=".$file);

		$ret = 1;

		$outputlangs->load("exports");
		$this->handle = fopen($file, "wt");
		if (!$this->handle) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFailToCreateFile", $file);
			$ret = -1;
		}


		return $ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output header into file
	 *
	 * 	@param		Translate	$outputlangs	Output language object
	 * 	@return		int							<0 if KO, >0 if OK
	 */
	public function write_header($outputlangs)
	{
		// phpcs:enable
		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output title line into file
	 *
	 *  @param      array		$array_export_fields_label   	Array with list of label of fields
	 *  @param      array		$array_selected_sorted       	Array with list of field to export
	 *  @param      Translate	$outputlangs    				Object lang to translate values
	 *  @param		array		$array_types					Array with types of fields
	 * 	@return		int											<0 if KO, >0 if OK
	 */
	public function write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, $array_types)
	{
		// phpcs:enable
		global $conf;

		if (!empty($conf->global->EXPORT_TAC3_FORCE_CHARSET)) {
			$outputlangs->charset_output = $conf->global->EXPORT_TAC3_FORCE_CHARSET;
		} else {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		$heads = implode($this->separator, array(
			'CORI',
			'TYPP',
			'NPIE',
			'REFC',
			'NCPT',
			'LECR',
			'DTEC',
			'MREG',
			'SENS',
			'DTCT',
			'DTPC',
			'CPTC',
			'AXE0',
			'AXE1',
			'AXE2',
			'AXE3',
			'AXE4',
			'AXE5',
			'AXE6',
			'AXE7',
			'AXE8',
			'AXE9',
			'NTVA',
			'CTVA',
			'CDEV',
			'MTDV',
			'OBSV',
			'MON1',
		));
		fwrite($this->handle, $heads);
		fwrite($this->handle, "\n");
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Output record line into file
	 *
	 *  @param     	array		$array_selected_sorted      Array with list of field to export
	 *  @param     	resource	$objp                       A record from a fetch with all fields from select
	 *  @param     	Translate	$outputlangs    			Object lang to translate values
	 *  @param		array		$array_types				Array with types of fields
	 * 	@return		int										<0 if KO, >0 if OK
	 */
	public function write_record($array_selected_sorted, $objp, $outputlangs, $array_types)
	{
		die(json_encode($array_types));
		// Codigo igual a ;
		// htdocs/compta/facture/class/api_invoices.class.php:164-270
		fwrite($this->handle, "\n");
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output footer into file
	 *
	 * 	@param		Translate	$outputlangs	Output language object
	 * 	@return		int							<0 if KO, >0 if OK
	 */
	public function write_footer($outputlangs)
	{
		// phpcs:enable
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Close file handle
	 *
	 * 	@return		int							<0 if KO, >0 if OK
	 */
	public function close_file()
	{
		// phpcs:enable
		fclose($this->handle);
		return 0;
	}


	/**
	 * Clean a cell to respect rules of Tac3 file cells
	 * Note: It uses $this->separator
	 * Note: We keep this function public to be able to test
	 *
	 * @param 	string	$newvalue	String to clean
	 * @param	string	$charset	Input AND Output character set
	 * @return 	string				Value cleaned
	 */
	public function Tac3Clean($newvalue, $charset)
	{
		global $conf;
		$addquote = 0;

		// Rule Dolibarr: No HTML
		//print $charset.' '.$newvalue."\n";
		//$newvalue=dol_string_nohtmltag($newvalue,0,$charset);
		$newvalue = dol_htmlcleanlastbr($newvalue);
		//print $charset.' '.$newvalue."\n";

		// Rule 1 Tac3: No CR, LF in cells (except if USE_STRICT_TAC3_RULES is on, we can keep record as it is but we must add quotes)
		$oldvalue = $newvalue;
		$newvalue = str_replace("\r", '', $newvalue);
		$newvalue = str_replace("\n", '\n', $newvalue);
		if (!empty($conf->global->USE_STRICT_TAC3_RULES) && $oldvalue != $newvalue) {
			// If strict use of Tac3 rules, we just add quote
			$newvalue = $oldvalue;
			$addquote = 1;
		}

		// Rule 2 Tac3: If value contains ", we must escape with ", and add "
		if (preg_match('/"/', $newvalue)) {
			$addquote = 1;
			$newvalue = str_replace('"', '""', $newvalue);
		}

		// Rule 3 Tac3: If value contains separator, we must add "
		if (preg_match('/'.$this->separator.'/', $newvalue)) {
			$addquote = 1;
		}

		return ($addquote ? '"' : '').$newvalue.($addquote ? '"' : '');
	}
}
