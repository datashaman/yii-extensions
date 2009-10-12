<?php
$cs = Yii::app()->getClientScript();
$am = Yii::app()->getAssetManager();
$css = $am->publish(dirname(__FILE__).'/../assets/css/crud.css');
$cs->registerCssFile($css);

require_once 'Inflect.php';
$this->pageTitle = 'Managing '.Inflect::pluralize(get_class($this->object));

$routeVar = Yii::app()->getUrlManager()->routeVar;
?>
<div class="header">
  <h1><?= $this->pageTitle ?></h1>
  <div id="actions">
    <?= $this->object->getActionLink('add') ?>
    <? $this->widget('CLinkPager',array('pages'=>$pages)) ?>
  </div>
</div>

<div class="content">
  <?= CHtml::beginForm($_SERVER['PHP_SELF'], 'GET') ?>
  <?= CHtml::hiddenField($routeVar, $_REQUEST[$routeVar]) ?>
  <?= CHtml::hiddenField('model', $_REQUEST['model']) ?>
  <table class="admin">
    <thead>
    <tr>
      <? $filterAttributes = $this->getFilterAttributes();
        foreach($attributes as $attribute): ?>
        <th><?
        if(in_array($attribute, $filterAttributes)) $this->renderFilterWidget($attribute);
        ?></th>
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
      <? $object->attachBehavior('crudify', 'application.extensions.ds.crudify.CrudBehavior') ?>
      <tr class="<?= $n % 2 ? 'odd' : 'even' ?>">
        <? foreach($attributes as $attribute): ?>
        <td><? $object->renderAttributeElement($attribute) ?></td>
        <? endforeach ?>
        <td class="actions">
          <? foreach(array('view', 'edit', 'delete') as $action): ?>
          <?= $object->getActionLink($action, false) ?>
          <? endforeach ?>
        </td>
      </tr>
    <? endforeach ?>
    </tbody>
  </table>
  <?= CHtml::endForm() ?>
  <br/>
</div>
