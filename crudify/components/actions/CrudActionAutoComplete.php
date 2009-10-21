<?php
class CrudActionAutoComplete extends CrudActionBase
{
  public function run()
  {
    $attribute = $_GET['attribute'];

    $relationName = preg_replace('/_id$/', '', $attribute);
    $relation = @$this->model->metaData->relations[$relationName];
    if(empty($relation)) {
      $primaryKey = $this->model->metaData->tableSchema->primaryKey;

      $id = $primaryKey == $attribute ? $primaryKey : $attribute;

      $q = $_GET['q'];
      $criteria = array(
        'select' => "$id as id, $attribute as name",
        'condition' => "$attribute like :q and ($attribute is not null or $attribute = '')",
        'params' => array(":q" => "%$q%"),
        'order' => "$attribute asc",
      );

      $model = $this->model;
    } else {
      if(is_a($relation, 'CBelongsToRelation')) {
        $thisTable = $this->model->tableName();

        $foreignModel = $this->controller->module->getModel($relation->className);
        $foreignTable = $foreignModel->tableName();

        $criteria = array(
          'condition' => "{$foreignTable}.name like :q",
          'params' => array('q' => '%'.$_GET['q'].'%'),
          'limit' => min(empty($_GET['limit']) ? 50 : $_GET['limit'], 50),
          'join' => "inner join $thisTable on $thisTable.{$relation->foreignKey} = {$foreignTable}.id",
        );

        empty($relation->condition) or $criteria['condition'] .= ' and '.$relation->condition;

        $model = $foreignModel;
      }
    }

    if(isset($model, $criteria)) {
      $result = '';
      foreach($model->options($criteria)->findAll() as $object) {
        $row = array_values($object->attributes);
        $result .= $row[1].'|'.$row[0]."\n";
      }
      echo $result;
    }
  }
}
