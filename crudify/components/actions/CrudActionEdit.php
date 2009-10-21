<?php
class CrudActionEdit extends CrudActionBase
{
  public $defaultValues = array();

  public function run()
  {
    $object = $this->controller->getObject();

    // JEditable
    if(isset($_POST['attribute'], $_POST['value'])) {
      $attribute = preg_replace('/^.*_/', '', $_POST['attribute']);
      $this->object->$attribute = $_POST['value'];
      if($this->object->save()) {
        echo $_POST['value'];
        exit;
      }
    } else {
      switch($_SERVER['REQUEST_METHOD']) {
        case 'DELETE':
          if($object->delete()) {
            echo 'Object deleted successfully';
          } else {
            throw new CHttpException(500, 'Object delete failed');
          }
          break;

        case 'GET':
        case 'POST':
          $form = new CrudForm($object->formConfig, $object, $this->controller);
          if($form and $form->submitted('save')) {
            try {
              $object->save();
              Yii::app()->getUser()->setFlash('success', 'Object saved successfully');
              $this->controller->returnTo(array('admin'));
            } catch(CDbException $e) {
              Yii::app()->getUser()->setFlash('error', 'There was an error saving the object:<br/><br/>'.$e->getMessage());
            }
          }

          foreach($form->elements as $element)
            $element->layout = '<div class="label">{label}</div> <div class="value">{input}{error}</div>{hint}';

          $this->controller->render('edit', array('form' => $form, 'object' => $object));
          break;
      }
    }
  }
}
