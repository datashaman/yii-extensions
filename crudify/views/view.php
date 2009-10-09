<?
$cs = Yii::app()->getClientScript();
$am = Yii::app()->getAssetManager();
$css = $am->publish(dirname(__FILE__).'/../assets/css/crud.css');
$cs->registerCssFile($css);

$this->pageTitle = 'View '.$this->object->name;
?>
<div class="header">
  <h1><?= $this->pageTitle ?></h1>

  <div id="actions">
    <?= $this->object->getActionLink('admin') ?>
    <?= $this->object->getActionLink('add') ?>
    <?= $this->object->getActionLink('edit') ?>
    <?= $this->object->getActionLink('delete') ?>
  </div>
</div>

<div class="content">
  <? if(empty($view)):
       if(empty($columns)):
         $columns = $this->object->metaData->columns;
         unset($columns['id']);
       endif;
  ?>

  <div class="view">
    <ul>
    <? foreach($columns as $column): ?>
      <li class="row">
        <span class="label"><label><?= $this->object->getAttributeLabel($column->name) ?></label></span>
        <span class="value"><? $this->object->renderAttributeElement($column->name) ?></span>
      </li>
    <? endforeach ?>
    </ul>
  </div>
  <? else:
       $this->renderPartial($view);
     endif
  ?>
</div>
