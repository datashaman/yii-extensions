<?
$cs = Yii::app()->getClientScript();

$cs->registerCssFile($this->assetPath.'/css/crud.css');
$cs->registerScriptFile($this->assetPath.'/js/crud.js');
$cs->registerScript('edit', "$('.form .label').autoWidth();");

$this->pageTitle = ($object->isNewRecord ? 'Add' : 'Edit').' '.$object->classTitle;
if(!$object->isNewRecord) $this->pageTitle .= ': '.$object->name;
?>
<div class="crud">
  <div class="header"><h2><?= $this->pageTitle ?></h2></div>

  <div class="actionBar">
    <ul>
      <li><?= $this->getActionLink(array('admin')) ?></li>
      <? if(!$object->isNewRecord): ?>
      <li><?= $this->getActionLink(array('delete')) ?></li>
      <li><?= $this->getActionLink(array('add')) ?></li>
      <? endif ?>
    </ul>
  </div>

  <div class="layout">
    <ul>
      <li class="form"><?= $form->render() ?></li>
      <? if(!$object->isNewRecord): ?>
        <?= $this->renderPartial('related', array('object' => $object)) ?>
      <? endif ?>
    </ul>
  </div>
</div>
