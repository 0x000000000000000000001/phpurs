// Validate the Conventional Commits shape used for PR titles and squash
// messages. CI passes the pull-request title; locally pass the message.
const pattern = /^(feat|fix|refactor|docs|test|build|perf|chore)(\([a-z0-9-]+\))?: .{3,}$/;
const message = process.argv.slice(2).join(' ').trim();

if (message.length === 0) {
  console.error('usage: npm run check:commit -- "type(scope): summary"');
  process.exit(1);
}
if (!pattern.test(message)) {
  console.error(
    `invalid commit message: ${JSON.stringify(message)}\n` +
      'expected: type(scope): summary, type in feat|fix|refactor|docs|test|build|perf|chore',
  );
  process.exit(1);
}
console.log(`commit message ok: ${message}`);
