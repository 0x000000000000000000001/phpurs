<?php
$depth=(int)($argv[1]??1000);
$repeats=(int)($argv[2]??1000);
$expected=$depth*$repeats;
foreach([0,1,2,17,127] as $n) {
    if(\Main\majMain_run(7,$n,11)!==11+7*$n) throw new \Exception('variable input parity');
}
for($i=0;$i<3;++$i) if(\Main\majMain_run($repeats,$depth,0)!==$expected) throw new \Exception('warmup');
$times=[];
for($i=0;$i<10;++$i) {
    $start=hrtime(true);
    $result=\Main\majMain_run($repeats,$depth,0);
    $times[]=(hrtime(true)-$start)/1e6;
    if($result!==$expected) throw new \Exception('result');
}
sort($times);
echo json_encode(['depth'=>$depth,'repeats'=>$repeats,'output'=>$result,'best_ms'=>$times[0],'median_ms'=>($times[4]+$times[5])/2,'samples_ms'=>$times,'php'=>PHP_VERSION,'jit'=>opcache_get_status(false)['jit']??null]),"\n";
