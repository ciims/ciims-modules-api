<?php

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
                'actions' => array('callback')
            )
        );  
    }

    /**
     * Returns a list of installed themes
     * @return array
     */
    public function actionInstalled()
    {
        $directories = glob(Yii::getPathOfAlias('webroot.themes') . DIRECTORY_SEPARATOR . "*", GLOB_ONLYDIR);
        foreach($directories as $dir)
        {
            $json = CJSON::decode(file_get_contents($dir . DIRECTORY_SEPARATOR . 'composer.json'));
            $name = $json['name'];
            $key = str_replace('ciims-themes/', '', $name);
            $files[$key] = array(
                'path' => $dir,
                'name' => $name,
            );
        }

        return $files;
    }

    /**
     * Determines if a particular theme (given by $name) is installed
     * @param string $name
     * @return bool
     */
    public function actionIsInstalled($name=false)
    {
    	if ($name == false)
   			throw new CHttpException(400, Yii::t('Api.Theme', 'Missing theme name'));

    	$installed = $this->actionInstalled();
    	$keys = array_keys($installed);
    	if (in_array($name, $keys))
            return true;
        
        throw new CHttpException(404, Yii::t('Api.Theme', 'Theme is not installed'));
    }

    /**
     * Lists ciims-themes that are available for download via packagist
     * @return array
     */
    public function actionList()
   	{
		$themes = Yii::app()->cache->get('packagist_themes');
		if ($themes === false)
		{
			$packagist = new Packagist\Api\Client();

			foreach ($packagist->search('ciims-themes') as $result)
			{
			    if (strpos($result->getName(), 'ciims-themes') !== false)
			    {
			        $themes[] = array(
			        	'name' => $result->getName(),
			        	'description' => $result->getDescription(),
			        	'url' => $result->getUrl(),
			        	'downloads' => $result->getDownloads()
			        );
			    }
			}
			Yii::app()->cache->set('packagist_themes', $themes, 900);
		}

		return $themes;
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

   		$result = Yii::app()->cache->get('packagist_ciims-themes/'.$name);
   		if ($result === false)
   		{
   			$array = array();
   			$packagist = new Packagist\Api\Client();
   			$theme = $packagist->get('ciims-themes/'.$name);

   			// List maintainers
   			$maintainers = array();
   			$versions = array();
   			$latestVersion = 0;
   			foreach ($theme->getMaintainers() as $maintainer);
   				$maintainers[] = array(
   					'name' => $maintainer->getName(),
   					'email' => $maintainer->getEmail(),
   					'homepage' => $maintainer->getHomepage()
   				);

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
   					$latestVersion = $version;
   			}

   			$result = array(
   				'name' => $theme->getName(),
   				'description' => $theme->getDescription(),
   				'repository' => $theme->getRepository(),
   				'maintainers' => $maintainers,
				'latest-version' => $latestVersion,
				'sha' => $info[$latestVersion]->getSource()->getReference(),
				'file' => $theme->getRepository().'/releases/download/'.$latestVersion.'/main.zip',
   				'downloads' => array(
   					'total' => $theme->getDownloads()->getTotal(),
   					'monthly' => $theme->getDownloads()->getMonthly(),
   					'daily' => $theme->getDownloads()->getDaily()
   				)
   			);
   			
   			Yii::app()->cache->set('packagist_ciims-themes/'.$name, $result, 900);
   		}

   		return $result;
   	}

	/**
	 * Allows themes to have their own dedicated callback resources.
	 *
	 * This enables theme developers to not have to hack CiiMS Core in order to accomplish stuff
	 * @param  string $method The method of the current theme they want to call
	 * @return The output or action of the callback
	 */
	public function actionCallback($theme=NULL, $method=NULL)
	{
		if ($theme == NULL)
			throw new CHttpException(400, Yii::t('Api.Theme', 'Missing Theme'));

		if ($method == NULL)
			throw new CHttpException(400, Yii::t('Api.Theme', 'Method name is missing'));

		Yii::import('webroot.themes.' . $theme . '.Theme');
		$theme = new Theme;

		if (method_exists($theme, $method))
			return $theme->$method($_POST);

		throw new CHttpException(404, Yii::t('Api.Theme', 'Missing callback method.'));
	}
}