const flag = '--purmeta-cache-mib';

export const parseBudgetImpl = left => absent => right => args => {
  // Like the shared CLI parser, the first occurrence wins. Both spellings use
  // decimal, integral MiB, with an exact safe integer byte count passed to PBO.
  for (let i = 0; i < args.length; i++) {
    const arg = args[i];
    let value;
    if (arg === flag) value = args[i + 1];
    else if (arg.startsWith(flag + '=')) value = arg.slice(flag.length + 1);
    else continue;
    if (value === undefined) return left('Missing value for ' + flag);
    const bytes = Number(value) * 1024 * 1024;
    if (!/^\d+$/.test(value) || !Number.isSafeInteger(bytes) || bytes < 0) {
      return left('Invalid value for ' + flag + ': ' + JSON.stringify(value)
        + '; expected a non-negative integer MiB budget with a safe integer byte count');
    }
    return right(bytes);
  }
  return absent;
};
