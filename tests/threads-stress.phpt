--TEST--
Four threads running the same workloads as a single thread get identical results while contending for PDFium
--EXTENSIONS--
pdfium
--SKIPIF--
<?php require __DIR__ . '/threads.inc'; threads_skipif(); ?>
--FILE--
<?php
require __DIR__ . '/threads.inc';
$dir = __DIR__ . '/fixtures';
$iterations = 20;

$expected = [];
for ($seed = 0; $seed < 4; $seed++) {
    $expected[$seed] = stress_workload($dir, $seed)['results'];
}

$futures = [];
for ($seed = 0; $seed < 4; $seed++) {
    $runtime = new parallel\Runtime(__DIR__ . '/threads.inc');
    $futures[$seed] = $runtime->run(static function (string $dir, int $seed, int $iterations): array {
        $all = [];
        $busy = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $run = stress_workload($dir, $seed);
            $all[] = $run['results'];
            $busy += $run['busy'];
        }
        return ['runs' => $all, 'busy' => $busy];
    }, [$dir, $seed, $iterations]);
}

$match = true;
$busy = 0;
foreach ($futures as $seed => $future) {
    $out = $future->value();
    $busy += $out['busy'];
    if (count($out['runs']) !== $iterations) {
        $match = false;
    }
    foreach ($out['runs'] as $i => $results) {
        if ($results !== $expected[$seed]) {
            $match = false;
            echo "seed $seed run $i differs\n";
            foreach (array_diff_assoc($results, $expected[$seed]) as $k => $v) {
                echo "  $k\n";
            }
        }
    }
}
echo 'results match: ', $match ? 'yes' : 'no', "\n";
echo 'busy seen: ', $busy > 0 ? 'yes' : 'no', "\n";
?>
--EXPECT--
results match: yes
busy seen: yes
