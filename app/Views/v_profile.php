<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-body">
    <h5 class="card-title">Profil Pengguna</h5>

    <div class="row">
      <div class="col-md-4">Username</div>
      <div class="col-md-8"><?= session()->get('username') ?></div>
    </div>

    <div class="row">
      <div class="col-md-4">Role</div>
      <div class="col-md-8"><?= session()->get('role') ?></div>
    </div>

    <div class="row">
      <div class="col-md-4">Email</div>
      <div class="col-md-8"><?= session()->get('email') ?></div>
    </div>

    <div class="row">
      <div class="col-md-4">Waktu Login</div>
      <div class="col-md-8"><?= session()->get('login_time') ?></div>
    </div>

    <div class="row">
      <div class="col-md-4">Status</div>
      <div class="col-md-8 text-success">Sudah Login</div>
    </div>

  </div>
</div>
<?= $this->endSection() ?>