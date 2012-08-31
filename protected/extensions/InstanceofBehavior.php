<?php



/**
 * @author Benjamin
 */
class InstanceofBehavior extends CBehavior
{
  public function isInstanceof( $obj, $className )
  {
    return (bool)$this->getInstanceof($obj, $className);
  }

  public function getInstanceof( $obj, $className )
  {
    $retVal = false;

    if ($obj instanceof $className)
    {
      $retVal = $obj;
    }
    else if ($obj instanceof CComponent)
    {
      $behaviorNames = $this->getBehaviorNames( $obj );

      foreach ($behaviorNames as $behaviorName)
      {
        $behavior = $obj->asa( $behaviorName );

        if ($behavior instanceof $className)
        {
          $retVal = $behavior;
          break;
        }
      }
    }

    return $retVal;
  }

  private function getBehaviorNames( CComponent $obj )
  {
    // Create an instance of the ReflectionProperty class
    $prop = new ReflectionProperty( 'CComponent', '_m' );
    $prop->setAccessible( true );
    $behaviors = $prop->getValue( $obj );

    return array_keys( $behaviors );
  }
}
