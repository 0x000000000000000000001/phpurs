"""Conservative TAST region analysis prototype, not a compiler pass.

Only saturated direct calls, monomorphic local layouts and scalar entry/exit.
Unknown syntax/types, local callbacks and cross-module ADT flows fail closed.
Public bindings are never removed: the result describes copies to specialize.
"""
import json
from pathlib import Path
import sys

SCALARS = {'Int', 'Number', 'String', 'Char', 'Boolean', 'Unit'}

class Refused(Exception):
    pass

class Regions:
    def __init__(self, module):
        self.module = module
        self.types = module['typeTable']
        self.name = module['moduleName']
        self.decls = {d['identifier']: d for group in module['decls']
                      for d in (group['binds'] if group['bindType'] == 'Rec' else [group])}
        self.layouts = {'.'.join(self.name + [d['typeName']]): d for d in module['dataDecls']}
        self.constructors = {c['name']: c for d in self.layouts.values() for c in d['constructors']}

    def ty(self, ref):
        if isinstance(ref, int) and 0 <= ref < len(self.types):
            return self.types[ref]
        if isinstance(ref, dict):
            return ref
        raise Refused('missing type')

    def annotation(self, expr):
        return expr.get('annotation', {}).get('type')

    def type_info(self, ref, related, seen=frozenset()):
        t = self.ty(ref)
        kind = t['type']
        if kind in SCALARS or kind == 'RowEmpty': return False
        if kind == 'Adt':
            name = '.'.join(t['fqn'])
            children = [self.type_info(x, related, seen) for x in t.get('args', [])]
            if name in related: return True
            if name in self.layouts and name not in seen:
                layout = self.layouts[name]
                if layout.get('typeVars'): raise Refused('polymorphic local layout')
                children += [self.type_info(x, related, seen | {name})
                             for c in layout['constructors'] for x in c['fieldTypes']]
            return any(children)
        if kind == 'Func':
            return any([self.type_info(x, related, seen) for x in t['args'] + [t['ret']]])
        if kind == 'Record': return self.type_info(t['row'], related, seen)
        if kind == 'Row':
            children = [self.type_info(x['type'], related, seen) for x in t['fields']]
            if t.get('tail') is not None: children.append(self.type_info(t['tail'], related, seen))
            return any(children)
        if kind == 'ConstrainedType':
            children = [self.type_info(t['body'], related, seen)]
            children += [self.type_info(a, related, seen) for c in t['constraints'] for a in c['args']]
            return any(children)
        raise Refused('unsupported or unresolved type: ' + kind)

    def enums(self):
        return {name: d for name, d in self.layouts.items()
                if not d.get('typeVars') and len(d['constructors']) >= 2
                and all(not c['fieldTypes'] for c in d['constructors'])}

    def shape(self, name):
        expr = self.decls[name]['expression']
        args = []
        while expr['type'] == 'Abs':
            args.append(expr['argument'])
            expr = expr['body']
        return args, expr

    def analyze(self, entry, enum, max_bindings=64, max_nodes=8192):
        related = {enum}
        try:
            for _ in range(64):
                expanded = related | {name for name, d in self.layouts.items()
                    if any(self.type_info(f, related) for c in d['constructors'] for f in c['fieldTypes'])}
                if expanded == related: break
                related = expanded
            else: raise Refused('layout closure budget')
            signature = self.ty(self.annotation(self.decls[entry]['expression']))
            params, _ = self.shape(entry)
            if (signature['type'] != 'Func' or len(signature['args']) != len(params)
                    or not params or any(self.ty(t)['type'] not in SCALARS
                                         for t in signature['args'] + [signature['ret']])):
                raise Refused('entry/exit is not a monomorphic scalar function')
            visited, visiting, used = set(), set(), set()
            node_count = 0

            def binding(name):
                if name in visited or name in visiting: return
                if len(visited | visiting) >= max_bindings: raise Refused('binding budget')
                args, body = self.shape(name)
                ty = self.ty(self.annotation(self.decls[name]['expression']))
                if ty['type'] != 'Func' or len(ty['args']) != len(args):
                    raise Refused('unknown or partial local signature: ' + name)
                self.type_info(ty, related)
                visiting.add(name)
                walk(body)
                visiting.remove(name)
                visited.add(name)

            def unwrap(expr):
                while expr['type'] == 'TypeApp': expr = expr['expression']
                return expr

            def walk(expr):
                nonlocal node_count
                node_count += 1
                if node_count > max_nodes: raise Refused('node budget')
                has_enum = self.type_info(self.annotation(expr), related)
                if has_enum: used.add(enum)
                kind = expr['type']
                if kind == 'App':
                    args, target = [], expr
                    while True:
                        target = unwrap(target)
                        if target['type'] != 'App': break
                        args.insert(0, target['argument'])
                        target = target['abstraction']
                    target = unwrap(target)
                    if target['type'] != 'Var': raise Refused('indirect call')
                    value = target['value']
                    mod, name = value.get('moduleName'), value['identifier']
                    if mod == self.name and name in self.constructors:
                        if len(args) != len(self.constructors[name]['fieldTypes']):
                            raise Refused('partial constructor')
                    elif mod == self.name and name in self.decls:
                        params, _ = self.shape(name)
                        if len(args) != len(params): raise Refused('partial or overapplied local call: ' + name)
                        binding(name)
                    else:
                        if not mod: raise Refused('unknown callable: ' + name)
                        if has_enum or any(self.type_info(self.annotation(a), related) for a in args):
                            raise Refused('ADT crosses external call: ' + '.'.join(mod + [name]))
                    for arg in args: walk(arg)
                elif kind == 'Var':
                    value = expr['value']
                    if self.ty(self.annotation(expr))['type'] == 'Func':
                        raise Refused('function used as a value')
                    if value.get('moduleName') and has_enum:
                        if value['moduleName'] != self.name or value['identifier'] not in self.constructors:
                            raise Refused('ADT global value outside the region')
                elif kind == 'Literal':
                    lit = expr['value']
                    if lit['literalType'] == 'ObjectLiteral':
                        for _, value in lit['value']: walk(value)
                    elif lit['literalType'] == 'ArrayLiteral':
                        for value in lit['value']: walk(value)
                    elif lit['literalType'] not in ['IntLiteral','NumberLiteral','StringLiteral','CharLiteral','BooleanLiteral']:
                        raise Refused('unsupported literal')
                elif kind == 'Case':
                    for value in expr['caseExpressions']: walk(value)
                    for alt in expr['caseAlternatives']:
                        if alt['isGuarded']: raise Refused('guarded case')
                        walk(alt['expression'])
                elif kind == 'Let':
                    for decl in expr['binds']:
                        if decl['bindType'] != 'NonRec': raise Refused('local recursive binding')
                        walk(decl['expression'])
                    walk(expr['expression'])
                elif kind == 'Accessor': walk(expr['expression'])
                elif kind == 'TypeApp': walk(expr['expression'])
                else: raise Refused('unsupported expression: ' + kind)

            binding(entry)
            if not used: raise Refused('no enum use')
            return {'eligible': True, 'entry': entry, 'enum': enum, 'bindings': sorted(visited),
                    'related_layouts': sorted(related), 'inspected_nodes': node_count}
        except Refused as e:
            return {'eligible': False, 'entry': entry, 'enum': enum, 'reason': str(e)}

def analyze_file(path):
    analyzer = Regions(json.loads(Path(path).read_text()))
    return [analyzer.analyze(name, enum) for enum in analyzer.enums() for name in analyzer.decls
            if name not in analyzer.constructors]

if __name__ == '__main__':
    print(json.dumps(analyze_file(sys.argv[1]), indent=2))
