<?php
require_once '../config/db.php';
require_once '../config/cms.php';
require_once 'includes/auth.php';
ska_admin_require();

$c = cms_conn();
$editSlug = $_GET['page'] ?? '';
$editBlockParam = $_GET['block'] ?? '';
$showBlockForm = ($editBlockParam === 'new');
$editBlockId = (int)$editBlockParam;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_page') {
        $slug = trim($_POST['slug'] ?? '');
        $stmt = $c->prepare("UPDATE cms_pages SET page_title=?, meta_description=?, hero_eyebrow=?, hero_title=?, hero_subtitle=?, hero_image=?, body_html=?, active=? WHERE slug=?");
        $active = (int)($_POST['active'] ?? 1);
        $stmt->bind_param('sssssssis',
            $_POST['page_title'], $_POST['meta_description'], $_POST['hero_eyebrow'],
            $_POST['hero_title'], $_POST['hero_subtitle'], $_POST['hero_image'],
            $_POST['body_html'], $active, $slug
        );
        $stmt->execute();
        $stmt->close();
        header('Location: pages.php?page=' . urlencode($slug) . '&saved=1');
        exit;
    }

    if ($action === 'save_block') {
        $id = (int)($_POST['id'] ?? 0);
        $pageSlug = trim($_POST['page_slug'] ?? '');
        $blockKey = trim($_POST['block_key'] ?? '');
        $tag = trim($_POST['tag'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $image = trim($_POST['image'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $linkLabel = trim($_POST['link_label'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $active = (int)($_POST['active'] ?? 1);

        if ($id) {
            $stmt = $c->prepare("UPDATE cms_blocks SET tag=?, title=?, subtitle=?, body=?, image=?, link_url=?, link_label=?, sort_order=?, active=? WHERE id=?");
            $stmt->bind_param('sssssssiii', $tag, $title, $subtitle, $body, $image, $linkUrl, $linkLabel, $sort, $active, $id);
        } else {
            $stmt = $c->prepare("INSERT INTO cms_blocks (page_slug, block_key, tag, title, subtitle, body, image, link_url, link_label, sort_order, active) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssssii', $pageSlug, $blockKey, $tag, $title, $subtitle, $body, $image, $linkUrl, $linkLabel, $sort, $active);
        }
        $stmt->execute();
        $stmt->close();
        header('Location: pages.php?page=' . urlencode($pageSlug) . '&saved=1');
        exit;
    }

    if ($action === 'delete_block') {
        $id = (int)$_POST['id'];
        $c->query("DELETE FROM cms_blocks WHERE id = $id");
        header('Location: pages.php?page=' . urlencode($_POST['page_slug'] ?? '') . '&deleted=1');
        exit;
    }
}

$pages = $c->query("SELECT slug, page_title FROM cms_pages ORDER BY slug ASC")->fetch_all(MYSQLI_ASSOC);
$currentPage = null;
if ($editSlug) {
    $stmt = $c->prepare("SELECT * FROM cms_pages WHERE slug = ? LIMIT 1");
    $stmt->bind_param('s', $editSlug);
    $stmt->execute();
    $currentPage = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$blocks = $editSlug ? cms_blocks($editSlug, false) : [];
$editingBlock = null;
if ($editBlockId) {
    foreach ($blocks as $b) if ((int)$b['id'] === $editBlockId) $editingBlock = $b;
}
if ($showBlockForm && !$editingBlock) $editingBlock = [];

$activePage = 'pages';
$pageTitle = 'Pages & Content';
$pageBreadcrumb = 'Edit page hero, body text, and content blocks';
if (isset($_GET['saved'])) { $toastMsg = 'Content saved.'; $toastType = 'success'; $includeToast = true; }
include 'includes/layout-start.php';
?>

<div class="row g-4">
  <div class="col-lg-3">
    <div class="ska-card">
      <div class="ska-card__header"><div class="ska-card__title">Pages</div></div>
      <div class="ska-card__body ska-card__body--padded">
        <?php foreach ($pages as $p): ?>
        <a href="?page=<?= urlencode($p['slug']) ?>" class="d-block py-2 text-decoration-none <?= $editSlug === $p['slug'] ? 'fw-bold text-dark' : 'text-muted' ?>">
          <?= htmlspecialchars($p['page_title']) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-9">
    <?php if (!$editSlug): ?>
      <div class="ska-empty"><p>Select a page to edit its content.</p></div>
    <?php else: ?>

    <div class="ska-card mb-4">
      <div class="ska-card__header"><div class="ska-card__title">Page: <?= htmlspecialchars($currentPage['page_title'] ?? $editSlug) ?></div></div>
      <div class="ska-card__body ska-card__body--padded">
        <form method="POST">
          <input type="hidden" name="action" value="save_page">
          <input type="hidden" name="slug" value="<?= htmlspecialchars($editSlug) ?>">
          <div class="row g-3">
            <div class="col-md-6"><label class="ska-label">Page Title (SEO)</label><input name="page_title" class="ska-input" value="<?= htmlspecialchars($currentPage['page_title'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="ska-label">Meta Description</label><input name="meta_description" class="ska-input" value="<?= htmlspecialchars($currentPage['meta_description'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="ska-label">Hero Eyebrow</label><input name="hero_eyebrow" class="ska-input" value="<?= htmlspecialchars($currentPage['hero_eyebrow'] ?? '') ?>"></div>
            <div class="col-md-8"><label class="ska-label">Hero Title</label><input name="hero_title" class="ska-input" value="<?= htmlspecialchars($currentPage['hero_title'] ?? '') ?>"></div>
            <div class="col-12"><label class="ska-label">Hero Subtitle</label><textarea name="hero_subtitle" class="ska-textarea" rows="2"><?= htmlspecialchars($currentPage['hero_subtitle'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="ska-label">Hero Image Path</label><input name="hero_image" class="ska-input" value="<?= htmlspecialchars($currentPage['hero_image'] ?? '') ?>" placeholder="assets/images/..."></div>
            <div class="col-md-6"><label class="ska-label">Active</label><select name="active" class="ska-select"><option value="1" <?= ($currentPage['active'] ?? 1) ? 'selected' : '' ?>>Yes</option><option value="0" <?= !($currentPage['active'] ?? 1) ? 'selected' : '' ?>>No</option></select></div>
            <div class="col-12"><label class="ska-label">Body HTML (legal pages & extra content)</label><textarea name="body_html" class="ska-textarea" rows="8"><?= htmlspecialchars($currentPage['body_html'] ?? '') ?></textarea></div>
          </div>
          <button type="submit" class="ska-btn ska-btn--gold mt-3"><i class="fa-solid fa-floppy-disk"></i> Save Page</button>
        </form>
      </div>
    </div>

    <div class="ska-card">
      <div class="ska-card__header">
        <div class="ska-card__title">Content Blocks <span>Sections, cards, property features</span></div>
        <a href="?page=<?= urlencode($editSlug) ?>&block=new" class="ska-btn ska-btn--outline"><i class="fa-solid fa-plus"></i> Add Block</a>
      </div>
      <div class="ska-card__body ska-card__body--padded">
        <?php if ($showBlockForm || $editingBlock): ?>
        <form method="POST" class="mb-4 p-3 border rounded">
          <input type="hidden" name="action" value="save_block">
          <input type="hidden" name="id" value="<?= (int)($editingBlock['id'] ?? 0) ?>">
          <input type="hidden" name="page_slug" value="<?= htmlspecialchars($editSlug) ?>">
          <div class="row g-3">
            <div class="col-md-4"><label class="ska-label">Block Key</label><input name="block_key" class="ska-input" value="<?= htmlspecialchars($editingBlock['block_key'] ?? '') ?>" <?= $editBlockId ? 'readonly' : 'required' ?> placeholder="e.g. dining"></div>
            <div class="col-md-4"><label class="ska-label">Tag / Eyebrow</label><input name="tag" class="ska-input" value="<?= htmlspecialchars($editingBlock['tag'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="ska-label">Sort Order</label><input name="sort_order" type="number" class="ska-input" value="<?= (int)($editingBlock['sort_order'] ?? 0) ?>"></div>
            <div class="col-md-6"><label class="ska-label">Title</label><input name="title" class="ska-input" value="<?= htmlspecialchars($editingBlock['title'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="ska-label">Subtitle</label><input name="subtitle" class="ska-input" value="<?= htmlspecialchars($editingBlock['subtitle'] ?? '') ?>"></div>
            <div class="col-12"><label class="ska-label">Body</label><textarea name="body" class="ska-textarea" rows="3"><?= htmlspecialchars($editingBlock['body'] ?? '') ?></textarea></div>
            <div class="col-md-4"><label class="ska-label">Image Path</label><input name="image" class="ska-input" value="<?= htmlspecialchars($editingBlock['image'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="ska-label">Link URL</label><input name="link_url" class="ska-input" value="<?= htmlspecialchars($editingBlock['link_url'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="ska-label">Link Label</label><input name="link_label" class="ska-input" value="<?= htmlspecialchars($editingBlock['link_label'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="ska-label">Active</label><select name="active" class="ska-select"><option value="1" <?= ($editingBlock['active'] ?? 1) ? 'selected' : '' ?>>Yes</option><option value="0" <?= !($editingBlock['active'] ?? 1) ? 'selected' : '' ?>>No</option></select></div>
          </div>
          <button type="submit" class="ska-btn ska-btn--gold mt-3">Save Block</button>
          <a href="?page=<?= urlencode($editSlug) ?>" class="ska-btn ska-btn--outline mt-3">Cancel</a>
        </form>
        <?php endif; ?>

        <table class="ska-table">
          <thead><tr><th>Key</th><th>Title</th><th>Tag</th><th>Order</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($blocks as $b): ?>
            <tr>
              <td><code><?= htmlspecialchars($b['block_key']) ?></code></td>
              <td><?= htmlspecialchars($b['title'] ?: '—') ?></td>
              <td class="ska-muted"><?= htmlspecialchars($b['tag'] ?: '—') ?></td>
              <td><?= (int)$b['sort_order'] ?></td>
              <td><span class="ska-badge ska-badge--<?= $b['active'] ? 'confirmed' : 'muted' ?>"><?= $b['active'] ? 'Active' : 'Hidden' ?></span></td>
              <td>
                <a href="?page=<?= urlencode($editSlug) ?>&block=<?= $b['id'] ?>" class="ska-btn ska-btn--ghost-edit"><i class="fa-regular fa-pen-to-square"></i></a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete block?')"><input type="hidden" name="action" value="delete_block"><input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="page_slug" value="<?= htmlspecialchars($editSlug) ?>"><button class="ska-btn ska-btn--ghost-del"><i class="fa-regular fa-trash-can"></i></button></form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/layout-end.php'; ?>
