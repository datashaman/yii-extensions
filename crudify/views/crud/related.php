<?
$relations = $this->getRelations($object)
?>
<li class="relations">
  <ul>
    <?
    $index = 0;
    foreach(array(CActiveRecord::HAS_MANY, CActiveRecord::MANY_MANY, CActiveRecord::BELONGS_TO) as $type):
      $items = $relations[$type];
      foreach($items as $item):
        $class = $index%2 ? 'odd' : 'even';
        $title = empty($item['relation']->data['title']) ? $this->humanize($item['relation']->name) : $item['relation']->data['title'];
        $count = count($object->{$item['relation']->name}) ?>
        <li class="<?= $class ?>">
          <a class="icon" href="<?= $item['link'] ?>" title="<?= $title ?>"><img src="<?= $item['icon'] ?>" border="0" alt="<?= $title ?>" /></a>
          <a class="label" href="<?= $item['link'] ?>" title="<?= $title ?>"><?= $title ?></a>
          <? if($type == CActiveRecord::HAS_MANY): ?>
          <span class="value"><?= $count ?></span>
          <? else: ?>
          <? $object->renderProperty($item['relation']->name, array(), array('class' => 'value')) ?>
          <? endif ?>
        </li>
        <? $index = $index + 1 ?>
      <? endforeach;
    endforeach ?>
  </ul>
</li>
