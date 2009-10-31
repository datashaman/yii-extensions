<?php
class NeatForm extends CForm
{
  public $inputElementClass = 'CrudInputElement';

  public function renderButtons()
  {
    $output='';

    foreach($this->getButtons() as $button)
      $output.=$this->renderElement($button);

    return $output!=='' ? "<div class=\"row buttons\"><div class=\"label\"></div><div class=\"value\">".$output."</div></div>\n" : '';
  }
}
