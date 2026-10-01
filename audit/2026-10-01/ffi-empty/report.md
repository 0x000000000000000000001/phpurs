# B3 — Éviter les recherches FFI des modules sans foreign

Référence : commit PHPurs `de27407`, après le cleanup Composer. Comparaison avec la garde `Map.isEmpty backendMod.foreign` ajoutée dans `Main`, le 1er octobre 2026.

## Contrat et régression

Le backend conserve les déclarations foreign du CoreFn dans `backendMod.foreign`. Si cette table est vide, `Main` fournit directement une source vide à `genForeignModule` : ni recherche ni lecture d'un fichier PHP. Un fichier adjacent ou trouvé par le fallback ne constitue pas une déclaration foreign à lui seul.

`tests/codegen/file-emission.mjs` couvre désormais deux modules purement PureScript, avec un ancien PHP adjacent et un autre candidat dans le fallback. Ces fichiers lèvent une exception s'ils sont incorporés. Une sonde des appels `fs.existsSync` vérifie que leurs chemins ne sont même pas recherchés. Les modules avec foreign continuent à résoudre leurs fichiers locaux et via `--ffi`, et les dépendances Composer des modules sans foreign sont conservées.

- Avant correction : les trois variantes d'émission échouent sur l'assertion d'absence de sondes FFI (6 sondes inutiles par défaut, 9 avec `--ffi`).
- Après correction : les trois variantes passent, avec les entrées modulaires et bundle qui affichent `42`, sans diagnostic PHP.
- `npm run build` réussit sans avertissement du compilateur PureScript ; `npm run test:codegen` passe les 17 contrôles rapportés par Node.

## Mesure et diff b8x

Même snapshot que le [lot Composer](../composer-emission/report.md) : 2 684 CoreFn, dont 299 modules avec foreign et 2 385 sans foreign, accompagnés des mêmes FFI et manifestes. Deux copies fraîches sont générées avec `--main Inter.Api.Main --bundle` et la même instrumentation. Les deux processus terminent avec code 0, sans erreur de décodage.

Le compteur exploité est `phpExists`, qui intercepte `fs.existsSync` pour les chemins terminant par `.php` : **17 978 → 1 289**, soit **16 689 sondes évitées**. Cette mesure porte sur les appels de sondage, pas sur le temps total ni sur un nombre de lectures disque physiques.

La liste des 5 371 fichiers de sortie est identique. Deux fichiers changent :

- `Control.Applicative/index.php` ;
- `Inter.Api.Main/main.bundle.php`.

Dans chacun, le seul changement est le retrait du même bloc FFI de **213 octets** : création d'`$arrayPure` et table `$ffi_Control_Applicative`. Le module ne déclare plus cette foreign ; son instance `applicativeArray` construit déjà le tableau directement en PureScript. Une comparaison exacte après retrait de ce bloc confirme l'identité de tout le reste. Les 2 684 autres fichiers PHP, les CoreFn et le manifeste Composer sont identiques.

La paire instrumentée donne 53 388 → 54 019 ms pour le backend total, et 47 253 → 46 931 ms pour « optimize + emit ». Elle ne démontre pas un gain stable de temps de compilation. Le résultat établi est la suppression des recherches inutiles et du bloc FFI mort.

Empreintes SHA-256 des bundles du backend :

- Avant : `d483614dd897061b39ad998e8ad7160f46f7bb840d11d4b1aae4f75c79f62781`.
- Après : `7a73d55366e40f67a1ffc6f12f17381ae9367d627c23df1e8318aa7250a9d198`.

Artefacts locaux : `/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/phpurs-b3-ffi-snccgo55` contient les copies avant/après, les journaux, `count-ffi.mjs`, les `ffi-counts.json` et `comparison.json`.
