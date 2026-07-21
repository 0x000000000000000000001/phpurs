import fs from 'fs';
import path from 'path';

let cachedScanDirs = null;

function getScanDirs(mbFfiDir) {
    if (cachedScanDirs !== null) return cachedScanDirs;
    
    const rootDir = process.cwd();
    const scanDirs = [];
    
    const spagoDirs = [
        path.join(rootDir, '.spago'),
        path.join(rootDir, 'spago.d'),
        path.join(rootDir, 'bak/spago.d/php/p')
    ];
    
    for (const spagoDir of spagoDirs) {
        if (fs.existsSync(spagoDir) && fs.statSync(spagoDir).isDirectory()) {
            const packages = fs.readdirSync(spagoDir);
            for (const pkg of packages) {
                const pkgDir = path.join(spagoDir, pkg);
                if (fs.statSync(pkgDir).isDirectory()) {
                    let hasVersion = false;
                    const subdirs = fs.readdirSync(pkgDir);
                    for (const subdir of subdirs) {
                        const versionDir = path.join(pkgDir, subdir);
                        if (subdir.startsWith('v') && fs.statSync(versionDir).isDirectory()) {
                            scanDirs.push(versionDir);
                            hasVersion = true;
                        }
                    }
                    if (!hasVersion) {
                        scanDirs.push(pkgDir);
                    }
                }
            }
        }
    }
    
    if (mbFfiDir) {
        scanDirs.push(path.join(rootDir, mbFfiDir));
    }
    
    cachedScanDirs = scanDirs;
    return scanDirs;
}

// FFI Support Helpers

let phpFileIndex = null;

function buildPhpFileIndex(scanDirs) {
    if (phpFileIndex !== null) return;
    phpFileIndex = new Set();
    
    function walk(dir) {
        let entries;
        try {
            entries = fs.readdirSync(dir, { withFileTypes: true });
        } catch (e) {
            return;
        }
        for (const entry of entries) {
            const res = path.join(dir, entry.name);
            if (entry.isDirectory()) {
                walk(res);
            } else if (entry.name.endsWith('.php')) {
                phpFileIndex.add(res);
            }
        }
    }
    
    for (const d of scanDirs) {
        walk(d);
    }
}

export const findFfiFileImpl = function(mbFfiDir) {
    return function(modNameStr) {
        return function(mbModulePath) {
            return function() {
                if (mbModulePath) {
                    const phpPath = mbModulePath.replace(/\.purs$/, '.php');
                    if (fs.existsSync(phpPath)) {
                        return phpPath;
                    }
                }
                
                const scanDirs = getScanDirs(mbFfiDir);
                buildPhpFileIndex(scanDirs);
                
                for (const dir of scanDirs) {
                    // Search in dir/src and dir/
                    const searchPaths = [
                        path.join(dir, 'src', ...modNameStr.split('.')) + '.php',
                        path.join(dir, 'src', modNameStr + '.php'),
                        path.join(dir, modNameStr + '.php')
                    ];
                    for (const p of searchPaths) {
                        if (phpFileIndex.has(p)) {
                            return p;
                        }
                    }
                }
                return null;
            };
        };
    };
};

