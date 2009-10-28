<?php
$cs = Yii::app()->getClientScript();

$cs->registerScriptFile($this->assetPath.'/js/crud.js');
$cs->registerCssFile($this->assetPath.'/css/modules/crud.css');

$controllerId = Yii::app()->controller->getControllerId(get_class($model));
?>
<div class="crud">
  <div class="header"><h2>Manage <?= $this->humanize(Inflect::pluralize($model->classTitle)) ?></h2></div>

  <div class="actionBar">
    <?= $this->getActionLink(array("$controllerId/index")) ?>
    <?= $this->getActionLink(array("$controllerId/add")) ?>
    <div class="pager"><? $this->widget('CLinkPager',array('pages'=>$pages)) ?></div>
  </div>

  <table class="manage">
    <thead>
    <tr>
      <? foreach($criteria->select as $attribute): ?>
        <?= CHtml::tag('th', array(), $sort->link($attribute, @$labels[$attribute])) ?>
      <? endforeach ?>
      <th></th>
    </tr>
    </thead>
    <tbody>
    <? $this->renderAdmin($controllerId, $criteria, $objects, $sort) ?>
    </tbody>
  </table>
</div>
