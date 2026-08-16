import fs from "fs";

const corefn = JSON.parse(fs.readFileSync("../phpurs-strings-extra/output/Data.CodePoint.Unicode.Internal/corefn.json", "utf8"));
// Find bsearch
const bsearch = corefn.decls.find(d => d.binds && d.binds.some(b => b.identifier === "bsearch"));
if (bsearch) {
  console.log(JSON.stringify(bsearch, null, 2));
} else {
  console.log("bsearch not found");
}
