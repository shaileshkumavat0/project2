<?php foreach (getFlashes() as $flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show rounded-14" role="alert">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>
