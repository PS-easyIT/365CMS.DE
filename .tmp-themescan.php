<?php
$theme = $argv[1]; $cms = $argv[2];
function phpFiles($dir, $skip = '~[\\\\/](vendor|assets|node_modules|cache|logs|uploads|backups)[\\\\/]~') { $out = []; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)); foreach ($it as $f) { if ($f->getExtension() === 'php' && !preg_match($skip, $f->getPathname())) $out[] = $f->getPathname(); } return $out; }
$defined = []; $classes = [];
foreach (array_merge(phpFiles($cms), phpFiles($theme)) as $file) {
  $tokens = @token_get_all(file_get_contents($file)); $ns = ''; $n = count($tokens);
  for ($i = 0; $i < $n; $i++) { $t = $tokens[$i];
    if (is_array($t) && $t[0] === T_NAMESPACE) { $ns = ''; for ($j = $i + 1; $j < $n; $j++) { if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_NAME_QUALIFIED, T_STRING], true)) { $ns = $tokens[$j][1]; break; } if ($tokens[$j] === ';' || $tokens[$j] === '{') break; } }
    if (is_array($t) && $t[0] === T_FUNCTION) { for ($j = $i + 1; $j < $n; $j++) { if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) { $defined[strtolower($tokens[$j][1])] = true; break; } if ($tokens[$j] === '(') break; } }
    if (is_array($t) && in_array($t[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) { for ($j = $i + 1; $j < $n; $j++) { if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) { $classes[strtolower(ltrim($ns . '\\' . $tokens[$j][1], '\\'))] = $file; break; } if ($tokens[$j] === '{' ) break; } }
  }
}
$undef = []; $classRefs = [];
foreach (phpFiles($theme) as $file) {
  $tokens = @token_get_all(file_get_contents($file)); $n = count($tokens); $rel = substr($file, strlen($theme) + 1);
  for ($i = 0; $i < $n; $i++) { $t = $tokens[$i]; if (!is_array($t)) continue;
    if (in_array($t[0], [T_STRING, T_NAME_FULLY_QUALIFIED], true)) {
      $k = $i + 1; while ($k < $n && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) $k++;
      if (($tokens[$k] ?? null) !== '(') continue;
      $p = $i - 1; while ($p >= 0 && is_array($tokens[$p]) && $tokens[$p][0] === T_WHITESPACE) $p--;
      $prev = $tokens[$p] ?? null;
      if (is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_CONST], true)) continue;
      $name = ltrim($t[1], '\\'); if (str_contains($name, '\\')) { $name = substr($name, strrpos($name, '\\') + 1); }
      $lname = strtolower($name);
      if (function_exists($lname) || isset($defined[$lname]) || in_array($lname, ['isset','empty','unset','list','array','echo','print','exit','die','eval','include','require','fn','match'], true)) continue;
      $undef[$lname][] = $rel . ':' . $t[2];
    }
    if (in_array($t[0], [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED], true) && stripos($t[1], 'CMS\\') !== false) {
      $k = $i + 1; while ($k < $n && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) $k++;
      $next = $tokens[$k] ?? null; $cls = strtolower(ltrim($t[1], '\\'));
      if (is_array($next) && $next[0] === T_DOUBLE_COLON) { $m = $tokens[$k + 1] ?? null; $classRefs[$cls][is_array($m) ? $m[1] : '?'][] = $rel . ':' . $t[2]; }
      elseif (!isset($classRefs[$cls])) { $classRefs[$cls] = $classRefs[$cls] ?? []; }
    }
  }
}
echo "== Undefinierte Funktionen\n"; foreach ($undef as $f => $locs) echo "  $f  (" . count($locs) . "x) " . implode(', ', array_slice($locs, 0, 3)) . "\n";
echo "== CMS-Klassen/Methoden\n";
foreach ($classRefs as $cls => $methods) {
  $exists = isset($classes[$cls]);
  $miss = [];
  if ($exists && $methods) { $src = file_get_contents($classes[$cls]); foreach ($methods as $m => $locs) { if ($m === 'class' || $m === '?') continue; if (!preg_match('/function\s+' . preg_quote($m, '/') . '\s*\(/i', $src) && !preg_match('/const\s+(?:[a-z]+\s+)?' . preg_quote($m, '/') . '\b/i', $src)) $miss[] = "$m (" . $locs[0] . ")"; } }
  if (!$exists || $miss) echo "  $cls " . ($exists ? 'fehlende Methoden: ' . implode(', ', $miss) : 'KLASSE FEHLT') . "\n";
}
