<?php
//
// iTop module definition file
//

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-views/1.0.0',
	array(
		// Identification
		//
		'label' => 'Views management',
		'category' => 'business',

		// Setup
		//
		'dependencies' => array(
			
		),
		'mandatory' => false,
		'visible' => true,
		'installer' => 'CombodoViewsInstaller',

		// Components
		//
		'datamodel' => array(
		),
		'webservice' => array(
			
		),
		'data.struct' => array(
			// add your 'structure' definition XML files here,
		),
		'data.sample' => array(
			// add your sample data XML files here,
		),
		
		// Documentation
		//
		'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
		'doc.more_information' => '', // hyperlink to more information, if any 

		// Default settings
		//
		'settings' => array(
			// Module specific settings go here, if any
		),
	)
);

if (!class_exists('CombodoViewsInstaller'))
{
// Module installation handler
//
	class CombodoViewsInstaller extends ModuleInstallerAPI
	{
		public static function AfterDatabaseSetup(Config $oConfiguration, $sPreviousVersion, $sCurrentVersion)
		{
			SetupPage::log_info("Create default views");

			$aMessages = array();
			$aSugFix = array();

			// Reporting views (must be created after any other table)
			//
			foreach (MetaModel::GetClasses('bizmodel') as $sClass)
			{
				$sView = MetaModel::DBGetView($sClass);
				// Create the view
				//
				$oFilter = new DBObjectSearch($sClass, '');
				$oFilter->AllowAllData();
				$sSQL = $oFilter->MakeSelectQuery();
				// N°3431 - Need to remove class name from attribute alias to conform with 2.6 style
				$aSQL = preg_split("@\n@", $sSQL);
				$aResult = [];
				$sState = 'start';
				foreach ($aSQL as $sLine) {
					$sLine = trim($sLine);
					switch ($sState) {
						case 'start':
							if ($sLine == 'SELECT') {
								$aResult[] = $sLine;
								$sState = 'select';
							}
							break;
						case 'select':
							if ($sLine == 'FROM') {
								$aResult[] = $sLine;
								$sState = 'end';
							} else {
								if (preg_match("@^(?<left>.*) AS `{$sClass}(?<right>.*)`(?<sep>,?)$@", $sLine, $aMatches)) {
									$sOldStyle = $aMatches['left'].' AS `'.$aMatches['right'].'`'.$aMatches['sep'];
									$aResult[] = $sOldStyle;
								} else {
									$aResult[] = $sLine;
								}
							}
							break;
						case 'end':
							$aResult[] = $sLine;
							break;
					}
				}
				$sSQL = implode(" ", $aResult);

				$aMessages[$sClass] = "Creating view for class: $sClass";
				$aSugFix[$sClass][] = "DROP VIEW IF EXISTS `$sView`";
				$aSugFix[$sClass][] = "CREATE VIEW `$sView` AS $sSQL";
			}

			foreach ($aSugFix as $sClass => $aQueries)
			{
				SetupLog::Info($aMessages[$sClass]);
				foreach ($aQueries as $sQuery)
				{
					CMDBSource::Query($sQuery);
				}
			}
		}
	}
}
