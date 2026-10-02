export const isMissingFile = error => error.code === 'ENOENT';

// Same bounded policy as PBO.App; scheduling must preserve the input order.
export const moduleReadConcurrency = () => {
  const configured = process.env.GOPURS_JOBS ?? '';
  const jobs = Number(configured);
  return /^\d+$/.test(configured) && jobs >= 1 && jobs <= 64 ? jobs : 1;
};
