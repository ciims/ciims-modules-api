<?php

/**
 * Class handles theme management
 */
class ThemeController extends ApiController
{
	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',
				'actions' => array('callback', 'callbackPost')
			),
			array('allow',
				'actions' => array('installed', 'install', 'changetheme', 'update', 'updateCheck', 'uninstall', 'list', 'isinstalled', 'details'),
				'expression' => '$user!=NULL&&$user->role->hasPermission("manage")'
			),
			array('deny')
		);
	}

	/**
	 * Changes the theme to the one with the given $name
	 * @param string $name
	 * @return boolean
	 */
	public function actionChangeTheme($name=false)
	{
		if ($name == false)
			throw new CHttpException(400, Yii::t('Api.Theme', 'Missing theme name'));

		if ($this->actionIsInstalled($name))
		{
			$model = Configuration::model()->getPrototype('Configuration', array(
			             'key' => 'theme'
			         ), array('value' => $name));
					 
			$model->value = $name;

			if ($model->save())
			{
				Yii::app()->cache->delete('settings_theme');
				// Cache flush is required to reset the views
				Yii::app()->cache->flush();
				return Cii::getConfig('theme');
			}
		}

		return $this->returnError(200, Yii::t('Api.main', 'Unable to change theme'), false);
	}

	/**
	 * Returns a list of installed themes
	 * @return array
	 */
	public function actionInstalled()
	{
		$themeSettings = new ThemeSettings;
		return $themeSettings->getThemes();
	}

	/**
	 * Determines if a particular theme (given by $name) is installed
	 * @param string $name
	 * @return boolean
	 */
	public function actionIsInstalled($name=false, $return = false)
	{
		if ($name == false)
			throw new CHttpException(400, Yii::t('Api.Theme', 'Missing theme name'));

		$installed 	= $this->actionInstalled();
		$keys 		= array_keys($installed);
		
		if (in_array($name, $keys))
			return true;

		if ($return)
			return false;

		return $this->returnError(200, Yii::t('Api.main', 'Theme is not installed'), false);
	}

	/**
	 * Checks if an update is necessary
	 * @param string $name
	 * @return boolean
	 */
	private function updateCheck($name)
	{
		$filePath = Yii::getPathOfAlias('base.themes').DS.$name;
		$details = $this->actionDetails($name);

		if (file_exists($filePath.DS.'VERSION'))
		{
			$version = file_get_contents($filePath.DS.'VERSION');
			if ($version != $details['latest-version'])
				return true;
		}
		else
			return true;

		return false;

	}

	/**
	 * Exposed action to check for an update
	 * @param string $name
	 * @return boolean
	 */
	public function actionUpdateCheck($name=false)
	{
		if ($name == false)
			return false;

		if (defined('CII_CONFIG'))
			return $this->returnError(200, Yii::t('Api.main', 'Update is not required'), false);

		if ($this->updateCheck($name))
			return $this->returnError(200, Yii::t('Api.main', 'Update is available'), true);

		return $this->returnError(200, Yii::t('Api.main', 'Update is not required'), false);
	}

	/**
	 * Performs an update
	 * @return boolean
	 */
	public function actionUpdate($name=false)
	{
		if ($name == false || defined('CII_CONFIG'))
			return false;

		// Performs an update check, and if an update is available performs the update
		if ($this->updateCheck($name))
		{
			if (!$this->actionInstall($name))
				return $this->returnError(500, Yii::t('Api.main', 'Update failed'), false);
		}

		// If an update is unecessary, dump the current details
		return $this->actionDetails($name);
	}

	/**
	 * Installs and or updates a theme  using the provided name
	 * @return boolean
	 */
	public function actionInstall($name=false)
	{
		if ($name == false || defined('CII_CONFIG'))
			return false;

		$filePath 	= Yii::getPathOfAlias('base.themes').DS.$name;
		$details 	= $this->actionDetails($name);

		// If the theme is already installed, make sure it is the correct version, otherwise we'll be performing an upgrade
		if ($this->actionIsInstalled($name, true))
		{
			if (file_exists($filePath.DS.'VERSION'))
			{
				$version = file_get_contents($filePath.DS.'VERSION');

				if ($version == $details['latest-version'])
					return true;
			}
		}

		// Set several variables to store the various paths we'll need
		$tmpPath 	= Yii::getPathOfAlias('application.runtime.themes').DS.$name;
		$themesPath = Yii::getPathOfAlias('application.runtime').DS.'themes';
		$zipPath 	= $themesPath.DS.$details['sha'].'.zip';

		// Verify the temporary directory exists
		if (!file_exists($themesPath))
			mkdir($themesPath);

		// Download the ZIP package to the runtime/themes temporary directory
		$this->downloadPackage($details['sha'], $details['file'], Yii::getPathOfAlias('application.runtime.themes'));
		$zip = new ZipArchive;

		// If we can open the file
		if ($zip->open($zipPath) === true)
		{
			// And we were able to extract it
			if ($zip->extractTo($themesPath.DS.$details['sha']))
			{
				// Close the ZIP connection
				$zip->close();

				// Delete the downloaded ZIP file
				unlink($zipPath);

				// Move the folders around so we're operating on the bare folder, then delete the teporary folder
				rename($themesPath.DS.$details['sha'].DS.'ciims-themes-'.$name.'-'.$details['latest-version'], $tmpPath);
				rmdir(str_replace('.zip', '', $zipPath));

				// Store the version with the theme
				file_put_contents($tmpPath.'/VERSION', $details['latest-version']);

				// If a theme is already installed that has that name, rename it to "$filePath-old"
				if (file_exists($filePath) && is_dir($filePath))
					rename($filePath, $filePath . "-old");

				// Then copy over the theme from the tmpPath to the final destination path
				rename($tmpPath, $filePath);

				// Then purge the -old directories
				if (file_exists($filePath . "-old"))
				{
					foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($filePath . "-old", FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path)
					$path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());

					rmdir($filePath . "-old");
				}

				// Purge the cache
				Yii::app()->cache->delete('settings_themes');
				return true;
			}

		}

		unlink($tmpPath.'.zip');
		throw new CHttpException(500, 'Unable to extract downloaded ZIP package');
	}

	/**
	 * Uninstalls a theme by name
	 * @return boolean
	 */
	public function actionUninstall($name=false)
	{
		if ($name == false)
			return false;

		if ($name == 'default')
			throw new CHttpException(400, Yii::t('Api.theme', 'You cannot uninstall the default theme.'));

		if (!$this->actionIsInstalled($name))
			return false;

		$installedThemes 	= $this->actionInstalled();
		$theme 				= $installedThemes[$name]['path'];
		$iterator 			= new RecursiveIteratorIterator(
									new RecursiveDirectoryIterator($theme, FilesystemIterator::SKIP_DOTS),
									RecursiveIteratorIterator::CHILD_FIRST
							  );
		foreach ($iterator as $path)
			$path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());

		return rmdir($theme);
	}

	/**
	 * Lists ciims-themes that are available for download via
	 * @return array
	 */
	public function actionList()
	{
		// Don't allow theme listing in CII_CONFIG
		if (defined('CII_CONFIG'))
			return false;

		$url = 'https://themes.ciims.io/index.json';

		$result = Yii::app()->cache->get('CiiMS::Themes::Available');
		if ($result === false)
		{
			$ch = curl_init();
			curl_setopt_array($ch, array(
              	CURLOPT_RETURNTRANSFER => true,
              	CURLOPT_FOLLOWLOCATION => true,
              	CURLOPT_URL            => $url,
          	));

			$data = curl_exec($ch);

			if (curl_error($ch))
				throw new CHttpException(500, Yii::t('Api.theme', 'Unable to download list of approved themes'));

			curl_close($ch);

			$result = CJSON::decode($data);
			Yii::app()->cache->set('CiiMS::Themes::Available', $result, 900);
		}

		return $result;
	}

	/**
	 * Retrieves the details about a particular theme
	 * @param string $name
	 * @return array
	 */
	public function actionDetails($name=false)
	{
		if ($name == false)
			throw new CHttpException(400, Yii::t('Api.Theme', 'Missing theme name'));

		$result = Yii::app()->cache->get('CiiMS::Packagist::Themes/'.$name);
		
		if ($result === false)
		{
			$array = array();
			$packagist = new Packagist\Api\Client();

			try {
				$theme = $packagist->get('ciims-themes/'.$name);
			} catch (Exception $e) {
				throw new CHttpException($e->getResponse()->getStatusCode(), $e->getResponse()->getReasonPhrase());
			}

			// List maintainers
			$maintainers 	= array();
			$versions 		= array();
			$latestVersion 	= 0;
			foreach ($theme->getMaintainers() as $maintainer)
			{
				$maintainers[] 	= array(
                	'name' 		=> $maintainer->getName(),
                    'email' 	=> $maintainer->getEmail(),
                    'homepage' 	=> $maintainer->getHomepage()
                );
			}

			$info = $theme->getVersions();
			foreach ($info as $version=>$details)
			{
				// Ignore dev-master
				if ($version == 'dev-master')
					continue;

				// Push the version
				$versionId = preg_replace("/[^0-9]/", "", $version);

				$versions[$versionId] = $version;
				if ($versionId > $latestVersion)
					$latestVersion = $versionId;
			}

			$result = array(
				'name' 				=> $theme->getName(),
				'description' 		=> $theme->getDescription(),
				'repository' 		=> $theme->getRepository(),
				'maintainers' 		=> $maintainers,
				'latest-version' 	=> $versions[$latestVersion],
				'sha' 				=> $info[$versions[$latestVersion]]->getSource()->getReference(),
				'file' 				=> $theme->getRepository().'/archive/'.$versions[$latestVersion].'.zip',
				'downloads' 		=> array(
							'total' 	=> $theme->getDownloads()->getTotal(),
							'monthly' 	=> $theme->getDownloads()->getMonthly(),
							'daily' 	=> $theme->getDownloads()->getDaily()
						)
	        );

			Yii::app()->cache->set('CiiMS::Packagist::Themes/'.$name, $result, 900);
		}

		return $result;
	}

	/**
	 * Allows themes to have their own dedicated callback resources for $_GET
	 *
	 * This enables theme developers to not have to hack CiiMS Core in order to accomplish stuff
	 * @param  string $method The method of the current theme they want to call
	 * @param string $theme   The theme name
	 * @return The output or action of the callback
	 */
	public function actionCallback($theme=NULL, $method=NULL)
	{
		$this->callback($theme, $method, $_GET);
	}

	/**
	 * Allows themes to have their own dedicated callback resources for POST
	 *
	 * This enables theme developers to not have to hack CiiMS Core in order to accomplish stuff
	 * @param  string $method The method of the current theme they want to call
	 * @param string $theme   The theme name
	 * @return The output or action of the callback
	 */
	public function actionCallbackPost($theme=NULL, $method=NULL)
	{
		$this->callback($theme, $method, $_POST);
	}

	/**
	 * Callback method for actionCallback and actionCallbackPost
	 *
	 * @param  string $method The method of the current theme they want to call
	 * @param string $theme   The theme name
	 * @param array $data $_GET or $_POST
	 * @return mixed
	 */
	private function callback($theme, $method, $data)
	{
		if ($theme == NULL)
			throw new CHttpException(400, Yii::t('Api.Theme', 'Missing Theme'));

		if ($method == NULL)
			throw new CHttpException(400, Yii::t('Api.Theme', 'Method name is missing'));

		Yii::import('base.themes.' . $theme . '.Theme');
		$theme = new Theme;

		if (method_exists($theme, $method))
			return $theme->$method($data);

		throw new CHttpException(404, Yii::t('Api.Theme', 'Missing callback method.'));
	}

	/**
	 * Retrieves a file from our CDN provider and returns it
	 * WARNING: This has the potential to return a _very_ large response object that can exceed PHP's MAX_MEMORY settings
	 *
	 * @param string $file  The Path to the package ZIP file
	 * @param string $id
	 * @return ZIP Package
	 */
	private function downloadPackage($id, $file, $path)
	{
		// We're downloading a file - prevent the client aborting the process from screwing up the download
		// and ensure that we have sufficient time to complete it
		ignore_user_abort(true);
		set_time_limit(0);

		$fp = fopen($path . DS . $id . '.zip', 'w+');
		$curl = curl_init();
		curl_setopt_array($curl, array(
          	CURLOPT_FILE => $fp,
          	CURLOPT_FOLLOWLOCATION => true,
          	CURLOPT_URL => $file,
          	CURLOPT_BINARYTRANSFER => true
      	));

		curl_exec($curl);
		curl_close($curl);
		fclose($fp);

		return;
	}
}
