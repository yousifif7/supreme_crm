<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "DB connection default: " . config('database.default') . "\n";
$cn = config('database.default');
$conf = config("database.connections.$cn");
echo "Connection config: driver=" . ($conf['driver'] ?? '') . ", database=" . ($conf['database'] ?? '') . ", host=" . ($conf['host'] ?? '') . "\n\n";

try {
    $db = \DB::selectOne('select database() as db');
    echo "Current database: " . ($db->db ?? 'NULL') . "\n";
} catch (\Exception $e) {
    echo "Could not get current database: " . $e->getMessage() . "\n";
}

$table = 'user_pinned_conversations';
try {
    // PDO param binding isn't supported for SHOW TABLES LIKE on some MySQL/MariaDB versions,
    // so interpolate the table name safely (it's fixed in this script).
    $res = \DB::select("SHOW TABLES LIKE '{$table}'");
    echo "SHOW TABLES LIKE '$table' => ";
    echo empty($res) ? "NO\n" : "YES\n";
} catch (\Exception $e) {
    echo "SHOW TABLES failed: " . $e->getMessage() . "\n";
}

if (!empty($res)) {
    try {
        $create = \DB::selectOne("SHOW CREATE TABLE `$table`");
        echo "\nSHOW CREATE TABLE $table:\n";
        // The array key differs by driver; print whole object
        print_r($create);
    } catch (\Exception $e) {
        echo "SHOW CREATE TABLE failed: " . $e->getMessage() . "\n";
    }

    try {
        $count = \DB::selectOne("select count(*) as c from `$table`");
        echo "\nRow count: " . ($count->c ?? 'N/A') . "\n";
    } catch (\Exception $e) {
        echo "Count query failed: " . $e->getMessage() . "\n";
    }
}

// Also print user privileges (MySQL)
try {
    $user = \DB::selectOne("select user() as user");
    echo "\nCurrent DB user: " . ($user->user ?? 'unknown') . "\n";
    $grants = \DB::select("SHOW GRANTS FOR CURRENT_USER");
    echo "\nGrants for current user:\n";
    foreach ($grants as $g) {
        foreach ((array)$g as $v) echo $v . "\n";
    }
} catch (\Exception $e) {
    echo "Could not fetch grants: " . $e->getMessage() . "\n";
}
