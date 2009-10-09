<?php
$cs = Yii::app()->getClientScript();
$am = Yii::app()->getAssetManager();
$css = $am->publish(dirname(__FILE__).'/../assets/css/crud.css');
$cs->registerCssFile($css);

require_once 'Inflect.php';
$this->pageTitle = 'Managing '.Inflect::pluralize(get_class($this->object));
?>
<div class="header">
  <h1><?= $this->pageTitle ?></h1>
  <div id="actions">
    <?= $this->object->getActionLink('add') ?>
  </div>
</div>

<div class="content">
  <table class="admin">
    <thead>
    <tr>
      <? foreach($columns as $column): ?>
        <?= CHtml::tag('th', array(), $sort->link($column)) ?>
      <? endforeach ?>
      <th></th>
    </tr>
    </thead>
    <tbody>
    <? foreach($objects as $n=>$object): ?>
      <? $object->attachBehavior('crudify', 'application.extensions.ds.crudify.CrudBehavior') ?>
      <tr class="<?= $n % 2 ? 'odd' : 'even' ?>">
        <? foreach($columns as $column): ?>
        <td><? $object->renderAttributeElement($column) ?></td>
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
  <br/>
</div>

<div class="footer">
  <?php $this->widget('CLinkPager',array('pages'=>$pages)); ?>
</div>
