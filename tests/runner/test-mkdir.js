const fs = require('fs');
try {
  fs.mkdirSync('output/B');
  console.log('created');
} catch (e) {
  console.log('mkdir failed:', e);
}
try {
  fs.writeFileSync('output/B/index.php', 'hello');
  console.log('written');
} catch(e) {
  console.log('write failed:', e);
}
