export const objectHasOwnProperty = function (k) {
  return function (m) {
    return Object.prototype.hasOwnProperty.call(m, k);
  };
};
