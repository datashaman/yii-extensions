<?
$cs = Yii::app()->getClientScript();
$am = Yii::app()->getAssetManager();
$css = $am->publish(dirname(__FILE__).'/../assets/css/crud.css');
$cs->registerCssFile($css);

$this->pageTitle = 'View '.$object->name;
?>
<div class="crud">
  <div class="header"><h1><?= $this->pageTitle ?></h1></div>

  <div class="actionBar">
    <ul>
      <li><?= $object->getActionLink('admin') ?></li>
      <li><?= $object->getActionLink('add') ?></li>
      <li><?= $object->getActionLink('edit') ?></li>
      <li><?= $object->getActionLink('delete') ?></li>
    </ul>
  </div>

  <? if(empty($view)):
       if(empty($columns)):
         $columns = $object->metaData->columns;
         unset($columns['id']);
       endif;
  ?>

  <div class="layout">
    <ul>
      <li class="view">
        <ul>
        <? foreach($columns as $column): ?>
          <li class="row">
            <span class="label"><label><?= $object->getAttributeLabel($column->name) ?></label></span>
            <span class="value"><? $object->renderAttributeElement($column->name) ?></span>
          </li>
        <? endforeach ?>
        </ul>
      </li>
      <?= $this->renderPartial('related', array('object' => $object)) ?>
    </ul>
  </div>
  <? else: $this->renderPartial($view); endif ?>
</div>
