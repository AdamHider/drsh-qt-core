<?= $this->extend('layouts/'.$settings['layout']) ?>
<?= $this->section('content') ?>
<div class="container">
    <form action="/admin/languages/update/<?= $language['id'] ?>" method="post">
        <div class="mb-3">
            <label for="name" class="form-label">Language Name</label>
            <input type="text" name="title" class="form-control" id="title" value="<?= set_value('title', $language['title']) ?>" required>
            <?php if(isset(session()->getFlashdata('errors')['title'])): ?>
                <div class="alert alert-danger mt-2">
                    <?= session()->getFlashdata('errors')['title'] ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label for="code" class="form-label">Language Code</label>
            <input type="text" name="code" class="form-control" id="code" value="<?= set_value('code', $language['code']) ?>" required>
            <?php if(isset(session()->getFlashdata('errors')['code'])): ?>
                <div class="alert alert-danger mt-2">
                    <?= session()->getFlashdata('errors')['code'] ?>
                </div>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Update Language</button>
    </form>
</div>
<?= $this->endSection() ?>