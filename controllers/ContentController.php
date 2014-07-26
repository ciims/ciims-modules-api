<?php

class ContentController extends ApiController
{
    public function accessRules()
    {
        return array(
            array('allow',
                'actions' => array('index', 'tag')
            ),
            array('allow',
                'actions' => array('indexPost', 'indexDelete', 'tagPost', 'tagDelete'),
                'expression' => '$user!=NULL'
            ),
            array('allow',
                'actions' => array('publish', 'unpublish'),
                'expression' => '$user->role->hasPermission("publish")'
            ),
            array('deny')
        );
    }

    /**
     * [GET] [/content]
     * Retrieves an article by the requested id, or retrieves all articles based upon permissions
     *
     * NOTE: This endpoint behaves differently for authenticated and unauthenticated users. Authenticated users are show posts they can mutate, whereas
     *
     * @param int $id   The Content id
     */
    public function actionIndex($id=NULL)
    {
        $model = new Content('search');
        $model->unsetAttributes();  // clear any default values
	    $model->pageSize = 20;
        
        if(isset($_GET['Content']))
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
        $role = Cii::get($this->user, 'role', NULL);
        $hiddenFields = array('category_id', 'parent_id', 'author_id');

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
     * [POST] [/content/<id>] [/content]
     * Creates or modifies an existing entry
     * @param int $id   The Content id
     */
    public function actionIndexPost($id=NULL)
    {
        if ($id===NULL)
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

        return $this->loadModel($id)->delete();
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

        if (!($this->user->id == $model->author->id || $this->user->role->hasPermission('modify')))
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
        if (!($this->user->id == $model->author->id || $this->user->role->hasPermission('modify')))
            throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to modify tags.'));

        if ($model->removeTag(Cii::get($_POST, 'tag')))
            return $model->getTags();

        return $this->returnError(400, NULL, $model->getErrors());
    }

    /**
     * Creates a new entry
     */
    private function createNewPost()
    {
        if (!$this->user->role->hasPermission('create'))
            throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to create new entries.'));

        $model = new Content;
        $model->savePrototype();
        $model->attributes = $_POST;
        $model->author_id = $this->user->id;

        if ($model->save(false))
            return $model->getApiAttributes(array('password', 'like_count'));

        return $this->returnError(400, NULL, $model->getErrors());
    }

    /**
     * Updates an existing entry
     * @param int $id   The Content id
     */
    private function updatePost($id)
    {
        $model = $this->loadModel($id);

        if ($model->author->id != $this->user->id ||!$this->user->role->hasPermission('modify'))
            throw new CHttpException(403, Yii::t('Api.content', 'You do not have permission to create new entries.'));

        if ($this->user->role->isA('author') || $this->user->role->isA('collaborator'))
            $model->author_id = $this->user->id;

        $vid = $model->vid;
        $model->attributes = $_POST;
        $model->vid = $vid++;

        if ($model->save())
            return $model->getApiAttributes(array('password', 'like_count'));

        return $this->returnError(400, NULL, $model->getErrors());
    }

    /**
     * Retrieves the model
     * @param  int    $id The content ID
     * @return Content
     */
    private function loadModel($id=NULL)
    {
        if ($id===NULL)
            throw new CHttpException(400, Yii::t('Api.content', 'Missing id'));

        $model = Content::model()->findByPk($id);
        if ($model===NULL)
            throw new CHttpException(404, Yii::t('Api.content', 'An entry with the id of {{id}} was not found', array('{{id}}' => $id)));

        return $model;
    }
}
