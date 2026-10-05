import fs from 'node:fs';

const bindings = [];
globalThis.phpursAuditRewriteStart = (qualified, limit, initialSize) => {
  const row = { module: qualified.value0.value0 ?? '', binding: qualified.value1,
    limit, initialSize, passes: [], chunkNodes: 0, chunkLeaves: 0, largestChunk: 0 };
  bindings.push(row);
  return {
    step(remaining, size, rewrite) { row.passes.push({ remaining, size, rewrite }); },
    chunk(size) {
      row.chunkNodes++;
      if (size <= 2000) row.chunkLeaves++;
      row.largestChunk = Math.max(row.largestChunk, size);
    },
  };
};
process.on('exit', code => {
  fs.writeFileSync(process.env.PHPURS_REWRITE_PROBE, JSON.stringify({ code, bindings }) + '\n');
});
