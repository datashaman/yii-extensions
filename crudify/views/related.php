      <li class="related">
        <div class="header">Collections</div>
        <ul class="content">
          <? foreach($object->getRelatedLinks() as $link): ?>
          <li><?= $link ?></li>
          <? endforeach ?>
        </ul>
      </li>
