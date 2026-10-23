-- | Module compilation pipeline. Private-worker proofs run before TCO and
-- | declaration lowering; the PHP passes then consume their layout metadata.
module Phpurs.CodeGen
  ( module Phpurs.CodeGen.Context
  , module Expr
  , translate
  , translateWithCallbacks
  , translateWithContracts
  ) where

import Prelude

import Data.Array as Array
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.Tuple (Tuple(..))
import Phpurs.ArrayRefs as ArrayRefs
import Phpurs.ArrayCallbacks as ArrayCallbacks
import Phpurs.CallbackSpecialization as CallbackSpecialization
import Phpurs.CodeGen.Bindings (analyzeBindings)
import Phpurs.CodeGen.Context
  ( LoopContext
  , TranslatedValues
  , TranslationContext
  , TranslationResult
  , initialContext
  , valueContext
  )
import Phpurs.CodeGen.Declarations (translateBindingGroup, translateConstructors)
import Phpurs.CodeGen.Expr (translateExpr) as Expr
import Phpurs.CodeGen.Types (extractFuncType, remainingArity)
import Phpurs.CopyCleanup as CopyCleanup
import Phpurs.EnumRegions as EnumRegions
import Phpurs.ForeignTraversals (Traversals)
import Phpurs.NativeCallbacks (Callbacks)
import Phpurs.NullableConstructors as Nullable
import Phpurs.PartialBindings as PartialBindings
import Phpurs.PhpAst (PhpExpr(..), PhpFile)
import Phpurs.StateFusion as StateFusion
import Phpurs.TailInline as TailInline
import Phpurs.ThunkFusion as ThunkFusion
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..))

-- | Main translation function.
-- | Takes module imports and the optimizer's `BackendModule`, performs local TCO
-- | analysis and returns a fully constructed `PhpFile` ready for printing.
translate :: Array (Array String) -> BackendModule -> PhpFile
translate = translateWithCallbacks Set.empty

translateWithCallbacks :: Callbacks -> Array (Array String) -> BackendModule -> PhpFile
translateWithCallbacks callbacks = translateWithContracts callbacks Set.empty

translateWithContracts
  :: Callbacks -> Traversals -> Array (Array String) -> BackendModule -> PhpFile
translateWithContracts callbacks traversals imports input =
  let
    partialBindings = PartialBindings.optimize input
    fusion = ThunkFusion.optimize partialBindings.module_
    stateFusion = StateFusion.optimize fusion.module_
    specialized = CallbackSpecialization.optimize callbacks stateFusion.module_
    arrays = ArrayCallbacks.optimize traversals callbacks specialized.module_
    regions = EnumRegions.optimize arrays.module_
    mod = regions.module_
    modNameStr = String.replaceAll (Pattern ".") (Replacement "_") (unwrap mod.name)
    modPrefix = modNameStr <> "_"

    rawDecls = translateConstructors modPrefix regions.privateConstructors mod.dataDecls

    tcoBindings = analyzeBindings mod
    decls = Array.concatMap
      (translateBindingGroup modNameStr (Set.isEmpty regions.privateConstructors))
      tcoBindings
    moduleArities = Map.fromFoldable
      ( Array.concatMap
          ( \group -> map
              (\(Tuple (Ident name) expr) -> Tuple (modPrefix <> name) (remainingArity expr))
              group.bindings
          )
          tcoBindings
      )

    privateNames = Set.map (\ident -> modPrefix <> unwrap ident)
      ( Set.unions
          [ regions.privateNames
          , fusion.privateNames
          , stateFusion.privateNames
          , specialized.privateNames
          , partialBindings.privateNames
          ]
      )
    checkedSignatures = Set.map (\ident -> modPrefix <> unwrap ident)
      (Set.union stateFusion.checkedDepths specialized.privateNames)
    isArrayType = case _ of
      ADT name _ _ -> Set.member name regions.arrayLayouts
      _ -> false
    -- Private workers whose parameters hold packed arrays; ArrayRefs promotes
    -- them to references where every call site can hand over ownership.
    arrayParams = Map.fromFoldable
      ( Array.mapMaybe
          ( \(Tuple (Ident name) expr) ->
              if Set.member (modPrefix <> name) privateNames then
                case extractFuncType expr of
                  Just { fArgs } ->
                    let
                      idxs = Array.mapMaybe identity
                        ( Array.mapWithIndex (\i ty -> if isArrayType ty then Just i else Nothing)
                            fArgs
                        )
                    in
                      if Array.null idxs then Nothing
                      else Just (Tuple (modPrefix <> name) (Set.fromFoldable idxs))
                  Nothing -> Nothing
              else Nothing
          )
          (Array.concatMap _.bindings tcoBindings)
      )
    regionWorkers = Set.map (\ident -> modPrefix <> unwrap ident) regions.privateNames
    privateClasses = Set.map
      ( \name ->
          "\\" <> String.replaceAll (Pattern ".") (Replacement "\\") (unwrap mod.name) <> "\\"
            <> modPrefix
            <> String.replaceAll (Pattern "'") (Replacement "_prime_") name
      )
      regions.privateConstructors
    nullableClasses = Map.fromFoldable
      ( map
          ( \(Tuple name empty) ->
              Tuple
                ( "\\" <> String.replaceAll (Pattern ".") (Replacement "\\") (unwrap mod.name)
                    <> "\\"
                    <> modPrefix
                    <> String.replaceAll (Pattern "'") (Replacement "_prime_") name
                )
                empty
          )
          (Map.toUnfoldable regions.nullableConstructors :: Array (Tuple String Boolean))
      )
    -- Every private call is proven saturated and its arguments are checked on
    -- the typed AST. Avoid adding dynamic scalar coercions that would prevent
    -- the existing terminal inliner from simplifying these internal workers.
    -- State guards preserve the original builder's Int depth check. Their
    -- result is deliberately untyped, just like the original record field.
    -- Callback-specialized copies also retain the original fold signature.
    internalSignatures d =
      if Set.member d.identifier privateNames && not (Set.member d.identifier checkedSignatures) then
        case d.expression of
          PhpNativeFunction name args _ body -> d
            { expression = PhpNativeFunction name (map (\a -> a { type_ = "" }) args) "" body }
          _ -> d
      else d
    hideWorker d =
      if Set.member d.identifier privateNames then case d.expression of
        PhpNativeFunction name args ret body -> d
          { expression = PhpPrivateFunction name args ret body }
        _ -> d
      else d
    -- Keep the pass order explicit: later passes consume the forms and private
    -- layout proofs established by the earlier ones.
    phpFile =
      { namespace: String.split (Pattern ".") (unwrap mod.name)
      , rawDecls
      , decls: map internalSignatures decls
      , imports
      , arities: Map.union arrays.arities moduleArities
      }
    inlined = TailInline.optimize phpFile
    privateWorkers = inlined { decls = map hideWorker inlined.decls }
    nullable = Nullable.lower nullableClasses privateWorkers
    cleaned = CopyCleanup.optimize { workers: regionWorkers, constructors: privateClasses } nullable
    owned = ArrayRefs.optimize arrayParams privateClasses cleaned
  in
    owned { decls = owned.decls <> arrays.workers }
