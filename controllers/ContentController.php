<?php

/**
 * Class handles management of content
 */
class ContentController extends ApiController
{
	public function accessRules()
	{
		return array(
			array('allow',
				'actions' => array('index', 'tag')
			),
			array('allow',
				'actions' => array('like'),
				'expression' => '$user!=NULL'
			),
			array('allow',
				'actions' => array('indexPost', 'tagPost', 'tagDelete', 'uploadImagePost', 'autosavePost', 'autosave', 'revisions'),
				'expression' => '$user!=NULL&&$user->role->hasPermission("create")'
			),
			array('allow',
				'actions' => array('indexDelete', 'publish', 'unpublish'),
				'expression' => '$user->role->hasPermission("publish")'
			),
			array('deny')
		);
	}

	/**
	 * [GET] [/content]
	 * Retrieves an article by the requested id, or retrieves all articles based upon permissions
	 *
	 * NOTE: This endpoint behaves differently for authenticated and unauthenticated users. Authenticated users are show posts they can mutate,
	 * whereas unauthenticated users will see every published item
	 *
	 * @param int $id   The Content id
	 */
	public function actionIndex($id=NULL)
	{
		$model = new Content('search');
		$model->unsetAttributes();  // clear any default values
		$model->pageSize = 20;

		if (isset($_GET['Content']))
			$model->attributes = $_GET['Content'];

		// Published is now a special field in the API, so we need to do type conversions on it to make things easier in the search() method
		if ($model->published == 'true')
			$model->published = true;
		else if ($model->published == 'false')
			$model->published = false;

		// Let the search getter do all tha hard work
		if ($id != NULL)
			$model->id = $id;

		// Normaly users can only see published entries
		$role 			= Cii::get($this->user, 'role', NULL);
		$hiddenFields 	= array('category_id', 'author_id');

		if ($this->user == NULL || UserRoles::model()->isA('user', $this->user->role->id))
		{
			$model->status = 1;
			$model->published = true;
			$model->password = "";

			array_push($hiddenFields, 'password', 'vid');
		}
		else if ($this->user->role->isA('admin'))
		{
			// Users with this role can do everything
		}
		else if ($this->user->role->isA('publisher') || $this->user->role->isA('author'))
		{
			// Restrict collaborates to only being able to see their own content
			$model->author_id = $this->user->id;
		}

		// Modify the pagination variable to use page instead of Content page
		$dataProvider = $model->search();
		$dataProvider->pagination = array(
			'pageVar' => 'page'
		);

		// Throw a 404 if we exceed the number of available results
		if ($dataProvider->totalItemCount == 0 || ($dataProvider->totalItemCount / ($dataProvider->itemCount * Cii::get($_GET, 'page', 1))) < 1)
			throw new CHttpException(404, Yii::t('Api.content', 'No results found'));

		$response = array();

		foreach ($dataProvider->getData() as $content)
		{
			$response[] = $content->getAPIAttributes($hiddenFields, array(
			                  'author' => array(
			                      'password',
			                      'activation_key',
			                      'email',
			                      'about',
			                      'user_role',
			                      'status',
			                      'created',
			                      'updated'
			                  ),
			                  'category' => array(
			                      'parent_id'
			                  ),
			                  'metadata' => array(
			                      'content_id'
			                  )
			              ));
		}

		return $response;
	}

	/**
	 * [GET] [/content/like/<id>]
	 * @param int $id   The Content id
	 * @return [type]     [description]
	 */
	public function actionLike($id=NULL)
	{
		if ($id == NULL)
			throw new CHttpException(400, Yii::t('Api.content', 'Missing ID'));

		$model = new ContentMetadata;
		$content = $model->getPrototype('ContentMetadata', array('content_id' => $id, 'key' => 'likes'), array('value' => 0));

		if ($id === NULL || $content === NULL)
			throw new CHttpException(404, Yii::t('Api.content', 'No content entry with that ID was'));

		// Load the user likes, create one if it does not exist
		$user = $model->getPrototype('UserMetadata', array('user_id' => $this->user->id, 'key' => 'likes'), array('value' => '[]'));

		$likes = json_decode($user->value, true);

		$type = "inc";
		if (in_array($id, array_values($likes)))
		{
			$type = "dec";
			$content->value -= 1;
			
			if ($content->value <= 0)
				$content->value = 0;
				
			$element = array_search($id, $likes);
			unset($likes[$element]);
		}
		else
		{
			$content->value += 1;
			array_push($likes, $id);
		}

		$user->value = CJSON::encode($likes);

		if (!$user->save())
			throw new CHttpException(500, Yii::t('Api.content', 'Unable to save user like'));

		if (!$content->save())
			throw new CHttpException(500, Yii::t('Api.content', 'Unable to save like'));

		return array(
			'type' => $type
		);
	}

	/**
	 * [GET] [/content/revisions/<id>]
	 * Shows all the revisions for a given entry
	 * @param int $id   The Content id
	 */
	public function actionRevisions($id=NULL)
	{
		if ($id == NULL)
			throw new CHttpException(400, Yii::t('Api.content', 'Missing ID'));

		$data = Content::model()->findRevisions($id);
		$response = array();
		foreach ($data as $model)
		{
			$response[] = $model->getAPIAttributes(array(),array(
				'author' => array(
					'password',
					'activation_key',
					'about',
					'user_role',
					'status',
					'created',
					'updated'
				),
				'category' => array(
					'parent_id'
				),
				'metadata' => array(
					'content_id'
				)
			));
		}

		return array(
			'count' => count($response),
			'data' => $response
		);
	}

	/**
	 * [POST] [/content/<id>] [/content]
	 * Creates or modifies an existing entry
	 * @param int $id   The Content id
	 */
	public function actionIndexPost($id=NULL)
	{
		if ($id === NULL)
			return $this->createNewPost();
		else
			return $this->updatePost($id);
	}

	/**
	 * [DELETE] [/content/<id>]
	 * Deletes an article
	 * @param int $id   The Content id
	 */
	public function actionIndexDelete($id=NULL)
	{
		if (!$this->user->role->hasPermission('delete'))
			throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to delete entries.'));

		return $this->loadModel($id)->deleteAllByAttributes(array('id' => $id));
	}

	/**
	 * [GET] [/content/tag/<id>]
	 * Retrieves tags for a given entry
	 */
	public function actionTag($id=NULL)
	{
		$model = $this->loadModel($id);
		return $model->getTags();
	}

	/**
	 * [POST} [/content/tag/<id>]
	 * Creates a new tag for a given entry
	 */
	public function actionTagPost($id=NULL)
	{
		$model = $this->loadModel($id);

		if (!$this->user->role->hasPermission('modify'))
			throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to modify tags.'));

		if ($model->addTag(Cii::get($_POST, 'tag')))
			return $model->getTags();

		return $this->returnError(400, NULL, $model->getErrors());
	}

	/**
	 * [DELETE] [/content/tag/<id>]
	 * Deletes a tag for a given entry
	 */
	public function actionTagDelete($id=NULL, $tag=NULL)
	{
		$model = $this->loadModel($id);

		if (!$this->user->role->hasPermission('modify'))
			throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to modify tags.'));

		if ($model->removeTag($tag))
			return $model->getTags();

		return $this->returnError(400, NULL, $model->getErrors());
	}

	/**
	 * [GET] [/content/autosave/<id>]
	 * Fetches the autosave data for a given model
	 * @param  integer $id  The content ID to autosave
	 * @return mixed
	 */
	public function actionAutosave($id=NULL)
	{
		$model = $this->loadModel($id);

		if (!$this->user->role->hasPermission('modify'))
			throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to create new entries.'));

		$autosaveModel = ContentMetadata::model()->findByAttributes(array('content_id' => $model->id, 'key' => 'autosave'));
		if ($autosaveModel === NULL)
			return false;

		return CJSON::decode($autosaveModel->value);
	}

	/**
	 * [POST] [/content/autosave/<id>]
	 * Autosave action.
	 * This method is used to prevent Content::$vid incriments unecessarily, and makes the history log more tolerable
	 * @param  integer $id  The content ID to autosave
	 * @return boolean
	 */
	public function actionAutosavePost($id=NULL)
	{
		$model = $this->loadModel($id);

		if (!$this->user->role->hasPermission('modify'))
			throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to create new entries.'));

		$model->populate($_POST);

		if ($this->user->role->isA('author') || $this->user->role->isA('collaborator'))
			$model->author_id = $this->user->id;

		$asModel = $model->getPrototype('ContentMetadata', array('content_id' => $model->id, 'key' => 'autosave'));

		$asModel->value = CJSON::encode($model->attributes);
		
		if ($asModel->save())
			return true;
		else
			return $this->returnError(400, NULL, $asModel->getErrors());
	}

	/**
	 * Creates a new entry
	 */
	private function createNewPost()
	{
		if (!$this->user->role->hasPermission('create'))
			throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to create new entries.'));

		$model = new Content;

		$model->populate($_POST);

		// Return a model instance to work with
		if ($model->savePrototype($this->user->id))
		{
			return $model->getAPIAttributes(array(
					'category_id',
					'parent_id',
					'author_id'
				),
				array(
					'author' => array(
						'password',
						'activation_key',
						'email',
						'about',
						'user_role',
						'status',
						'created',
						'updated'
					),
					'category' => array(
						'parent_id'
					),
					'metadata' => array(
						'content_id'
					)
				));
		}

		return $this->returnError(400, NULL, $model->getErrors());
	}

	/**
	 * Updates an existing entry
	 * @param int $id   The Content id
	 */
	private function updatePost($id)
	{
		$model = $this->loadModel($id);

		if (!$this->user->role->hasPermission('modify'))
			throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to create new entries.'));

		if ($this->user->role->isA('author') || $this->user->role->isA('collaborator'))
			$model->author_id = $this->user->id;

		$vid 	= $model->vid;
		$model 	= new Content;
		$model->id = $id;
		$model->populate($_POST);
		$model->vid = Yii::app()->db->createCommand('SELECT MAX(vid)+1 FROM content WHERE id = :id')->bindParam(':id', $id)->queryScalar();

		if ($model->save())
			return $model->getAPIAttributes(array(
					'category_id',
					'parent_id',
					'author_id'
				),
				array(
					'author' => array(
						'password',
						'activation_key',
						'email',
						'about',
						'user_role',
						'status',
						'created',
						'updated'
					),
					'category' => array(
						'parent_id'
					),
					'metadata' => array(
						'content_id'
					)
				));

		return $this->returnError(400, NULL, $model->getErrors());
	}

	/**
	 * Retrieves the model
	 * @param  int    $id The content ID
	 * @return Content
	 */
	private function loadModel($id=NULL)
	{
		if ($id === NULL)
			throw new CHttpException(400, Yii::t('Api.content', 'Missing id'));

		$model = Content::model()->findByPk($id);
		if ($model === NULL)
			throw new CHttpException(404, Yii::t('Api.content', 'An entry with the id of {{id}} was not found', array('{{id}}' => $id)));

		return $model;
	}

	/**
	 * Uploads a video to the site.
	 * @param integer $id           The content_id
	 * @param integer $promote      Whether or not this image should be a promoted image or not
	 * @return array
	 */
	public function actionUploadImagePost($id=NULL, $promote = 0)
	{
		$result = new CiiFileUpload($id, $promote);
		return $result->uploadFile();
	}
}
