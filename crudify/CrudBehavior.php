<?php
class CrudBehavior extends CModelBehavior
{
  private $assetPath;

  public function attach($component)
  {
    parent::attach($component);
    $this->assetPath = Yii::app()->getAssetManager()->publish(dirname(__FILE__).'/assets/');
  }

  protected function renderAttributeElement($attribute, $params = array()) {
    switch($attribute) {
      case 'id':
        array_unshift($params, 'view');
        $params['id'] = $this->owner->id;
        echo CHtml::link($this->owner->id, $params);
        return;
      case 'password':
        echo '********';
        return;
      case 'email':
        echo empty($this->owner->email) ? '-' : CHtml::link($this->owner->email, 'mailto:'.$this->owner->email);
        return;
      default:
        if(preg_match('/uri$/', $attribute)) {
          echo empty($this->owner->$attribute) ? '-' : CHtml::link($this->owner->$attribute, $this->owner->$attribute);
          return;
        } else {
          foreach($this->owner->metaData->relations as $property => $relation) {
            if($relation->foreignKey === $attribute) {
              switch(get_class($relation)) {
                case 'CBelongsToRelation':
                  if(empty($this->owner->$attribute)) {
                    echo '-';
                  } else {
                    array_unshift($params, 'view');
                    $params['id'] = $this->owner->$attribute;
                    $params['model'] = $relation->className;
                    echo CHtml::link($this->owner->{$relation->name}->name, $params);
                  }
                  return;
                case 'CHasOneRelation':
                case 'CHasManyRelation':
                default:
                  //$class = get_class($relation);
                  //return "<div class=\"error\">Fix {$class} relationship</div>";
              }
            }
          }

          echo empty($this->owner->$attribute) ? '-' : $this->owner->$attribute;
        }
      }
  }

  protected function getActionLink($action, $labelled = true) {
    $parameters = array("/crud/$action");
    foreach($_GET as $name => $value) {
      if(!($name == 'r' || $name == 'id' && ($action == 'add' || $action == 'admin'))) $parameters[$name] = $value;
    }

    switch($action) {
      case 'delete':
        $name = CHtml::encode($this->owner->name);
        return CHtml::linkButton($this->getActionLabel('delete', $labelled), array(
          'submit'=> Yii::app()->getUrlManager()->createUrl('/crud/delete', $parameters),
          'params'=>array('id'=>$this->owner->id),
          'confirm'=>"Are you sure you want to delete '{$name}'?",
          'title' => 'Delete'
        ));
      default:
        if($action != 'add' && $action != 'admin') {
          if(!empty($this->owner->id)) $parameters['id'] = $this->owner->id;
        }
        return CHtml::link($this->getActionLabel($action, $labelled), $parameters, array('title' => ucfirst($action)));
    }
  }

  protected function getActionLabel($action, $labelled = true, $title = null) {
    empty($title) and $title = ucfirst($action);
    $html = CHtml::image($this->assetPath.'/images/actions/'. $action . '.png', $title, array('class' => $action, 'border' => 0));
    $labelled and $html .= ' '.$title;
    return $html;
  }

  private function getAdminColumns()
  {
    $columns = $this->owner->metaData->columns;
    foreach(array('created', 'updated', 'deleted') as $action) {
      unset($columns["{$action}_at"]);
      unset($columns["{$action}_by_id"]);
    }
    unset($columns['id']);
    return array_keys($columns);
  }

  protected function getValidatorsByAttribute($attribute)
  {
    $validators = array();
    foreach($this->owner->getValidators() as $validator) {
      if(in_array($attribute, $validator->attributes)) {
        $validators[] = $validator;
      }
    }
    return $validators;
  }
}
