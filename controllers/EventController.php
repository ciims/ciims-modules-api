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
				'actions' => array('index', 'countPost'),
				'expression' => '$user!=NULL&&$user->role->hasPermission("create")'
			),
			array('deny')
		);
	}

	public function actionIndex()
	{
		$model = new Events('search');
		$model->unsetAttributes();  // clear any default values
        
        if(isset($_GET['Event']))
            $model->attributes = $_GET['Event'];

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
        	'count' => $model->count(), // This to the TOTAL count (as if pagination DNE)
        	'data' => $response // This is JUST the paginated response
        );
	}

	/**
	 * [POST] [/event/count]
	 * Retrieves the counts of a given set of entries over a given start and end date
	 * @return array
	 */
	public function actionCountPost()
	{
		$ids = Yii::app()->request->getParam('ids', array());

		// Default count range between the current time and the last 24 hours
		$start = Yii::app()->request->getParam('start', time());
		$end = Yii::app()->request->getParam('end', time()-(24*60*60));

		$response = array();

		foreach($ids as $id)
		{
			$criteria = new CDbCriteria;
			$criteria->compare('content_id', $id);
			$criteria->addBetweenCondition('created', $end, $start);
			$criteria->compare('event', '_trackPageView');
			$response[$id] = Events::model()->count($criteria);
		}

		return $response;
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
		if (strpos($attributes['uri'], '/dashboard/') !== false)
			return true;

		$event->attributes = $attributes;

		// Set failure?
		if (isset($attributes['content_id']))
			$event->content_id = $attributes['content_id'];
		
		// Try to figure out the correct content_id if one isn't provided
        if ($event->content_id == NULL)
        {
        	$content = Content::model()->findByAttributes(array('slug' => $event->uri));
        	if ($content !== NULL)
        		$event->content_id = $content->id;
        }

		if ($id = Cii::get($attributes, 'content_id', false))
		{
			$content = Content::model()->findByPk($id);
			if ($content != NULL)
				$event->uri = $content->slug;
		}
		else
			$event->content_id = null;

		if ($event->save())
			return $event->getApiAttributes();

		return $this->returnError(400, NULL, $event->getErrors());
	}
}
