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
	protected $object;
	protected $criteria;
  
  protected $filterWidgets = array();

  public function init()
  {
    $this->criteria = new CDbCriteria();

    $cs = Yii::app()->getClientScript();
    $am = Yii::app()->getAssetManager();

    $js = $am->publish(dirname(__FILE__).'/assets/js/crud.js');
    $cs->registerScriptFile($js);

    if(empty($_REQUEST['model'])) {
      throw new CHttpException(400, "I don't know which model you want to manage. Please specify the 'model' parameter.");
    } else {
      $this->object = new $_REQUEST['model'];
      $this->object->attachBehavior('crudify', 'application.extensions.ds.crudify.CrudBehavior');
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
			'identifier + view, edit, delete',
			'filters',
      //'addFilters',
		);
    return $filters;
	}

  protected function renderFilterWidget($attribute)
  {
    foreach($this->object->metaData->relations as $property => $relation) {
      if($relation->foreignKey == $attribute && get_class($relation) == 'CBelongsToRelation') {
        $foreignClass = $relation->className;
        break;
      }
    }

    if(!empty($foreignClass)) {
      $class = get_class($this->object);

      $value = '';
      if(!empty($_GET[$class][$attribute])) {
        $this->object->$attribute = $_GET[$class][$attribute];
        $value = $this->object->$property->name;
      }

      $hiddenId = CHtml::activeId($this->object, $attribute);

      $this->widget('CAutoComplete', array(
        'id' => 'filter_'.$attribute,
        'name' => 'q',
        'url' => $this->createUrl('crud/autoComplete'),
        'max' => 10,
        'minChars' => 1,
        'delay' => 500,
        'matchCase' => false,
        'mustMatch' => true,
        'value' => $value,
        'extraParams' => array('model' => $_GET['model'], 'relation' => $property),
        'htmlOptions' => array('class' => 'filter', 'onchange' => 'if(jQuery("#filter_'.$attribute.'").val() == "") jQuery("#'.$hiddenId.'").val("");'),
        'methodChain' => '.result(function(event,item){ jQuery("#'.$hiddenId.'").val(item[1]); this.form.submit(); })',
      ));
      echo CHtml::activeHiddenField($this->object, $attribute);

      if(!empty($_GET[$class][$attribute])) {
        echo CHtml::imageButton('/images/icons/actions/cross.png', array('alt' => 'Remove filter', 'title' => 'Remove filter', 'onclick' => 'jQuery("#'.$hiddenId.'").val("")'));
      }
    }
  }

  protected function getFilterAttributes()
  {
    $filterAttributes = $this->getModelConfig('filterAttributes');
    is_null($filterAttributes) and $filterAttributes = $this->getModelConfig('adminAttributes');
    is_null($filterAttributes) and $filterAttributes = array_keys($this->object->attributes);
    return $filterAttributes;
  }

  public function filterFilters($filterChain)
  {
    foreach($this->getFilterAttributes() as $attribute) {
      $model = get_class($this->object);
      if(!empty($_GET[$model][$attribute])) {
        $this->criteria->addCondition("$attribute = {$_GET[$model][$attribute]}");
      }
    }

    return $filterChain->run();
  }

  public function filterIdentifier($filterChain)
  {
    if(empty($_REQUEST['id'])) {
      throw new CHttpException(400, "I don't know which object you want to edit. Please specify the 'id' parameter.");
    } else {
      $this->object = $this->object->model()->findByPk($_REQUEST['id']);

      if(!$this->object)
        throw new CHttpException(404, "I cannot find an object with that identifier. Perhaps it has been deleted?");

      $this->object->attachBehavior('crudify', 'application.extensions.ds.crudify.CrudBehavior');
    }

    return $filterChain->run();
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

    Yii::app()->getClientScript()->registerScript('view', "$('div.view .label').autoWidth();");

		$this->render('view', array('view' => $view));
	}

  public function actionEdit()
  {
    if(isset($_POST['attribute'], $_POST['value'])) {
      $attribute = preg_replace('/^.*_/', '', $_POST['attribute']);
      $this->object->$attribute = $_POST['value'];
      if($this->object->save()) {
        echo $_POST['value'];
        exit;
      }
    } else {
      $config = file_exists(Yii::getPathOfAlias('application.forms.'.$_REQUEST['model']).'.php') ? 'application.forms.'.$_REQUEST['model'] : null;

      if($form = new CrudForm($config, $this->object, $this)
        and $form->submitted('save')
        and $this->object->save()) {
        $route = array('view');
        $route['id'] = $this->object->id;
        $route['model'] = $_REQUEST['model'];

        Yii::app()->getUser()->setFlash('success', 'Object saved successfully');
        $this->redirect($route);
      }

      foreach($form->elements as $element)
        $element->layout = '<div class="label">{label}</div> <div class="value">{input}{error}</div>{hint}';

      Yii::app()->getClientScript()->registerScript('edit', "$('div.edit .label').autoWidth(); var width = $('div.edit .label:eq(0)').width(); $('div.edit .buttons').css('margin-left', (width + 6) + 'px');");

      $this->render('edit', array('form' => $form));
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
    $this->object->deleted_at = date('Y-m-d H:i:s');
    $this->object->save();
    $this->redirect(array('admin', $_GET));
	}

	public function actionAdmin()
	{
		$pages = new CPagination($this->object->model()->count());
		$pages->pageSize = $this->pageSize;
    $pages->applyLimit($this->criteria);

		$sort=new CSort(get_class($this->object));
    $sort->applyOrder($this->criteria);

		$objects = $this->object->model()->findAll($this->criteria);

    $attributes = $this->getModelConfig('adminAttributes');

		$this->render('admin', compact('attributes', 'objects', 'pages', 'sort'));
	}

  public function actionAutoComplete()
  {
    if(isset($_GET['relation'], $_GET['q'])) {
      foreach($this->object->metaData->relations as $property => $relation) {
        if($property == $_GET['relation'] && is_a($relation, 'CBelongsToRelation')) {
          $criteria = new CDbCriteria();
          $criteria->addCondition("name like :q");
          empty($relation->condition) or $criteria->addCondition($relation->condition);
          $criteria->params = array(':q' => "%{$_GET['q']}%");
          $criteria->limit = min(empty($_GET['limit']) ? 50 : (int) $_GET['limit'], 50);

          $foreignObject = new $relation->className;

          $result = '';
          foreach($foreignObject->model()->findAll($criteria) as $object) {
            $result .= $object->getAttribute('name').'|'.$object->getAttribute('id')."\n";
          }

          echo $result;
          break;
        }
      }
    }
  }

  private function getModelConfig($attribute)
  {
    static $configs = array();

    if(!isset($configs[$attribute])) {
      $config = null;

      if(method_exists($this->object, 'get'.ucfirst($attribute))) {
        $config = call_user_func(array($this->object, 'get'.ucfirst($attribute)));
      } else if(property_exists($this->object, $attribute)) {
        $config = $this->object->$attribute;
      }

      $configs[$attribute] = $config;
    }

    return $configs[$attribute];
  }

	protected function getRelatedLinks()
  {
    $links = array();
    foreach($this->object->metaData->relations as $name => $relation) {
      if(is_a($relation, 'CHasManyRelation')) {
        $controller = strtolower($relation->className);
        $links[] = $this->getRelatedLink($name, ucfirst($name), array($relation->foreignKey => $this->object->id));
      }
    }
    return $links;
  }

  protected function getRelatedLink($controller, $title, $condition)
  {
    $parameters = um()->createUrl("$controller/list", $condition);
    return CHtml::link($this->getActionLabel('admin', true, $title), $parameters, compact('title'));
  }
}
