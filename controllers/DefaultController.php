<?php

/**
 * Class handles some non model specific endpoints
 */
class DefaultController extends ApiController
{
	public function accessRules()
	{
		return array(
			array('allow',
				'actions' => array('index', 'error')
			),
			array('allow',
				'actions' => array('JsonProxyPost'),
				'expression' => '$user!=NULL&&$user->role->id>=5'
			),
			array('deny')
		);
	}

	/**
	 * Checks the current connection status and returns it as the default API response
	 * @return boolean
	 */
	public function actionIndex()
	{
		if (Yii::app()->db->connectionStatus)
			return true;

		return false;
	}

	/**
	 * Global proxy for CiiMS to support JavaScript endpoints that either don't have proper CORS headers or SSL
	 * This endpoint will only process JSON, and will cache data for 10 minutes.
	 * @return mixed
	 */
	public function actionJsonProxyPost()
	{
		$url = Cii::get($_POST, 'url', false);

		if ($url === false)
			throw new CHttpException(400, Yii::t('Api.index', 'Missing $_POST[url] parameter'));

		$hash = md5($url);
		$response = Yii::app()->cache->get('CiiMS::API::Proxy::'.$hash);

		if ($response == false)
		{
			$curl = new \Curl\Curl;			
			$response = serialize($curl->get($url));

			if ($curl->error)
				throw new CHttpException(500, Yii::t('Api.index', 'Failed to retrieve remote resource.'));

			$curl->close();

			Yii::app()->cache->set('API::Proxy::'.$hash, $response, 600);
		}
		
		return unserialize($response);
	}
}
