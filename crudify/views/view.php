<div class="view">
  <div class="header">
    <h2>View <?= $this->object->name ?></h2>

    <div id="actions">
      <?= $this->object->getActionLink('admin') ?>
      <?= $this->object->getActionLink('add') ?>
      <?= $this->object->getActionLink('edit') ?>
      <?= $this->object->getActionLink('delete') ?>
    </div>
  </div>

  <div class="content">
    <? if(empty($view)):
         empty($columns) and $columns = $this->object->metaData->columns; ?>

    <div class="view">
      <ul>
      <? foreach($columns as $column): ?>
        <li class="attribute">
          <span class="label"><?= $this->object->getAttributeLabel($column->name) ?></span>
          <span class="value"><? $this->object->renderAttributeElement($column->name) ?></span>
        </li>
      <? endforeach ?>
    </table>
    <? else:
         $this->renderPartial($view);
       endif
    ?>
  </div>
</div>
