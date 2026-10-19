module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Test.Assert (assert')

type DeepRecord =
  { a :: Int
  , b :: { c :: Int, d :: { e :: Int, f :: Int } }
  }

old :: DeepRecord
old = { a: 1, b: { c: 2, d: { e: 3, f: 4 } } }

full :: DeepRecord -> DeepRecord
full r = r
  { a = r.a + 1
  , b = r.b
      { c = r.b.c + 2
      , d = r.b.d { e = r.b.d.e + 3, f = r.b.d.f + 4 }
      }
  }

partial :: DeepRecord -> DeepRecord
partial r = r { a = r.a + 10 }

main :: Effect Unit
main = do
  let fresh = full old
  assert' "full update fields" (fresh.a == 2 && fresh.b.c == 4 && fresh.b.d.e == 6 && fresh.b.d.f == 8)
  assert' "old record untouched" (old.a == 1 && old.b.c == 2 && old.b.d.e == 3 && old.b.d.f == 4)
  let alias = old.b.d
  assert' "old nested alias untouched" (alias.e == 3 && alias.f == 4)
  let observe = \_ -> old.a + old.b.d.e
  assert' "closure over old record" (observe unit == 4)
  let changed = partial old
  assert' "partial update" (changed.a == 11 && changed.b.c == 2 && changed.b.d.f == 4)
  assert' "partial old untouched" (old.a == 1)
  log "Done"
