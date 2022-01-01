<?php

/* If we're able to, detect the first time this PHP context is initialized.
 * - **Optional** QoL things can be gated to be run only on this init round.
 * - Use IS_INIT to gate those things.
* */


if(extension_loaded('apcu') && apcu_enabled()){
  if (!apcu_exists($namespace)){
    apcu_store($namespace, time());
    define('IS_INIT', true);
  } else {
    define('IS_INIT', false);
  }
} else {
  // If APCu becomes more widely used in the codebase we could flip the default to be the other way around eventually.
  define('IS_INIT', false);
}
