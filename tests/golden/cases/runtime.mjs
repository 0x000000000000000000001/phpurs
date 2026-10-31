// Golden case: the shared PHP runtime, including the Aff promise bridge.
import { sharedRuntimeFile } from '../../../output/Phpurs.Printer.Runtime/index.js';

export const name = 'runtime';

export function render() {
  return { 'runtime.php': sharedRuntimeFile };
}
