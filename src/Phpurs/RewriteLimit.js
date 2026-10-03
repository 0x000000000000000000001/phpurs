const flag = '--rewrite-limit';

export const parseLimitImpl = left => right => defaultLimit => args => {
  // Match the other PHPurs numeric option: decimal syntax, either spelling,
  // and the first occurrence wins. PBO decrements a PureScript Int to zero.
  for (let i = 0; i < args.length; i++) {
    const arg = args[i];
    let value;
    if (arg === flag) value = args[i + 1];
    else if (arg.startsWith(flag + '=')) value = arg.slice(flag.length + 1);
    else continue;
    if (value === undefined) return left('Missing value for ' + flag);
    const limit = Number(value);
    if (value.length === 0 || /[^0-9]/.test(value) || !Number.isInteger(limit) || limit < 1 || limit > 2147483647) {
      return left('Invalid value for ' + flag + ': ' + JSON.stringify(value)
        + '; expected a positive decimal integer from 1 to 2147483647');
    }
    return right(limit);
  }
  return right(defaultLimit);
};
