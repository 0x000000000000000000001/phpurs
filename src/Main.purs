module Main where

import Prelude

import Effect (Effect)
import Effect.Class (liftEffect)
import Effect.Aff (Aff, launchAff_, attempt)
import Effect.Console as Console
import Node.FS.Aff as FS
import Node.FS.Stats as Stats
import Node.Encoding (Encoding(..))
import Node.Process as Process
import Data.Argonaut.Parser (jsonParser)
import Data.Foldable (foldl)
import Data.Either (Either(..), isRight)
import Data.Bifunctor (lmap)
import Data.Argonaut.Decode.Error (printJsonDecodeError)
import Data.Array as Array
import Data.List as List
import Data.Maybe (Maybe(..), isJust, maybe, fromMaybe)
import Data.Map as Map
import Data.Set as Set
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.String as String
import PureScript.Backend.Optimizer.CoreFn.Json (decodeModule)
import PureScript.Backend.Optimizer.CoreFn.Sort (sortModules)
import PureScript.Backend.Optimizer.Builder (buildModules)
import PureScript.Backend.Optimizer.CoreFn (Module(..), Ann, Ident(..), importName, Qualified(..), ModuleName(..), ExprType(..))
import PureScript.Backend.Optimizer.Semantics.Foreign (coreForeignSemantics)
import Phpurs.CodeGen (translate)
import Phpurs.GenNativeForeign (genNativeWrapper, flattenFuncType)
import Phpurs.Printer (printPhpFile, safeName, safeFuncName)
import Phpurs.ComposerMerge (mergeComposers)
import PureScript.Backend.Optimizer.FfiSupport (findFfiFile)
import Data.Newtype (unwrap)
import Data.String (joinWith, replace, replaceAll, trim, length, contains)
import Effect.Ref as Ref
import PureScript.Backend.Optimizer.App (coreFnModulesFromOutput, parseCLIArgs, checkCache, writeCache, loadDirectives)
import PureScript.Backend.Optimizer.Reachability (moduleReachability)

import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..))
import PureScript.Backend.Optimizer.Convert (BackendBindingGroup(..))

countNodes :: NeutralExpr -> Int
countNodes (NeutralExpr expr) = 1 + case expr of
  Var _ -> 0
  Local _ _ -> 0
  Lit _ -> 0
  App f args -> countNodes f + foldl (+) 0 (map countNodes args)
  Abs _ body -> countNodes body
  UncurriedApp f args -> countNodes f + foldl (+) 0 (map countNodes args)
  UncurriedAbs _ body -> countNodes body
  UncurriedEffectApp f args -> countNodes f + foldl (+) 0 (map countNodes args)
  UncurriedEffectAbs _ body -> countNodes body
  Accessor obj _ -> countNodes obj
  Update obj _ -> countNodes obj
  CtorSaturated _ _ _ _ args -> foldl (+) 0 (map (\(Tuple _ a) -> countNodes a) args)
  CtorDef _ _ _ _ -> 0
  LetRec _ binds body -> foldl (+) 0 (map (\(Tuple _ a) -> countNodes a) binds) + countNodes body
  Let _ _ val body -> countNodes val + countNodes body
  EffectBind _ _ val body -> countNodes val + countNodes body
  EffectPure val -> countNodes val
  EffectDefer val -> countNodes val
  Branch _ _ -> 0
  PrimOp _ -> 0
  _ -> 0

cacheVersion :: String
cacheVersion = "1.0.0"

main :: Effect Unit
main = launchAff_ do
  argsRaw <- liftEffect Process.argv
  let 
    args = parseCLIArgs argsRaw
    outputDir = fromMaybe "output" args.mbOutputDir

  finalModules <- coreFnModulesFromOutput outputDir

  bundleContentRef <- liftEffect $ Ref.new "<?php\n\n"
  globalAritiesRef <- liftEffect $ Ref.new Map.empty
  backendModulesRef <- liftEffect $ Ref.new Map.empty

  directives <- loadDirectives

  buildModules
    { directives
    , analyzeCustom: \_ _ -> Nothing
    , foreignSemantics: coreForeignSemantics
    , traceIdents: Set.empty
    , onPrepareModule: \_ m -> pure m
    , onSkipModule: \_ (Module coreFnMod) -> do
        let modNameStr = unwrap coreFnMod.name
        pure Nothing
    , onCodegenModule: \_ (Module coreFnMod) backendMod _ -> do
        let modNameStr = unwrap backendMod.name
        let totalNodes = foldl (+) 0 (map (\bg -> foldl (+) 0 (map (\(Tuple _ expr) -> countNodes expr) bg.bindings)) backendMod.bindings)
        liftEffect $ Console.log $ "Generating PHP code for " <> modNameStr <> " (Total AST Nodes: " <> show totalNodes <> ")"
        liftEffect $ Ref.modify_ (Map.insert backendMod.name { imports: backendMod.imports, implementations: backendMod.implementations }) backendModulesRef
        _ <- attempt (FS.mkdir (outputDir <> "/" <> modNameStr))
        let
          importsArray = map (\i -> String.split (Pattern ".") (unwrap (importName i))) coreFnMod.imports
          phpFile = translate importsArray backendMod

        ffiPathMb <- liftEffect $ findFfiFile ".php" ["bak/spago.d/php/p"] args.mbFfiDir modNameStr (Just coreFnMod.path)
        ffiCode <- case ffiPathMb of
          Nothing -> pure ""
          Just ffiPath -> do
            content <- FS.readTextFile UTF8 ffiPath
            pure (trim (replace (Pattern "<?php\n") (Replacement "") (replace (Pattern "<?php") (Replacement "") content)))

        let
          phpModName = replaceAll (Pattern ".") (Replacement "_") modNameStr
          getArity = case _ of
            Just t -> Array.length (flattenFuncType t).args
            Nothing -> 0
          
          foreignArities = Map.fromFoldable $ map (\(Tuple (Ident f) type_) -> Tuple (safeName (phpModName <> "_" <> f)) (getArity type_)) (Map.toUnfoldable backendMod.foreign :: Array _)
        
        currentArities <- liftEffect $ Ref.read globalAritiesRef
        let allArities = Map.union foreignArities (Map.union phpFile.arities currentArities)
        liftEffect $ Ref.write allArities globalAritiesRef

        let
          getType = case _ of
            Just t -> t
            Nothing -> Any
          wrappedFfiCode =
            if length ffiCode > 0 then
              let
                closureStart = "$ffi_" <> phpModName <> " = \\call_user_func(function() {\n  $exports = [];\n"
                closureEnd = "\n  return $exports;\n});\n"
                mappings = joinWith "\n" (map (\(Tuple (Ident f) type_) -> genNativeWrapper (safeName (phpModName <> "_" <> f)) (safeFuncName (phpModName <> "_" <> f)) ("$ffi_" <> phpModName) ("(\\array_key_exists('" <> f <> "', $ffi_" <> phpModName <> ") ? $ffi_" <> phpModName <> "['" <> f <> "'] : new class { public function __invoke(...$args) { return $this; } })") (getType type_)) (Map.toUnfoldable backendMod.foreign))
              in
                closureStart <> ffiCode <> closureEnd <> mappings <> "\n"
            else
              let
                mappings = joinWith "\n" (map (\(Tuple (Ident f) type_) -> genNativeWrapper (safeName (phpModName <> "_" <> f)) (safeFuncName (phpModName <> "_" <> f)) "null" "new class { public function __invoke(...$args) { return $this; } }" (getType type_)) (Map.toUnfoldable backendMod.foreign))
              in
                mappings <> (if length mappings > 0 then "\n" else "")

        if args.bundle then do
          let phpCodeBundle = printPhpFile true wrappedFfiCode allArities phpFile
          liftEffect $ Ref.modify_ (\s -> s <> phpCodeBundle <> "\n") bundleContentRef
        else pure unit

        let phpCode = printPhpFile false wrappedFfiCode allArities phpFile
        FS.writeTextFile UTF8 (outputDir <> "/" <> modNameStr <> "/index.php") phpCode
    }
    finalModules

  backendModules <- liftEffect $ Ref.read backendModulesRef

  let
    targetMainModules = case args.mbMainModule of
      Just mainMod -> [ mainMod ]
      Nothing -> Array.mapMaybe (\(Module m) -> if isJust (Array.elemIndex (Ident "main") m.exports) then Just (unwrap m.name) else Nothing) (Array.fromFoldable finalModules)

  _ <- traverse
    ( \mainMod -> do
        let
          autoloadStr = case args.mbAutoloadPath of
            Just p -> "if (file_exists(__DIR__ . '/../../" <> p <> "')) require_once __DIR__ . '/../../" <> p <> "';\nelseif (file_exists('" <> p <> "')) require_once '" <> p <> "';\n"
            Nothing -> "if (file_exists(__DIR__ . '/../../vendor/autoload.php')) require_once __DIR__ . '/../../vendor/autoload.php';\n"

          sanitizedMain = String.replaceAll (Pattern ".") (Replacement "_") mainMod <> "_main"
          callStr = "$GLOBALS['" <> sanitizedMain <> "']();\nif (class_exists('\\\\Revolt\\\\EventLoop')) { \\Revolt\\EventLoop::run(); }\n"

        if args.bundle then do
          bundleContent <- liftEffect $ Ref.read bundleContentRef
          let entryPoint = "namespace {\n" <> autoloadStr <> "set_exception_handler(function($e) { echo 'FATAL: ' . $e->getMessage() . \"\\n\" . $e->getTraceAsString() . \"\\n\"; exit(1); });\n" <> callStr <> "}\n"
          FS.writeTextFile UTF8 ("output/" <> mainMod <> "/main.bundle.php") (bundleContent <> "\n" <> entryPoint)
        else pure unit

        let
          reachableSet = moduleReachability [ModuleName mainMod] backendModules
          reachable = Array.filter (\(Module m) -> Set.member m.name reachableSet) (Array.fromFoldable finalModules)
          requires = joinWith "" (map (\(Module m) -> "require_once __DIR__ . '/../" <> unwrap m.name <> "/index.php';\n") reachable)
          modEntryPoint = "<?php\n" <> autoloadStr <> "set_exception_handler(function($e) { echo 'FATAL: ' . $e->getMessage() . \"\\n\" . $e->getTraceAsString() . \"\\n\"; exit(1); });\n" <> requires <> callStr
        liftEffect $ Console.log $ "Writing main.mod.php for " <> mainMod
        FS.writeTextFile UTF8 (outputDir <> "/" <> mainMod <> "/main.mod.php") modEntryPoint
    )
    targetMainModules

  if args.bundle then do
    case args.mbMainModule of
      Just _ -> pure unit
      Nothing -> do
        bundleContent <- liftEffect $ Ref.read bundleContentRef
        FS.writeTextFile UTF8 (outputDir <> "/bundle.php") bundleContent
  else pure unit

  liftEffect $ mergeComposers ""
