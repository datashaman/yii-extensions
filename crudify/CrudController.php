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

  public $form;

  public function init()
  {
    $this->criteria = new CDbCriteria();
    $css = Yii::app()->getAssetManager()->publish(dirname(__FILE__).'/assets/css/crud.css');
    Yii::app()->getClientScript()->registerCssFile($css);

    empty($this->form) and $this->form = array(
      'showErrorSummary' => true,
      'buttons' => array(
        'save' => array('type' => 'submit', 'label' => 'Save'),
      )
    );

    if(empty($_REQUEST['model'])) {
      throw new CHttpException(400, "I don't know which model you want to manage. Please specify the 'model' parameter.");
    } else {
      $this->object = new $_REQUEST['model'];
    }
  }

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete',
			'type',
			'identifier + view, edit, delete',
		);
	}

  public function filterType($filterChain)
  {
    if(!empty($_REQUEST['type'])) {
      $type = Type::model()->findBySlug($_REQUEST['type']);
      $this->criteria->addCondition('type_id = '.$type->id);
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
				'actions'=>array('add','edit'),
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
    $this->object->delete();
    $this->redirect(array('admin', 'model' => $_REQUEST['model']));
	}

	public function actionAdmin()
	{
		$pages = new CPagination($this->object->model()->count());
		$pages->pageSize = $this->pageSize;
    $pages->applyLimit($this->criteria);

		$sort=new CSort(get_class($this->object));
    $sort->applyOrder($this->criteria);

		$objects = $this->object->model()->findAll($this->criteria);

    $columns = method_exists($this->object, 'getAdminAttributes') ? $this->object->getAdminAttributes() : array_keys($this->object->attributes);

		$this->render('admin', compact('columns', 'objects', 'pages', 'sort'));
	}

	protected function getRelatedLinks() {
    $links = array();
    foreach($this->object->metaData->relations as $name => $relation) {
      if(is_a($relation, 'CHasManyRelation')) {
        $controller = strtolower($relation->className);
        $links[] = $this->getRelatedLink($name, ucfirst($name), array($relation->foreignKey => $this->object->id));
      }
    }
    return $links;
  }

  protected function getRelatedLink($controller, $title, $condition) {
    $parameters = um()->createUrl("$controller/list", $condition);
    return CHtml::link($this->getActionLabel('admin', true, $title), $parameters, compact('title'));
  }

  protected function publishAsset($path)
  {
    $asset = join(DIRECTORY_SEPARATOR, array(app()->basePath, $path));
    return Yii::app()->getAssetManager()->publish($asset);
  }

  protected function pluralize($string)
  {
    $overrides = array(
      'person' => 'people',
      'company' => 'companies',
      'security-company' => 'security-companies',
    );
    return ucfirst(empty($overrides[strtolower($string)]) ? CConsoleCommand::pluralize($string) : $overrides[strtolower($string)]);
  }
}
