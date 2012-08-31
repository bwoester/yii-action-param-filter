<?php

class ActionParam extends CComponent
{
  const SRC_SERVER    = 'SERVER';
  const SRC_GET       = 'GET';
  const SRC_POST      = 'POST';
  const SRC_PUT       = 'PUT';
  const SRC_DELETE    = 'DELETE';
  const SRC_FILES     = 'FILES';
  const SRC_REQUEST   = 'REQUEST';
  const SRC_SESSION   = 'SESSION';
  const SRC_ENV       = 'ENV';
  const SRC_COOKIE    = 'REQUEST';

  /**
   * Name of the action paramter.
   * @var string
   */
  public $name = '';

  /**
   * Array of possible sources from which the action parameter might be read.
   * @var array
   */
  private $_aAllowedSources  = array();

  /**
   * The source that is used to read the action parameter. The first possible
   * source that contains the name of the action parameter will be used.
   * @var string
   */
  private $_source  = '';

  /**
   * Looks for the parameter in all possible sources. Returns the name of the
   * first source that contains the parameter.
   * @return string
   */
  public function getSource()
  {
    if ($this->_source === '')
    {
      foreach ($this->_aAllowedSources as $allowedSource)
      {
        if ($this->inSource($allowedSource)) {
          $this->_source = $allowedSource;
        }
      }
    }

    return $this->_source;
  }

  /**
   * Set allowed sources.
   *
   * Provide a string of one or more source from which the action parameter can
   * be read. Separate multiple source names with comma ",". The source names
   * will be converted to upper case for internal usage.
   *
   * @param string $value
   */
  public function setSource( $value )
  {
    $aAllowedSources = explode( ',', $value );
    foreach ($aAllowedSources as $allowedSource) {
      $this->_aAllowedSources[] = trim( strtoupper($allowedSource) );
    }
  }

  /**
   * Validate the action parameter.
   *
   * The parameter will only be validated if it is included in $actionParams.
   * If it is not included in $actionParams, it means the user didn't submit
   * the parameter with his request, so there is nothing to validate.
   *
   * If the parameter is included in $actionParams, we make sure that the
   * parameter is also included in the source array. The source array is the
   * first array in the list of allowed sources that contains the parameter.
   *
   * For example, let's assume an action parameter with the config
   *
   * array(
   *   'name'   => 'foo',
   *   'source' => 'get'
   * ),
   *
   * If the current controller merges $_GET and $_POST arrays to provide action
   * parameters, and 'foo' is in $actionParams, it is clear that it originates
   * from $_GET or $_POST. But since the configuration of the param only allows
   * it to originate from get, we validate that $_GET['foo'] exists.
   *
   * If this test passes, we validate that $actionParams['foo'] and
   * $_GET['foo'] are equal. This is important, because we need to make sure
   * that nobody injected a 'foo' variable in $_POST data (we don't know which
   * order the controller uses to merge the arrays).
   *
   * @param array $actionParams. The array that will be used by CAction to
   * populate parameters for its invokation.
   * @return boolean. Validation result.
   */
  public function validate( array $actionParams )
  {
    // we don't need to validate what isn't provided
    if (!array_key_exists($this->name,$actionParams)) {
      return true;
    }

    // make sure the param is provided in one of the allowed sources
    $valid  = $this->provided();

    // if the validation passed, validate equality
    if ($valid)
    {
      $valid = $actionParams[$this->name] === $this->getValue();
    }

    return $valid;
  }

  /**
   * Check if the action parameter is provided in the current request.
   * Only take allowed sources into account.
   * @return bool
   */
  public function provided()
  {
    return $this->getSource() !== '';
  }

  /**
   * Get the value of a provided action parameter from the first allowed
   * source.
   *
   * Only call this method if you are sure the parameter is provided!
   *
   * @return mixed
   */
  public function getValue()
  {
    $aSource  = $this->getSourceArray( $this->getSource() );
    return $aSource[ $this->name ];
  }

  private function inSource( $sourceName )
  {
    $retVal = false;

    switch ($sourceName)
    {
    case self::SRC_COOKIE:
        $retVal = array_key_exists( $this->name, $_COOKIE );
        break;
    case self::SRC_ENV:
        $retVal = array_key_exists( $this->name, $_ENV );
        break;
    case self::SRC_FILES:
        $retVal = array_key_exists( $this->name, $_FILES );
        break;
    case self::SRC_GET:
        $retVal = array_key_exists( $this->name, $_GET );
        break;
    case self::SRC_POST:
        $retVal = array_key_exists( $this->name, $_POST );
        break;
    case self::SRC_REQUEST:
        $retVal = array_key_exists( $this->name, $_REQUEST );
        break;
    case self::SRC_SERVER:
        $retVal = array_key_exists( $this->name, $_SERVER );
        break;
    case self::SRC_SESSION:
        $retVal = array_key_exists( $this->name, $_SESSION );
        break;
    case self::SRC_PUT:
        $retVal = Yii::app()->request->getPut( $this->name ) !== null;
        break;
    case self::SRC_DELETE:
        $retVal = Yii::app()->request->getDelete( $this->name ) !== null;
        break;
    default:
        throw new CException( "Unknown source for action params '{$this->source}'." );
    }

    return $retVal;
  }

  private function getSourceArray( $sourceName )
  {
    $retVal = array();

    switch ($sourceName)
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
