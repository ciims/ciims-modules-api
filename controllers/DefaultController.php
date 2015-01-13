<?php

class DefaultController extends ApiController
{
	public function accessRules()
    {
        return array(
        	array('allow',
        		'actions' => array('index')
        	),
            array('allow',
                'actions' => array('JsonProxyPost'),
                'expression' => '$user!=NULL'
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

    	$response = Yii::app()->cache->get('API::Proxy::'.$url);

    	if ($response == false)
    	{
    		$ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_URL            => $url,
            ));

            $data = curl_exec($ch);

            if (curl_error($ch))
                throw new CHttpException(500, Yii::t('Api.index', 'Failed to retrieve remote resource.'));

            curl_close($ch);

            Yii::app()->cache->set('API::Proxy::'.$url, CJSON::encode($data), 600);
            return CJSON::decode($data);
    	}

    	return CJSON::encode($response);
    }
}
