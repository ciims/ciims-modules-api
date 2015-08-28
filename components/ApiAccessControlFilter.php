<?php

/**
 * ApiAccessControlFilter
 * Provides overrides necessary for ApiController to function properly with auth-token headers and CiiMS User model
 */
class ApiAccessControlFilter extends CAccessControlFilter
{
    /**
     * The user model
     * @var User $user
     */
    public $user = null;
    
    /**
     * Access rules
     * @var array $_rules
     */
    private $_rules = array();

    /**
     * Performs the pre-action filtering. 
     *
     * Override of CAccessControlFilter method
     * @param CFilterChain $filterChain the filter chain that the filter is on.
     * @return boolean whether the filtering process should continue and the action
     * should be executed.
     */
    protected function preFilter($filterChain)
    {
        $app        = Yii::app();
        $request    = $app->getRequest();
        $user       = $this->user;
        $verb       = $request->getRequestType();
        $ip         = $request->getUserHostAddress();
        
        foreach ($this->getRules() as $rule)
        {
            if (($allow=$rule->isUserAllowed($user,$filterChain->controller,$filterChain->action,$ip,$verb)) > 0) // allowed
                break;
            elseif ($allow < 0) // denied
            {
                if (isset($rule->deniedCallback))
                    call_user_func($rule->deniedCallback, $rule);
                else
                    $this->accessDenied($user,$this->resolveErrorMessage($rule));
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Retrieves the access control rules
     * @return array
     */
    public function getRules()
    {
        return $this->_rules;
    }

    /**
     * Sets the access control rules
     * @param array $rules list of access rules.
     */
    public function setRules($rules)
    {
        foreach($rules as $rule)
        {
            if (is_array($rule) && isset($rule[0]))
            {
                $r=new CAccessRule;
                $r->allow=$rule[0]==='allow';
                foreach (array_slice($rule,1) as $name=>$value)
                {
                    if ($name==='expression' || $name==='roles' || $name==='message' || $name==='deniedCallback')
                        $r->$name=$value;
                    else
                        $r->$name=array_map('strtolower',$value);
                }
                $this->_rules[]=$r;
            }
        }
    }

    /**
     * Denies the access of the user.
     * This method is invoked when access check fails.
     * @param IWebUser $user the current user
     * @param string $message the error message to be displayed
     */
    protected function accessDenied($user,$message=NULL)
    {
        http_response_code(403);
        Yii::app()->controller->renderOutput(array(), 403, $message);
    }
}
