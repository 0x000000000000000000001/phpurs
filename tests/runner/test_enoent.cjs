const fs = require('fs');

async function test() {
  fs.mkdirSync('output/Test.Module', { recursive: true });
  fs.writeFileSync('output/Test.Module/corefn.json', '{}');
  
  // Pretend coreFnModulesFromOutput found it
  
  // Now try to write cache
  try {
    fs.writeFileSync('output/Test.Module/.phpurs-cache.json', '{}');
    console.log("Write success");
  } catch (e) {
    console.log("Write failed", e);
  }
}
test();
