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
  Constructor _ _ _ -> []
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
buildDepGraph modules = foldl processModule Object.empty modules
  where
    processModule acc (Module m) =
      let modPrefix = joinWith "." m.moduleName <> "."
      in foldl (processDecl modPrefix) acc m.decls

    processDecl prefix acc (NonRec ident expr) =
      Object.insert (prefix <> ident) (globalDeps expr) acc
    processDecl prefix acc (Rec binds) =
      foldl (\a b -> Object.insert (prefix <> b.identifier) (globalDeps b.expression) a) acc binds

-- | Compute the reachable set using BFS
computeReachable :: String -> DepGraph -> Set.Set String
computeReachable root graph = bfs (Set.singleton root) [root]
  where
    bfs visited [] = visited
    bfs visited queue =
      let
        processNode { v, q } node =
          case Object.lookup node graph of
            Just deps ->
              let
                newDeps = filter (\d -> not (Set.member d v)) deps
              in { v: foldl (flip Set.insert) v newDeps, q: q <> newDeps }
            Nothing -> { v, q }
        res = foldl processNode { v: visited, q: [] } queue
      in bfs res.v res.q

-- | Filter out dead code based on the reachable set
filterModules :: Set.Set String -> Array Module -> Array Module
filterModules reachable modules = 
  let
    aliveDeclsOnly = map processModule modules
    aliveModules = filter hasContent aliveDeclsOnly
    
    aliveMap = foldl (\acc (Module m) -> Object.insert (joinWith "." m.moduleName) true acc) Object.empty aliveModules
    
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
