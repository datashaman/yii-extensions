<?php
class CrudFormConfig extends CApplicationComponent
{
  protected $defaults = array(
    'hidden' => array('type' => 'hidden'),
    'text' => array('type' => 'text', 'size' => 40),
    'textarea' => array('type' => 'textarea', 'rows' => 10, 'cols' => 46),
    'select' => array('type' => 'dropdownlist'),
    'date' => array('type' => 'application.extensions.ds.datetime.DsDateTimeWidget', 'timeFormat' => null),
    'datetime' => array('type' => 'application.extensions.ds.datetime.DsDateTimeWidget'),
    'float' => array('type' => 'text', 'size' => 10),
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

  public function generate($model)
  {
    CHtml::$errorCss = 'validation-error';

    if(empty($config)) {
      $columns = $model->metaData->columns;

      $elements = array();

      foreach($model->attributeNames() as $attribute) {
        if(in_array($attribute, $this->unrenderedElements)) {
          continue;
        }

        if($this->readOnly || isset($_GET[$attribute])) {
          $elements[$attribute] = $this->defaults['hidden'];
          continue;
        }

        if(in_array($attribute, $this->readOnlyElements)) {
          if($model->scenario != 'insert') {
            $elements[$attribute] = $this->getReadOnly($model, $attribute);
          }
          continue;
        }

        $validators = $model->getValidatorsByAttribute($attribute);

        foreach($validators as $validator) {
          if(is_a($validator, 'CTypeValidator')) {
            if($validator->type == 'date') {
              if(strlen($validator->dateFormat) > 10) { // Assume it's datetime, rather than date only
                $elements[$attribute] = $this->defaults['datetime'];
              } else {
                $elements[$attribute] = $this->defaults['date'];
              }
              continue 2;
            }
          }
        }

        switch($attribute) {
          case $model->metaData->tableSchema->primaryKey:
            if($model->metaData->columns[$attribute]->type == 'integer') continue;

          default:
            if(preg_match('/.*_at/', $attribute)) {
              $elements[$attribute] = $this->defaults['datetime'];
              continue;
            }

            if(preg_match('/.*_on/', $attribute)) {
              $elements[$attribute] = $this->defaults['date'];
              continue;
            }

            $column = $columns[$attribute];

            foreach($model->metaData->relations as $property => $relation) {
              if($relation->foreignKey === $attribute && get_class($relation) == 'CBelongsToRelation') {
                $required = false;

                foreach($validators as $validator) {
                  if(is_a($validator, 'CRequiredValidator')) {
                    $required = true;
                    break;
                  }
                }

                $required or $required = !$column->allowNull;

                $foreign = call_user_func(array($relation->className, 'model'));

                $objects = $foreign->options($relation)->findAll();
                $items = CHtml::listData($objects, 'id', 'name');
                $required or $items = array('' => $this->listPrompt) + $items;
                $elements[$attribute] = $this->defaults['select'];
                $elements[$attribute]['items'] = $items;

                continue 2;
              }
            }

            if(empty($column)) {
              $elements[$attribute] = $this->defaults['text'];
            } else if($column->dbType == 'text') {
              $elements[$attribute] = $this->defaults['textarea'];
            } else if(preg_match('/^(char|varchar)/', $column->dbType)) {
              $element = $this->defaults['text'];
              $element['maxlength'] = $column->size;
              $element['size'] > $element['maxlength'] and $element['size'] = $element['maxlength'];
              $attribute == 'password' and $element['type'] = 'password';
              $elements[$attribute] = $element;
            } else if(!empty($this->defaults[$column->dbType])) {
              $elements[$attribute] = $this->defaults[$column->dbType];
            } else {
              throw new CHttpException(500, 'Unhandled dbType of '.$column->dbType.' for '.$model->tableName().'::'.$column->name);
            }
        }
      }
      $buttons = array(
        'save' => array('type' => 'htmlButton', 'label' => Yii::app()->controller->getActionLabel('save'), 'buttonType' => 'submit')
      );

      $config = compact('elements', 'buttons');

      return $config;
    }
  }
}
