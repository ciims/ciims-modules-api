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