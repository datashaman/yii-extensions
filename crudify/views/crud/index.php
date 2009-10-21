<?php
$cs = Yii::app()->getClientScript();

$cs->registerScriptFile($this->assetPath.'/js/crud.js');
$cs->registerCssFile($this->assetPath.'/css/crud.css');

require_once 'Inflect.php';
$this->pageTitle = 'Listing '.Inflect::pluralize($model->classTitle);
?>
<div class="crud">
  <div class="header"><h2><?= $this->pageTitle ?></h2></div>

  <div class="actionBar">
    <?= $this->getActionLink(array('admin')) ?>
    <?= $this->getActionLink(array('add')) ?>
    <div class="pager"><? $this->widget('CLinkPager',array('pages'=>$pages)) ?></div>
  </div>

  <table class="admin">
    <thead>
    <tr class="filters">
      <?= CHtml::beginForm(null, 'get') ?>

      <? foreach($attributes as $attribute): ?>
        <th><? $this->widget('crud.components.widgets.CrudFilterWidget', array(
          'url' => $this->createUrl('autoComplete'),
          'model' => $model,
          'attribute' => $attribute,
          'max' => 10,
          'minChars' => 0,
          'delay' => 500,
          'matchCase' => false,
          'mustMatch' => true,
          'extraParams' => array('attribute' => $attribute),
          'htmlOptions' => array('class' => 'filter'),
        )) ?></th>
      <? endforeach ?>

      <?= CHtml::endForm() ?>
    </tr>
    <tr>
      <? foreach($attributes as $attribute): ?>
        <?= CHtml::tag('th', array(), $sort->link($attribute)) ?>
      <? endforeach ?>
    </tr>
    </thead>
    <tbody>
    <? foreach($objects as $n=>$object): ?>
      <tr class="<?= $n % 2 ? 'odd' : 'even' ?>">
        <? foreach($attributes as $attribute): ?>
        <td><? $object->renderProperty($attribute) ?></td>
        <? endforeach ?>
      </tr>
    <? endforeach ?>
    </tbody>
  </table>
</div>
