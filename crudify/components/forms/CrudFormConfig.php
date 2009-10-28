<?php
class CrudFormConfig extends CApplicationComponent
{
  public $id = null;

  protected $defaults = array(
      'hidden' => array('type' => 'hidden'),
      'text' => array('type' => 'text', 'size' => 40),
      'textarea' => array('type' => 'textarea', 'rows' => 10, 'cols' => 46),
      'select' => array('type' => 'dropdownlist'),
      'date' => array('type' => 'application.extensions.ds.datetime.DsDateTimeWidget', 'timeFormat' => null),
      'datetime' => array('type' => 'application.extensions.ds.datetime.DsDateTimeWidget'),
      'float' => array('type' => 'text', 'size' => 10),
      'tinyint(1)' => array('type' => 'checkbox'),
      );

  public $readOnly = false;
  public $listPrompt = 'Not selected';
  public $readOnlyElements = array(
      'created_at',
      'updated_at',
      'deleted_at',
      'created_by_id',
      'updated_by_id',
      'deleted_by_id',
      );
  public $unrenderedElements = array();

  private function getReadOnly($model, $attribute)
  {
    Yii::app()->controller->beginClip('element');
    $model->renderProperty($attribute);
    Yii::app()->controller->endClip();
    $element = Yii::app()->controller->clips['element'];

    $content = '<div class="row field_'.$attribute.'">';
    $content .= '  <div class="label">'.$model->getAttributeLabel($attribute).'</div>';
    $content .= '  <div class="value readonly">'.$element.'</div>';
    $content .= '</div>';

    return array('type' => 'string', 'content' => $content);
  }

  public function generate($model, $id)
  {
    CHtml::$errorCss = 'ui-state-error';

    if(empty($config)) {
      $hints = $model->formHints;

      $columns = $model->metaData->columns;

      $elements = array();

      foreach($model->attributeNames() as $attribute) {
        $element = null;

        if(in_array($attribute, $this->unrenderedElements)) {
          continue;
        } else if($this->readOnly || isset($_GET[$attribute])) {
          $element = $this->defaults['hidden'];
        } else if(in_array($attribute, $this->readOnlyElements)) {
          if($model->scenario != 'insert') {
            $element = $this->getReadOnly($model, $attribute);
          } else {
            continue;
          }
        } else {
          $validators = $model->getValidatorsByAttribute($attribute);

          foreach($validators as $validator) {
            if(is_a($validator, 'CTypeValidator')) {
              if($validator->type == 'date') {
                if(strlen($validator->dateFormat) > 10) { // Assume it's datetime, rather than date only
                  $element = $this->defaults['datetime'];
                } else {
                  $element = $this->defaults['date'];
                }
                break;
              }
            }
          }

          switch($attribute) {
            case $model->metaData->tableSchema->primaryKey:
              if($model->metaData->columns[$attribute]->type == 'integer') continue;

            default:
              if(preg_match('/.*_at/', $attribute)) {
                $element = $this->defaults['datetime'];
                break;
              }

              if(preg_match('/.*_on/', $attribute)) {
                $element = $this->defaults['date'];
                break;
              }

              $column = $columns[$attribute];

              if($foreignKey = @$model->metaData->tableSchema->foreignKeys[$attribute]) {
                foreach($model->metaData->relations as $property => $relation) {
                  if($relation->foreignKey == $attribute && is_a($relation, 'CBelongsToRelation')) {
                    $required = false;

                    foreach($validators as $validator) {
                      if(is_a($validator, 'CRequiredValidator')) {
                        $required = true;
                        break;
                      }
                    }

                    $required or $required = !$column->allowNull;

                    $foreign = call_user_func(array($relation->className, 'model'));
                    empty($relation->alias) and $relation->alias = $relation->name;

                    $objects = $foreign->options($relation)->findAll();
                    $items = CHtml::listData($objects, 'id', 'name');
                    $required or $items = array('' => $this->listPrompt) + $items;
                    $element = $this->defaults['select'];
                    $element['items'] = $items;
                  }
                }
              }

              if(empty($element)) {
                if(empty($column)) {
                  $element = $this->defaults['text'];
                  $elements[$attribute]['hint'] = @$hints[$attribute];
                } else if($column->dbType == 'text') {
                  $element = $this->defaults['textarea'];
                } else if(preg_match('/^(char|varchar)/', $column->dbType)) {
                  $element = $this->defaults['text'];
                  $element['maxlength'] = $column->size;
                  $element['size'] > $element['maxlength'] and $element['size'] = $element['maxlength'];
                  $attribute == 'password' and $element['type'] = 'password';
                } else if(!empty($this->defaults[$column->dbType])) {
                  $element = $this->defaults[$column->dbType];
                } else {
                  throw new CHttpException(500, 'Unhandled dbType of '.$column->dbType.' for '.$model->tableName().'::'.$column->name);
                }
              }
          }
        }

        if(!empty($element)) $elements[$attribute] = $element;
      }

      $buttons = array(
        'submit'.ucfirst($id) => array('type' => 'htmlButton', 'label' => Yii::app()->controller->getActionLabel('save'), 'buttonType' => 'submit', 'class' => 'ui-state-default')
      );

      $config = compact('elements', 'buttons');
      $config['id'] = $id;

      return $config;
    }
  }
}
