<?php

class ActionParam extends CComponent
{
  const SRC_SERVER    = 'SERVER';
  const SRC_GET       = 'GET';
  const SRC_POST      = 'POST';
  const SRC_FILES     = 'FILES';
  const SRC_REQUEST   = 'REQUEST';
  const SRC_SESSION   = 'SESSION';
  const SRC_ENV       = 'ENV';
  const SRC_COOKIE    = 'REQUEST';
  
  public $name = '';
  private $_source  = '';
  
  private $_valid = true;
  private $_actionParams = array();

  public function getSource() {
    return $this->_source;
  }
  
  public function setSource( $value ) {
    $this->_source = strtoupper($value);
  }
  
  private function isValid() {
    return $this->_valid;
  }
  
  private function setValid( $value ) {
    $this->_valid = $value;
  }
  
  private function getActionParams() {
    return $this->_actionParams;
  }
  
  private function setActionParams( array $value ) {
    $this->_actionParams = $value;
  }
  
  public function validate( array $actionParams )
  {
    // we don't need to validate what isn't provided
    if (!array_key_exists($this->name,$actionParams)) {
      return true;
    }
    
    $this->setActionParams( $actionParams );
    
    $this->validateParamExistsInSourceArray();
    $this->validateEquality();

    return $this->isValid();
  }

  /**
   * Ensure that the param exists in the source array.
   * @return void
   */
  private function validateParamExistsInSourceArray()
  {
    if (!$this->isValid()) {
      return;
    }
    
    $src = $this->getSourceArray();
    $this->setValid( array_key_exists($this->name,$src) );
  }

  /**
   * Ensure that the param returned by getActionParams is the same as the param
   * from the source array. This way, we can make sure that nobody 
   * @return void
   */
  private function validateEquality()
  {
    if (!$this->isValid()) {
      return;
    }
    
    $src          = $this->getSourceArray();
    $actionParams = $this->getActionParams();
    $name         = $this->name;
    
    $this->setValid( $src[$name] === $actionParams[$name] );
  }
  
  private function getSourceArray()
  {
    $retVal = array();
    
    switch ($this->getSource())
    {
    case self::SRC_COOKIE:
        $retVal = $_COOKIE;
        break;
    case self::SRC_ENV:
        $retVal = $_ENV;
        break;
    case self::SRC_FILES:
        $retVal = $_FILES;
        break;
    case self::SRC_GET:
        $retVal = $_GET;
        break;
    case self::SRC_POST:
        $retVal = $_POST;
        break;
    case self::SRC_REQUEST:
        $retVal = $_REQUEST;
        break;
    case self::SRC_SERVER:
        $retVal = $_SERVER;
        break;
    case self::SRC_SESSION:
        $retVal = $_SESSION;
        break;
    default:
        throw new CException( "Unknown source for action params '{$this->source}'." );
    }
    
    return $retVal;
  }
}
