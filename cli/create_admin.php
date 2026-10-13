<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only.');}
require dirname(__DIR__).'/app/bootstrap.php';
function prompt(string $text): string {echo $text;return trim((string)fgets(STDIN));}
try {
    $name=prompt('Administrator name: ');$email=strtolower(prompt('Administrator email: '));
    echo "Password entry is visible in this terminal. Do not record or screenshot it.\n";
    $password=prompt('Choose a NEW password (12 to 72 bytes): ');$confirm=prompt('Confirm password: ');
    if($name===''||mb_strlen($name)>100||strlen($email)>190||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Enter a valid name and email.');
    if(strlen($password)<12||strlen($password)>72||$password!==$confirm)throw new InvalidArgumentException('Passwords must match and contain 12 to 72 bytes.');
    $repo=new FleetRepository(connect());
    $repo->query('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),'admin']);
    echo "Administrator created. Sign in using public/index.php.\n";
}catch(Throwable $ex){fwrite(STDERR,"Account was not created. Check the database, required values and duplicate email addresses.\n");exit(1);}
