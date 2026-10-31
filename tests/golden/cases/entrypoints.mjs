// Golden case: modular and bundle entrypoints share startup behavior.
import { Nothing } from '../../../output/Data.Maybe/index.js';
import { empty } from '../../../output/Data.Map/index.js';
import { ModuleName } from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import {
  printBundleEntryPoint,
  printModularEntryPoint,
} from '../../../output/Phpurs.EntryPoint/index.js';

export const name = 'entrypoints';

const options = {
  mainModule: 'Demo',
  autoloadPath: Nothing.value,
  arities: empty,
};
const dependencies = [new ModuleName('Demo')];

export function render() {
  return {
    'bin/main.php': printModularEntryPoint(options)(dependencies),
    'bundle.php': printBundleEntryPoint(options),
  };
}
