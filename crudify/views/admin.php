<?php
$cs = Yii::app()->getClientScript();
$am = Yii::app()->getAssetManager();
$css = $am->publish(dirname(__FILE__).'/../assets/css/crud.css');
$cs->registerCssFile($css);

require_once 'Inflect.php';
$this->pageTitle = 'Managing '.Inflect::pluralize(get_class($this->object));

$routeVar = Yii::app()->getUrlManager()->routeVar;
?>
<div class="crud">
  <div class="header"><h1><?= $this->pageTitle ?></h1></div>

  <div class="actionBar">
    <?= $this->_model->getActionLink('add') ?>
    <? $this->widget('CLinkPager',array('pages'=>$pages)) ?>
  </div>

  <?= CHtml::beginForm($_SERVER['PHP_SELF'], 'GET') ?>
  <?= CHtml::hiddenField($routeVar, $_REQUEST[$routeVar]) ?>
  <?= CHtml::hiddenField('model', $_REQUEST['model']) ?>
  <table class="admin">
    <thead>
    <tr class="filters">
      <? foreach($attributes as $attribute): ?>
        <th><?  $this->renderFilterWidget($attribute) ?></th>
      <? endforeach ?>
      <th></th>
    </tr>
    <tr>
      <? foreach($attributes as $attribute): ?>
        <?= CHtml::tag('th', array(), $sort->link($attribute)) ?>
      <? endforeach ?>
      <th></th>
    </tr>
    </thead>
    <tbody>
    <? foreach($objects as $n=>$object): ?>
      <tr class="<?= $n % 2 ? 'odd' : 'even' ?>">
        <? foreach($attributes as $attribute): ?>
        <td><? $object->renderAttributeElement($attribute) ?></td>
        <? endforeach ?>
        <td class="actions">
          <? foreach(array('view', 'edit', 'delete') as $action):
            echo $object->getActionLink($action, false);
          endforeach ?>
        </td>
      </tr>
    <? endforeach ?>
    </tbody>
  </table>
  <?= CHtml::endForm() ?>
</div>
