<?php

Yii::import( '_actionParamFilter.IActionParamProvider', true );

/**
 * IActionParamProvider that can be attached to CComponents as a CBehavior.
 * It doesn't provide action parameters itself but acts as an adapter, so it
 * will only forward the call to another IActionParamProvider.
 *
 * @author Benjamin
 */
class ActionParamProvider extends CBehavior implements IActionParamProvider
{
  private $_provider = null;

  public function __construct( IActionParamProvider $provider ) {
    $this->_provider = $provider;
  }

  public function provideActionParams() {
    return $this->_provider->provideActionParams();
  }

}
