<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Gregor Hermens <gregor@a-mazing.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   51: class tx_ghtabbedcontent_pi1 extends tslib_pibase
 *   66:     function main($content, $conf)
 *  102:     function getContentPid()
 *  142:     function getContent()
 *  161:     function getMenu()
 *  218:     function buildUrlParameters($getVars)
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Tabbed Content' for the 'gh_tabbedcontent' extension.
 *
 * @author	Gregor Hermens <gregor@a-mazing.de>
 * @package	TYPO3
 * @subpackage	tx_ghtabbedcontent
 */
class tx_ghtabbedcontent_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_ghtabbedcontent_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_ghtabbedcontent_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'gh_tabbedcontent';	// The extension key.
	var $pageUid       = 0; // Uid of parent page
	var $contenPid     = 0; // Uid of content page
	var $contentCol    = 0; // Column to show

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The		content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
//		$this->pi_initPIflexForm();		// Init FlexForm configuration for plugin
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!

		$this->pageUid = (int) $this->conf['pageUid'];
		if(!empty($this->cObj->data['pages'])) {
			$pages = explode(',', $this->cObj->data['pages'], 2);
			$this->pageUid = (int) trim($pages[0]);
		}

		if(empty($this->pageUid)) {
			return false;
		}
		$this->contentCol = (int) $this->conf['contentCol'];
		$this->getContentPid();

		$content = $this->cObj->wrap($this->getContent(), $this->conf['contentWrap']);
		$menu = $this->getMenu();

		$allWrap = '<'.$this->conf['allWrapTag'].' class="'.$this->prefixId.' selected'.$this->contentPid.'">|</'.$this->conf['allWrapTag'].'>';

		if(!empty($this->conf['menuFirst'])) {
			return $this->cObj->wrap($menu.$content, $allWrap);
		} else {
			return $this->cObj->wrap($content.$menu, $allWrap);
		}
	}

	/**
	 * Determines the actual contentPid and saves it to the FE session
	 *
	 * @return	boolean		success
	 */
	function getContentPid() {

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid = '.$this->pageUid.' AND deleted = 0 AND hidden = 0 AND ( starttime = 0 OR starttime > UNIX_TIMESTAMP() ) AND ( endtime = 0 OR endtime < UNIX_TIMESTAMP() )', '', 'sorting');

		$pages = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$pages[] = $row['uid'];
		}

		$this->contentPid = $pages[0];

		if(!empty($this->conf['contentPid'])) {
			$this->contentPid = (int) $this->conf['contentPid'];
		}

		$sessionValue = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_ghtabbedcontent_contentpid');

		if('' != $sessionValue) {
			$this->contentPid = (int) $sessionValue;
		}

		if(isset($this->piVars['pid']) and '' !== $this->piVars['pid']) {
			$newPid = (int) $this->piVars['pid'];
			if(in_array($newPid, $pages)) {
				$this->contentPid = $newPid;
			}
		}

		if($this->contentPid != $sessionValue) {
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_ghtabbedcontent_contentpid', $this->contentPid);
		}

		return true;
	}

	/**
	 * Renders the content
	 *
	 * @return	string		HTML of the content
	 */
	function getContent() {

		$conf = array(
			'table' => 'tt_content',
			'select.' => array(
				'pidInList' => $this->contentPid,
				'where' => 'colPos = '.$this->contentCol,
				'orderBy' => 'sorting',
				'languageField' => 'sys_language_uid',
			),
		);
		return $this->cObj->CONTENT($conf);
	}

	/**
	 * Renders the menu
	 *
	 * @return	string		HTML of the menu
	 */
	function getMenu() {

		$where = 'pid = '.$this->pageUid.' AND deleted = 0 AND hidden = 0 '.(empty($this->conf['includeNotInMenu']) ? ' AND nav_hide = 0 ' : '').' AND ( starttime = 0 OR starttime > UNIX_TIMESTAMP() ) AND ( endtime = 0 OR endtime < UNIX_TIMESTAMP() )';

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title, nav_title', 'pages', $where, '', 'sorting');

		$menu = '';

		$getVars = array();
		if(!empty($this->conf['keepUrlParameters'])) {
			$getVars = t3lib_div::_GET();
		}

		if(!empty($this->conf['itemTag'])) {
			$wrap = '<'.$this->conf['itemTag'].' class="###CLASSES###">|</'.$this->conf['itemTag'].'>';
		} else {
			$wrap = '|';
		}

		$num = 0;
		$numrows = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$item = (empty($row['nav_title'])) ? $row['title'] : $row['nav_title'];
			$getVars[$this->prefixId]['pid'] = $row['uid'];
			$urlParameters = $this->buildUrlParameters($getVars);
			$url = str_replace('&', '&amp;', $this->pi_getPageLink($GLOBALS['TSFE']->id, '', $urlParameters));
			$item = '<a href="'.$url.'">'.$item.'</a>';

			$num++;
			$classes = array();
			$classes[] = 'tab'.$num;
			$classes[] = 'pid'.$row['uid'];
			if($row['uid'] == $this->contentPid) {
				$classes[] = 'selected';
			}
			if(1 == $num) {
				$classes[] = 'firsttab';
			}
			if($numrows == $num) {
				$classes[] = 'lasttab';
			}
			$wrapclasses = $this->cObj->substituteMarker($wrap, '###CLASSES###', implode(' ', $classes));

			$item = $this->cObj->wrap($item, $wrapclasses);
			$menu .= $item;
		}

		return $this->cObj->wrap($menu, $this->conf['menuWrap']);

	}

	/**
	 * transforms multi-dimensional array into a one-dimensional array
	 *
	 * @param	array		$getVars: url parameters to be set
	 * @return	array		url parameters to be set, prepared for pi_getPageLink()
	 */
	function buildUrlParameters($getVars) {
		if(empty($getVars) or !is_array($getVars)) {
			return array();
		}

		$return = array();

		foreach($getVars as $key => $value) {
			if(is_array($value)) {
				foreach($value as $key2 => $value2) {
					if(is_array($value2)) {
						foreach($value2 as $key3 => $value3) {
							$return[$key.'['.$key2.']['.$key3.']'] = $value3;
						}
					} else {
						$return[$key.'['.$key2.']'] = $value2;
					}
				}
			} else {
				$return[$key] = $value;
			}
		}

		return $return;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/gh_tabbedcontent/pi1/class.tx_ghtabbedcontent_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/gh_tabbedcontent/pi1/class.tx_ghtabbedcontent_pi1.php']);
}

?>