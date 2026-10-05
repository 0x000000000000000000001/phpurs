<?php
$depth=(int)($argv[1]??1000);
$repeats=(int)($argv[2]??1000);
$seed=(int)($argv[3]??11);
$expected=($depth+$seed)*$repeats;
foreach([0,1,2,17,127,1000] as $n) {
    foreach([-31,0,11,2147483647] as $value) {
        if(\Main\majMain_run(7,$n,$value,13)!==13+7*($n+$value)) throw new \Exception('variable input parity');
    }
}
for($i=0;$i<3;++$i) if(\Main\majMain_run($repeats,$depth,$seed,0)!==$expected) throw new \Exception('warmup');
$times=[];
for($i=0;$i<10;++$i) {
    $start=hrtime(true);
    $result=\Main\majMain_run($repeats,$depth,$seed,0);
    $times[]=(hrtime(true)-$start)/1e6;
    if($result!==$expected) throw new \Exception('result');
}
$sorted=$times;
sort($sorted);
$status=opcache_get_status(false);
if(!$status['opcache_enabled'] || !$status['jit']['on']) throw new \Exception('JIT/OPcache inactive');
echo json_encode(['depth'=>$depth,'repeats'=>$repeats,'seed'=>$seed,'output'=>$result,
    'best_ms'=>$sorted[0],'median_ms'=>($sorted[4]+$sorted[5])/2,'samples_ms'=>$times,
    'peak_bytes'=>memory_get_peak_usage(true),'php'=>PHP_VERSION,'jit'=>$status['jit'],
    'opcache_enabled'=>$status['opcache_enabled'],'opcache_file_cache'=>ini_get('opcache.file_cache'),
    'xdebug_mode'=>ini_get('xdebug.mode')]),"\n";
