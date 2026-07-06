export const sanitizeImpl = function(s) {
  let s1 = s.replace(/\$/g, '__dollar__').replace(/'/g, '__prime__').replace(/"/g, '__quote__');
  
  if (s1 === 'Node_URL_pathToFileURL' || s1 === 'pathToFileURL') {
    return s1 + '__2';
  }
  if (s1 === 'Node_URL_fileURLToPath' || s1 === 'fileURLToPath') {
    return s1 + '__2';
  }

  let parts = s1.split('_');
  let lastPart = parts[parts.length - 1] || "";
  if (/^[A-Z]/.test(lastPart)) {
    return s1 + "__data";
  }
  return s1;
};
