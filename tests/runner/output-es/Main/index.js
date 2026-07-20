import * as $runtime from "../runtime.js";
import * as Data$dEq from "../Data.Eq/index.js";
import * as Data$dShow from "../Data.Show/index.js";
import * as Effect$dConsole from "../Effect.Console/index.js";
import * as Test$dAssert from "../Test.Assert/index.js";
const tco4 = /* #__PURE__ */ (() => {
  const f = f$a0$copy => f$a1$copy => {
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
  return f(0);
})();
const tco3 = y0 => {
  const f = f$a0$copy => f$a1$copy => {
    let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
    while (f$c) {
      const x = f$a0, y = f$a1;
      const g = g$a0$copy => g$a1$copy => {
        let g$a0 = g$a0$copy, g$a1 = g$a1$copy, g$c = true, g$r;
        while (g$c) {
          const x$p = g$a0, y$p = g$a1;
          if (y$p <= 0) {
            f$c = g$c = false;
            f$r = x$p;
            continue;
          }
          if (y$p > $runtime.intDiv(y0, 2)) {
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
  return f(0)(y0);
};
const tco2 = /* #__PURE__ */ (() => {
  const f = f$a0$copy => f$a1$copy => {
    let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
    while (f$c) {
      const x = f$a0, y = f$a1;
      const $0 = x + 2 | 0;
      const $1 = y - 1 | 0;
      if ($1 <= 0) {
        f$c = false;
        f$r = $0;
        continue;
      }
      f$a0 = $0;
      f$a1 = $1;
    }
    return f$r;
  };
  return f(0);
})();
const tco1 = /* #__PURE__ */ (() => {
  const f = f$a0$copy => f$a1$copy => {
    let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
    while (f$c) {
      const x = f$a0, y = f$a1;
      const $0 = x + 2 | 0;
      const $1 = y - 1 | 0;
      if ($1 <= 0) {
        f$c = false;
        f$r = $0;
        continue;
      }
      f$a0 = $0;
      f$a1 = $1;
    }
    return f$r;
  };
  return f(0);
})();
const ntco4 = /* #__PURE__ */ (() => {
  const f = f$a0$copy => f$a1$copy => {
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
  return f(0);
})();
const ntco3 = /* #__PURE__ */ (() => {
  const f = x => y => {
    const g = f(x + 2 | 0);
    if (y <= 0) { return x; }
    return g(y - 1 | 0);
  };
  return f(0);
})();
const ntco2 = /* #__PURE__ */ (() => {
  const f = f$a0$copy => f$a1$copy => {
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
  return f(0);
})();
const ntco1 = y0 => {
  const f = x => {
    if (x > (10 * y0 | 0)) { return v => x + v | 0; }
    return y$p => f(x + 10 | 0)(y$p - 1 | 0);
  };
  return f(0)(y0);
};
const main = /* #__PURE__ */ (() => {
  const $0 = Test$dAssert.assertEqual$p(Data$dEq.eqInt)(Data$dShow.showInt)("")({
    expected: 200000,
    actual: (() => {
      const f = f$a0$copy => f$a1$copy => {
        let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
        while (f$c) {
          const x = f$a0, y = f$a1;
          const $0 = x + 2 | 0;
          const $1 = y - 1 | 0;
          if ($1 <= 0) {
            f$c = false;
            f$r = $0;
            continue;
          }
          f$a0 = $0;
          f$a1 = $1;
        }
        return f$r;
      };
      return f(0)(100000);
    })()
  });
  return () => {
    $0();
    Test$dAssert.assertEqual$p(Data$dEq.eqInt)(Data$dShow.showInt)("")({
      expected: 200000,
      actual: (() => {
        const f = f$a0$copy => f$a1$copy => {
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
        return f(0)(100000);
      })()
    })();
    Test$dAssert.assertEqual$p(Data$dEq.eqInt)(Data$dShow.showInt)("")({
      expected: 249997,
      actual: (() => {
        const f = f$a0$copy => f$a1$copy => {
          let f$a0 = f$a0$copy, f$a1 = f$a1$copy, f$c = true, f$r;
          while (f$c) {
            const x = f$a0, y = f$a1;
            const g = g$a0$copy => g$a1$copy => {
              let g$a0 = g$a0$copy, g$a1 = g$a1$copy, g$c = true, g$r;
              while (g$c) {
                const x$p = g$a0, y$p = g$a1;
                if (y$p <= 0) {
                  f$c = g$c = false;
                  f$r = x$p;
                  continue;
                }
                if (y$p > 50000) {
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
        return f(0)(100000);
      })()
    })();
    Test$dAssert.assertEqual$p(Data$dEq.eqInt)(Data$dShow.showInt)("")({
      expected: 200000,
      actual: (() => {
        const f = f$a0$copy => f$a1$copy => {
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
        return f(0)(100000);
      })()
    })();
    Test$dAssert.assertEqual$p(Data$dEq.eqInt)(Data$dShow.showInt)("")({
      expected: 1009,
      actual: (() => {
        const f = x => {
          if (x > 1000) { return v => x + v | 0; }
          return y$p => f(x + 10 | 0)(y$p - 1 | 0);
        };
        return f(0)(100);
      })()
    })();
    Test$dAssert.assertThrows$p("Assertion failed: An error should have been thrown")(v => {
      const f = x => {
        if (x > 1000000) { return v$1 => x + v$1 | 0; }
        return y$p => f(x + 10 | 0)(y$p - 1 | 0);
      };
      return f(0)(100000);
    })();
    Test$dAssert.assertEqual$p(Data$dEq.eqInt)(Data$dShow.showInt)("")({
      expected: 200,
      actual: (() => {
        const f = f$a0$copy => f$a1$copy => {
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
    Test$dAssert.assertThrows$p("Assertion failed: An error should have been thrown")(v => {
      const f = f$a0$copy => f$a1$copy => {
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
      return f(0)(100000);
    })();
    Test$dAssert.assertEqual$p(Data$dEq.eqInt)(Data$dShow.showInt)("")({
      expected: 200,
      actual: (() => {
        const f = x => y => {
          const g = f(x + 2 | 0);
          if (y <= 0) { return x; }
          return g(y - 1 | 0);
        };
        return f(0)(100);
      })()
    })();
    Test$dAssert.assertThrows$p("Assertion failed: An error should have been thrown")(v => {
      const f = x => y => {
        const g = f(x + 2 | 0);
        if (y <= 0) { return x; }
        return g(y - 1 | 0);
      };
      return f(0)(100000);
    })();
    Test$dAssert.assertEqual$p(Data$dEq.eqInt)(Data$dShow.showInt)("")({
      expected: 200,
      actual: (() => {
        const f = f$a0$copy => f$a1$copy => {
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
    Test$dAssert.assertThrows$p("Assertion failed: An error should have been thrown")(v => {
      const f = f$a0$copy => f$a1$copy => {
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
      return f(0)(100000);
    })();
    return Effect$dConsole.log("Done")();
  };
})();
export {main, ntco1, ntco2, ntco3, ntco4, tco1, tco2, tco3, tco4};
