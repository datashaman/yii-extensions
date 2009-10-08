<?
$this->pageTitle = ($this->object->isNewRecord ? 'Add' : 'Edit').' '.($this->object->isNewRecord ? get_class($this->object) : $this->object->name);
?>
<div class="edit">
  <div class="header">
    <h2>
    <?= $this->pageTitle ?>
    </h2>

    <div id="actions">
      <?= $this->object->getActionLink('admin') ?>
    </div>
  </div>

  <div class="content">
    <div class="form">
      <?= $form->render() ?>
    </div>
  </div>
</div>
