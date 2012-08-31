<?php

Yii::setPathOfAlias( '_actionParamFilter', dirname(__FILE__) );
Yii::import( '_actionParamFilter.IActionParamProvider', true );

/**
 * This filter allows fine grained control over action parameters.
 *
 * For each action and parameter, you can define where the parameter is allowed
 * to come from. This way, your actions can rely solely on the parameters that
 * are passed to them and don't have to matter where they come from.
 *
 * A configuration example:
 *
 * @code
 *
 * class ModelController extends CController
 * {
 *
 *   public function getActionParams()
 *   {
 *     return array_merge($_GET, $_POST);
 *   }
 *
 *   // This filter configuration requires the delete to be
 *   // 1) a POST
 *   // 2) to http://doma.in/index.php?r=model/delete&id=123 [ &ajax=1 ]
 *   // 3) with a body "returlUrl=http%3A%2F%2Fdoma.in%2Findex.php%3Fr%3Dmodel"
 *   public function filters()
 *   {
 *     return array(
 *       'postOnly + delete',
 *       array(
 *         'class'  => 'ext.filters.actionParamFilter.ActionParamFilter',
 *         'params' => array(
 *           'delete' => array(
 *             'id'         => array( 'source' => 'get' ),
 *             'returnUrl'  => array( 'source' => 'post' ),
 *             'ajax'       => array( 'source' => 'get' ),
 *           ),
 *         ),
 *       ),
 *     );
 *   }
 *
 *   // But the same action could also be reused by a REST client, that tries
 *   // to issue a DELETE request. This filter configuration could now look
 *   // like the following to require the delete action to be
 *   // 1) a DELETE
 *   // 2) to http://doma.in/index.php?r=model/delete&id=123
 *   //    (=== http://doma.in/index.php/model/123, if formatted by CUrlManager)
 *   // 3) since I've never written a rest client, I have no idea how they
 *   //    handle redirects, so I ignore this param...
 *   public function restFilters()
 *   {
 *     return array(
 *       'deleteOnly + delete',
 *       array(
 *         'class'  => 'ext.filters.actionParamFilter.ActionParamFilter',
 *         'params' => array(
 *           'delete' => array(
 *             'id' => array( 'source' => 'delete' ),
 *           ),
 *         ),
 *       ),
 *     );
 *   }
 *
 *   public function filterDeleteOnly($filterChain)
 *   {
 *     if(Yii::app()->getRequest()->getIsDeleteRequest())
 *       $filterChain->run();
 *     else
 *       throw new CHttpException(400,Yii::t('yii','Your request is invalid.'));
 *   }
 *
 *   // Now, with the ActionParamFilter, we don't need to know where the
 *   // parameters come from. This is ideal for CAction instances that might
 *   // be reused in several controlers.
 *   // We still have full control over where the data comes from. We only need
 *   // to configure the filter.
 *   public function actionDelete( $id, $returnUrl='', $ajax=null )
 *   {
 *     $this->loadModel($id)->delete();
 *
 *     // if AJAX request (triggered by deletion via admin grid view),
 *     // we should not redirect the browser
 *     if (!isset($ajax)) {
 *       $this->redirect( $returnUrl ==== '' ? array('admin') : $returnUrl );
 *     }
 *   }
 * }
 *
 * @endcode
 */
class ActionParamFilter extends CFilter implements IActionParamProvider
{

  private $_actionParams = array();
  private $_controller = null;
  private $_action = null;

  public $provideActionParams = false;
  public $actionParamProviderId = 'actionParamProvider';

  /////////////////////////////////////////////////////////////////////////////

  /**
   * Returns the configured ActionParams for the given $actionId.
   *
   * @param string $actionId
   * @return array of ActionParam instances
   */
  public function getActionParams( $actionId )
  {
    return array_key_exists( $actionId, $this->_actionParams )
      ? $this->_actionParams[$actionId]
      : array();
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * Most important configuration option for the filter. Sets all the
   * ActionParam configurations for all actions.
   *
   * The parameter $params must be an array of arrays of ActionParam
   * configurations. The array keys identify the actions to which the
   * configured ActionParams belong.
   *
   * @code
   *   public function filters()
   *   {
   *     return array(
   *       array(
   *         'class' => 'ext.filters.actionParamFilter.ActionParamFilter'
   *         'params' => array(
   *           // params for the create action
   *           'create' => array(
   *             'Article'  => array( 'source' => 'post' ),
   *           ),
   *           // params for the create action
   *           'update' => array(
   *             'id'       => array( 'source' => 'get' ),
   *             'Article'  => array( 'source' => 'post' ),
   *           ),
   *           // params for the delete action
   *           'delete' => array(
   *             'id'         => array( 'source' => 'get' ),
   *             'returnUrl'  => array( 'source' => 'post' ),
   *             'ajax'       => array( 'source' => 'get' ),
   *           ),
   *         ),
   *       ),
   *     );
   *   }
   * @endcode
   *
   * @param array $params
   */
  public function setActionParams( array $params )
  {
    foreach ($params as $actionId => $aParams)
    {
      foreach ($aParams as $paramName => $param) {
        $this->setActionParam( $actionId, $paramName, $param );
      }
    }
  }

  /////////////////////////////////////////////////////////////////////////////

  public function provideActionParams()
  {
    $aActionParams = array();

    /* @var $param ActionParam */
    foreach ($this->getActionParams($this->getAction()->getId()) as $param)
    {
      if ($param->provided()) {
        $aActionParams[ $param->name ] = $param->getValue();
      }
    }

    return $aActionParams;
  }

  /////////////////////////////////////////////////////////////////////////////

  protected function preFilter( $filterChain )
  {
    if (!($filterChain instanceof CFilterChain)) {
      return false;
    }

    /* @var $filterChain CFilterChain */
    $this->setController( $filterChain->controller );
    $this->setAction( $filterChain->action );

    if ($this->provideActionParams)
    {
      $actionProvider = Yii::createComponent( '_actionParamFilter.ActionParamProvider', $this );
      $this->getController()->attachBehavior( $this->actionParamProviderId, $actionProvider );
    }

    /* @var $param ActionParam */
    foreach ($this->getActionParams($this->getAction()->getId()) as $param)
    {
      if (!$param->validate($this->getController()->getActionParams())) {
        return false;
      }
    }

    return true;
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * @return CController
   */
  private function getController() {
    return $this->_controller;
  }

  /////////////////////////////////////////////////////////////////////////////

  private function setController( CController $value ) {
    $this->_controller = $value;
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * @return IAction
   */
  private function getAction() {
    return $this->_action;
  }

  /////////////////////////////////////////////////////////////////////////////

  private function setAction( IAction $value ) {
    $this->_action = $value;
  }

  /////////////////////////////////////////////////////////////////////////////

  private function setActionParam( $actionId, $paramName, $param )
  {
    if (is_array($param))
    {
      if (!isset($param['class'])) {
        Yii::import( '_actionParamFilter.ActionParam', true );
        $param['class'] = 'ActionParam';
      }

      $param['name'] = $paramName;
      $param = Yii::createComponent( $param );
    }

    if ($param instanceof ActionParam) {
      $this->_actionParams[$actionId][$param->name] = $param;
    } else {
      throw new CException("Failed to set param '{$paramName}' for action '$actionId'.");
    }
  }

  /////////////////////////////////////////////////////////////////////////////

}
