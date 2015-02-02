<?php
	/**
	 * This file contains the QDateTimePicker class.
	 *
	 * @package Controls
	 */

	/**
	 * This class is meant to be a date-picker.  It will essentially render an uneditable HTML textbox
	 * as well as a calendar icon.  The idea is that if you click on the icon or the textbox,
	 * it will pop up a calendar in a new small window.
	 *
	 * @package Controls
	 *
	 * @property mixed $DateTime
	 * @property string $DateTimePickerType
	 * @property string $DateTimePickerFormat
	 * @property integer $MinimumYear
	 * @property integer $MaximumYear
	 */
	class QDateTimePicker extends QControl {
		///////////////////////////
		// Private Member Variables
		///////////////////////////

		// MISC
		protected $dttDateTime = null;
		protected $strDateTimePickerType = QDateTimePickerType::Date;
		protected $strDateTimePickerFormat = QDateTimePickerFormat::MonthDayYear;

		protected $intMinimumYear = 1970;
		protected $intMaximumYear = 2020;

		protected $intSelectedMonth = null;
		protected $intSelectedDay = null;
		protected $intSelectedYear = null;

		// SETTINGS
		protected $strJavaScripts = 'date_time_picker.js';
		protected $strCssClass = 'datetimepicker';

		//////////
		// Methods
		//////////
		public function ParsePostData() {
			if (array_key_exists($this->strControlId . '_lstMonth', $_POST) || array_key_exists($this->strControlId . '_lstHour', $_POST)) {
				$dttNewDateTime = new QDateTime();

				// Update Date Component
				switch ($this->strDateTimePickerType) {
					case QDateTimePickerType::Date:
					case QDateTimePickerType::DateTime:
					case QDateTimePickerType::DateTimeSeconds:
						$strKey = $this->strControlId . '_lstMonth';
						if (array_key_exists($strKey, $_POST))
							$intMonth = $_POST[$strKey];
						else
							$intMonth = null;
	
						$strKey = $this->strControlId . '_lstDay';
						if (array_key_exists($strKey, $_POST))
							$intDay = $_POST[$strKey];
						else
							$intDay = null;
	
						$strKey = $this->strControlId . '_lstYear';
						if (array_key_exists($strKey, $_POST))
							$intYear = $_POST[$strKey];
						else
							$intYear = null;
	
						$this->intSelectedMonth = $intMonth;
						$this->intSelectedDay = $intDay;
						$this->intSelectedYear = $intYear;
	
						if (strlen($intYear) && strlen($intMonth) && strlen($intDay))
							$dttNewDateTime->setDate($intYear, $intMonth, $intDay);
						else
							$dttNewDateTime->Year = null;
						break;
				}
	
				// Update Time Component
				switch ($this->strDateTimePickerType) {
					case QDateTimePickerType::Time:
					case QDateTimePickerType::TimeSeconds:
					case QDateTimePickerType::DateTime:
					case QDateTimePickerType::DateTimeSeconds:
						$strKey = $this->strControlId . '_lstHour';
						if (array_key_exists($strKey, $_POST)) {
							$intHour = $_POST[$strKey];
							if (strlen($intHour)) {
								$intHour = $_POST[$this->strControlId . '_lstHour'];
								$intMinute = $_POST[$this->strControlId . '_lstMinute'];
								$intSecond = 0;
								if (($this->strDateTimePickerType == QDateTimePickerType::TimeSeconds) ||
									($this->strDateTimePickerType == QDateTimePickerType::DateTimeSeconds))
									$intSecond = $_POST[$this->strControlId . '_lstSecond'];
	
								if (strlen($intHour) && strlen($intMinute) && strlen($intSecond))
									$dttNewDateTime->setTime($intHour, $intMinute, $intSecond);
								else
									$dttNewDateTime->Hour = null;
							}
						}
						break;
				}
	
				// Update local intTimestamp
				$this->dttDateTime = $dttNewDateTime;
			}
		}

		protected function GetControlHtml() {
			// Ignore Class
			$strCssClass = $this->strCssClass;
			$this->strCssClass = '';
			$strAttributes = $this->GetAttributes();
			$this->strCssClass = $strCssClass;

			$strStyle = $this->GetStyleAttributes();
			if ($strStyle)
				$strAttributes .= sprintf(' style="%s"', $strStyle);

			$strCommand = sprintf(' onchange="Qcodo__DateTimePicker_Change(\'%s\', this);"', $this->strControlId);

			if ($this->dttDateTime) {
				$dttDateTime = $this->dttDateTime;
			} else {
				$dttDateTime = new QDateTime();
			}

			$strToReturn = '';

			// Generate Date-portion
			switch ($this->strDateTimePickerType) {
				case QDateTimePickerType::Date:
				case QDateTimePickerType::DateTime:
				case QDateTimePickerType::DateTimeSeconds:
					// Month
					$strMonthListbox = sprintf('<select name="%s_lstMonth" id="%s_lstMonth" class="month" %s%s>', $this->strControlId, $this->strControlId, $strAttributes, $strCommand);
					if (!$this->blnRequired || $dttDateTime->IsDateNull())
						$strMonthListbox .= '<option value="">--</option>';
					for ($intMonth = 1; $intMonth <= 12; $intMonth++) {
						if ((!$dttDateTime->IsDateNull() && ($dttDateTime->Month == $intMonth)) || ($this->intSelectedMonth == $intMonth))
							$strSelected = ' selected="selected"';
						else
							$strSelected = '';
						$strMonthListbox .= sprintf('<option value="%s"%s>%s</option>',
							$intMonth,
							$strSelected,
							QApplication::Translate(strftime("%b", mktime(0, 0, 0, $intMonth, 1, 2000))));
					}
					$strMonthListbox .= '</select>';

					// Day
					$strDayListbox = sprintf('<select name="%s_lstDay" id="%s_lstDay" class="day" %s%s>', $this->strControlId, $this->strControlId, $strAttributes, $strCommand);
					if (!$this->blnRequired || $dttDateTime->IsDateNull())
						$strDayListbox .= '<option value="">--</option>';
					if ($dttDateTime->IsDateNull()) {
						if ($this->blnRequired) {
							// New DateTime, but we are required -- therefore, let's assume January is preselected
							for ($intDay = 1; $intDay <= 31; $intDay++) {
								$strDayListbox .= sprintf('<option value="%s">%s</option>', $intDay, $intDay);
							}
						} else {
							// New DateTime -- but we are NOT required
							
							// See if a month has been selected yet.
							if ($this->intSelectedMonth) {
								$intSelectedYear = ($this->intSelectedYear) ? $this->intSelectedYear : 2000;
								$intDaysInMonth = date('t', mktime(0, 0, 0, $this->intSelectedMonth, 1, $intSelectedYear));
								for ($intDay = 1; $intDay <= $intDaysInMonth; $intDay++) {
									if (($dttDateTime->Day == $intDay) || ($this->intSelectedDay == $intDay))
										$strSelected = ' selected="selected"';
									else
										$strSelected = '';
									$strDayListbox .= sprintf('<option value="%s"%s>%s</option>',
										$intDay,
										$strSelected,
										$intDay);
								}
							} else {
								// It's ok just to have the "--" marks and nothing else
							}
						}
					} else {
						$intDaysInMonth = $dttDateTime->PHPDate('t');
						for ($intDay = 1; $intDay <= $intDaysInMonth; $intDay++) {
							if (($dttDateTime->Day == $intDay) || ($this->intSelectedDay == $intDay))
								$strSelected = ' selected="selected"';
							else
								$strSelected = '';
							$strDayListbox .= sprintf('<option value="%s"%s>%s</option>',
								$intDay,
								$strSelected,
								$intDay);
						}
					}
					$strDayListbox .= '</select>';
					
					// Year
					$strYearListbox = sprintf('<select name="%s_lstYear" id="%s_lstYear" class="year" %s%s>', $this->strControlId, $this->strControlId, $strAttributes, $strCommand);
					if (!$this->blnRequired || $dttDateTime->IsDateNull())
						$strYearListbox .= '<option value="">--</option>';
					for ($intYear = $this->intMinimumYear; $intYear <= $this->intMaximumYear; $intYear++) {
						if (/*!$dttDateTime->IsDateNull() && */(($dttDateTime->Year == $intYear) || ($this->intSelectedYear == $intYear)))
							$strSelected = ' selected="selected"';
						else
							$strSelected = '';
						$strYearListbox .= sprintf('<option value="%s"%s>%s</option>', $intYear, $strSelected, $intYear);
					}
					$strYearListbox .= '</select>';

					// Put it all together
					switch ($this->strDateTimePickerFormat) {
						case QDateTimePickerFormat::MonthDayYear:
							$strToReturn .= $strMonthListbox . $strDayListbox . $strYearListbox;
							break;
						case QDateTimePickerFormat::DayMonthYear:
							$strToReturn .= $strDayListbox . $strMonthListbox . $strYearListbox;
							break;
						case QDateTimePickerFormat::YearMonthDay:
							$strToReturn .= $strYearListbox . $strMonthListbox . $strDayListbox;
							break;
					}
			}

			switch ($this->strDateTimePickerType) {
				case QDateTimePickerType::DateTime:
				case QDateTimePickerType::DateTimeSeconds:
					$strToReturn .= '<span class="divider"></span>';
			}

			switch ($this->strDateTimePickerType) {
				case QDateTimePickerType::Time:
				case QDateTimePickerType::TimeSeconds:
				case QDateTimePickerType::DateTime:
				case QDateTimePickerType::DateTimeSeconds:
					// Hour
					$strHourListBox = sprintf('<select name="%s_lstHour" id="%s_lstHour" class="hour" %s>', $this->strControlId, $this->strControlId, $strAttributes);
					if (!$this->blnRequired || $dttDateTime->IsTimeNull())
						$strHourListBox .= '<option value="">--</option>';
					for ($intHour = 0; $intHour <= 23; $intHour++) {
						if (!$dttDateTime->IsTimeNull() && ($dttDateTime->Hour == $intHour))
							$strSelected = ' selected="selected"';
						else
							$strSelected = '';
						$strHourListBox .= sprintf('<option value="%s"%s>%s</option>',
							$intHour,
							$strSelected,
							date('g A', mktime($intHour, 0, 0, 1, 1, 2000)));
					}
					$strHourListBox .= '</select>';


					// Minute
					$strMinuteListBox = sprintf('<select name="%s_lstMinute" id="%s_lstMinute" class="minute" %s>', $this->strControlId, $this->strControlId, $strAttributes);
					if (!$this->blnRequired || $dttDateTime->IsTimeNull())
						$strMinuteListBox .= '<option value="">--</option>';
					for ($intMinute = 0; $intMinute <= 59; $intMinute++) {
						if (!$dttDateTime->IsTimeNull() && ($dttDateTime->Minute == $intMinute))
							$strSelected = ' selected="selected"';
						else
							$strSelected = '';
						$strMinuteListBox .= sprintf('<option value="%s"%s>%02d</option>',
							$intMinute,
							$strSelected,
							$intMinute);
					}
					$strMinuteListBox .= '</select>';


					// Seconds
					$strSecondListBox = sprintf('<select name="%s_lstSecond" id="%s_lstSecond" class="second" %s>', $this->strControlId, $this->strControlId, $strAttributes);
					if (!$this->blnRequired || $dttDateTime->IsTimeNull())
						$strSecondListBox .= '<option value="">--</option>';
					for ($intSecond = 0; $intSecond <= 59; $intSecond++) {
						if (!$dttDateTime->IsTimeNull() && ($dttDateTime->Second == $intSecond))
							$strSelected = ' selected="selected"';
						else
							$strSelected = '';
						$strSecondListBox .= sprintf('<option value="%s"%s>%02d</option>',
							$intSecond,
							$strSelected,
							$intSecond);
					}
					$strSecondListBox .= '</select>';
					
					
					// PUtting it all together
					if (($this->strDateTimePickerType == QDateTimePickerType::DateTimeSeconds) ||
						($this->strDateTimePickerType == QDateTimePickerType::TimeSeconds))
						$strToReturn .= $strHourListBox . ':' . $strMinuteListBox . ':' . $strSecondListBox;
					else
						$strToReturn .= $strHourListBox . ':' . $strMinuteListBox;
			}

			if ($this->strCssClass)
				$strCssClass = ' class="' . $this->strCssClass . '"';
			else
				$strCssClass = '';
			return sprintf('<span id="%s"%s>%s</span>', $this->strControlId, $strCssClass, $strToReturn);
		}

		public function Validate() {
			if ($this->blnRequired) {
				$blnIsNull = false;
				
				if (!$this->dttDateTime)
					$blnIsNull = true;
				else {
					if ((($this->strDateTimePickerType == QDateTimePickerType::Date) ||
						($this->strDateTimePickerType == QDateTimePickerType::DateTime) ||
						($this->strDateTimePickerType == QDateTimePickerType::DateTimeSeconds )) && 
						($this->dttDateTime->IsDateNull()))
						$blnIsNull = true;
					else if ((($this->strDateTimePickerType == QDateTimePickerType::Time) ||
						($this->strDateTimePickerType == QDateTimePickerType::TimeSeconds)) &&
						($this->dttDateTime->IsTimeNull()))
						$blnIsNull = true;
				}

				if ($blnIsNull) {
					if ($this->strName)
						$this->strValidationError = sprintf(QApplication::Translate('%s is required'), $this->strName);
					else
						$this->strValidationError = QApplication::Translate('Required');
					return false;
				}
			} else {
				if ((($this->strDateTimePickerType == QDateTimePickerType::Date) ||
					($this->strDateTimePickerType == QDateTimePickerType::DateTime) ||
					($this->strDateTimePickerType == QDateTimePickerType::DateTimeSeconds )) &&
					($this->intSelectedDay || $this->intSelectedMonth || $this->intSelectedYear) &&
					($this->dttDateTime->IsDateNull())) {
					$this->strValidationError = QApplication::Translate('Invalid Date');
					return false;
				}
			}

			$this->strValidationError = '';
			return true;
		}

		/////////////////////////
		// Public Properties: GET
		/////////////////////////
		public function __get($strName) {
			switch ($strName) {
				// MISC
				case "DateTime":
					if (is_null($this->dttDateTime) || $this->dttDateTime->IsNull())
						return null;
					else {
						$objToReturn = clone($this->dttDateTime);
						return $objToReturn;
					}

				case "DateTimePickerType": return $this->strDateTimePickerType;
				case "DateTimePickerFormat": return $this->strDateTimePickerFormat;
				case "MinimumYear": return $this->intMinimumYear;
				case "MaximumYear": return $this->intMaximumYear;

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
		public function __set($strName, $mixValue) {
			$this->blnModified = true;

			switch ($strName) {
				// MISC
				case "DateTime":
					try {
						$dttDate = QType::Cast($mixValue, QType::DateTime);
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

					$this->intSelectedMonth = null;
					$this->intSelectedDay = null;
					$this->intSelectedYear = null;

					if (is_null($dttDate) || $dttDate->IsNull())
						$this->dttDateTime = null;
					else
						$this->dttDateTime = $dttDate;

					break;

				case "DateTimePickerType":
					try {
						$this->strDateTimePickerType = QType::Cast($mixValue, QType::String);
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
					break;

				case "DateTimePickerFormat":
					try {
						$this->strDateTimePickerFormat = QType::Cast($mixValue, QType::String);
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
					break;

				case "MinimumYear":
					try {
						$this->intMinimumYear = QType::Cast($mixValue, QType::String);
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
					break;

				case "MaximumYear":
					try {
						$this->intMaximumYear = QType::Cast($mixValue, QType::String);
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
					break;

				default:
					try {
						parent::__set($strName, $mixValue);
						break;
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}

		/* === Codegen Helpers, used during the Codegen process only. === */

		/**
		 * @param string $strPropName
		 *
		 * @return string
		 */
		public static function Codegen_VarName($strPropName) {
			return 'cal' . $strPropName;
		}

		/**
		 * Generate code that will be inserted into the MetaControl to connect a database object with this control.
		 * This is called during the codegen process.
		 *
		 * @param QCodeGen $objCodeGen
		 * @param QTable   $objTable
		 * @param QColumn  $objColumn
		 *
		 * @return string
		 */
		public static function Codegen_MetaCreate(QCodeGen $objCodeGen, QTable $objTable, QColumn $objColumn) {
			$strObjectName = $objCodeGen->ModelVariableName($objTable->Name);
			$strControlVarName = $objCodeGen->MetaControlVariableName($objColumn);
			$strLabelName = addslashes(QCodeGen::MetaControlControlName($objColumn));

			// Read the control type in case we are generating code for a subclass
			$strControlType = $objCodeGen->MetaControlControlClass($objColumn);

			$strRet = <<<TMPL
		/**
		 * Create and setup a $strControlType $strControlVarName
		 * @param string \$strControlId optional ControlId to use
		 * @return $strControlType
		 */
		public function {$strControlVarName}_Create(\$strControlId = null) {

TMPL;
			$strControlIdOverride = $objCodeGen->GenerateControlId($objTable, $objColumn);

			if ($strControlIdOverride) {
				$strRet .= <<<TMPL
			if (!\$strControlId) {
				\$strControlId = '$strControlIdOverride';
			}

TMPL;
			}
			$strRet .= <<<TMPL
			\$this->{$strControlVarName} = new $strControlType(\$this->objParentObject, \$strControlId);
			\$this->{$strControlVarName}->Name = QApplication::Translate('$strLabelName');
			\$this->{$strControlVarName}->DateTime = \$this->{$strObjectName}->{$objColumn->PropertyName};

TMPL;
			switch ($objColumn->DbType) {
				case QDatabaseFieldType::DateTime:
					$strRet .= "\t\t\t\$this->{$strControlVarName}->DateTimePickerType = QDateTimePickerType::DateTime;\n";
					break;

				case QDatabaseFieldType::Time:
					$strRet .= "\t\t\t\$this->{$strControlVarName}->DateTimePickerType = QDateTimePickerType::Time;\n";
					break;

				default:
					$strRet .= "\t\t\t\$this->{$strControlVarName}->DateTimePickerType = QDateTimePickerType::Date;\n";
			}

			if ($strMethod = QCodeGen::$PreferredRenderMethod) {
				$strRet .= <<<TMPL
			\$this->{$strControlVarName}->PreferredRenderMethod = '$strMethod';

TMPL;
			}

			$strRet .= static::Codegen_MetaCreateOptions ($objCodeGen, $objTable, $objColumn, $strControlVarName);

			$strRet .= <<<TMPL
			return \$this->{$strControlVarName};
		}


TMPL;

			return $strRet;

		}

		/**
		 * Generate code to reload data from the MetaControl into this control, or load it for the first time
		 *
		 * @param QCodeGen $objCodeGen
		 * @param QTable   $objTable
		 * @param QColumn  $objColumn
		 * @param boolean  $blnInit Generate initialization code instead of reload
		 *
		 * @return string
		 */
		public static function Codegen_MetaRefresh(QCodeGen $objCodeGen, QTable $objTable, QColumn $objColumn, $blnInit = false) {
			$strObjectName = $objCodeGen->ModelVariableName($objTable->Name);
			$strPropName = $objColumn->Reference ? $objColumn->Reference->PropertyName : $objColumn->PropertyName;
			$strControlVarName = static::Codegen_VarName($strPropName);

			if ($blnInit) {
				$strRet = "\t\t\t\$this->{$strControlVarName}->DateTime = \$this->{$strObjectName}->{$strPropName};";
			} else {
				$strRet = "\t\t\tif (\$this->{$strControlVarName}) \$this->{$strControlVarName}->DateTime = \$this->{$strObjectName}->{$strPropName};";
			}
			return $strRet . "\n";
		}

		/**
		 * Generate the code to move data from the control to the database.
		 * @param QCodeGen $objCodeGen
		 * @param QTable $objTable
		 * @param QColumn $objColumn
		 * @return string
		 */
		public static function Codegen_MetaUpdate(QCodeGen $objCodeGen, QTable $objTable, QColumn $objColumn) {
			$strObjectName = $objCodeGen->ModelVariableName($objTable->Name);
			$strPropName = $objColumn->Reference ? $objColumn->Reference->PropertyName : $objColumn->PropertyName;
			$strControlVarName = static::Codegen_VarName($strPropName);
			$strRet = <<<TMPL
				if (\$this->{$strControlVarName}) \$this->{$strObjectName}->{$objColumn->PropertyName} = \$this->{$strControlVarName}->DateTime;

TMPL;
			return $strRet;
		}

	}
?>