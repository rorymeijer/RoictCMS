<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();

$activeTheme = ThemeManager::getActive();
if (!$activeTheme) {
    flash('error', 'Geen actief thema gevonden.');
    redirect(BASE_URL . '/admin/themes/');
}

$themePath   = THEMES_PATH . '/' . $activeTheme;
$allowedExt  = ['php', 'css', 'js', 'json', 'html', 'htm', 'txt', 'md'];

// Security: resolve file path and ensure it stays within the theme directory
function resolveThemeFile(string $base, string $rel): string|false {
    if (empty($rel)) return false;
    $rel = str_replace(['\\', "\0"], ['/', ''], $rel);
    if (str_contains($rel, '..') || str_starts_with($rel, '/')) return false;
    $full    = $base . '/' . $rel;
    $realBase = realpath($base);
    $realFull = realpath($full);
    if (!$realBase || !$realFull) return false;
    if (!str_starts_with($realFull . '/', $realBase . '/')) return false;
    return $realFull;
}

// Handle POST: save file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Ongeldige aanvraag (CSRF).');
        redirect(BASE_URL . '/admin/themes/editor.php');
    }
    $saveRel     = trim($_POST['file'] ?? '');
    $saveContent = $_POST['content'] ?? '';
    $realFile    = resolveThemeFile($themePath, $saveRel);
    if ($realFile && is_file($realFile)) {
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExt)) {
            file_put_contents($realFile, $saveContent);
            flash('success', 'Bestand <strong>' . e(basename($realFile)) . '</strong> opgeslagen.');
        } else {
            flash('error', 'Bestandstype niet toegestaan.');
        }
    } else {
        flash('error', 'Bestand niet gevonden of niet toegestaan.');
    }
    redirect(BASE_URL . '/admin/themes/editor.php?file=' . urlencode($saveRel));
}

// Build recursive file tree
function buildFileTree(string $dir, string $base, array $allowed): array {
    $tree  = [];
    $items = @scandir($dir);
    if (!$items) return $tree;
    // Sort: dirs first, then files
    usort($items, function ($a, $b) use ($dir) {
        if ($a === '.' || $a === '..') return -1;
        if ($b === '.' || $b === '..') return 1;
        $aDir = is_dir($dir . '/' . $a);
        $bDir = is_dir($dir . '/' . $b);
        if ($aDir !== $bDir) return $aDir ? -1 : 1;
        return strcasecmp($a, $b);
    });
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . '/' . $item;
        $rel  = $base ? $base . '/' . $item : $item;
        if (is_dir($full)) {
            $children = buildFileTree($full, $rel, $allowed);
            if ($children) {
                $tree[] = ['type' => 'dir', 'name' => $item, 'rel' => $rel, 'children' => $children];
            }
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $tree[] = ['type' => 'file', 'name' => $item, 'rel' => $rel, 'ext' => $ext];
            }
        }
    }
    return $tree;
}

function renderFileTree(array $tree, string $currentFile, int $depth = 0): void {
    foreach ($tree as $node) {
        if ($node['type'] === 'dir') {
            echo '<div class="tree-dir" style="padding-left:' . ($depth * 12) . 'px">'
               . '<i class="bi bi-folder2-open" aria-hidden="true"></i> ' . e($node['name']) . '</div>';
            renderFileTree($node['children'], $currentFile, $depth + 1);
        } else {
            $iconMap = [
                'php'  => 'bi-filetype-php',
                'css'  => 'bi-filetype-css',
                'js'   => 'bi-filetype-js',
                'json' => 'bi-filetype-json',
                'html' => 'bi-filetype-html',
                'htm'  => 'bi-filetype-html',
                'md'   => 'bi-markdown',
                'txt'  => 'bi-file-earmark-text',
            ];
            $icon    = $iconMap[$node['ext']] ?? 'bi-file-earmark';
            $active  = $node['rel'] === $currentFile ? ' active' : '';
            echo '<a href="' . BASE_URL . '/admin/themes/editor.php?file=' . urlencode($node['rel'])
               . '" class="tree-file' . $active . '" style="padding-left:' . (($depth * 12) + 4) . 'px"'
               . ($active ? ' aria-current="true"' : '') . '>'
               . '<i class="bi ' . e($icon) . '" aria-hidden="true"></i> ' . e($node['name']) . '</a>';
        }
    }
}

// Load current file
$selectedRel  = trim($_GET['file'] ?? '');
$fileContent  = '';
$fileExt      = '';
$fileValid    = false;
$cmMode       = 'text/plain';

if ($selectedRel) {
    $realFile = resolveThemeFile($themePath, $selectedRel);
    if ($realFile && is_file($realFile)) {
        $fileExt = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        if (in_array($fileExt, $allowedExt)) {
            $fileContent = file_get_contents($realFile);
            $fileValid   = true;
            $cmMode = match($fileExt) {
                'php'        => 'application/x-httpd-php',
                'css'        => 'text/css',
                'js'         => 'text/javascript',
                'json'       => 'application/json',
                'html','htm' => 'text/html',
                default      => 'text/plain',
            };
        }
    }
}

// Theme info
$themeInfo = null;
foreach (ThemeManager::getAvailable() as $t) {
    if ($t['slug'] === $activeTheme) { $themeInfo = $t; break; }
}
$themeName = $themeInfo['name'] ?? $activeTheme;

$fileTree  = buildFileTree($themePath, '', $allowedExt);
$pageTitle = 'Thema-editor';
$activePage = 'themes';

// Inject CodeMirror into <head> via hook
add_action('admin_head', function() {
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.css">' . "\n";
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/theme/dracula.min.css">' . "\n";
    echo '<style>
/* ── Theme editor layout ─────────────────────────────────────────────── */
#editor-wrap {
  display: flex;
  height: calc(100vh - var(--topbar-h) - 3.5rem);
  gap: 1rem;
}
#file-tree-panel {
  width: 220px;
  min-width: 180px;
  flex-shrink: 0;
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 14px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}
#file-tree-header {
  padding: .75rem 1rem;
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
#file-tree-body { flex: 1; overflow-y: auto; padding: .5rem 0; }
.tree-dir {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .3rem .75rem;
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--text-muted);
  margin-top: .25rem;
}
.tree-file {
  display: flex;
  align-items: center;
  gap: .45rem;
  padding: .35rem .75rem;
  font-size: .82rem;
  color: var(--text);
  text-decoration: none;
  transition: background .1s;
  border-radius: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.tree-file:hover { background: var(--surface); color: var(--primary); }
.tree-file.active { background: var(--sidebar-active-bg); color: var(--primary); font-weight: 600; }
#editor-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 14px;
  overflow: hidden;
  min-width: 0;
}
#editor-toolbar {
  padding: .65rem 1rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: .65rem;
  flex-shrink: 0;
  flex-wrap: wrap;
}
#editor-filename {
  font-size: .85rem;
  font-weight: 600;
  color: var(--text);
  font-family: monospace;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
#editor-area { flex: 1; overflow: hidden; display: flex; flex-direction: column; }
.CodeMirror {
  height: 100% !important;
  font-family: "Cascadia Code","Fira Code","Consolas","Courier New",monospace;
  font-size: .84rem;
  line-height: 1.6;
  border-radius: 0;
}
.CodeMirror-scroll { height: 100%; }
.editor-placeholder {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  gap: 1rem;
}
/* Mobile: stack layout */
@media (max-width: 768px) {
  #editor-wrap { flex-direction: column; height: auto; }
  #file-tree-panel { width: 100%; min-width: unset; height: auto; max-height: 240px; }
  #editor-panel { min-height: 400px; }
  .CodeMirror { min-height: 400px; }
}
</style>' . "\n";
});

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3" style="flex-wrap:wrap;gap:.5rem;">
  <div class="d-flex align-items-center gap-2">
    <sl-button href="<?= BASE_URL ?>/admin/themes/" size="small" variant="neutral" outline>
      <i slot="prefix" class="bi bi-arrow-left"></i> Thema's
    </sl-button>
    <div>
      <span style="font-size:1.1rem;font-weight:800;"><?= e($themeName) ?></span>
      <span class="text-muted" style="font-size:.82rem;margin-left:.5rem;">Thema-editor</span>
    </div>
  </div>
  <?php if ($fileValid): ?>
  <sl-button form="editor-form" type="submit" variant="primary" size="small">
    <i slot="prefix" class="bi bi-floppy"></i> Opslaan
  </sl-button>
  <?php endif; ?>
</div>

<?= renderFlash() ?>

<div id="editor-wrap">
  <!-- File tree -->
  <aside id="file-tree-panel" aria-label="Themabestanden">
    <div id="file-tree-header">
      <i class="bi bi-folder2" aria-hidden="true"></i> <?= e($activeTheme) ?>
    </div>
    <div id="file-tree-body" role="tree">
      <?php renderFileTree($fileTree, $selectedRel); ?>
    </div>
  </aside>

  <!-- Editor panel -->
  <div id="editor-panel">
    <?php if ($fileValid): ?>
    <form id="editor-form" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="file" value="<?= e($selectedRel) ?>">
      <div id="editor-toolbar">
        <span id="editor-filename"><?= e($selectedRel) ?></span>
        <sl-badge variant="neutral" pill><?= e(strtoupper($fileExt)) ?></sl-badge>
        <sl-button type="submit" variant="primary" size="small">
          <i slot="prefix" class="bi bi-floppy"></i> Opslaan
        </sl-button>
      </div>
      <div id="editor-area">
        <textarea id="code-editor" name="content"><?= htmlspecialchars($fileContent, ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/php/php.min.js"></script>
    <script>
    (function() {
      var ta = document.getElementById('code-editor');
      var cm = CodeMirror.fromTextArea(ta, {
        mode: '<?= e($cmMode) ?>',
        theme: 'dracula',
        lineNumbers: true,
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        lineWrapping: false,
        matchBrackets: true,
        autoCloseBrackets: true,
        autofocus: true,
        extraKeys: {
          'Ctrl-S': function(instance) { document.getElementById('editor-form').submit(); },
          'Cmd-S':  function(instance) { document.getElementById('editor-form').submit(); },
          'Tab': function(cm) {
            if (cm.somethingSelected()) cm.indentSelection('add');
            else cm.replaceSelection('    ', 'end');
          }
        }
      });
      // Ensure CodeMirror fills the editor area
      function resizeEditor() {
        var panel   = document.getElementById('editor-area');
        var toolbar = document.getElementById('editor-toolbar');
        if (!panel || !toolbar) return;
        var h = panel.offsetHeight;
        cm.setSize(null, Math.max(200, h) + 'px');
        cm.refresh();
      }
      window.addEventListener('resize', resizeEditor);
      // Wait for layout, then size
      requestAnimationFrame(function() { setTimeout(resizeEditor, 60); });
    })();
    </script>

    <?php else: ?>
    <div class="editor-placeholder">
      <i class="bi bi-file-earmark-code" style="font-size:3.5rem;opacity:.2;" aria-hidden="true"></i>
      <div style="text-align:center;">
        <div style="font-size:1rem;font-weight:700;color:var(--text);margin-bottom:.35rem;">Selecteer een bestand</div>
        <div style="font-size:.85rem;">Kies een bestand uit de bestandsstructuur links om het te bewerken.</div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
