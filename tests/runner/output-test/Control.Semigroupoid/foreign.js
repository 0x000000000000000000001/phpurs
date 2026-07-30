export const composeImpl = function (f) {
  return function (g) {
    return function (x) {
      return f(g(x));
    };
  };
};
