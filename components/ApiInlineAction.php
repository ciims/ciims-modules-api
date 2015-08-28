<?php

class ApiInlineAction extends CInlineAction
{
    /**
     * This method was modified ~1.1.16, and has been changed to return a response rather than false
     * @inheritdoc
     */
    public function runWithParams($params)
    {
        $methodName='action'.$this->getId();
        $controller=$this->getController();
        $method=new ReflectionMethod($controller, $methodName);
        return $this->runWithParamsInternal($controller, $method, $params);
    }

    /**
     * This method has been overloaded so that it returns a response rather than a boolean value
     *
     * @inheritdoc
     */
    protected function runWithParamsInternal($object, $method, $params)
    {
        $ps=array();
        foreach($method->getParameters() as $i=>$param)
        {
            $name=$param->getName();
            if(isset($params[$name]))
            {
                if($param->isArray())
                    $ps[]=is_array($params[$name]) ? $params[$name] : array($params[$name]);
                elseif(!is_array($params[$name]))
                    $ps[]=$params[$name];
                else
                    return false;
            }
            elseif($param->isDefaultValueAvailable())
                $ps[]=$param->getDefaultValue();
            else
                return false;
        }
        
        return $method->invokeArgs($object,$ps);
    }

}
