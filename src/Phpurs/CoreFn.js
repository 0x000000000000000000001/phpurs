import * as CoreFn from "../Phpurs.CoreFn/index.js";
import { Just, Nothing } from "../Data.Maybe/index.js";

function parseLiteral(obj, parseElem) {
  switch (obj.literalType) {
    case "IntLiteral": return new CoreFn.IntLiteral(obj.value);
    case "NumberLiteral": return new CoreFn.NumberLiteral(obj.value);
    case "StringLiteral": return new CoreFn.StringLiteral(obj.value);
    case "CharLiteral": return new CoreFn.CharLiteral(obj.value);
    case "BooleanLiteral": return new CoreFn.BooleanLiteral(obj.value);
    case "ArrayLiteral": return new CoreFn.ArrayLiteral(obj.value.map(parseElem));
    case "ObjectLiteral": return new CoreFn.ObjectLiteral(obj.value.map(function(kv) {
      return { key: kv[0], value: parseElem(kv[1]) };
    }));
    default: return new CoreFn.StringLiteral("unsupported literal");
  }
}

function parseBinder(obj) {
  switch (obj.binderType) {
    case "NullBinder": return CoreFn.NullBinder.value;
    case "LiteralBinder": return new CoreFn.LiteralBinder(parseLiteral(obj.literal, parseBinder));
    case "VarBinder": return new CoreFn.VarBinder(obj.identifier);
    case "ConstructorBinder": {
      let mod = obj.constructorName.moduleName || [];
      let isNewtype = false;
      if (obj.annotation && obj.annotation.meta && obj.annotation.meta.metaType === "IsNewtype") {
        isNewtype = true;
      }
      return new CoreFn.ConstructorBinder(isNewtype, mod, obj.constructorName.identifier, obj.binders.map(parseBinder));
    }
    case "NamedBinder": return new CoreFn.NamedBinder(obj.identifier, parseBinder(obj.binder));
    default: return CoreFn.NullBinder.value;
  }
}

function parseBind(obj) {
  if (obj.bindType === "NonRec") {
    return [new CoreFn.NonRec(obj.identifier, parseExpr(obj.expression))];
  } else if (obj.bindType === "Rec") {
    return [new CoreFn.Rec(obj.binds.map(function(b) {
      return { identifier: b.identifier, expression: parseExpr(b.expression) };
    }))];
  }
  return [];
}

function parseExpr(obj) {
  switch (obj.type) {
    case "Abs": return new CoreFn.Lambda(obj.argument, parseExpr(obj.body));
    case "Var": {
      const mod = obj.value.moduleName ? new Just(obj.value.moduleName) : Nothing.value;
      return new CoreFn.Variable(mod, obj.value.identifier);
    }
    case "App": return new CoreFn.Call(parseExpr(obj.abstraction), parseExpr(obj.argument));
    case "Literal": return new CoreFn.Literal(parseLiteral(obj.value, parseExpr));
    case "Case": return new CoreFn.Case(obj.caseExpressions.map(parseExpr), obj.caseAlternatives.map(function(ca) {
      let expr = ca.isGuarded ? new CoreFn.Unsupported("Guards not supported") : parseExpr(ca.expression);
      return new CoreFn.CaseAlternative({ binders: ca.binders.map(parseBinder), isGuarded: ca.isGuarded, expression: expr });
    }));
    case "Let": {
      let binds = [];
      for (const b of obj.binds) {
        const parsed = parseBind(b);
        for (const p of parsed) { binds.push(p); }
      }
      return new CoreFn.Let(binds, parseExpr(obj.expression));
    }
    case "Constructor": return new CoreFn.Constructor(obj.typeName, obj.constructorName, obj.fieldNames);
    case "Accessor": return new CoreFn.Accessor(obj.fieldName, parseExpr(obj.expression));
    case "ObjectUpdate": return new CoreFn.ObjectUpdate(parseExpr(obj.expression), obj.updates.map(function(kv) {
      return { key: kv[0], value: parseExpr(kv[1]) };
    }));
    default: return new CoreFn.Unsupported(obj.type);
  }
}

export const parseModuleImpl = function(jsonStr) {
  return function() {
    try {
      const obj = JSON.parse(jsonStr);
      let binds = [];
      for (const b of obj.decls) {
        const parsed = parseBind(b);
        for (const p of parsed) { binds.push(p); }
      }
      const imports = (obj.imports || []).map(function(i) { return i.moduleName || []; });
      return new Just(CoreFn.Module({
        moduleName: obj.moduleName || [],
        decls: binds,
        imports: imports,
        foreign: obj.foreign || [],
        modulePath: obj.modulePath ? new Just(obj.modulePath) : Nothing.value
      }));
    } catch (e) {
      return Nothing.value;
    }
  };
};
