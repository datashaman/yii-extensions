<?php
class CrudForm extends NeatForm
{
  public function __construct($model, $id, $parent = null)
  {
    $config = $model->getFormConfig($id);
    parent::__construct($config, $model, $parent);
  }
}
