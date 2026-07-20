(() => {
  // output-es/runtime.js
  function fail() {
    throw new Error("Failed pattern match");
  }

  // output-es/Data.Eq/foreign.js
  var refEq = function(r1) {
    return function(r2) {
      return r1 === r2;
    };
  };
  var eqIntImpl = refEq;

  // output-es/Data.Eq/index.js
  var eqInt = { eq: eqIntImpl };

  // output-es/Data.Show/foreign.js
  var showIntImpl = function(n) {
    return n.toString();
  };

  // output-es/Data.Show/index.js
  var showInt = { show: showIntImpl };

  // output-es/Effect.Console/foreign.js
  var log = function(s) {
    return function() {
      console.log(s);
    };
  };
  var error = function(s) {
    return function() {
      console.error(s);
    };
  };

  // output-es/Test.Assert/foreign.js
  function assertImpl(message) {
    return function(success) {
      return function() {
        if (!success) throw new Error(message);
      };
    };
  }
  function checkThrows(fn) {
    return function() {
      try {
        fn();
        return false;
      } catch (e) {
        if (e instanceof Error) return true;
        var err = new Error("Threw something other than an Error");
        err.something = e;
        throw err;
      }
    };
  }

  // output-es/Test.Assert/index.js
  var assertEqual$p = (dictEq) => (dictShow) => (userMessage) => (v) => {
    const result = dictEq.eq(v.actual)(v.expected);
    const message = (userMessage === "" ? "Expected: " : userMessage + "\nExpected: ") + dictShow.show(v.expected) + "\nActual:   " + dictShow.show(v.actual);
    const $0 = error(message);
    const $1 = (() => {
      if (!result) {
        return $0;
      }
      if (result) {
        return () => {
        };
      }
      fail();
    })();
    return () => {
      $1();
      return assertImpl(message)(result)();
    };
  };
  var assertThrows$p = (msg) => (fn) => {
    const $0 = assertImpl(msg);
    const $1 = checkThrows(fn);
    return () => {
      const $2 = $1();
      return $0($2)();
    };
  };

  // output-es/Main/index.js
  var main = /* @__PURE__ */ (() => {
    const $0 = assertEqual$p(eqInt)(showInt)("")({
      expected: 2e5,
      actual: (() => {
        const f = (f$a0$copy) => (f$a1$copy) => {
          let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
          while (f$c) {
            const x = f$a0, y = f$a1;
            const $02 = x + 2 | 0;
            const $1 = y - 1 | 0;
            if ($1 <= 0) {
              f$c = false;
              f$r = $02;
              continue;
            }
            f$a0 = $02;
            f$a1 = $1;
          }
          return f$r;
        };
        return f(0)(1e5);
      })()
    });
    return () => {
      $0();
      assertEqual$p(eqInt)(showInt)("")({
        expected: 2e5,
        actual: (() => {
          const f = (f$a0$copy) => (f$a1$copy) => {
            let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
            while (f$c) {
              const x = f$a0, y = f$a1;
              const $1 = x + 2 | 0;
              const $2 = y - 1 | 0;
              if ($2 <= 0) {
                f$c = false;
                f$r = $1;
                continue;
              }
              f$a0 = $1;
              f$a1 = $2;
            }
            return f$r;
          };
          return f(0)(1e5);
        })()
      })();
      assertEqual$p(eqInt)(showInt)("")({
        expected: 249997,
        actual: (() => {
          const f = (f$a0$copy) => (f$a1$copy) => {
            let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
            while (f$c) {
              const x = f$a0, y = f$a1;
              const g = (g$a0$copy) => (g$a1$copy) => {
                let g$a0 = g$a0$copy, g$a1 = g$a1$copy, g$c = true, g$r;
                while (g$c) {
                  const x$p = g$a0, y$p = g$a1;
                  if (y$p <= 0) {
                    f$c = g$c = false;
                    f$r = x$p;
                    continue;
                  }
                  if (y$p > 5e4) {
                    g$a0 = x$p + 3 | 0;
                    g$a1 = y$p - 1 | 0;
                    continue;
                  }
                  g$c = false;
                  f$a0 = x$p + 2 | 0;
                  f$a1 = y$p;
                }
                return g$r;
              };
              g(x)(y - 1 | 0);
            }
            return f$r;
          };
          return f(0)(1e5);
        })()
      })();
      assertEqual$p(eqInt)(showInt)("")({
        expected: 2e5,
        actual: (() => {
          const f = (f$a0$copy) => (f$a1$copy) => {
            let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
            while (f$c) {
              const x = f$a0, y = f$a1;
              if (y <= 0) {
                f$c = false;
                f$r = x;
                continue;
              }
              f$a0 = x + 2 | 0;
              f$a1 = y - 1 | 0;
            }
            return f$r;
          };
          return f(0)(1e5);
        })()
      })();
      assertEqual$p(eqInt)(showInt)("")({
        expected: 1009,
        actual: (() => {
          const f = (x) => {
            if (x > 1e3) {
              return (v) => x + v | 0;
            }
            return (y$p) => f(x + 10 | 0)(y$p - 1 | 0);
          };
          return f(0)(100);
        })()
      })();
      assertThrows$p("Assertion failed: An error should have been thrown")((v) => {
        const f = (x) => {
          if (x > 1e6) {
            return (v$1) => x + v$1 | 0;
          }
          return (y$p) => f(x + 10 | 0)(y$p - 1 | 0);
        };
        return f(0)(1e5);
      })();
      assertEqual$p(eqInt)(showInt)("")({
        expected: 200,
        actual: (() => {
          const f = (f$a0$copy) => (f$a1$copy) => {
            let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
            while (f$c) {
              const x = f$a0, y = f$a1;
              if (y <= 0) {
                f$c = false;
                f$r = x;
                continue;
              }
              f$a0 = x + 2 | 0;
              f$a1 = y - 1 | 0;
            }
            return f$r;
          };
          return f(0)(100);
        })()
      })();
      assertThrows$p("Assertion failed: An error should have been thrown")((v) => {
        const f = (f$a0$copy) => (f$a1$copy) => {
          let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
          while (f$c) {
            const x = f$a0, y = f$a1;
            if (y <= 0) {
              f$c = false;
              f$r = x;
              continue;
            }
            f$a0 = x + 2 | 0;
            f$a1 = y - 1 | 0;
          }
          return f$r;
        };
        return f(0)(1e5);
      })();
      assertEqual$p(eqInt)(showInt)("")({
        expected: 200,
        actual: (() => {
          const f = (x) => (y) => {
            const g = f(x + 2 | 0);
            if (y <= 0) {
              return x;
            }
            return g(y - 1 | 0);
          };
          return f(0)(100);
        })()
      })();
      assertThrows$p("Assertion failed: An error should have been thrown")((v) => {
        const f = (x) => (y) => {
          const g = f(x + 2 | 0);
          if (y <= 0) {
            return x;
          }
          return g(y - 1 | 0);
        };
        return f(0)(1e5);
      })();
      assertEqual$p(eqInt)(showInt)("")({
        expected: 200,
        actual: (() => {
          const f = (f$a0$copy) => (f$a1$copy) => {
            let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
            while (f$c) {
              const x = f$a0, y = f$a1;
              if (y <= 0) {
                f$c = false;
                f$r = x;
                continue;
              }
              f$a0 = x + 2 | 0;
              f$a1 = y - 1 | 0;
            }
            return f$r;
          };
          return f(0)(100);
        })()
      })();
      assertThrows$p("Assertion failed: An error should have been thrown")((v) => {
        const f = (f$a0$copy) => (f$a1$copy) => {
          let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
          while (f$c) {
            const x = f$a0, y = f$a1;
            if (y <= 0) {
              f$c = false;
              f$r = x;
              continue;
            }
            f$a0 = x + 2 | 0;
            f$a1 = y - 1 | 0;
          }
          return f$r;
        };
        return f(0)(1e5);
      })();
      return log("Done")();
    };
  })();

  // <stdin>
  main();
})();
