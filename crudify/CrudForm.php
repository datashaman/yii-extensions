<?php
class CrudForm extends CForm
{
  public $readOnlyElements = array(
    'created_at',
    'created_by_id',
    'updated_at',
    'updated_by_id',
    'deleted_at',
    'deleted_by_id',
  );

  private function getDateTimeWidget()
  {
    return array('type' => 'application.extensions.ds.datetime.DsDateTimeWidget',
      'dateOptions' => array(
        'dateFormat' => 'ymd-',
        'spinnerImage' => '',
      ),
      'timeOptions' => array(
        'show24Hours' => true,
        'showSeconds' => true,
        'spinnerImage' => '',
      ),
    );
  }

  private function getDateWidget()
  {
    $config = $this->getDateTimeWidget();
    $config['timeFormat'] = null;
    return $config;
  }

  private function getReadOnly($model, $parent, $attribute)
  {
    while(!empty($parent) && !is_a($parent, 'CBaseController')) {
      $parent = $parent->getParent();
    }
    if(empty($parent)) {
      throw new CHttpException(500, "You have constructed a CrudForm without a CBaseController in its parents");
    }

    $parent->beginClip('crud');
    echo '<div class="label">'.$model->getAttributeLabel($attribute).'</div>';
    echo '<div class="value">';
    $model->renderAttributeElement($attribute);
    echo '</div>';
    $parent->endClip();

    $content = $parent->clips['crud'];

    return array('type' => 'string', 'content' => $content);
  }

  public function __construct($config = null, $model, $parent = null)
  {
    if(empty($config)) {
      $showErrorSummary = true;
      $elements = array();
      $attributes = method_exists($model, 'getEditAttributes') ? $model->getEditAttributes() : array_keys($model->attributes);
      foreach($attributes as $attribute) {
        if(in_array($attribute, $this->readOnlyElements)) {
          if($model->scenario != 'insert')
            $elements[$attribute] = $this->getReadOnly($model, $parent, $attribute);
          continue;
        }

        foreach($model->getValidatorsByAttribute($attribute) as $validator) {
          if(is_a($validator, 'CTypeValidator')) {
            if($validator->type == 'date') {
              if(strlen($validator->dateFormat) > 10) { // Assume it's datetime, rather than date only
                $elements[$attribute] = $this->getDateTimeWidget();
              } else {
                $elements[$attribute] = $this->getDateWidget();
              }
              continue 2;
            }
          }
        }

        switch($attribute) {
          case 'id':
            continue;
          case 'password':
            $elements[$attribute] = array('type' => 'password', 'maxlength' => 60, 'size' => 40);
            continue;
          default:
            if(preg_match('/.*_at/', $attribute)) {
              $elements[$attribute] = $this->getDateTimeWidget();
              continue;
            }

            if(preg_match('/.*_on/', $attribute)) {
              $elements[$attribute] = $this->getDateWidget();
              continue;
            }

            foreach($model->metaData->relations as $property => $relation) {
              if($relation->foreignKey === $attribute && get_class($relation) == 'CBelongsToRelation') {
                $foreign = call_user_func(array($relation->className, 'model'));
                $items = CHtml::listData($foreign->findAll(), 'id', 'name');
                $elements[$attribute] = array('type' => 'dropdownlist', 'items' => $items);
                continue 2;
              }
            }

            $elements[$attribute] = array('type' => 'text', 'maxlength' => 60, 'size' => 40);
        }
      }
      $buttons = array(
        'save' => array('type' => 'submit', 'label' => 'Save'),
      );

      $config = compact('showErrorSummary', 'elements', 'buttons');
    }

    parent::__construct($config, $model, $parent);
  }
}
