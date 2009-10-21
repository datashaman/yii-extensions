<?php
class CrudFilterWidget extends CAutoComplete
{
  public function init()
  {
    if(empty($this->model->metaData->tableSchema->foreignKeys[$this->attribute])) {
      $column = $this->model->metaData->columns[$this->attribute];
      $this->htmlOptions['size'] = min($column->size, 15);
      $this->htmlOptions['maxlength'] = $column->size;
      $this->methodChain = '.result(function(event,item){ this.form.submit(); })';
    } else {
      $relationName = preg_replace('/_id$/', '', $this->attribute);
      $relation = $this->model->getActiveRelation($relationName);
      if($relation) {
        $foreignModel = $this->controller->getModelByClass($relation->className);
        $primaryKey = $foreignModel->metaData->tableSchema->primaryKey;

        $this->htmlOptions['size'] = min($foreignModel->metaData->columns[$primaryKey]->size, 15);

        if(!empty($_GET[$this->attribute])) {
          $row = $foreignModel->findByPk($_GET[$this->attribute]);
          $this->htmlOptions['value'] = $row->name;
        }

        $this->htmlOptions['onchange'] = 'if(jQuery("#filter_'.$this->attribute.'").val() == "") jQuery("#'.$this->id.'").val("");';
        $this->methodChain = '.result(function(event,item){ jQuery("#'.$this->id.'").val(item[1]); this.form.submit(); })';
      }
    }
  }

  public function run()
  {
    parent::run();

    if(!empty($this->value)) {
        echo CHtml::htmlButton($this->controller->getActionLabel('cross', false), array('type' => 'submit', 'alt' => 'Remove filter', 'title' => 'Remove filter', 'onclick' => 'jQuery("#'.$this->id.'").val("")'));
    }
  }
}
