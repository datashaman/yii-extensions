<?php
abstract class CrudController extends CController
{
  public $pageSize = 10;
  public $defaultValues = array();
  public $assetPath;

  public $model;

  private $_models = array();

  public function init()
  {
    if(empty($this->model) && !empty($this->modelClass)) {
      $this->setModelClass($this->modelClass);
    }
    $this->assetPath = Yii::app()->getAssetManager()->publish(Yii::app()->basePath.'/assets');

    $session = Yii::app()->session;
    if(empty($session['returnStack'])) {
      $session['returnStack'] = array();
    }

    if(isset($_GET['returnUrl'])) {
      $this->setReturnUrl($_GET['returnUrl']);
      unset($_GET['returnUrl']);
    }
  }

  public function returnTo($default = null)
  {
    $url = $this->getReturnUrl();
    empty($url) and $url = $default;
    empty($url) or $this->redirect($url);
  }

  public function setReturnUrl($url)
  {
    $session = Yii::app()->session;
    array_push($session->itemAt('returnStack'), $url);
    return $url;
  }

  public function getReturnUrl()
  {
    $session = Yii::app()->session;
    $url = array_pop($session->itemAt('returnStack'));
    return $url;
  }

  public function setModelClass($modelClass)
  {
    if(!is_a($this->model, $modelClass)) {
      $this->model = $this->getModelByClass($modelClass);
    }
    return $modelClass;
  }

  public function getModelClass()
  {
    return get_class($this->model);
  }

  public function getModelByClass($modelClass)
  {
    if(!isset($this->_models[$modelClass])) {
      $this->_models[$modelClass] = call_user_func(array($modelClass, 'model'));
    }
    return $this->_models[$modelClass];
  }

  public function getObject()
  {
    if($this->action->id == 'add') {
      $object = new $this->model;
      if(!empty($this->defaultValues)) {
        foreach($this->defaultValues as $attribute => $value) {
          isset($this->defaultValues[$attribute]) and $object->$attribute = $this->defaultValues[$attribute];
        }
      }
    } else {
      $object = $this->model->filtered()->find();
      if(!$object) {
        throw new CHttpException(404, 'Object not found');
      }
    }
    return $object;
  }

  public function getAdminAttributes($model = null)
  {
    empty($model) and $model = $this->model;

    $attributes = array();
    foreach($model->metaData->columns as $column) {
      foreach(array('created', 'updated', 'deleted') as $action) {
        if($column->name == "{$action}_by_id" || $column->name == "{$action}_at") continue 2;
      }
      $attributes[] = $column->name;
    }
    return $attributes;
  }

  public function getPage($model = null, $criteria = array())
  {
    is_null($model) and $model = $this->model;
    is_array($criteria) and $criteria = new CDbCriteria($criteria);

    $select = is_array($criteria->select) ? $criteria->select : preg_split('/\s*,\s*/', $criteria->select);

    $pages = new CPagination($model->filtered()->count($criteria));
    $pages->pageSize = $this->pageSize;

    $sort=new CSort(get_class($model));

    $objects = $model->filtered()->paged($pages, $sort)->findAll($criteria);

    return compact('criteria', 'model', 'objects', 'pages', 'sort');
  }

  public function renderAdmin($controllerId, $criteria, $objects, $sort)
  {
    foreach($objects as $n=>$object) {
      $params = $object->getPkArray();
      $edit = array("$controllerId/edit") + $params;
      $delete = array("$controllerId/delete") + $params;

      echo '<tr class="'.($n % 2 ? 'odd' : 'even').'">';

      foreach($criteria->select as $attribute) {
        if(!empty($sort->attributes[$attribute]) && strpos($sort->attributes[$attribute], '.') !== false) {
          $relationName = $relationAttribute = null;
          list($relationName, $relationAttribute) = preg_split('/\./', $sort->attributes[$attribute]);
          if(!empty($relationName) && $relationName !== $object->tableName()) {
            echo '<td>';
            empty($object->$relationName) ? '-' : $object->renderProperty($relationName);
            echo '</td>';
            continue;
          }
        }
        echo '<td>';
        empty($object) ? '-' : $object->renderProperty($attribute);
        echo '</td>';
      }

      echo '<td class="actions">';
      echo $this->getActionLink($edit, false);
      echo $this->getActionLink($delete, false);
      echo '</td>';
      echo '</tr>';
    }
  }
      
  public function actions()
  {
    $model = $this->model;

    return array(
      'index' => array(
        'class' => 'crud.components.actions.CrudActionAdmin',
        'model' => $model,
        'view' => 'index',
       ),
      'admin' => array(
        'class' => 'crud.components.actions.CrudActionAdmin',
        'model' => $model
       ),
      'edit' => array(
        'class' => 'crud.components.actions.CrudActionEdit',
        'model' => $model
      ),
      'add' => array(
        'class' => 'crud.components.actions.CrudActionEdit',
        'model' => $model,
        'defaultValues' => $_GET,
      ),
      'delete' => array(
        'class' => 'crud.components.actions.CrudActionDelete',
        'model' => $model
      ),
      'confirm' => array(
        'class' => 'crud.components.actions.CrudActionConfirm',
        'model' => $model
      ),
      'autoComplete' => array(
        'class' => 'crud.components.actions.CrudActionAutoComplete',
        'model' => $model
      ),
    );
  }

  public function filters()
  {
    $filters = array(
        'accessControl',
        'postOnly + delete',
        //'ajaxOnly + autoComplete, delete',
    );
    return $filters;
  }

  public function accessRules()
  {
    return array(
        array('allow', 'actions'=>array('index'), 'users'=>array('*')),
        array('allow', 'actions'=>array('add','edit','autoComplete'), 'users'=>array('@')),
        array('allow', 'actions'=>array('admin','delete'), 'users'=>array('admin'), 'verbs'=>array('GET','POST','DELETE')),
        array('deny', 'users'=>array('*'))
    );
  }

  public function getActionLink($url, $labelled = true, $title = null, $method = 'htmlButton', $icon_size = '16x16') {
    $src = null;

    if(is_array($url)) {
      $route = array_shift($url);
      $parameters = $url;

      @list($controllerId, $actionId) = split('/', $route);

      empty($actionId) and $actionId = $controllerId and $controllerId = $this->id;

      if($actionId != 'admin' && $actionId != 'edit') {
        $model = call_user_func(array($this->modelClass, 'model'));
        foreach($model->attributeNames() as $attribute) {
          empty($_GET[$attribute]) or $parameters[$attribute] = $_GET[$attribute];
        }
        $primaryKey = $this->model->metaData->tableSchema->primaryKey;
        if($actionId == 'add') unset($parameters[$primaryKey]);
      }


      switch($actionId) {
        case 'delete':
          $actionId = 'edit';
          $url = $this->createUrl($controllerId.'/'.$actionId, $parameters);
          return CHtml::$method($this->getActionLabel($actionId, $labelled, 'Delete'), array(
                'confirm'=>"Are you sure you want to delete this object?",
                'title' => 'Delete',
                'ajax' => array(
                  'url' => $url,
                  'type' => 'DELETE',
                  'success' => 'handleSuccess',
                  'error' => 'handleError',
                  ),
                ));
        case 'admin':
          $path = $this->assetPath.'/images/controllers/'.$icon_size.'/'.$controllerId.'.png';
          $src = file_exists($path) ? $this->assetPath.'/images/controllers/'.$icon_size.'/'.$controllerId.'.png' : null;
        default:
          /*
          if($actionId != 'add' && $actionId != 'admin') {
            if(!empty($this->id)) {
              empty($parameters[$this->modelClass]) and $parameters[$this->modelClass] = array();
              $parameters[$this->modelClass]['id'] = $this->id;
            }
          }
          */

          $url = $this->createUrl($controllerId.'/'.$actionId, $parameters);

          if($method == 'link') {
            return CHtml::$method($this->getActionLabel($actionId, $labelled, $title, $src), $url, array('title' => $this->humanize($actionId)));
          } else {
            return CHtml::$method($this->getActionLabel($actionId, $labelled, $title, $src), array('title' => $this->humanize($actionId), 'onclick' => 'location.href='.CJavaScript::encode($url)));
          }
      }
    }
  }

  public function getActionLabel($actionId, $labelled = true, $title = null, $src = null) {
    empty($title) and $title = $this->humanize($actionId);

    empty($src) and $src = $this->assetPath.'/images/actions/'.$actionId.'.png';

    $html = CHtml::image($src, $title, array('class' => $actionId, 'border' => 0));
    $labelled and $html .= ' '.$title;
    return $html;
  }

  public function getControllerId($className)
  {
    $controllerId = strtolower($className[0]).substr($className, 1);
    return $controllerId;
  }

  public function getRelations($object)
  {
    $relations = array(CActiveRecord::HAS_MANY => array(), CActiveRecord::BELONGS_TO => array());

    foreach($object->metaData->relations as $relation) {
      $primaryKey = $object->metaData->tableSchema->primaryKey;
      $controllerId = $this->getControllerId($relation->className);
      $foreignModel = $this->getModelByClass($relation->className);

      switch(get_class($relation)) {
        case 'CBelongsToRelation':
        case 'CHasOneRelation':
          if(empty($object->{$relation->foreignKey}))
            continue;

          $parameters = array(
            $foreignModel->metaData->tableSchema->primaryKey => $object->{$relation->foreignKey},
            'returnUrl' => $_SERVER['REQUEST_URI']
          );

          $link = Yii::app()->controller->createUrl("/$controllerId/edit", $parameters);

          $path = dirname(__FILE__).'/assets/images/models/32x32/'.$relation->className.'.png';
          $icon = file_exists($path) ? $this->assetPath.'/images/models/32x32/'.$relation->className.'.png' : $this->assetPath.'/images/actions/admin.png';

          $relations[CActiveRecord::BELONGS_TO][$relation->name] = compact('relation', 'link', 'icon');
          break;
        case 'CHasManyRelation':
          foreach($object->metaData->relations as $foreignName => $foreignRelation) {
            if($foreignRelation->className == get_class($object) && is_a($foreignRelation, 'CBelongsToRelation')) {
              if(!empty($foreignRelation->condition)) {
                $object->getDbCriteria()->mergeWith($foreignRelation);
                $result = $object->filtered()->find();
                if(empty($result)) continue 2;
              }
            }
          }

          $parameters = array(
            $relation->foreignKey => $object->$primaryKey
          );

          $link = $this->createUrl("/$controllerId/admin", $parameters);

          $path = dirname(__FILE__).'/assets/images/models/32x32/'.$relation->className.'.png';
          $icon = file_exists($path) ? $this->assetPath.'/images/models/32x32/'.$relation->className.'.png' : $this->assetPath.'/images/actions/admin.png';

          $relations[CActiveRecord::HAS_MANY][$relation->name] = compact('relation', 'link', 'icon');
          break;
        default:
          var_dump("Unhandled relation $relation");
          continue;
      }
    }

    return $relations;
  }

  public function humanize($string)
  {
    return ucfirst(str_replace('_', ' ', $string));
  }
}
