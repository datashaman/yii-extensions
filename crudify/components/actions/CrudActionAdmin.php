<?php
class CrudActionAdmin extends CrudActionBase
{
  protected $pageSize;
  public $view = 'admin';

  public function run()
  {
    $variables = $this->controller->getPage();
    $this->controller->render($this->view, $variables);
  }
}
