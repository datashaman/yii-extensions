<?
$cs = Yii::app()->getClientScript();
$am = Yii::app()->getAssetManager();
$css = $am->publish(dirname(__FILE__).'/../assets/css/crud.css');
$cs->registerCssFile($css);

$this->pageTitle = ($this->object->isNewRecord ? 'Add' : 'Edit').' '.($this->object->isNewRecord ? get_class($this->object) : $this->object->name);
?>
<div class="header">
  <h1><?= $this->pageTitle ?></h1>

  <div id="actions">
    <?= $this->object->getActionLink('admin') ?>
    <? empty($this->object->id) or print($this->object->getActionLink('view')) ?>
  </div>
</div>

<div class="content">
  <div class="edit">
    <?= $form->render() ?>
  </div>
</div>
