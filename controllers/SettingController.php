<?php

class SettingController extends ApiController
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
                'expression' => '$user!=NULL&&($user->role->hasPermission("manage"))'
            ),
            array('deny') 
        );  
    }

    /**
	 * [GET] [/api/setting]
	 * @class GeneralSettings
	 */
	public function actionIndex()
	{
		$model = new GeneralSettings;
		return $this->getModelAttributes($model);
	}

	/**
	 * [POST] [/api/setting]
	 * @class GeneralSettings
	 */
	public function actionIndexPost()
	{
		$model = new GeneralSettings;
		return $this->loadData($_POST, $model);
	}

	/**
	 * [GET] [/api/setting/email]
	 * @class EmailSettings
	 */
	public function actionEmail()
	{
		$model = new EmailSettings;
		return $this->getModelAttributes($model);
	}

	/**
	 * [POST] [/api/setting/email]
	 * @class EmailSettings
	 */
	public function actionEmailPost()
	{
		$model = new EmailSettings;
		return $this->loadData($_POST, $model);
	}

	/**
	 * [GET] [/api/settings/emailtest]
	 * Provides functionality to send a test email
	 */
	public function actionEmailTest()
	{
		$data = $this->sendEmail($this->user,  Yii::t('Api.settings', 'CiiMS Test Email'), 'application.modules.api.views.email.test', array(), true, true, true, true);

		if ($data !== true)
			throw new CHttpException(400, $data);

		$this->message = Yii::t('Api.settings', 'CiiMS was successfully able to send an email to {{email}}. Please verify that you recieved the test email.', array(
			'{{email}}' => CHtml::tag('strong', array(), $this->user->email)
		));
		return $data;
	}

	/**
	 * [GET] [/api/setting/social]
	 * @class SocialSettings
	 */
	public function actionSocial()
	{
		$model = new SocialSettings;
		return $this->getModelAttributes($model);
	}

	/**
	 * [POST] [/api/setting/social]
	 * @class SocialSettings
	 */
	public function actionSocialPost()
	{
		$model = new SocialSettings;
		return $this->loadData($_POST, $model);
	}

	/**
	 * [GET] [/api/setting/analytics]
	 * @class AnalyticsSettings
	 */
	public function actionAnalytics()
	{
		$model = new AnalyticsSettings;
		return $this->getModelAttributes($model);
	}

	/**
	 * [POST] [/api/setting/analytics]
	 * @class AnalyticsSettings
	 */
	public function actionAnalyticsPost()
	{
		$model = new AnalyticsSettings;
		return $this->loadData($_POST, $model);
	}

	/**
	 * [GET] [/api/setting/appearance]
	 * @class ThemeSettings
	 */
	public function actionAppearance()
	{
		$model = new ThemeSettings;
		return $this->getModelAttributes($model);
	}

	/**
	 * [GET] [/api/setting/appearance]
	 * @class ThemeSettings
	 */
	public function actionAppearancePost()
	{
		$model = new ThemeSettings;
		return $this->loadData($_POST, $model);
	}

	/**
	 * [GET] [/api/setting/theme]
	 * @class Theme
	 */
	public function actionTheme()
	{
		$model = $this->getTheme();
		return $this->getModelAttributes($model);
	}

	/**
	 * [POST] [/api/setting/theme]
	 * @class Theme
	 */
	public function actionThemePost()
	{
		$model = $this->getThemeAttributes();
		return $this->loadData($_POST, $model);
	}

	/**
	 * [GET] [/api/settings/flushcache]
	 * @return boolean
	 */
	public function actionFlushCache()
	{
		return Yii::app()->cache->flush();
	}

	/**
	 * Retrieves the appropriate model for the theme
	 * @param  string $type The data type to load
	 * @return Theme
	 */
	private function getThemeAttributes()
	{
		$theme = Cii::getConfig('theme', 'default');

		if (!file_exists(Yii::getPathOfAlias('webroot.themes.' . $theme) . DIRECTORY_SEPARATOR . 'Theme.php'))
			throw new CHttpException(400, Yii::t('Api.setting',  'The requested theme type is not set. Please set a theme before attempting to change theme settings.'));

		Yii::import('webroot.themes.' . $theme . '.Theme');

		try {
			$model = new Theme();
		} catch(Exception $e) {
			throw new CHttpException(400,  Yii::t('Api.setting', 'The requested theme type is not set. Please set a theme before attempting to change theme settings.'));
		}

		return $model;
	}

	/**
	 * Populates and saves model attributes
	 * @param  $_POST $post            $_POST data
	 * @param  CiiSettingsModel $model The model we want to populate
	 * @return array                   The saved model attributes or an error message
	 */
	private function loadData($post, &$model)
	{
		$model->populate($_POST, false);

		if ($model->save())
			return $this->getModelAttributes($model);

		return $this->returnError(400, NULL, $model->getErrors());
	}

	/**
	 * Retrieves model attributes for a particular model
	 * @param  CiiSettingsModel $model The model we want to query against
	 * @return array
	 */
	private function getModelAttributes(&$model)
	{
		$response = array();
		$reflection = new ReflectionClass($model);
		$properties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED);

		foreach ($properties as $property)
			$response[$property->name] = $model[$property->name];

		return $response;
	}
}