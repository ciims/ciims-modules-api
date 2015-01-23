<?php

class CategoryController extends ApiController
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
                'actions' => array('index')
            ),
            array('allow',
                'actions' => array('indexPost', 'indexDelete'),
                'expression' => '$user!=NULL&&$user->role->hasPermission("manage")'
            ),
            array('deny') 
        );  
    }

	/**
     * [GET] [/category/<id>]
     * @return array    List of categories
     */
    public function actionIndex($id=NULL)
    {
        if ($id !== NULL)
        {
            $category = Categories::model()->findByPk($id);
            if ($category == NULL)
                throw new CHttpException(404, Yii::t('Api.category', 'A category with the id of {{id}} was not found.', array('{{id}}' => $id)));

            return $category->getAPIAttributes(array('parent_id'), array('parent', 'metadata'));
        }
        
        $model = new Categories('search');
        $model->unsetAttributes();  // clear any default values
        if(isset($_GET['Categories']))
            $model->attributes = $_GET['Categories'];

        // Modify the pagination variable to use page instead of Categories page
        $dataProvider = $model->search();
        $dataProvider->pagination = array(
            'pageVar' => 'page'
        );

        // Throw a 404 if we exceed the number of available results
        if ($dataProvider->totalItemCount == 0 || ($dataProvider->totalItemCount / ($dataProvider->itemCount * Cii::get($_GET, 'page', 1))) < 1)
            throw new CHttpException(404, Yii::t('Api.category', 'No results found'));

        $response = array();

        foreach ($dataProvider->getData() as $category)
            $response[] = $category->getAPIAttributes(array('parent_id'), array('parent', 'metadata'));

        return $response;
    }

    /**
     * [POST] [/category/<id>]
     * @return array    Category
     */
    public function actionIndexPost($id=NULL)
    {
        if ($id === NULL)
        {
            $category = new Categories;
            $category->parent_id = 1;
        }
        else
        {
            $category = Categories::model()->findByPk($id); 
            if ($category == NULL)
                throw new CHttpException(404, Yii::t('Api.category', 'A category with the id of {{id}} was not found.', array('{{id}}' => $id)));
        }
        
        $category->attributes = $_POST;
        
        if ($category->save())
            return $category->getAPIAttributes(array('parent_id'), array('parent', 'metadata'));

        return $this->returnError(400, NULL, $category->getErrors());         
    }

    /**
     * [DELETE] [/category/<id>]
     * @return boolean
     */
    public function actionIndexDelete($id=NULL)
    {
        if ($id == NULL)
            throw new CHttpException(400, Yii::t('Api.category', 'A category id must be specified to delete.'));
        
        $category = Categories::model()->findByPk($id); 
        if ($category == NULL)
            throw new CHttpException(404, Yii::t('Api.category', 'A category with the id of {{id}} was not found.', array('{{id}}' => $id)));

        if ($category->id == 1)
            throw new CHttpException(400, Yii::t('Api.category', 'The root category cannot be deleted.'));

        return $category->delete();
    }
}
