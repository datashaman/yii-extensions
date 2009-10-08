<div class="edit">
  <div class="header">
    <h2>New <?= get_class($this->object) ?></h2>

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
