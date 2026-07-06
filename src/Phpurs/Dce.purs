module Phpurs.Dce where

import Prelude

import Data.Array (concatMap, filter, length)
import Data.Maybe (Maybe(..))
import Data.Set as Set
import Foreign.Object as Object
import Data.String (joinWith)
import Phpurs.CoreFn (Bind(..), Binder(..), CaseAlternative(..), Expr(..), Literal(..), Module(..))
import Data.Foldable (foldl)
import Data.Tuple (Tuple(..))

-- | Extract all global dependencies from an expression
globalDeps :: Expr -> Array String
globalDeps = case _ of
  Lambda _ body -> globalDeps body
  Variable mbMod ident -> case mbMod of
    Just m -> [joinWith "." m <> "." <> ident]
    Nothing -> []
  Call f arg -> globalDeps f <> globalDeps arg
  Case exprs alts -> concatMap globalDeps exprs <> concatMap (globalDepsAlt) alts
  Let binds body -> concatMap globalDepsBind binds <> globalDeps body
  Constructor typeName _ _ -> [typeName]
  Accessor _ expr -> globalDeps expr
  ObjectUpdate expr updates -> globalDeps expr <> concatMap (\u -> globalDeps u.value) updates
  Literal lit -> case lit of
    ArrayLiteral arr -> concatMap globalDeps arr
    ObjectLiteral arr -> concatMap (\item -> globalDeps item.value) arr
    _ -> []
  Unsupported _ -> []

globalDepsAlt :: CaseAlternative -> Array String
globalDepsAlt (CaseAlternative ca) = globalDeps ca.expression

globalDepsBind :: Bind -> Array String
globalDepsBind = case _ of
  NonRec _ expr -> globalDeps expr
  Rec rBinds -> concatMap (\rb -> globalDeps rb.expression) rBinds

type DepGraph = Object.Object (Array String)

-- | Build the dependency graph for all modules
buildDepGraph :: Array Module -> DepGraph
buildDepGraph modules = Object.fromFoldable $ concatMap processModule modules
  where
    processModule (Module m) =
      let modPrefix = joinWith "." m.moduleName <> "."
      in concatMap (processDecl modPrefix) m.decls

    processDecl prefix (NonRec ident expr) =
      [Tuple (prefix <> ident) (globalDeps expr)]
    processDecl prefix (Rec binds) =
      map (\b -> Tuple (prefix <> b.identifier) (globalDeps b.expression)) binds

-- | Compute the reachable set using DFS
computeReachable :: String -> DepGraph -> Set.Set String
computeReachable root graph = dfs Set.empty root
  where
    dfs visited node
      | Set.member node visited = visited
      | otherwise =
          let visited' = Set.insert node visited
          in case Object.lookup node graph of
               Nothing -> visited'
               Just deps -> foldl dfs visited' deps

-- | Filter out dead code based on the reachable set
filterModules :: Set.Set String -> Array Module -> Array Module
filterModules reachable modules = 
  let
    aliveDeclsOnly = map processModule modules
    aliveModules = filter hasContent aliveDeclsOnly
    
    aliveMap = Object.fromFoldable $ map (\(Module m) -> Tuple (joinWith "." m.moduleName) true) aliveModules
    
    filterImports (Module m) =
      let newImports = filter (\imp -> Object.member (joinWith "." imp) aliveMap) m.imports
      in Module (m { imports = newImports })
  in map filterImports aliveModules
  where
    processModule (Module m) =
      let
        modPrefix = joinWith "." m.moduleName <> "."
        isReachable ident = Set.member (modPrefix <> ident) reachable

        filterDecl (NonRec ident expr) =
          if isReachable ident then Just (NonRec ident expr) else Nothing
        filterDecl (Rec binds) =
          let filteredBinds = filter (\b -> isReachable b.identifier) binds
          in if length filteredBinds > 0 then Just (Rec filteredBinds) else Nothing
          
        newDecls = concatMap (\d -> case filterDecl d of
                                      Just fd -> [fd]
                                      Nothing -> []) m.decls
      in Module (m { decls = newDecls })
      
    hasContent (Module m) =
      let modPrefix = joinWith "." m.moduleName <> "."
          foreignReachable = filter (\f -> Set.member (modPrefix <> f) reachable) m.foreign
      in length m.decls > 0 || length foreignReachable > 0
