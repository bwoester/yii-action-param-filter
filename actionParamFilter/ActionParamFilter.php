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
 * When used with a default CController, obviously the action parameters will
 * only contain of $_GET variables. So with this default setup, there's not
 * much reason to use this filter. But it starts to come in handy as soon as
 * you use more sources for the action paramters.
 *
 * A configuration example:
 *
 * @code
 *
 * class ModelController extends CController
 * {
 *
 *   // this controller not only uses $_GET params, but also $_POST params
 *   // for action parameter binding.
 *   public function getActionParams()
 *   {
 *     return array_merge($_GET, $_POST);
 *   }
 *
 *   // Normally, with merged action parameters, you loose control over where
 *   // they come from.
 *   //
 *   // But now, with the ActionParamFilter, you can gain back the control.
 *   //
 *   // This filter configuration below forces the delete action to be:
 *   // 1) a POST
 *   // 2) to http://doma.in/index.php?r=model/delete&id=123 [ &ajax=1 ]
 *   // 3) with a body "returlUrl=http%3A%2F%2Fdoma.in%2Findex.php%3Fr%3Dmodel"
 *   public function filters()
 *   {
 *     return array(
 *       'postOnly + delete',
 *       array(
 *         'class'  => 'ext.filters.actionParamFilter.ActionParamFilter',
 *         'actionParams' => array(
 *           'delete' => array(
 *             'id'         => array( 'source' => 'get' ),
 *             'returnUrl'  => array( 'source' => 'post' ),
 *             'ajax'       => array( 'source' => 'get' ),
 *             // if you don't mind where the $ajax parameter comes from:
 *             // use (get takes precedence over post):
 *             //'ajax' => array( 'source' => 'get,post' ),
 *             // or (post takes precedence over get):
 *             //'ajax' => array( 'source' => 'post,get' ),
 *             // or even (precedence depends on you php settings):
 *             // please note that even if you php settings *might* include
 *             // cookies in $_REQUEST, the actionParams in this example will
 *             // only be populated from $_GET and $_POST.
 *             //'ajax' => array( 'source' => 'request' ),
 *           ),
 *         ),
 *       ),
 *     );
 *   }
 *
 *   // So at this point, we don't need to know where the parameters come from.
 *   // Just keep in mind that what we receive is data submitted by the user.
 *   // Don't trust it. Input has to be validated.
 *   // We know that that the code below will only be executed if the request
 *   // is a POST, but this is only because of configuration. It doesn't matter
 *   // to the action.
 *   // Removing the tight coupling to $_GET or $_POST params from the action
 *   // implementation is ideal for CAction instances that might be reused in
 *   // several controlers.
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
 *
 * Binding $_GET and $_POST data to our actions parameters will probably be
 * enough in 90% of all cases. However, sometimes we need more.
 *
 * Maybe you require some variables from $_COOKIE for a certain action. Again,
 * I argue that coupling the action tight to the fact that this particular
 * piece of information comes from a cookie is bad practice. A reusable action
 * only needs the data to work with. It does need to validate the data and it
 * needs to use the data. But it doesn't need to know how the user submitted
 * it.
 *
 * One way to go would be to override getActionParams() and merge $_GET, $_POST
 * and $_COOKIE. You can still configure the ActionParamFilter to only allow
 * most of the parameters from $_GET and/ or $_POST:
 *
 * @code
 * class ModelController extends CController
 * {
 *
 *   // this controller uses $_GET, $_POST and $_COOKIE params for action
 *   // parameter binding. POST data overrides GET data, COOKIE data overrides
 *   // everything else. This is !!! B A D !!!!
 *   public function getActionParams()
 *   {
 *     return array_merge( $_GET, $_POST, $_COOKIE );
 *   }
 *
 *   // Again, we configure our filter for the delete action...
 *   public function filters()
 *   {
 *     return array(
 *       'postOnly + delete',
 *       array(
 *         'class'  => 'ext.filters.actionParamFilter.ActionParamFilter',
 *         'actionParams' => array(
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
 *   // ... and can be sure no cookie data will accidently override one of our
 *   // parameters (they are all explicitly configured).
 *   public function actionDelete( $id, $returnUrl='', $ajax=null )
 *   {
 *   }
 *
 *   // Now this could be a problem. If we forget to configure the param with
 *   // the filter and if the user has a cookie containing an id variable, he
 *   // probably won't ever be able to duplicate the model he's trying to
 *   // duplicate. The cookie variable overrides all his GET and POST data.
 *   public function actionDuplicate( $id )
 *   {
 *   }
 * }
 *
 * @endcode
 *
 * Another solution would be to activate ActionParamFilter's
 * "actionParamProvider"-feature. If this feature is activated, the
 * ActionParamFilter will attach a behavior to the controller that is currently
 * running (executing the filter). This behavior adds a method
 * "provideActionParams()" to the controller. You can use this method in your
 * overload of getActionParams() to instruct the ActionParamFilter to search
 * for actionParams exactly in those sources that you configured for each
 * param. This way, you don't need to blindly merge everything that exists
 * together, but can selectively pick actionParameters from where you want
 * them:
 *
 * @code
 * class ModelController extends CController
 * {
 *   // Again, we configure our filter for the delete action...
 *   public function filters()
 *   {
 *     return array(
 *       'postOnly + delete',
 *       array(
 *         'class' => 'ext.filters.actionParamFilter.ActionParamFilter',
 *         // enable feature
 *         'provideActionParams'   => true,
 *         // name of the behavior used when attaching
 *         'actionParamProviderId' => 'actionParamProvider'
 *         'actionParams' => array(
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
 *   // this controller tries to use "actionParamProvider"-feature of
 *   // ActionParamFilter. It falls back to $_GET and $_POST if it isn't
 *   // available.
 *   public function getActionParams()
 *   {
 *     // Femember the name from config. 'actionParamProvider' is the default.
 *     $actionParamProvider = $this->asa( 'actionParamProvider' );
 *     if ($actionParamProvider instanceof IActionParamProvider) {
 *       return $actionParamProvider->provideActionParams();
 *     }
 *
 *     // fallback if behavior isn't attached (actions that don't use the
 *     // ActionParamFilter)
 *     return array_merge( $_GET, $_POST );
 *   }
 *
 *   // well... id is take from get, returnUrl from post, ajax from get.
 *   // see config.
 *   public function actionDelete( $id, $returnUrl='', $ajax=null )
 *   {
 *   }
 *
 *   // Okay, this a problem again. We configured ActionParamFilter for all
 *   // actions, use the "actionParamProvider"-feature but didn't configure
 *   // params for this action. That's why the param won't be bound.
 *   //
 *   // There are three solutions:
 *   // 1) Configure all actions and params with the filter
 *   // 2) Configure the filter to only apply to configured actions
 *   // 3) in getActionParams(), merge $_GET, $_POST and the results of
 *   //    provideActionParams()
 *   public function actionDuplicate( $id )
 *   {
 *   }
 * }
 * @endcode
 *
 * Since the ActionParamFilter knows quite some sources for action parameters,
 * the "actionParamProvider"-feature is really powerfull. For example, we could
 * make actionDelete accessable to REST clients:
 *
 * @code
 *
 * class ModelController extends CController
 * {
 *
 *   public function getActionParams() {
 *     // ...
 *   }
 *
 *   public function filters()
 *   {
 *     return array(
 *       'deleteOnly + delete',
 *       array(
 *         'class'  => 'ext.filters.actionParamFilter.ActionParamFilter',
 *         'provideActionParams' => true,
 *         'actionParams' => array(
 *           'delete' => array(
 *              // this means the id parameter will be read from DELETE-params
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
 *   public function actionDelete( $id, $returnUrl='', $ajax=null ) {
 *     // ...
 *   }
 * }
 *
 * @endcode
 *
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
   *         'actionParams' => array(
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
