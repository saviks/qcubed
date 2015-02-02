<?php
	/**
	 * This file contains the QFileControl class.
	 *
	 * @package Controls
	 */

	/**
	 * This class will render an HTML File input.
	 *
	 * @package Controls
	 *
	 * @property-read string $FileName is the name of the file that the user uploads
	 * @property-read string $Type is the MIME type of the file
	 * @property-read integer $Size is the size in bytes of the file
	 * @property-read string $File is the temporary full file path on the server where the file physically resides
	 */
	class QFileControl extends QControl {
		///////////////////////////
		// Private Member Variables
		///////////////////////////

		// MISC
		protected $strFileName = null;
		protected $strType = null;
		protected $intSize = null;
		protected $strFile = null;
		
		// SETTINGS
		protected $strFormAttributes = array('enctype'=>'multipart/form-data');

		//////////
		// Methods
		//////////
		public function ParsePostData() {
			// Check to see if this Control's Value was passed in via the POST data
			if ((array_key_exists($this->strControlId, $_FILES)) && ($_FILES[$this->strControlId]['tmp_name'])) {
				// It was -- update this Control's value with the new value passed in via the POST arguments
				$this->strFileName = $_FILES[$this->strControlId]['name'];
				$this->strType = $_FILES[$this->strControlId]['type'];
				$this->intSize = QType::Cast($_FILES[$this->strControlId]['size'], QType::Integer);
				$this->strFile = $_FILES[$this->strControlId]['tmp_name'];
			}
		}

		/**
		 * Returns the HTML of the control which can be sent to user's browser
		 *
		 * @return string HTML of the control
		 */
		protected function GetControlHtml() {
			// Reset Internal Values
			$this->strFileName = null;
			$this->strType = null;
			$this->intSize = null;
			$this->strFile = null;

			$strStyle = $this->GetStyleAttributes();
			if ($strStyle)
				$strStyle = sprintf('style="%s"', $strStyle);

			$strToReturn = sprintf('<input type="file" name="%s" id="%s" %s%s />',
				$this->strControlId,
				$this->strControlId,
				$this->GetAttributes(),
				$strStyle);

			return $strToReturn;
		}

		/**
		 * Tells if the file control is valid
		 *
		 * @return bool
		 */
		public function Validate() {
			$this->strValidationError = "";
			if ($this->blnRequired) {
				if (strlen($this->strFileName) > 0)
					return true;
				else {
					$this->strValidationError = QApplication::Translate($this->strName) . ' ' . QApplication::Translate('is required');
					return false;
				}
			} else
				return true;
		}

		/////////////////////////
		// Public Properties: GET
		/////////////////////////
		/**
		 * PHP magic method
		 * @param string $strName
		 *
		 * @return mixed
		 * @throws Exception
		 * @throws QCallerException
		 */
		public function __get($strName) {
			switch ($strName) {
				// MISC
				case "FileName": return $this->strFileName;
				case "Type": return $this->strType;
				case "Size": return $this->intSize;
				case "File": return $this->strFile;

				default:
					try {
						return parent::__get($strName);
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}

		/////////////////////////
		// Public Properties: SET
		/////////////////////////
		/**
		 * @param string $strName
		 * @param string $mixValue
		 *
		 * @return mixed
		 * @throws Exception|QCallerException
		 */
		public function __set($strName, $mixValue) {
			$this->blnModified = true;

			switch ($strName) {
				default:
					try {
						parent::__set($strName, $mixValue);
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
					break;
			}
		}
	}
?>