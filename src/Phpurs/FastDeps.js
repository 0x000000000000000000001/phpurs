import fs from 'fs';

export const parseAllImportsImpl = function(files) {
  return function() {
    const results = [];
    for (const file of files) {
      try {
        const content = fs.readFileSync(file, 'utf8');
        const json = JSON.parse(content);
        const imports = json.imports || [];
        results.push({
          moduleName: json.moduleName || [],
          imports: imports.map(function(i) { return i.moduleName || []; })
        });
      } catch (e) {
        // ignore virtual modules or errors
      }
    }
    return results;
  };
};
