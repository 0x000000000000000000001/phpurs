export const computeTransitiveDirtyFastImpl = function(initiallyDirty) {
  return function(modules) {
    const reverseGraph = Object.create(null);
    for (const m of modules) {
      const importer = m.moduleName.join('.');
      for (const imp of m.imports) {
        const imported = imp.join('.');
        if (!reverseGraph[imported]) {
          reverseGraph[imported] = [];
        }
        reverseGraph[imported].push(importer);
      }
    }
    
    const visited = new Set();
    const stack = [];
    for (const d of initiallyDirty) {
      if (!visited.has(d)) {
        visited.add(d);
        stack.push(d);
      }
    }
    
    while (stack.length > 0) {
      const node = stack.pop();
      const deps = reverseGraph[node];
      if (deps) {
        for (const dep of deps) {
          if (!visited.has(dep)) {
            visited.add(dep);
            stack.push(dep);
          }
        }
      }
    }
    
    const res = [];
    visited.forEach(function(v) { res.push(v); });
    return res;
  };
};

export const computeReachableModulesFastImpl = function(root) {
  return function(modules) {
    const graph = Object.create(null);
    for (const m of modules) {
      const importer = m.moduleName.join('.');
      const deps = [];
      for (const imp of m.imports) {
        deps.push(imp.join('.'));
      }
      graph[importer] = deps;
    }
    
    const visited = new Set();
    const stack = [root];
    visited.add(root);
    
    while (stack.length > 0) {
      const node = stack.pop();
      const deps = graph[node];
      if (deps) {
        for (const dep of deps) {
          if (!visited.has(dep)) {
            visited.add(dep);
            stack.push(dep);
          }
        }
      }
    }
    
    const res = [];
    visited.forEach(function(v) { res.push(v); });
    return res;
  };
};
