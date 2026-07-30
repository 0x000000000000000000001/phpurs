const fs = require('fs');
try {
  fs.writeFileSync('output/Control.Category/.phpurs-cache.json', '{}');
  console.log("Success");
} catch (e) {
  console.log(e);
}
