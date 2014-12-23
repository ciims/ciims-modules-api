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

            return $user->getAPIAttributes(array('password'));
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

		// Throw a 404 if we exceed the number of available results
		if ($dataProvider->totalItemCount == 0 || ($dataProvider->totalItemCount / ($dataProvider->itemCount * Cii::get($_GET, 'page', 1))) < 1)
			throw new CHttpException(404, Yii::t('Api.user', 'No results found'));

		$response = array();

		foreach ($dataProvider->getData() as $user)
			$response[] = $user->getAPIAttributes(array('password'), array('role', 'metadata'));

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
	 * [POST] [/user/register]
	 * API endpoint for registering a user
	 * @return array
	 */
	public function actionRegisterPost()
	{
		$this->createUser(false);
	}

	/**
	 * [POST] [/user/invite]
	 * Invites a user to join the blog as a collaborator
	 * @return mixed
	 */
	public function actionInvitePost()
	{
		$model = new InvitationForm;

		if (!empty($_POST))
		{
			$model->attributes = $_POST;

            // Save the user's information
			if ($model->invite())
		    	return Users::model()->findByAttributes(array('email' => $_POST['email']))->getAPIAttributes(array('password'), array('role', 'metadata'));
		    else
		    	return $this->returnError(400, NULL, $model->getErrors());
		}

		throw new CHttpException(400, Yii::t('Api.user', 'An unexpected error occured fulfilling your request.'));
	}

	/**
	 * Updates the attributes for a given user with $id. Administrators can always access this method. Users can also edit their own information
	 * @param  int    $id The user's ID
	 * @return array
	 */
	private function updateUser($id)
	{
		$override = false;
		$model = new ProfileForm;
        
        // Allow admins to override the self password check
        if($this->user->role->hasPermission("manage"))
        	$override = true;

        $model->load($id, $override);

		if (!empty($_POST))
		{
			// Prevent users from promoting or demoting themselves
			// TODO: Figure out how to move this to the ProfileForm model
			// 	     Since $this->user != Yii::app()->user
			if ($this->user->id == $_POST['id'] && Cii::get($_POST, 'user_role', $this->user->role->id) != $this->user->role->id)
				$model->addError('user_role', Yii::t('ciims.models.ProfileForm', 'You cannot promote or demote yourself.'));

            $model->attributes = $_POST;

			if ($model->save())
		    	return Users::model()->findByAttributes(array('email' => $model->email))->getAPIAttributes(array('password'), array('role', 'metadata'));
		    else
		    	return $this->returnError(400, NULL, $model->getErrors());
		}

		throw new CHttpException(400, Yii::t('Api.user', 'An unexpected error occured fulfilling your request.'));
	}

	/** 
	 * Utilizes the registration form to create a new user
	 * @return array
	 */
	private function createUser($sendEmail = true)
	{
		$model = new RegisterForm;

		if (!empty($_POST))
		{
			$model->attributes = $_POST;

            // Save the user's information
			if ($model->save($sendEmail))
		    	return Users::model()->findByAttributes(array('email' => $_POST['email']))->getAPIAttributes(array('password'), array('role', 'metadata'));
		    else
		    	return $this->returnError(400, NULL, $model->getErrors());
		}

		throw new CHttpException(400, Yii::t('Api.user', 'An unexpected error occured fulfilling your request.'));
	}
}
