import fs from 'fs';
import path from 'path';

function getScanDirs(rootDir, ffiDir) {
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
                    if (!hasVersion) scanDirs.push(pkgDir);
                }
            }
        }
    }

    if (ffiDir) scanDirs.push(path.resolve(rootDir, ffiDir));
    return scanDirs;
}

export const resolvePackagePathsImpl = function({ ffiDir, modulePaths }) {
    return function() {
        const rootDir = process.cwd();
        const scanDirs = getScanDirs(rootDir, ffiDir);
        const composerRoots = new Set(scanDirs);

        // Module roots contribute Composer requirements in module-name order.
        // Their FFI already has a direct path through the adjacent-file lookup.
        for (const modulePath of modulePaths) {
            if (!modulePath) continue;
            const match = modulePath.match(/^(.*?)\/(?:src|test)\//);
            composerRoots.add(path.resolve(rootDir, match ? match[1] : path.dirname(modulePath)));
        }

        return {
            ffiRoots: [...new Set([...scanDirs, rootDir])],
            composerRoots: [...composerRoots]
        };
    };
};

export const findForeignFileImpl = roots => moduleName => modulePath => () => {
    const seen = new Set();
    const exists = candidate => {
        const key = path.resolve(candidate);
        if (seen.has(key)) return false;
        seen.add(key);
        return fs.existsSync(candidate);
    };

    if (modulePath) {
        const adjacent = modulePath.replace(/\.purs$/, '.php');
        if (exists(adjacent)) return adjacent;
    }

    for (const root of roots) {
        const candidates = [
            path.join(root, 'src', ...moduleName.split('.')) + '.php',
            path.join(root, 'src', moduleName + '.php'),
            path.join(root, moduleName + '.php')
        ];
        for (const candidate of candidates) {
            if (exists(candidate)) return candidate;
        }
    }
    return null;
};
