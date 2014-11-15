<?php

class CardController extends ApiController
{
	public function accessRules()
    {
        return array(
            array('allow',
                'actions' => array('index', 'indexPost', 'indexDelete', 'rearrangePost', 'details', 'detailsPost'),
                'expression' => '$user!=NULL'
            ),
            array('deny')
        );
    }

    /**
     * Retrieves all the dashboard cards for the current user
     */
    public function actionIndex()
    {
    	// Fetch all the dashboard cards for the current user
    	$cards = $this->loadDashboardCards();
    	$cardData = array();

        // Find all the metadata for those cards and load them
    	foreach ($cards->value as $id=>$url)
    	{
            $metadata = $this->loadCardDetailsModel($id);
            $cardData[$id] = $metadata->value;
    	}

    	return array(
    		'cards'    => $cards->value,
    		'cardData' => $cardData
    	);
    }

    /**
     * Installs a new card to the dashboard cards, and adds the appropriate settings to the database
     * @return boolean
     */
    public function actionIndexPost()
    {
    	$cards = $this->loadDashboardCards();
    	$id = Cii::get($_POST, 'id', false);
    	$url = Cii::get($_POST, 'url', false);
        $data = Cii::get($_POST, 'details', false);

    	if ($id === false || $url === false || $data == false)
    		throw new CHttpException(400, Yii::t('Api.card', 'Invalid card data'));

        $values = $cards->value;
        $values[$id] = $url;
    	$cards->value = CJSON::encode($values);

        $newCard = $this->loadCardDetailsModel($id);
        $newCard->value = CJSON::encode($data);

        // If we saved the card metadata
        if ($newCard->save())
        {
            // Try to save the card to the dashboard
            if ($cards->save())
                return true;

            // If that fails, delete the new card data, and throw the 400 error below
            $newCard->delete();
        }

        throw new CHttpException(500, Yii::t('Api.card', 'Card could not be saved.'));
    }

    /**
     * Deletes a card from the dashboard
     * @param  string $id The card ID
     * @return boolean
     */
    public function actionIndexDelete($id=NULL)
    {
    	$cards = $this->loadDashboardCards();

    	if (array_key_exists($id, $cards->value))
    		unset($cards->value[$id]);

    	return $card->save();
    }

    /**
     * Retrieves the properties of a given card ID 
     * @param  string $id The card ID
     * @return UserMetadata
     */
    public function actionDetails($id=NULL)
    {
    	$model = $this->loadCardDetailsModel($id);
    	return $model->value;
    }

    /**
     * Updates the card details (properties)
     * @param  string $id The card ID
     * @return boolean
     */
    public function actionDetailsPost($id=NULL)
    {
    	$model = $this->loadCardDetailsModel($id);
    	$model->value = CJSON::encode(array(
    		'size' => Cii::get($_POST, 'size', 'square'),
    		'properties' => Cii::get($_POST, 'properties', array())
    	));

    	return $model->save();
    }

    /**
     * Rearranges cards according to the data provided by $_POST['cards']
     * @return boolean
     */
    public function actionRearrangePost()
    {
        $cards = $this->loadDashboardCards();
        $submittedCards = Cii::get($_POST, 'cards', array());

        // Prevent an empty submission from wiping all the dashboard cards and doing an out of band uninstall
        if (empty($submittedCards))
            throw new CHttpException(400, Yii::t('Api.card', 'No cards were provided for re-arrangement'));

        // Prevent installations from occuring on this endpoint
        foreach ($submittedCards as $id=>$url)
        {
            // If the card doesn't exist in the existing card list, remove it from the submitted data
            if (!isset($cards->value[$id]))
                unset($submittedCards[$id]);
        }

        // Save the new card data
        $cards->value = CJSON::encode($submittedCards);
        return $cards->save();
    }

    /**
     * Loads all the dashboard cards, in order for this particular user
     * @return UserMetadata
     */
    private function loadDashboardCards()
    {
    	// Fetch all the dashboard cards for the current user
    	$model = UserMetadata::model()->getPrototype('UserMetadata', array(
    		'user_id' => $this->user->id,
    		'key' => 'dashboard_cards'
    	), array('value' => array()));

    	$model->value = CJSON::decode($model->value);

    	return $model;
    }

    /**
     * Returns a UserMetadata Object for a given card, containing the properties and settings for that card.
     * @param  string $id The card ID
     * @return UserMetadata
     */
    private function loadCardDetailsModel($id=NULL)
    {
    	if ($id == NULL)
    		throw new CHttpException(400, Yii::t('Api.card', 'Missing card ID'));

    	$model = UserMetadata::model()->getPrototype('UserMetadata', array(
    		'user_id' => $this->user->id,
    		'key' => $id.'_card_settings'
    	), array('value' => '{}'));

    	$model->value = CJSON::decode($model->value);

    	return $model;
    }
}