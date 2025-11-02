<?php
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = str_replace('\\', '/', dirname($scriptName));
if ($scriptDir === '.' || $scriptDir === '') {
    $scriptDir = '';
} elseif ($scriptDir !== '/') {
    $scriptDir = '/' . ltrim($scriptDir, '/');
    $scriptDir = rtrim($scriptDir, '/');
} else {
    $scriptDir = '/';
}
$basename = $scriptDir === '' ? '/' : $scriptDir;
$prefix = $basename === '/' ? '/' : $basename . '/';
$assetPrefix = $prefix;
$rootForApi = $basename === '/' ? '/' : rtrim(dirname($basename), '/');
if ($rootForApi === '' || $rootForApi === '.') {
    $rootForApi = '/';
}
$apiPath = $rootForApi === '/' ? '/api' : $rootForApi . '/api';
$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
if (is_string($forwardedProto) && $forwardedProto !== '') {
    $scheme = explode(',', $forwardedProto)[0];
} elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
    $scheme = $_SERVER['REQUEST_SCHEME'];
} else {
    $https = $_SERVER['HTTPS'] ?? '';
    $scheme = (!empty($https) && $https !== 'off') ? 'https' : 'http';
}
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$apiUrl = rtrim($scheme . '://' . $host, '/') . $apiPath;
$config = [
    'basename' => $basename,
    'prefix' => $prefix,
    'apiUrl' => $apiUrl,
    'assetPrefix' => $assetPrefix,
];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mirza Web App</title>
    <base href="<?php echo htmlspecialchars($prefix, ENT_QUOTES); ?>" />
    <script src="<?php echo htmlspecialchars($assetPrefix . 'js/telegram-web-app.js', ENT_QUOTES); ?>"></script>
    <script>
      window.__APP_CONFIG__ = <?php echo json_encode($config, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script type="module" crossorigin src="<?php echo htmlspecialchars($assetPrefix . 'assets/index-C-2a0Dur.js', ENT_QUOTES); ?>"></script>
    <link rel="modulepreload" crossorigin href="<?php echo htmlspecialchars($assetPrefix . 'assets/vendor-CIGJ9g2q.js', ENT_QUOTES); ?>">
    <link rel="stylesheet" crossorigin href="<?php echo htmlspecialchars($assetPrefix . 'assets/index-BoHBsj0Z.css', ENT_QUOTES); ?>">
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
