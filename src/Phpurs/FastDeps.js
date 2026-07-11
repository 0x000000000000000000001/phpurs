import fs from 'fs';
import path from 'path';

export const parseAllImportsImpl = function(files) {
  return function() {
    const cachePath = 'output/.phpurs-cache.json';
    let cache = { modules: {} };
    try {
      if (fs.existsSync(cachePath)) {
        cache = JSON.parse(fs.readFileSync(cachePath, 'utf8'));
      }
    } catch (e) {}

    let newCache = { modules: {} };
    let changed = false;
    const results = [];

    for (const file of files) {
      try {
        const stat = fs.statSync(file, {throwIfNoEntry: false});
        if (!stat) continue;
        
        const mtime = stat.mtimeMs;
        const dirName = path.basename(path.dirname(file));

        if (cache.modules && cache.modules[dirName] && cache.modules[dirName].mtime === mtime) {
          const cachedMod = cache.modules[dirName];
          newCache.modules[dirName] = cachedMod;
          results.push({
            moduleName: cachedMod.moduleName,
            imports: cachedMod.imports,
            exports: cachedMod.exports || []
          });
        } else {
          const content = fs.readFileSync(file, 'utf8');
          const json = JSON.parse(content);
          const imports = json.imports || [];
          
          const modName = json.moduleName || [];
          const importsList = imports.map(function(i) { return i.moduleName || []; });
          const exportsList = json.exports || [];
          
          newCache.modules[dirName] = {
            mtime: mtime,
            moduleName: modName,
            imports: importsList,
            exports: exportsList,
            modulePath: json.modulePath || null
          };
          
          results.push({
            moduleName: modName,
            imports: importsList,
            exports: exportsList
          });
          changed = true;
        }
      } catch (e) {
        // ignore virtual modules or errors
      }
    }
    
    if (changed) {
      try {
        fs.writeFileSync(cachePath, JSON.stringify(newCache));
      } catch (e) {
        console.error("FastDeps cache write failed:", e);
      }
    }

    return results;
  };
};
