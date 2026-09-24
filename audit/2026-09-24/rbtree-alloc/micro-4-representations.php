<?php
// Micro-benchmark: four representations of the Okasaki red-black tree insert.
// Variants: A objects functional, B objects with in-place plain rebuild,
// C packed arrays with by-reference parameters (COW), D packed arrays by value.
// Constructions are counted. Usage: php rbt-micro.php <variant> <n> <rounds>
// Where variant is one of: A B C D.

$GLOBALS['constructions'] = 0;

// ---------------- shared helpers ----------------
function maxInt(int $a, int $b): int { return $a > $b ? $a : $b; }

// ---------------- A: objects, functional ----------------
final class AT {
    public function __construct(public $c, public $l, public int $k, public $r) {
        $GLOBALS['constructions']++;
    }
}

function A_balance($c, $a, $x, $b) {
    if ($c === 1) {
        if ($a !== null && $a->c === 0) {
            $al = $a->l; $ax = $a->k; $ar = $a->r;
            if ($al !== null && $al->c === 0) return new AT(0, new AT(1, $al->l, $al->k, $al->r), $ax, new AT(1, $ar, $x, $b));
            if ($ar !== null && $ar->c === 0) return new AT(0, new AT(1, $al, $ax, $ar->l), $ar->k, new AT(1, $ar->r, $x, $b));
        }
        if ($b !== null && $b->c === 0) {
            $bl = $b->l; $bx = $b->k; $br = $b->r;
            if ($bl !== null && $bl->c === 0) return new AT(0, new AT(1, $a, $x, $bl->l), $bl->k, new AT(1, $bl->r, $bx, $br));
            if ($br !== null && $br->c === 0) return new AT(0, new AT(1, $a, $x, $bl), $bx, new AT(1, $br->l, $br->k, $br->r));
        }
    }
    return new AT($c, $a, $x, $b);
}

function A_ins($x, $s) {
    if ($s === null) return new AT(0, null, $x, null);
    $c = $s->c; $a = $s->l; $y = $s->k; $b = $s->r;
    if ($x < $y) return A_balance($c, A_ins($x, $a), $y, $b);
    if ($x > $y) return A_balance($c, $a, $y, A_ins($x, $b));
    return new AT($c, $a, $y, $b);
}

function A_insert($x, $s) {
    $r = A_ins($x, $s);
    if ($r !== null && $r->c === 0) return new AT(1, $r->l, $r->k, $r->r);
    return $r;
}

function A_build($n, $acc) {
    // Like the generated code: the accumulator is rebound non-destructively.
    while ($n > 0) { $acc = A_insert($n, $acc); $n--; }
    return $acc;
}

function A_depth($t) { return $t === null ? 0 : 1 + maxInt(A_depth($t->l), A_depth($t->r)); }

// ---------------- B: objects, in-place plain rebuild (upper bound) ----------------
function B_ins($x, $s) {
    if ($s === null) return new AT(0, null, $x, null);
    $c = $s->c; $k = $s->k;
    if ($x < $k) {
        $l = B_ins($x, $s->l);
        $a = $l; $b = $s->r;
        if ($c === 1 && $a !== null && $a->c === 0) {
            $al = $a->l; $ax = $a->k; $ar = $a->r;
            if ($al !== null && $al->c === 0) return new AT(0, new AT(1, $al->l, $al->k, $al->r), $ax, new AT(1, $ar, $k, $b));
            if ($ar !== null && $ar->c === 0) return new AT(0, new AT(1, $al, $ax, $ar->l), $ar->k, new AT(1, $ar->r, $k, $b));
        }
        $s->l = $l;
        return $s;
    }
    if ($x > $k) {
        $r = B_ins($x, $s->r);
        $a = $s->l; $b = $r;
        if ($c === 1 && $b !== null && $b->c === 0) {
            $bl = $b->l; $bx = $b->k; $br = $b->r;
            if ($bl !== null && $bl->c === 0) return new AT(0, new AT(1, $a, $k, $bl->l), $bl->k, new AT(1, $bl->r, $bx, $br));
            if ($br !== null && $br->c === 0) return new AT(0, new AT(1, $a, $k, $bl), $bx, new AT(1, $br->l, $br->k, $br->r));
        }
        $s->r = $r;
        return $s;
    }
    return $s;
}

function B_insert($x, $s) {
    $r = B_ins($x, $s);
    if ($r !== null && $r->c === 0) { $r->c = 1; }
    return $r;
}

function B_build($n, $acc) {
    while ($n > 0) { $acc = B_insert($n, $acc); $n--; }
    return $acc;
}

function B_depth($t) { return $t === null ? 0 : 1 + maxInt(B_depth($t->l), B_depth($t->r)); }

// ---------------- C: packed arrays, by-reference (COW) ----------------
function C_rotate($c, &$s): bool {
    $a = $s[1]; $x = $s[2]; $b = $s[3];
    if ($c === 1) {
        if ($a !== null && $a[0] === 0) {
            $al = $a[1]; $ax = $a[2]; $ar = $a[3];
            if ($al !== null && $al[0] === 0) { $s = [0, [1, $al[1], $al[2], $al[3]], $ax, [1, $ar, $x, $b]]; $GLOBALS['constructions'] += 3; return true; }
            if ($ar !== null && $ar[0] === 0) { $s = [0, [1, $al, $ax, $ar[1]], $ar[2], [1, $ar[3], $x, $b]]; $GLOBALS['constructions'] += 3; return true; }
        }
        if ($b !== null && $b[0] === 0) {
            $bl = $b[1]; $bx = $b[2]; $br = $b[3];
            if ($bl !== null && $bl[0] === 0) { $s = [0, [1, $a, $x, $bl[1]], $bl[2], [1, $bl[3], $bx, $br]]; $GLOBALS['constructions'] += 3; return true; }
            if ($br !== null && $br[0] === 0) { $s = [0, [1, $a, $x, $bl], $bx, [1, $br[1], $br[2], $br[3]]]; $GLOBALS['constructions'] += 3; return true; }
        }
    }
    return false;
}

function C_ins($x, &$s): void {
    if ($s === null) { $s = [0, null, $x, null]; $GLOBALS['constructions']++; return; }
    $c = $s[0]; $k = $s[2];
    if ($x < $k) { C_ins($x, $s[1]); C_rotate($c, $s); return; }
    if ($x > $k) { C_ins($x, $s[3]); C_rotate($c, $s); return; }
}

function C_insert($x, &$s): void {
    C_ins($x, $s);
    if ($s !== null && $s[0] === 0) { $s[0] = 1; }
}

function C_build($n, &$acc): void {
    while ($n > 0) { C_insert($n, $acc); $n--; }
}

function C_depth($t) { return $t === null ? 0 : 1 + maxInt(C_depth($t[1]), C_depth($t[3])); }

// ---------------- D: packed arrays, by value ----------------
function D_balance($c, $a, $x, $b) {
    if ($c === 1) {
        if ($a !== null && $a[0] === 0) {
            $al = $a[1]; $ax = $a[2]; $ar = $a[3];
            if ($al !== null && $al[0] === 0) { $GLOBALS['constructions'] += 3; return [0, [1, $al[1], $al[2], $al[3]], $ax, [1, $ar, $x, $b]]; }
            if ($ar !== null && $ar[0] === 0) { $GLOBALS['constructions'] += 3; return [0, [1, $al, $ax, $ar[1]], $ar[2], [1, $ar[3], $x, $b]]; }
        }
        if ($b !== null && $b[0] === 0) {
            $bl = $b[1]; $bx = $b[2]; $br = $b[3];
            if ($bl !== null && $bl[0] === 0) { $GLOBALS['constructions'] += 3; return [0, [1, $a, $x, $bl[1]], $bl[2], [1, $bl[3], $bx, $br]]; }
            if ($br !== null && $br[0] === 0) { $GLOBALS['constructions'] += 3; return [0, [1, $a, $x, $bl], $bx, [1, $br[1], $br[2], $br[3]]]; }
        }
    }
    $GLOBALS['constructions']++;
    return [$c, $a, $x, $b];
}

function D_ins($x, $s) {
    if ($s === null) { $GLOBALS['constructions']++; return [0, null, $x, null]; }
    $c = $s[0]; $a = $s[1]; $y = $s[2]; $b = $s[3];
    if ($x < $y) return D_balance($c, D_ins($x, $a), $y, $b);
    if ($x > $y) return D_balance($c, $a, $y, D_ins($x, $b));
    $GLOBALS['constructions']++;
    return [$c, $a, $y, $b];
}

function D_insert($x, $s) {
    $r = D_ins($x, $s);
    if ($r !== null && $r[0] === 0) { $GLOBALS['constructions']++; return [1, $r[1], $r[2], $r[3]]; }
    return $r;
}

function D_build($n, $acc) {
    while ($n > 0) { $acc = D_insert($n, $acc); $n--; }
    return $acc;
}

function D_depth($t) { return $t === null ? 0 : 1 + maxInt(D_depth($t[1]), D_depth($t[3])); }

// ---------------- driver ----------------
$variant = $argv[1] ?? 'A';
$n = (int)($argv[2] ?? 100000);
$rounds = (int)($argv[3] ?? 10);
$warmup = (int)($argv[4] ?? 3);

$timer = function () use ($variant, $n) {
    $GLOBALS['constructions'] = 0;
    $t0 = hrtime(true);
    if ($variant === 'A') { $tree = A_build($n, null); $depth = A_depth($tree); }
    elseif ($variant === 'B') { $tree = B_build($n, null); $depth = B_depth($tree); }
    elseif ($variant === 'C') { $acc = null; C_build($n, $acc); $depth = C_depth($acc); }
    else { $tree = D_build($n, null); $depth = D_depth($tree); }
    return [(hrtime(true) - $t0) / 1e6, $depth, $GLOBALS['constructions']];
};

for ($i = 0; $i < $warmup; $i++) { $timer(); }
$times = [];
$last = null;
for ($i = 0; $i < $rounds; $i++) {
    $last = $timer();
    $times[] = $last[0];
}
sort($times);
echo json_encode([
    'variant' => $variant,
    'n' => $n,
    'best_ms' => $times[0],
    'median_ms' => $times[intdiv(count($times), 2)],
    'depth' => $last[1],
    'constructions' => $last[2],
    'peak_mib' => memory_get_peak_usage(true) / 1048576.0,
]);
