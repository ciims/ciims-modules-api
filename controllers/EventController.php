<?php

class EventController extends ApiController
{
	public function accessRules()
	{
		return array(
			array('allow',
				'actions' => array('indexPost')
			),
			array('allow',
				'actions' => array('index'),
				'expression' => '$user!=NULL&&$user->role->hasPermission("create")'
			),
			array('deny')
		);
	}

	public function actionIndex()
	{
		$model = new Events('search');
		$model->unsetAttributes();  // clear any default values
        
        if(isset($_GET['Events']))
            $model->attributes = $_GET['Events'];

        $dataProvider = $model->search();
        $dataProvider->pagination = array(
            'pageVar' => 'page'
        );

        // Throw a 404 if we exceed the number of available results
        if ($dataProvider->totalItemCount == 0 || ($dataProvider->totalItemCount / ($dataProvider->itemCount * Cii::get($_GET, 'page', 1))) < 1)
            throw new CHttpException(404, Yii::t('Api.events', 'No results found'));

        $response = array();

        foreach ($dataProvider->getData() as $content)
            $response[] = $content->getAPIAttributes();

        return array(
        	'count' => $model->count(),
        	'data' => $response
        );
	}

	/**
	 * [POST] [/event/index]
	 * Post endpoint for submitting new events
	 * @return array
	 */
	public function actionIndexPost()
	{
		$event = new Events;
		$attributes = Yii::app()->request->getParam('Event', array());
		$event->attributes = $attributes;

		if ($id = Cii::get($attributes, 'content_id', false))
		{
			$content = Content::model()->findByPk($id);
			if ($content != NULL)
				$event->uri = $content->slug;
		}

		if ($event->save())
			return $event->getApiAttributes();

		return $this->returnError(400, NULL, $event->getErrors());
	}
}
