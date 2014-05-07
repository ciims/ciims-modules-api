<?php

class UserController extends ApiController
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
        		'actions' => array('tokenPost', 'registerPost')
        	),
        	array('allow',
        		'actions' => array('tokenDelete'),
        		'expression' => '$user!=NULL'
        	),
            array('allow',
                'actions' => array('index', 'indexPost'),
                'expression' => '$user!=NULL&&($user->role->hasPermission("manage")||(Yii::app()->request->getParam("id")==$user->id))'
            ),
            array('allow',
            	'actions' => array('invitePost'),
            	'expression' => '$user!=NULL&&$user->role->hasPermission("manage")'
            ),
            array('deny') 
        );  
    }

    /**
     * [POST] [/user/token]
     * Allows for the generation of new LL API Token
     * @return array
     */
    public function actionTokenPost()
    {
    	$model = new LoginForm;
    	$model->username = Cii::get($_POST, 'email');
    	$model->password = Cii::get($_POST, 'password');

    	if (Cii::get($_POST, 'name', NULL) == NULL)
    		throw new CHttpException(400, Yii::t('Api.user', 'Application name must be defined.'));
    	else
    		$model->app_name = Cii::get($_POST, 'name', 'api');

    	if ($model->validate())
    	{
    		if ($model->login())
    			return UserMetadata::model()->findByAttributes(array('user_id' => Users::model()->findByAttributes(array('email' => $_POST['email']))->id, 'key' => 'api_key' . $_POST['name']))->value;
    	}

    	throw new CHttpException(403, Yii::t('Api.user', 'Unable to authenticate.'));
    }

    /**
     * [DELETE] [/user/token]
     * Allows for the deletion of the active API token
     * @return array
     */
    public function actionTokenDelete()
    {
    	$model = UserMetadata::model()->findByAttributes(array('user_id' => $this->user->id, 'value' => $this->xauthtoken));

    	if ($model === NULL)
    		throw new CHttpException(500, Yii::t('Api.user', 'An unexpected error occured while deleting the token. Please re-generate a new token for subsequent requests.'));
    	return $model->delete();
    }

	/**
	 * [GET] [/user/<id>]
	 * @return array    List of users
	 */
	public function actionIndex($id=NULL)
	{
        if ($id !== NULL)
        {
            $user = Users::model()->findByPk($id);
            if ($user == NULL)
                throw new CHttpException(404, Yii::t('Api.user', 'A user with the id of {{id}} was not found.', array('{{id}}' => $id)));

            return $user->getAPIAttributes(array('password', 'activation_key'));
		}

		// Prevent non management users from doing a blanket queryall
		if (!$this->user->role->hasPermission("manage"))
			throw new CHttpException(401, Yii::t('Api.user', 'Do you not have sufficient permissions to view this data'));
        
        $model = new Users('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Users']))
			$model->attributes = $_GET['Users'];

		// Modify the pagination variable to use page instead of User_page
		$dataProvider = $model->search();
		$dataProvider->pagination = array(
			'pageVar' => 'page'
		);

		$response = array();

		foreach ($dataProvider->getData() as $user)
			$response[] = $user->getAPIAttributes(array('password', 'activation_key'), array('role'));

		return $response;
	}

	/**
	 * [POST] [/user/<id>]
	 * @return array    Updated user details
	 */
	public function actionIndexPost($id=NULL)
	{
		if ($id === NULL)
		{
			if ($this->user->role->hasPermission("manage"))
				return $this->createUser();
			else
				throw new CHttpException(403, Yii::t('Api.user', 'You do not have sufficient privileges to create a new user.'));
		}
		else
			return $this->updateUser($id);
	}

	/**
	 * API endpoint for registering a user
	 * @return array
	 */
	public function actionRegisterPost()
	{
		$model = new RegisterForm;

		if (!empty($_POST))
		{
			$model->attributes = $_POST;

            // Save the user's information
			if ($model->save())
		    	return $model->getAPIAttributes(array('password', 'activation_key'));
		    else
		    	return $this->returnError(400, NULL, $model->getErrors());
		}

		throw new CHttpException(400, Yii::t('Api.user', 'An unexpected error occured fulfilling your request.'));
	}

	public function actionInvitePost()
	{
		// TODO: implement Invitation functionality
	}

	/**
	 * Updates the attributes for a given user with $id. Administrators can always access this method. Users can also edit their own information
	 * @param  int    $id The user's ID
	 * @return array
	 */
	private function updateUser($id)
	{
		$model = new ProfileForm;
        $model->load($id);

		if (!empty($_POST))
		{
            $model->attributes = $_POST;

			if ($model->save(false))
		    	return $model->getAPIAttributes(array('password', 'activation_key'));
		    else
		    	return $this->returnError(400, NULL, $model->getErrors());
		}

		throw new CHttpException(400, Yii::t('Api.user', 'An unexpected error occured fulfilling your request.'));
	}

	/** 
	 * Utilizes the registration form to create a new user
	 * @return array
	 */
	private function createUser()
	{
		$model = new RegisterForm;

		if (!empty($_POST))
		{
			$model->attributes = $_POST;

            // Save the user's information
			if ($model->save(false))
		    	return $model->getAPIAttributes(array('password', 'activation_key'));
		    else
		    	return $this->returnError(400, NULL, $model->getErrors());
		}

		throw new CHttpException(400, Yii::t('Api.user', 'An unexpected error occured fulfilling your request.'));
	}
}
