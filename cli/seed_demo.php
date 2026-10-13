<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only.');}
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/demo_data.php';
try {seed_demo(new FleetRepository(connect()));echo "Added 5 fictional vehicles, 3 drivers, 1 assignment, 3 services and 2 repairs.\n";}
catch(Throwable $ex){fwrite(STDERR,$ex->getMessage()."\n");exit(1);}
