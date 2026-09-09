# R13 — réutiliser les applications partielles internes

La passe `Phpurs.PartialBindings` est intégrée au générateur, avant la fusion des thunks et les régions ADT. Sur le point d'entrée normal de la suite, Church passe de **9,011–9,170 ms à 2,088–2,170 ms**, soit **75,9–77,2 % de moins**. Le total passe de **130,428–132,246 ms à 126,069–127,121 ms**, soit **2,54–4,67 % de moins** dans les deux paires de processus.

## Transformation et preuve

Le TAST montre le constructeur récursif suivant après optimisation PBO :

```text
build 0 = neutral
build n = let previous = build (n - 1)
          in \f -> \x -> f (previous f x)
```

Dans une copie interne, le générateur émet :

```text
build n = let previous = build (n - 1)
          in \f -> let bound = previous f
                   in \x -> f (bound x)
```

Cette étape déplace uniquement l'application partielle du successeur. Les compositions de producteurs restent des compositions de closures ; leur provenance est vérifiée et leurs références pointent vers les copies internes. Le neutre prouvé est recopié comme une lambda identité spécialisée. Le TAST conserve l'arité runtime de un argument sur les copies, pour générer des appels directs et éviter des références `$GLOBALS` privées.

La preuve exige un constructeur récursif isolé, un neutre qui ignore son premier argument et retourne le second, un compte à rebours de un jusqu'à zéro, et la forme exacte des appels/captures. Les compositions ne peuvent utiliser que des producteurs déjà prouvés. La consommation doit être entièrement appliquée, retourner un entier et recevoir un callback littéral composé d'une addition, soustraction ou multiplication entière avec une constante. Le compte et la graine sont des locaux ou littéraux entiers typés. Le corps original de ce callback est conservé, opération par opération, y compris lors des débordements PHP.

L'induction porte sur une chaîne déjà construite : préparer son callback ne fait que retourner des closures. Ni le typage fonctionnel seul, ni une annotation « pure » implicite ne permettent de déplacer un appel arbitraire. Callbacks inconnus, FFI, captures de valeurs, divisions, appels partiels conservés et groupes mutuellement récursifs sont refusés. Les fonctions publiques et les chemins qui fuient restent inchangés.

Bornes : 512 nœuds par producteur, 8 192 par déclaration parcourue, profondeur 96, largeur 64, carburant total 32 768 et 32 copies au maximum. Les identifiants de locaux introduits sont frais ; les préfixes PHP sont comparés sans tenir compte de la casse. Aucun nom de benchmark n'intervient dans la passe.

## Preuves par l'exécution

- `bin/php/run -c` dans **altbak.pub-phpurs** termine avec code 0 et les 14 résultats attendus ; total de ce run **123,08 ms**, distinct du protocole comparatif ci-dessous. Le compilateur hôte garde ses 23 avertissements antérieurs ; la nouvelle passe n'en ajoute pas. Le programme TAST compile sans erreur ni avertissement.
- Neuf suites AST passent, dont `tests/codegen/partial-bindings.mjs` : provenance, refus, captures, annotations, limites, collisions, idempotence, code PHP sans globals privés, comptes des callbacks, réutilisation, exceptions et observation de pile sur le chemin public.
- Dix-sept fixtures compilées avec le fork TAST passent en PHP et JavaScript, avec les mêmes sorties. `PartialBindings` force l'appel de son consommateur via une valeur opaque afin de vérifier le chemin privé effectivement exécuté. Le runner isolé utilise les paquets frères installés ; le runner général `bin/test`, qui exige le paquet absent `phpurs-math`, n'est pas présenté comme validé.
- Le graphe PHP intégré passe 42 cas de validation : n = 0, 1, 2, 3, 5, 7, 10 ; graines −11, 0, 17 ; public et privé. Vérification de n⁵ invocations, absence d'appel précoce du callback, anciennes closures réutilisables, callback alternatif et applications saturées.
- Les comptes instrumentés dans **des copies**, séparément du chrono, donnent :

| Événement pour un act | Témoin | Intégré |
| --- | ---: | ---: |
| Closures internes du successeur | 100 202 | 2 030 |
| Préparations du neutre | 11 111 | 203 |
| Incréments finaux | 100 000 | 100 000 |
| Résultat | 100 000 | 100 000 |

Seul `Test.Church/index.php` change parmi **301 modules**. Ses déclarations publiques avant `act` sont identiques octet pour octet. Les 301 fichiers régénérés de la variante intégrée correspondent aux sorties actives du worktree. Les empreintes figurent dans `artifacts.json` ; RBTree conserve SHA-256 `bc22594905438cfd291cf479b6c5a25ede4489d842d052bc45ac31eca32c0a3c`.

## Mesures et limite du harnais

Les témoins sont régénérés à partir du même TAST frais, mêmes chemins sources/FFI et même PBO. Seule la nouvelle passe est remplacée par l'identité dans le témoin. PHP 8.5.4, JIT 1255, tampon 128 MiB, OPcache CLI actif, cache fichier désactivé et protection mtime désactivée. Quatre processus frais et séquentiels, ordre ABBA ; l'horloge, les échauffements et les minima de dix exécutions restent ceux de `Bench.purs`.

**La comparaison de référence lance directement `output/App/main.mod.php`, comme `bin/php/run`.**

| Ordre | Variante | Church | RBTree | Total |
| --- | --- | ---: | ---: | ---: |
| A1 | Témoin | 9,170 ms | 112,864 ms | 132,246 ms |
| B1 | Intégré | 2,088 ms | 113,675 ms | 126,069 ms |
| B2 | Intégré | 2,170 ms | 114,888 ms | 127,121 ms |
| A2 | Témoin | 9,011 ms | 111,257 ms | 130,428 ms |

Le premier harnais réutilisé de l'audit RBTree chargeait ce module avant `App/main.mod.php`. Dans ce contexte différent, RBTree ralentissait de 7–10 ms et masquait le gain Church, malgré son code inchangé. Les résultats sont conservés dans `preclean-full-results.json` et `preload-full-results.json`. Les essais d'ordre (`order-results.json`) confirment que les chiffres dépendent du contexte de chargement/exécution. Le mécanisme exact côté moteur/JIT n'a pas été établi ; il ne faut pas attribuer cette variation à une modification de RBTree, ni masquer les séries défavorables. La passe garde un gain Church important, et le gain global reste plus modeste et sensible aux variations de RBTree.

Référence officielle du README principal d'altbak.pub au moment de ce bilan : **Church 8,794 ms**, **RBTree 107,566 ms**, **total 126,41 ms**. Les témoins contemporains sont donc un peu plus lents que ce relevé historique. Le dernier natif affiche Church **0,105 ms** et total **200,45 ms**. Son Church utilise une boucle arithmétique de `limit * 10000` incréments, tandis que PureScript compose des fonctions pour `limit⁵` applications : la charge coïncide à la valeur configurée 10, mais les algorithmes diffèrent. Aucun nouveau chrono natif n'est annoncé.

Les README des benchmarks restent inchangés. Le prototype antérieur sur PHP modifié à la main (dont la variante qui déplaçait aussi les compositions) est distinct de cette intégration.

## Reproduction

Depuis phpurs/phpurs :

```sh
bash audit/2026-09-09/partial-bindings/build.sh
node tests/codegen/partial-bindings.mjs
python3 audit/2026-09-09/partial-bindings/run-fixtures.py
node audit/2026-09-09/partial-bindings/prepare-comparison.mjs
python3 audit/2026-09-09/partial-bindings/measure.py full
python3 audit/2026-09-09/partial-bindings/counts.py
php audit/2026-09-09/partial-bindings/validate.php audit/2026-09-09/partial-bindings/raw/integrated/output
```

Faire la reconstruction propre via `altbak.pub-phpurs/bin/php/run -c` avant la régénération comparative. Les dossiers `raw/` sont ignorés par Git. Les mesures se font sans compilation ni autre benchmark lancé en parallèle.

Prochaine micro-étape possible : évaluer séparément la réutilisation des compositions prouvées. Leurs 2 030 bindings résiduels sont maintenant mesurables, mais le gain total potentiel est inférieur à celui de cette étape ; RBTree reste dominant.
