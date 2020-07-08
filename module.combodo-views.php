<?php
//
// iTop module definition file
//

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-views/0.1.0',
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
				if (CMDBSource::IsTable($sView))
				{
					// Check that the view is complete
					//
					// Note: checking the list of attributes is not enough because the columns can be stable while the SELECT is not stable
					//       Example: new way to compute the friendly name
					//       The correct comparison algorithm is to compare the queries,
					//       by using "SHOW CREATE VIEW" (MySQL 5.0.1 required) or to look into INFORMATION_SCHEMA/views
					//       both requiring some privileges
					// Decision: to simplify, let's consider the views as being wrong anytime
					// Rework the view
					//
					$oFilter = new DBObjectSearch($sClass, '');
					$oFilter->AllowAllData();
					$sSQL = $oFilter->MakeSelectQuery();
					$aMessages[$sClass] = "Redeclare view '$sView' (systematic - to support an eventual change in the friendly name computation)";
					$aSugFix[$sClass][] = "ALTER VIEW `$sView` AS $sSQL";
				}
				else
				{
					// Create the view
					//
					$oFilter = new DBObjectSearch($sClass, '');
					$oFilter->AllowAllData();
					$sSQL = $oFilter->MakeSelectQuery();
					$aMessages[$sClass] = "Missing view for class: $sClass";
					$aSugFix[$sClass][] = "DROP VIEW IF EXISTS `$sView`";
					$aSugFix[$sClass][] = "CREATE VIEW `$sView` AS $sSQL";
				}
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
