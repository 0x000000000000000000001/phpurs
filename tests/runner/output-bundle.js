// output-es/Data.Show/foreign.js
var showIntImpl = function(n) {
  return n.toString();
};

// output-es/Effect.Console/foreign.js
var log = function(s) {
  return function() {
    console.log(s);
  };
};

// output-es/Main/foreign.js
var mergeImpl = function(l) {
  return function(r) {
    var o = {};
    return Object.assign(o, r, l);
  };
};

// output-es/Main/index.js
var test1 = /* @__PURE__ */ mergeImpl({ x: 1 })({ y: true });
var main = /* @__PURE__ */ (() => {
  const $0 = log(showIntImpl(test1.x));
  return () => {
    $0();
    log(test1.y ? "true" : "false")();
    log(test1.x === 1 ? "true" : "false")();
    log(showIntImpl(mergeImpl({ x: 1 })({ x: 0, y: true, z: 42 }).x))();
    log(showIntImpl(mergeImpl({ x: 1 })({ y: 1, z: 1 }).x))();
    log(showIntImpl(mergeImpl({ x: 1 })({ y: 1, z: 1 }).y))();
    log(showIntImpl(mergeImpl({ x: 1 })({ y: 1, z: 1 }).z))();
    log(showIntImpl(mergeImpl({ x: 1, y: 2 })({ y: 1, z: 1 }).x))();
    log(showIntImpl(mergeImpl({ x: 1, y: 2 })({ y: 1, z: 1 }).y))();
    log(showIntImpl(mergeImpl({ x: 1, y: 2 })({ y: 1, z: 1 }).z))();
    log(showIntImpl(mergeImpl({ x: 1, y: 2 })({ y: 1, z: 1 }).x))();
    log(showIntImpl(mergeImpl({ x: 1, y: 2 })({ y: 1, z: 1 }).y))();
    log(showIntImpl(mergeImpl({ x: 1, y: 2 })({ y: 1, z: 1 }).z))();
    return log("Done")();
  };
})();

// <stdin>
main();
