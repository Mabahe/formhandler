<?php
/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
*                                                                        *
* TYPO3 is free software; you can redistribute it and/or modify it under *
* the terms of the GNU General Public License version 2 as published by  *
* the Free Software Foundation.                                          *
*                                                                        *
* This script is distributed in the hope that it will be useful, but     *
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
* TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
* Public License for more details.                                       *
*                                                                        */

/**
 * A class removing uploaded files. This class is called via AJAX.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 */
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('formhandler') . 'Classes/Utils/Tx_Formhandler_Globals.php');
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('formhandler') . 'Classes/Utils/Tx_Formhandler_UtilityFuncs.php');
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('formhandler') . 'Classes/Component/Tx_Formhandler_Component_Manager.php');

class Tx_Formhandler_Utils_AjaxRemoveFile {

	/**
	 * Main method of the class.
	 *
	 * @return string The HTML list of remaining files to be displayed in the form
	 */
	public function main() {
		$this->init();
		$content = '';

		if ($this->fieldName) {
			$sessionFiles = $this->globals->getSession()->get('files');
			if (is_array($sessionFiles)) {

				foreach ($sessionFiles as $field => $files) {

					if (!strcmp($field, $this->fieldName)) {

						//get upload folder
						$uploadFolder = $this->utilityFuncs->getTempUploadFolder();

						//build absolute path to upload folder
						$uploadPath = $this->utilityFuncs->getTYPO3Root() . $uploadFolder;

						$found = FALSE;
						foreach ($files as $key=>&$fileInfo) {
							if (!strcmp($fileInfo['uploaded_name'], $this->uploadedFileName)) {
								$found = TRUE;
								unset($sessionFiles[$field][$key]);
								if(file_exists($uploadPath . $fileInfo['uploaded_name'])) {
									unlink($uploadPath . $fileInfo['uploaded_name']);
								}
							}
						}
						if (!$found) {
							foreach ($files as $key=>&$fileInfo) {
								if (!strcmp($fileInfo['name'], $this->uploadedFileName)) {
									$found = TRUE;
									unset($sessionFiles[$field][$key]);
									if(file_exists($uploadPath . $fileInfo['name'])) {
										unlink($uploadPath . $fileInfo['name']);
									}
								}
							}
						}
					}
				}
			}

			$this->globals->getSession()->set('files', $sessionFiles);

			// Add the content to or Result Box: #formResult
			if (is_array($sessionFiles) && !empty($sessionFiles[$field])) {
				$markers = array();
				$view = $this->componentManager->getComponent('Tx_Formhandler_View_Form');
				$view->setSettings($this->settings);
				$view->fillFileMarkers($markers);
				$langMarkers = $this->utilityFuncs->getFilledLangMarkers($markers['###'. $this->fieldName . '_uploadedFiles###'], $this->langFiles);
				$markers['###'. $this->fieldName . '_uploadedFiles###'] = $this->globals->getCObj()->substituteMarkerArray($markers['###'. $this->fieldName . '_uploadedFiles###'], $langMarkers);
				$content = $markers['###'. $this->fieldName . '_uploadedFiles###'];
			}
		}
		print $content;
	}

	/**
	 * Initialize the class. Read GET parameters
	 *
	 * @return void
	 */
	protected function init() {
		$this->fieldName = htmlspecialchars($_GET['field']);
		$this->uploadedFileName = htmlspecialchars($_GET['uploadedFileName']);
		if (isset($_GET['pid'])) {
			$this->id = intval($_GET['pid']);
		} else {
			$this->id = intval($_GET['id']);
		}
		
		$this->componentManager = Tx_Formhandler_Component_Manager::getInstance();
		$this->globals = Tx_Formhandler_Globals::getInstance();
		$this->utilityFuncs = Tx_Formhandler_UtilityFuncs::getInstance();
		tslib_eidtools::connectDB();
		$this->utilityFuncs->initializeTSFE($this->id);
		$this->globals->setCObj($GLOBALS['TSFE']->cObj);
		$randomID = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('randomID'));
		$this->globals->setRandomID($randomID);

		if(!$this->globals->getSession()) {
			$ts = $GLOBALS['TSFE']->tmpl->setup['plugin.']['Tx_Formhandler.']['settings.'];
			$sessionClass = $this->utilityFuncs->getPreparedClassName($ts['session.'], 'Session_PHP');
			$this->globals->setSession($this->componentManager->getComponent($sessionClass));
		}

		$this->settings = $this->globals->getSession()->get('settings');
		$this->langFiles = $this->utilityFuncs->readLanguageFiles(array(), $this->settings);

		//init ajax
		if ($this->settings['ajax.']) {
			$class = $this->utilityFuncs->getPreparedClassName($this->settings['ajax.'], 'AjaxHandler_JQuery');
			$ajaxHandler = $this->componentManager->getComponent($class);
			$this->globals->setAjaxHandler($ajaxHandler);

			$ajaxHandler->init($this->settings['ajax.']['config.']);
			$ajaxHandler->initAjax();
		}
	}

}

$output = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Formhandler_Utils_AjaxRemoveFile');
$output->main();

?>
