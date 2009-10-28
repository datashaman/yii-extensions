<?php
class CrudInputElement extends CFormInputElement
{
  public $layout = '<div class="label">{label}</div> <div class="value">{input}{error}{hint}</div>';

  public function __construct($config, $parent) {
    parent::__construct($config, $parent);
    unset($this->id);
  }
}
