<?php
class CrudForm extends CForm
{
  public $readOnly = false;

  public $emptyMessage = 'Select one';

  public $defaults = array(
    'text' => array('type' => 'text', 'size' => 40),
    'textarea' => array('type' => 'textarea', 'rows' => 10, 'cols' => 42),
    'select' => array('type' => 'dropdownlist'),
    'datetime' => array('type' => 'application.extensions.ds.datetime.DsDateTimeWidget',
      'dateOptions' => array('dateFormat' => 'ymd-', 'spinnerImage' => ''),
      'timeOptions' => array('show24Hours' => true, 'showSeconds' => true, 'spinnerImage' => ''),
    ),
    'date' => array('type' => 'application.extensions.ds.datetime.DsDateTimeWidget',
      'dateOptions' => array('dateFormat' => 'ymd-', 'spinnerImage' => ''),
      'timeFormat' => null,
    )
  );

  public $readOnlyElements = array(
    'created_at',
    'created_by_id',
    'updated_at',
    'updated_by_id',
    'deleted_at',
    'deleted_by_id',
  );

  private function getReadOnly($model, $attribute)
  {
    Yii::app()->controller->beginClip('element');
    $model->renderAttributeElement($attribute);
    Yii::app()->controller->endClip();
    $element = Yii::app()->controller->clips['element'];

    $content = '<div class="row field_'.$attribute.'">';
    $content .= '  <div class="label">'.$model->getAttributeLabel($attribute).'</div>';
    $content .= '  <div class="value readonly">'.$element.'</div>';
    $content .= '</div>';

    return array('type' => 'string', 'content' => $content);
  }

  public function __construct($config = null, $model, $parent = null)
  {
    CHtml::$errorCss = 'validation-error';

    if(empty($config)) {
      $columns = $model->metaData->columns;

      foreach(array_keys($_GET) as $parameter) {
        if(!empty($columns["{$parameter}_id"])) {
          array_push($this->readOnlyElements, $parameter.'_id');
        }
      }
     
      $showErrorSummary = true;
      $elements = array();

      $attributes = method_exists($model, 'getEditAttributes') ? $model->getEditAttributes() : array_keys($model->attributes);

      foreach($attributes as $attribute) {
        if($this->readOnly) {
          $elements[$attribute] = $this->getReadOnly($model, $attribute);
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
          case 'id':
            continue;
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
                $items = CHtml::listData($foreign->findAll($relation->condition), 'id', 'name');
                $required or $items = array('' => $this->emptyMessage) + $items;
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
            } else {
              throw new CHttpException(500, 'Unhandled dbType of '.$column->dbType);
            }
        }
      }
      $buttons = array(
        'save' => array('type' => 'htmlButton'),
      );

      $config = compact('showErrorSummary', 'elements', 'buttons');
    }

    parent::__construct($config, $model, $parent);
  }
}
