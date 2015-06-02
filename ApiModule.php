<?php
/**
 * CiiMS API Module
 * 
 * This is a stand alone module for CiiMS that adds a JSON REST API functionality to CiiMS
 *
 * @package     CiiMS (https://github.com/charlesportwoodii/CiiMS)
 * @link        https://github.com/charlesportwoodii/ciims-modules-api
 * @author      Charles R. Portwood I <charlesportwoodii@ethreal.net>
 * @copyright   2011-2015 Charles R. Portwood II
 * @license     MIT License
 */
 
/**
 * API Module
 * Required definition for CWebModule integratoin with Yii2
 */
class ApiModule extends CWebModule
{
    /**
     * Yii Init method
     * Implements basic configuration for module
     */
    public function init()
    {
        // Autoload the models and components directory 
        $this->setImport(array(
            'api.models.*',
            'api.components.*',
        ));
        
        // Disable layout rendering
        $this->layout = false;
        
        // Disable logging for the API
        foreach(Yii::app()->log->routes as $k=>$v)
        {
            if (get_class($v) == 'CWebLogRoute' || get_class($v) == "CProfileLogRoute")
                Yii::app()->log->routes[$k]->enabled = false; 
        }
        
        // Set default components and routes
        Yii::app()->setComponents(array(
            'errorHandler' => array(
                'errorAction'  => 'api/default/error',
            ),
            'messages' => array(
                'class' => 'cii.components.CiiPHPMessageSource',
                'basePath' => Yii::getPathOfAlias('application.modules.api')
            )
        ));
    }
}
