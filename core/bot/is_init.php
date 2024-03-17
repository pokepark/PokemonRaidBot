<?php

/* If we're able to, detect the first time this PHP context is initialized.
 * - **Optional** QoL things can be gated to be run only on this init round.
 * - Use IS_INIT to gate those things.
 * - **Mandatory** code that should get run, but ideally only once should be gated by IS_INIT_OR_WHATEVER instead.
* */


if(extension_loaded('apcu') && apcu_enabled()){
  if (isset($namespace) && !apcu_exists($namespace)){
    apcu_store($namespace, time());
    define('IS_INIT', true);
    define('IS_INIT_OR_WHATEVER', true);
  } else {
    define('IS_INIT', false);
    define('IS_INIT_OR_WHATEVER', false);
  }
} else {
  // If APCu becomes more widely used in the codebase we could flip the default to be the other way around eventually.
  define('IS_INIT', false);
  // In the meanwhile code that should be run anyway, but can be optimized away with APCu should be gated by this.
  define('IS_INIT_OR_WHATEVER', true);
}
