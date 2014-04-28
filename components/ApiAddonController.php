<?php

class ApiAddonController extends ApiController
{
    /**
     * Sets the layout file to null
     * @var string $layout
     */
    public $layout = NULL;

    /**
     * Retrieves the current type based upon the class name
     * @return string The Type
     */
    private function getType()
    {
        return strtolower(CiiInflector::singularize(str_replace('Controller', '', get_class($this))));
    }

    /**
     * Registers a card with this instance
     * @param string $id    The UUID of the Card
     */
    public function actionRegister($id=NULL)
    {
        return $this->curlRequest('default/addAddon/id/' . $id, array(null));
    }

    /**
     * Unregisters a card with this instance
     * @param string $id    The UUID of the Card
     */
    public function actionUnregister($id=NULL)
    {
        return $this->curlRequest('default/removeAddon/id/' . $id, array(null));
    }

    /**
     * Lists all Addons of this type registered to this instance
     */
    public function actionRegistered()
    {
        return $this->curlRequest('default/' . CiiInflector::pluralize($this->getType()));
    }

    /**
     * Provides functionality to perform a JSON search
     * @return JSON
     */
    public function actionSearch()
    {
        // Set the data up
        $data = array(
            'type' => $this->getType(),
            'text' =>  Cii::get($_POST, 'text')
        );

        return $this->curlRequest('default/search', $data);
    }

    /**
     * Retrives the details for a particular card
     * @param string   $id    The Addon ID
     * @return JSON
     */
    public function actionDetails($id=NULL)
    {
        if ($id == NULL)
            throw new CHttpException(400, 'Missing ID');

        return $this->curlRequest('default/addon/id/' . $id);
    }

    /**
     * Displays a menu for installing a new addon
     * @param string   $id    The Addon ID
     *
    public function actionDetailsView($id=NULL)
    {
        if ($id == NULL)
            throw new CHttpException(400, 'Missing ID');

        $details = $this->actionDetails($id);

        echo $this->renderPartial('application.modules.dashboard.views.settings.addoninstall', array(
            'details' => $details['response'],
            'id' => $id,
            'md' => new CMarkdownParser,
            'type' => $this->getType(),
        ));

        Yii::app()->end();
    }
    */

    /**
     * CURL wrapper for controllers that extend CiiDashboardAddonController
     * Ensures that the X-Auth details are set and that the correct cert is loaded.
     *
     * @param  string        $endpoint  The API endpoint we want to hit
     * @param  array|boolean $data      The POST data we want to send with the request, if any
     * @return array
     */
    protected function curlRequest($endpoint, $data = false)
    {
        // Make a curl request to ciims.org to search for soime cards.
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-Auth-ID: ' . Cii::getConfig('instance_id'),
                'X-Auth-Token: ' . Cii::getConfig('token')
            ),
            CURLOPT_URL => 'https://www.ciims.org/customize/' . $endpoint,
            CURLOPT_CAINFO => Yii::getPathOfAlias('application.config.certs') . DIRECTORY_SEPARATOR . 'GeoTrustGlobalCA.cer'
        ));

        // Set the POST attributes if the data is set
        if ($data != false)
        {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, CJSON::encode($data));
        }

        $response = CJSON::decode(curl_exec($curl));
        curl_close($curl);

        return $response;
    }

    /**
     * Retrieves a file from our CDN provider and returns it
     * WARNING: This has the potential to return a _very_ large response object that can exceed PHP's MAX_MEMORY settings
     *
     * @param string $file  The Path to the package ZIP file
     * @param string $id
     * @return ZIP Package
     */
    protected function downloadPackage($id, $file, $path)
    {
        // We're downloading a file - prevent the client aborting the process from screwing up the download
        // and ensure that we have sufficient time to complete it
        ignore_user_abort(true);
        set_time_limit(0);

        $fp = fopen($path . DIRECTORY_SEPARATOR . $id . '.zip', 'w+');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $file,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_CAINFO => Yii::getPathOfAlias('application.config.certs') . DIRECTORY_SEPARATOR . 'BaltimoreCyberTrustRootCA.crt'
        ));

        curl_exec($curl);
        curl_close($curl);
        fclose($fp);

        return;
    }
}
