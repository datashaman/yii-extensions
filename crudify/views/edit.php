<?
$cs = Yii::app()->getClientScript();
$am = Yii::app()->getAssetManager();
$css = $am->publish(dirname(__FILE__).'/../assets/css/crud.css');
$cs->registerCssFile($css);

$this->pageTitle = ($object->isNewRecord ? 'Add' : 'Edit').' '.($object->isNewRecord ? get_class($object) : $object->name);
?>
<div class="crud">
  <div class="header"><h1><?= $this->pageTitle ?></h1></div>

  <div class="actionBar">
    <ul>
      <li><?= $object->getActionLink('admin') ?></li>
      <? if(!empty($object->id)): ?>
      <li><?= $object->getActionLink('view') ?></li>
      <? endif ?>
    </ul>
  </div>

  <div class="layout">
    <ul>
      <li class="edit"><?= $form->render() ?></li>
      <? if($object->id): ?>
        <?= $this->renderPartial('related', array('object' => $object)) ?>
      <? endif ?>
    </ul>
  </div>
</div>
