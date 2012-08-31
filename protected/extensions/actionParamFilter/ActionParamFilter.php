<?php



class ActionParamFilter extends CFilter
{
  
  /////////////////////////////////////////////////////////////////////////////
  
  private $_params = array();
  
  /////////////////////////////////////////////////////////////////////////////
  
  public function getParams( $actionId )
  {
    return array_key_exists( $actionId, $this->_params )
      ? $this->_params[$actionId]
      : array();
  }
  
  /////////////////////////////////////////////////////////////////////////////
  
  public function setParams( array $params )
  {
    foreach ($params as $actionId => $aParams)
    {
      foreach ($aParams as $paramName => $param) {
        $this->setParam( $actionId, $paramName, $param );
      }
    }
  }
  
  /////////////////////////////////////////////////////////////////////////////
  
  public function setParam( $actionId, $paramName, $param )
  {
    if (is_array($param))
    {
      if (!isset($param['class'])) {
        Yii::setPathOfAlias( '_actionParamFilter', dirname(__FILE__) );
        Yii::import( '_actionParamFilter.ActionParam', true );
        $param['class'] = 'ActionParam';
      }
      
      $param['name'] = $paramName;
      $param = Yii::createComponent( $param );
    }
    
    if ($param instanceof ActionParam) {
      $this->_params[$actionId][$param->name] = $param;
    } else {
      throw new CException("Failed to set param '{$paramName}' for action '$actionId'.");
    }
  }
  
  /////////////////////////////////////////////////////////////////////////////
  
  protected function preFilter( $filterChain ) 
  {
    if (!($filterChain instanceof CFilterChain)) {
      return false;
    }
    
    /* @var $filterChain CFilterChain */    
    $actionParams = $filterChain->controller->getActionParams();
    $actionId     = $filterChain->action->id;
    
    /* @var $param ActionParam */
    foreach ($this->getParams($actionId) as $param)
    {
      if (!$param->validate($actionParams)) {
        return false;
      }
    }
    
    return true; 
  }  
  
  /////////////////////////////////////////////////////////////////////////////
  
}
