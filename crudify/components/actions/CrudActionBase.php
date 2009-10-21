<?php
abstract class CrudActionBase extends CAction
{
  /**
   * The model to be crudded
   */
  public $model;

  public function getClassName()
  {
    return get_class($this->model);
  }
}
