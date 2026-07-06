import fs from 'fs';

export const getMtimeMsImpl = function(file) {
  return function() {
    try {
      return fs.statSync(file).mtimeMs;
    } catch (e) {
      return 0.0;
    }
  };
};
