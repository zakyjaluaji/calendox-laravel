<?php
echo 'ALGO: ' . getenv('CALENDOX_JWT_ALGO') . PHP_EOL;
echo 'HAS_SECRET: ' . (getenv('CALENDOX_JWT_SECRET') ? 'yes' : 'no') . PHP_EOL;
echo 'PUBLIC: ' . getenv('CALENDOX_JWT_PUBLIC') . PHP_EOL;