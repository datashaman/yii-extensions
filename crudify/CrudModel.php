<?php
class CrudModel extends CActiveRecord
{
  private $assetPath;

  public function __construct($scenario='insert')
  {
    parent::__construct($scenario);
    $this->assetPath = Yii::app()->getAssetManager()->publish(dirname(__FILE__).'/assets/');
  }

  public function renderAttributeElement($attribute, $params = array()) {
    switch($attribute) {
      case 'id':
        array_unshift($params, 'view');
        $params = array(
          get_class($this->_model) => array('id' => $this->id)
        );
        echo CHtml::link($this->id, $params);
        return;
      case 'password':
        echo '********';
        return;
      case 'email':
        echo empty($this->email) ? '-' : CHtml::link($this->email, 'mailto:'.$this->email);
        return;
      default:
        if(preg_match('/uri$/', $attribute)) {
          echo empty($this->$attribute) ? '-' : CHtml::link($this->$attribute, $this->$attribute);
          return;
        } else {
          foreach($this->metaData->relations as $property => $relation) {
            if($relation->foreignKey === $attribute) {
              switch(get_class($relation)) {
                case 'CBelongsToRelation':
                  if(empty($this->$attribute)) {
                    echo '-';
                  } else {
                    $params = array(
                      'model' => $relation->className,
                      $relation->className => array(
                        'id' => $this->$attribute,
                      ),
                    );
                    echo CHtml::link($this->{$relation->name}->name, Yii::app()->controller->createUrl('crud/view', $params));
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

          echo empty($this->$attribute) ? '-' : $this->$attribute;
        }
      }
  }

  public function getActionLink($action, $labelled = true, $title = null, $parameters = array(), $method = 'htmlButton', $icon_size = '16x16', $description = null) {
    if(empty($parameters)) {
      foreach($_GET as $name => $value) {
        if(!($name == 'r' || $name == 'id' && ($action == 'delete' || $action == 'add' || $action == 'admin'))) $parameters[$name] = $value;
      }
    }

    $src = null;

    switch($action) {
      case 'delete':
        $name = CHtml::encode($this->name);
        return CHtml::htmlButton($this->getActionLabel('delete', $labelled), array(
          'submit'=> Yii::app()->getUrlManager()->createUrl('crud/delete', $parameters),
          'params' => array('id' => $this->id),
          'confirm'=>"Are you sure you want to delete '{$name}'?",
          'title' => 'Delete'
        ));
      case 'admin':
        $path = dirname(__FILE__).'/assets/images/models/'.$icon_size.'/'.get_class($this).'.png';
        $src = file_exists($path) ? $this->assetPath.'/images/models/'.$icon_size.'/'.get_class($this).'.png' : null;
      default:
        if($action != 'add' && $action != 'admin') {
          if(!empty($this->id)) {
            $class = get_class($this);
            empty($parameters[$class]) and $parameters[$class] = array();
            $parameters[$class]['id'] = $this->id;
          }
        }
        $url = Yii::app()->getUrlManager()->createUrl("crud/$action", $parameters);
        if($method == 'link') {
          return CHtml::$method($this->getActionLabel($action, $labelled, $title, $src), $url, array('title' => ucfirst($action)));
        } else {
          return CHtml::$method($this->getActionLabel($action, $labelled, $title, $src), array('title' => ucfirst($action), 'onclick' => 'location.href='.CJavaScript::encode($url)));
        }
    }
  }

  public function getActionLabel($action, $labelled = true, $title = null, $src = null) {
    empty($title) and $title = ucfirst($action);

    empty($src) and $src = $this->assetPath.'/images/actions/'.$action.'.png';

    $html = CHtml::image($src, $title, array('class' => $action, 'border' => 0));
    $labelled and $html .= ' '.$title;
    return $html;
  }

  private function getAdminColumns()
  {
    $columns = $this->metaData->columns;
    foreach(array('created', 'updated', 'deleted') as $action) {
      unset($columns["{$action}_at"]);
      unset($columns["{$action}_by_id"]);
    }
    unset($columns['id']);
    return array_keys($columns);
  }

  public function getValidatorsByAttribute($attribute)
  {
    $validators = array();
    foreach($this->getValidators() as $validator) {
      if(in_array($attribute, $validator->attributes)) {
        $validators[] = $validator;
      }
    }
    return $validators;
  }

  public function getRelatedLinks()
  {
    $links = array();
    foreach($this->metaData->relations as $name => $relation) {
      if(is_a($relation, 'CHasManyRelation')) {
        $object = new $relation->className();

        foreach($object->metaData->relations as $foreignName => $foreignRelation) {
          if($foreignRelation->className == get_class($this) && is_a($foreignRelation, 'CBelongsToRelation')) {
            //var_dump($this->getDbCriteria());
            $result = $this->find($foreignRelation->condition);
            //var_dump($foreignRelation->condition, $result);
            break;
          }
        }

        $links[] = $this->getRelatedLink($relation);
      }
    }
    return $links;
  }

  protected function getRelatedLink($relation)
  {
    $parameters = array(
      'model' => $relation->className,
      $relation->className => array($relation->foreignKey => $this->id)
    );
  }

  public function defaultScope()
  {
    $tableName = $this->tableName();
    return array(
      'condition' => "$tableName.deleted_at is null and $tableName.deleted_by_id is null"
    );
  }

  public function filtered()
  {
    $thisClass = get_class($this);

    foreach(array_keys($this->attributes) as $attribute) {
      if(!empty($_GET[$thisClass][$attribute])) {
        if(empty($this->metaData->tableSchema->foreignKeys[$attribute])) {
          $criteria = array(
            'condition' => "$attribute like :$attribute",
            'params' => array(":$attribute" => '%'.$_GET[$thisClass][$attribute].'%'),
          );
        } else {
          $criteria = array(
            'condition' => "$attribute = :$attribute",
            'params' => array(":$attribute" => $_GET[$thisClass][$attribute]),
          );
        }
        $this->getDbCriteria()->mergeWith($criteria);
      }
    }

    return $this;
  }

  public function options($criteria = array())
  {
    $tableAlias = strtolower(get_class($this));
    $criteria += array(
      'select' => "$tableAlias.id, $tableAlias.name",
      'order' => "$tableAlias.name asc",
      );
    $criteria['select'] = 'distinct '.$criteria['select'];
    $this->getDbCriteria()->mergeWith($criteria);
    return $this;
  }
}
