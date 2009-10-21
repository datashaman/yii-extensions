<?php
class CrudActionDelete extends CrudActionBase
{
  public function run()
  {
    $count = $this->model->filtered()->count();
    if($this->model->filtered()->count() == 1) {
      $object = $this->model->filtered()->find();
      if(empty($object)) {
        throw new CHttpException(404, 'Object not found. Perhaps it has already been deleted?');
      } else {
        var_dump($object);
        die;
        if($object->delete()) {
          echo 'Object deleted successfully';
        } else {
          throw new CHttpException(500, 'Object delete failed');
        }
      }
    } else {
      throw new CHttpException(500, 'Trying to delete more than 1 object');
    }
  }
}
