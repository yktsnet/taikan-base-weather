<?php
$content = file_get_contents('src/phpstan.neon');
if (!$content) {
   file_put_contents('src/phpstan.neon', "includes:\n    - ./vendor/larastan/larastan/extension.neon\nparameters:\n    paths:\n        - app/\n    level: 5\n");
}
