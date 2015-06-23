<?php

namespace TYPO3\GenericGallery\Backend\Hooks;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014-2015 Felix Nagel (info@felixnagel.com)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook class for PageLayoutView hook `list_type_Info`
 *
 * @todo fix this whole class, introduce TS settings class
 * @author Felix Nagel <info@felixnagel.com>
 * @package \TYPO3\GenericGallery\Backend\Hooks
 */
class TcaHook {

	/**
	 * Sets the items for the "Predefined" dropdown.
	 *
	 * @param array $config
	 * @return array The config including the items for the dropdown
	 */
	public function addPredefinedFields($config) {
		if (is_array($config['items'])) {
			$pid = $config['row']['pid'];
			if ($pid < 0) {
				$contentUid = str_replace('-', '', $pid);
				$res = $this->getDatabase()->exec_SELECTquery('pid', 'tt_content', 'uid=' . $contentUid);
				if ($res) {
					$row = $this->getDatabase()->sql_fetch_assoc($res);
					$pid = $row['pid'];
					$this->getDatabase()->sql_free_result($res);
				}
			}

			$typoscript = $this->loadTypoScript($pid);
			$settings = $typoscript['plugin.']['tx_genericgallery.']['settings.'];
			$predef = array();

			// no config available
			if (!is_array($settings['gallery.']) || sizeof($settings['gallery.']) === 0) {
				$optionList[] = array(
					0 => $this->translate('cms_layout.missing_config'), 1 => ''
				);

				return $config['items'] = array_merge($config['items'], $optionList);
			}

			// for each view
			foreach ($settings['gallery.'] as $key => $view) {

				if (is_array($view)) {
					$beName = $view['name'];

					if (!$predef[$key]) {
						$predef[$key] = $beName;
					}
				}
			}

			$optionList = array();
			$optionList[] = array(0 => $this->translate('cms_layout.please_select'), 1 => '');
			foreach ($predef as $k => $v) {
				$optionList[] = array(0 => $v, 1 => $k);
			}
			$config['items'] = array_merge($config['items'], $optionList);
		}

		return $config;
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabase() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @param string $key
	 * @param string $keyPrefix
	 *
	 * @return string
	 */
	protected function translate(
		$key,
		$keyPrefix = 'LLL:EXT:generic_gallery/Resources/Private/Language/locallang_db.xlf'
	) {
		return $GLOBALS['LANG']->sL($keyPrefix . ':' . $key);
	}

	/**
	 * Loads the TypoScript for the current page
	 *
	 * @param int $pageUid
	 * @return array The TypoScript setup
	 */
	protected function loadTypoScript($pageUid) {
		/* @var $sysPageObject \TYPO3\CMS\Frontend\Page\PageRepository */
		$sysPageObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$rootLine = $sysPageObject->getRootLine($pageUid);

		/* @var $templateService \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService */
		$templateService = GeneralUtility::makeInstance('TYPO3\CMS\Core\TypoScript\ExtendedTemplateService');
		$templateService->tt_track = 0;
		$templateService->init();
		$templateService->runThroughTemplates($rootLine);
		$templateService->generateConfig();

		return $templateService->setup;
	}
}