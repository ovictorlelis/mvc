<?php

namespace core;

use Exception;

class View
{
  protected $viewPath = "../view/";
  protected $cachePath = "../cache/";

  public function render($view, $data = [])
  {
    $viewFile = $this->viewPath . str_replace('.', '/', $view) . '.html';

    if (!file_exists($viewFile)) {
      throw new Exception('View não encontrada: ' . $viewFile);
    }

    $cacheFile = $this->getCacheFilePath($viewFile);

    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
      mkdir($cacheDir, 0755, true);
    }

    if (!file_exists($cacheFile) || $this->isCacheExpired($viewFile, $cacheFile)) {
      $this->clearOldCache($viewFile);

      $content = file_get_contents($viewFile);
      $content = $this->handleExtends($content);
      $content = $this->parseDirectives($content);

      $content .= "<?php error()->clear(); ?>";
      $content .= "<?php old()->clear(); ?>";

      file_put_contents($cacheFile, $content);
    }

    extract($data);
    include $cacheFile;
  }

  protected function getCacheFilePath($viewFile)
  {
    $hash = md5_file($viewFile);
    $cacheFileName = basename($viewFile, '.html') . '_' . $hash . '.php';
    return $this->cachePath . $cacheFileName;
  }

  protected function clearOldCache($viewFile)
  {
    $cacheFileNamePattern = basename($viewFile, '.html') . '_*.php';
    $cacheFiles = glob($this->cachePath . $cacheFileNamePattern);

    foreach ($cacheFiles as $cacheFile) {
      unlink($cacheFile);
    }
  }

  protected function isCacheExpired($viewFile, $cacheFile)
  {
    $viewFileMTime = filemtime($viewFile);
    $cacheFileMTime = filemtime($cacheFile);

    if ($viewFileMTime > $cacheFileMTime) {
      return true;
    }

    $content = file_get_contents($viewFile);
    $extendsPattern = "/@extends\(\s*['\"](.+?)['\"]\s*\)/";
    $includePattern = "/@include\(\s*['\"](.+?)['\"]\s*\)/";

    if (preg_match($extendsPattern, $content, $matches)) {
      $extendsPath = $this->viewPath . str_replace('.', '/', $matches[1]) . '.html';
      if (!file_exists($extendsPath) || filemtime($extendsPath) > $cacheFileMTime) {
        return true;
      }
    }

    if (preg_match_all($includePattern, $content, $matches)) {
      foreach ($matches[1] as $include) {
        $includePath = $this->viewPath . str_replace('.', '/', $include) . '.html';
        if (!file_exists($includePath) || filemtime($includePath) > $cacheFileMTime) {
          return true;
        }
      }
    }

    return false;
  }

  protected function handleExtends($content)
  {
    $extendsPattern = "/@extends\(\s*['\"](.+?)['\"]\s*\)/";

    if (preg_match($extendsPattern, $content, $matches)) {
      $extends = $matches[1];
      $extendsPath = $this->viewPath . str_replace('.', '/', $extends) . '.html';

      if (!file_exists($extendsPath)) {
        throw new Exception('Layout não encontrado: ' . $extendsPath);
      }

      $masterContent = file_get_contents($extendsPath);

      $sections = [];
      $sectionPattern = "/@section\(\s*['\"](.+?)['\"]\s*\)(.*?)@endsection/s";
      if (preg_match_all($sectionPattern, $content, $sectionMatches, PREG_SET_ORDER)) {
        foreach ($sectionMatches as $match) {
          $sections[$match[1]] = $match[2];
        }
      }

      foreach ($sections as $section => $sectionContent) {
        $masterContent = str_replace("@content('$section')", $sectionContent, $masterContent);
      }

      $masterContent = preg_replace_callback(
        "/@content\(\s*['\"](.+?)['\"]\s*\)/",
        function ($matches) use ($sections) {
          return isset($sections[$matches[1]]) ? $sections[$matches[1]] : '';
        },
        $masterContent
      );

      $content = $masterContent;
    }

    return $content;
  }

  protected function parseDirectives($content)
  {
    $patterns = [
      '/<!--(.+?)-->/s' => '<?php /* $1 */ ?>',
      '/{{--(.+?)--}}/s' => '<?php /* $1 */ ?>',
      '/{{\s*(.+?)\s*\|e\s*}}/' => '<?= htmlspecialchars($1, ENT_QUOTES, \'UTF-8\') ?>',
      '/{{\s*(.+?)\s*}}/' => '<?= $1 ?>',
      '/@php/' => '<?php',
      '/@endphp/' => '?>',
      '/@auth/' => '<?php if(auth()->user()): ?>',
      '/@endauth/' => '<?php endif; ?>',
      '/@guest/' => '<?php if(!auth()->user()): ?>',
      '/@endguest/' => '<?php endif; ?>',
      '/@error\(\s*(.+?)\s*\)/' => '<?php if(error()->has($1)): ?>',
      '/@enderror/' => '<?php endif; ?>',
      '/@if\(\s*(.+?)\s*\)/' => '<?php if($1): ?>',
      '/@endif/' => '<?php endif; ?>',
      '/@elseif\(\s*(.+?)\s*\)/' => '<?php elseif($1): ?>',
      '/@else/' => '<?php else: ?>',
      '/@foreach\(\s*(.+?)\s*\)/' => '<?php foreach($1): ?>',
      '/@endforeach/' => '<?php endforeach; ?>',
      '/@for\(\s*(.+?)\s*\)/' => '<?php for($1): ?>',
      '/@endfor/' => '<?php endfor; ?>',
      '/@while\(\s*(.+?)\s*\)/' => '<?php while($1): ?>',
      '/@endwhile/' => '<?php endwhile; ?>',
      '/@switch\(\s*(.+?)\s*\)/' => '<?php switch($1): ?>',
      '/@endswitch/' => '<?php endswitch; ?>',
      '/@case\(\s*(.+?)\s*\)/' => '<?php case $1: ?>',
      '/@endcase/' => '<?php break; ?>',
      '/@csrf/' => '<?= csrf_field() ?>',
    ];

    foreach ($patterns as $pattern => $replacement) {
      $content = preg_replace($pattern, $replacement, $content);
    }

    $content = preg_replace_callback('/@include\(\s*[\'"](.+?)[\'"]\s*\)/', function ($matches) {
      $includePath = $this->viewPath . str_replace('.', '/', $matches[1]) . '.html';
      if (!file_exists($includePath)) {
        throw new Exception('view não encontrada: ' . $includePath);
      }
      return file_get_contents($includePath);
    }, $content);

    return $content;
  }
}
