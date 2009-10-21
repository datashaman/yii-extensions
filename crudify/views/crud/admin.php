<?php
$cs = Yii::app()->getClientScript();

$cs->registerScriptFile($this->assetPath.'/js/crud.js');
$cs->registerCssFile($this->assetPath.'/css/crud.css');

$controllerId = Yii::app()->controller->getControllerId(get_class($model));
?>
<div class="crud">
  <div class="header"><h2>Manage <?= $this->humanize(Inflect::pluralize($model->classTitle)) ?></h2></div>

  <div class="actionBar">
    <?= $this->getActionLink(array("$controllerId/index")) ?>
    <?= $this->getActionLink(array("$controllerId/add")) ?>
    <div class="pager"><? $this->widget('CLinkPager',array('pages'=>$pages)) ?></div>
  </div>

  <? if(empty($objects)): ?>
  No records
  <? else: ?>
  <? $select = $criteria->select ?>
  <table class="admin">
    <thead>
    <tr>
      <? foreach($select as $attribute): ?>
        <?= CHtml::tag('th', array(), $sort->link($attribute)) ?>
      <? endforeach ?>
      <th></th>
    </tr>
    </thead>
    <tbody>
    <? $this->renderAdmin($controllerId, $criteria, $objects, $sort) ?>
    </tbody>
  </table>
  <? endif ?>
</div>
