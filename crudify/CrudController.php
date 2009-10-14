<?php

class CrudController extends CController
{
  /**
   * @var string specifies the default page size to be 10
   */
  public $pageSize = 10;

  /**
   * @var string specifies the default action to be 'admin'.
   */
  public $defaultAction='admin';

  /**
   * @var CActiveRecord the currently loaded data model instance.
   */
  protected $_model;

  protected $criteria;

  private $_object;

  protected $filterWidgets = array();

  public function init()
  {
    parent::init();

    $cs = Yii::app()->getClientScript();
    $am = Yii::app()->getAssetManager();

    $js = $am->publish(dirname(__FILE__).'/assets/js/crud.js');
    $cs->registerScriptFile($js);

    empty($this->_model) && $this->model = empty($_REQUEST['model']) ? null : $_REQUEST['model'];
  }

  protected function getObject()
  {
    if(empty($this->_object)) {
      $class = get_class($this->_model);
      $this->_object = new $class;
      empty($_GET[$class]) or $this->_object->setAttributes($_GET[$class]);
    }
    return $this->_object;
  }

  protected function setModel($className)
  {
    if(empty($className)) {
      throw new CHttpException(400, "I don't know which class of model you want to manage. Please specify the model's class as the 'className' property in your controller configuration or as a query parameter.");
    } else {
      $this->_model = CrudModel::model($className);
    }
  }

  /**
   * @return array action filters
   */
  public function filters()
  {
    $filters = array(
        'accessControl', // perform access control for CRUD operations
        'postOnly + delete',
        //'ajaxOnly + autoComplete',
        //'identifier + view, edit, delete',
        //'filters',
        //'addFilters',
        );
    return $filters;
  }

  protected function renderFilterWidget($attribute)
  {
    $column = $this->_model->metaData->columns[$attribute];
    $size = min($column->size, 20);

    $config = array(
        'id' => 'filter_'.$attribute,
        'url' => $this->createUrl('crud/autoComplete'),
        'max' => 10,
        'minChars' => 0,
        'delay' => 500,
        'matchCase' => false,
        'extraParams' => array('model' => $_GET['model']),
        'htmlOptions' => array('class' => 'filter', 'size' => $size, 'maxlength' => $column->size),
        );

    $thisClass = get_class($this->_model);

    if(empty($this->_model->metaData->tableSchema->foreignKeys[$attribute])) {
      $config['name'] = $thisClass.'['.$attribute.']';
      $config['mustMatch'] = false;
      $config['extraParams']['attribute'] = $attribute;
      empty($_GET[$thisClass][$attribute]) or $config['value'] = $_GET[$thisClass][$attribute];
      $config['methodChain'] = '.result(function(event,item){ this.form.submit(); })';
      $this->widget('CAutoComplete', $config);
      $hiddenId = $config['id'];
    } else {
      foreach($this->_model->metaData->relations as $relation) {
        if($relation->foreignKey == $attribute && get_class($relation) == 'CBelongsToRelation') {
          $foreignClass = $relation->className;
          break;
        }
      }

      if(!empty($foreignClass)) {
        $config['name'] = 'q';
        $config['mustMatch'] = true;
        $config['extraParams']['relation'] = $relation->name;

        if(!empty($_GET[$thisClass][$attribute])) {
          $this->getObject()->$attribute = $_GET[$thisClass][$attribute];
          $config['value'] = $this->getObject()->{$relation->name}->name;
        }

        $hiddenId = CHtml::activeId($this->object, $attribute);
        $config['htmlOptions']['onchange'] = 'if(jQuery("#filter_'.$attribute.'").val() == "") jQuery("#'.$hiddenId.'").val("");';
        $config['methodChain'] = '.result(function(event,item){ jQuery("#'.$hiddenId.'").val(item[1]); this.form.submit(); })';

        $this->widget('CAutoComplete', $config);
        echo CHtml::activeHiddenField($this->object, $attribute);

      }
    }

    if(!empty($_GET[$thisClass][$attribute])) {
        echo CHtml::htmlButton($this->object->getActionLink('cross', false), array('type' => 'submit', 'alt' => 'Remove filter', 'title' => 'Remove filter', 'onclick' => 'jQuery("#'.$hiddenId.'").val("")'));
    }
  }

  public function accessRules()
  {
    return array(
        array('allow',
          'actions'=>array('view'),
          'users'=>array('*'),
          ),
        array('allow',
          'actions'=>array('add','edit','autoComplete'),
          'users'=>array('@'),
          ),
        array('allow',
          'actions'=>array('admin','delete'),
          'users'=>array('admin'),
          ),
        array('deny',
          'users'=>array('*'),
          ),
        );
  }

  public function actionView()
  {
    $folder = strtolower($_REQUEST['model'][0]).substr($_REQUEST['model'], 1);
    $view_file = $this->getViewPath()."/../$folder/view.php";
    $view = file_exists($view_file) ? "/$folder/view" : null;

    Yii::app()->getClientScript()->registerScript('view', "$('.view .label').autoWidth();");

    $object = $this->_model->filtered()->find();

    $this->render('view', array('object' => $object, 'view' => $view));
  }

  public function actionEdit()
  {
    $object = $this->_model->filtered()->find();

    if(isset($_POST['attribute'], $_POST['value'])) {
      $attribute = preg_replace('/^.*_/', '', $_POST['attribute']);
      $object->$attribute = $_POST['value'];
      if($object->save()) {
        echo $_POST['value'];
        exit;
      }
    } else {
      $config = file_exists(Yii::getPathOfAlias('application.forms.'.$_REQUEST['model']).'.php') ? 'application.forms.'.$_REQUEST['model'] : null;

      if($form = new CrudForm($config, $object, $this)
          and $form->submitted('save')
          and $object->save()) {
        $route = array('admin');
        $route['model'] = $_REQUEST['model'];

        Yii::app()->getUser()->setFlash('success', 'Object saved successfully');
        $this->redirect($route);
      }

      foreach($form->elements as $element)
        $element->layout = '<div class="label">{label}</div> <div class="value">{input}{error}</div>{hint}';

      Yii::app()->getClientScript()->registerScript('edit', "$('.edit .label').autoWidth(); var width = $('.edit .label:eq(0)').width();");

      $this->render('edit', array('form' => $form, 'object' => $object));
    }
  }

  public function actionAdd()
  {
    if(!empty($_REQUEST['id']))
      throw new CHttpException(400, "I don't need to know the identifier of an object if you're adding a new one.");

    $this->actionEdit();
  }

  public function actionDelete()
  {
    $object = $this->_model->find();
    $object->deleted_at = date('Y-m-d H:i:s');
    $object->save();
    $this->redirect(array('admin', $_GET));
  }

  public function actionAdmin()
  {
    $criteria = new CDbCriteria();

    $pages = new CPagination($this->_model->filtered()->count());
    $pages->pageSize = $this->pageSize;
    $pages->applyLimit($criteria);

    $sort=new CSort(get_class($this->object));
    $sort->applyOrder($criteria);

    $objects = $this->_model->filtered()->findAll($criteria);

    $attributes = $this->_model->adminAttributes;
    is_null($attributes) and $attributes = array_keys($this->_model->attributes);

    $this->render('admin', compact('attributes', 'objects', 'pages', 'sort'));
  }

  public function actionAutoComplete()
  {
    if(isset($_GET['relation'], $_GET['q'])) {
      $relation = $this->_model->metaData->relations[$_GET['relation']];
      if(is_a($relation, 'CBelongsToRelation')) {
        $thisTable = $this->_model->tableName();

        $object = new $relation->className();
        $foreignTable = $object->tableName();

        $criteria = array(
          'condition' => "{$foreignTable}.name like :q",
          'params' => array('q' => '%'.$_GET['q'].'%'),
          'limit' => min(empty($_GET['limit']) ? 50 : $_GET['limit'], 50),
          'join' => "inner join $thisTable on $thisTable.{$relation->foreignKey} = {$foreignTable}.id",
        );

        empty($relation->condition) or $criteria['condition'] .= ' and '.$relation->condition;
      }
    } else if(isset($_GET['attribute'])) {
      $attribute = $_GET['attribute'];
      $q = $_GET['q'];
      $criteria = array(
        'select' => "id, $attribute as name",
        'condition' => "$attribute like :q and ($attribute is not null or $attribute = '')",
        'params' => array(":q" => "%$q%"),
        'order' => "$attribute asc",
      );
      $object = $this->_model;
    }

    if(isset($object, $criteria)) {
      $result = '';
      foreach($object->options($criteria)->findAll() as $object) {
        $row = array_values($object->attributes);
        $result .= $row[1].'|'.$row[0]."\n";
      }

      echo $result;
    }
  }
}
