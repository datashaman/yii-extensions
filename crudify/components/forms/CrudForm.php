<?php
class CrudForm extends CForm
{
  public $inputElementClass = 'CrudInputElement';

  public function __construct($model, $id, $parent = null)
  {
    $config = $model->getFormConfig($id);
    parent::__construct($config, $model, $parent);
  }

  public function renderButtons()
  {
    $output='';

    foreach($this->getButtons() as $button)
      $output.=$this->renderElement($button);

    return $output!=='' ? "<div class=\"row buttons\"><div class=\"label\"></div><div class=\"value\">".$output."</div></div>\n" : '';
  }
}
