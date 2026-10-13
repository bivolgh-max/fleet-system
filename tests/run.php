<?php
declare(strict_types=1);
// Self-contained automated tests. An in-memory SQLite database is created, never your live database.
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/demo_data.php';
$results=[];
function check(string $id,string $description,callable $fn): void {
    global $results;
    try {$fn();$results[]=['id'=>$id,'test'=>$description,'status'=>'PASS'];}
    catch(Throwable $ex){$results[]=['id'=>$id,'test'=>$description,'status'=>'FAIL','actual'=>$ex->getMessage()];}
}
function equal(mixed $actual,mixed $expected): void {
    if($actual!==$expected)throw new RuntimeException('Expected '.json_encode($expected).'; got '.json_encode($actual));
}
function rejects(callable $fn): void {
    try {$fn();}catch(InvalidArgumentException|PDOException $ex){return;}
    throw new RuntimeException('Expected the invalid operation to be rejected.');
}
function fixture(): FleetRepository {
    $db=new PDO('sqlite::memory:');$db->exec(file_get_contents(dirname(__DIR__).'/database/schema.sqlite.sql'));
    return new FleetRepository($db);
}
function vdata(array $changes=[]): array {return array_replace(['registration'=>'TEST01','make'=>'Ford','model'=>'Transit','model_year'=>'2020','mileage'=>'50000','status'=>'Available','mot_expiry'=>'2027-01-10','insurance_expiry'=>'2027-01-20','next_service_date'=>'2027-02-01','next_service_mileage'=>'55000'],$changes);}
function ddata(array $changes=[]): array {return array_replace(['name'=>'Test Driver','email'=>'driver@example.com','phone'=>'0000000000','licence_number'=>'TESTLICENCE01','licence_expiry'=>(new DateTimeImmutable())->modify('+1 year')->format('Y-m-d'),'active'=>'1'],$changes);}
function rdata(int $id,array $changes=[]): array {return array_replace(['vehicle_id'=>(string)$id,'record_date'=>date('Y-m-d'),'description'=>'Oil and filter','supplier'=>'Test Garage','cost'=>'100.50','mileage'=>'50000'],$changes);}
check('A01','Valid and leap-year dates',fn()=>equal(valid_date('2024-02-29','Date'),'2024-02-29'));
check('A02','Impossible calendar date rejected',fn()=>rejects(fn()=>valid_date('2026-02-31','Date')));
check('A03','Non-ISO date rejected',fn()=>rejects(fn()=>valid_date('13/10/2026','Date')));
check('A04','Optional dates remain null',fn()=>equal(valid_date('','Date',true),null));
check('A05','Missing dates do not imply compliance',fn()=>equal(date_status(null,'2026-10-13'),'Not recorded'));
check('A06','Expired date is overdue',fn()=>equal(date_status('2026-10-12','2026-10-13'),'Overdue'));
check('A07','Expiry today is due soon not yet expired',fn()=>equal(date_status('2026-10-13','2026-10-13'),'Due soon'));
check('A08','30 day inclusive alert boundary',fn()=>equal(date_status('2026-11-12','2026-10-13'),'Due soon'));
check('A09','31 day date is current',fn()=>equal(date_status('2026-11-13','2026-10-13'),'Current'));
check('A10','Currency is stored as exact pence',fn()=>equal(to_pence('185.5'),18550));
check('A11','Negative costs rejected',fn()=>rejects(fn()=>to_pence('-1')));
check('A12','More than two decimal places rejected',fn()=>rejects(fn()=>to_pence('2.001')));
check('A13','SQL text cannot become numeric input',fn()=>rejects(fn()=>integer_value('1 OR 1=1','Mileage',0,2000000)));
check('A14','HTML output is escaped',fn()=>equal(e('<script>alert(1)</script>'),'&lt;script&gt;alert(1)&lt;/script&gt;'));
check('A15','Array inputs rejected safely',fn()=>rejects(fn()=>input(['name'=>['bad']],'name')));
check('A16','Registration normalised',fn()=>equal(vehicle_values(vdata(['registration'=>' te st01 ']))['registration'],'TEST01'));
check('A17','Vehicle required fields checked',fn()=>rejects(fn()=>vehicle_values(vdata(['make'=>'']))));
check('A18','Invalid status rejected',fn()=>rejects(fn()=>vehicle_values(vdata(['status'=>'Deleted']))));
check('A19','Vehicle model year bounds checked',fn()=>rejects(fn()=>vehicle_values(vdata(['model_year'=>'1800']))));
check('A20','Driver email validated',fn()=>rejects(fn()=>driver_values(ddata(['email'=>'not-an-email']))));
check('A21','Driver phone validated',fn()=>rejects(fn()=>driver_values(ddata(['phone'=>'abc']))));
check('A22','Licence basic format validated',fn()=>rejects(fn()=>driver_values(ddata(['licence_number'=>'!']))));
check('A23','Missing service schedule reported',fn()=>equal(service_status(['next_service_date'=>null,'next_service_mileage'=>null,'mileage'=>0],'2026-10-13'),'Not recorded'));
check('A24','Service mileage threshold inclusive',fn()=>equal(service_status(['next_service_date'=>null,'next_service_mileage'=>50000,'mileage'=>50000],'2026-10-13'),'Overdue'));
check('A25','Service warning within 500 miles',fn()=>equal(service_status(['next_service_date'=>null,'next_service_mileage'=>50500,'mileage'=>50000],'2026-10-13'),'Due soon'));
check('A26','Service mileage 501 miles away is current',fn()=>equal(service_status(['next_service_date'=>null,'next_service_mileage'=>50501,'mileage'=>50000],'2026-10-13'),'Current'));
check('A27','Service date overdue despite future mileage',fn()=>equal(service_status(['next_service_date'=>'2026-10-12','next_service_mileage'=>70000,'mileage'=>50000],'2026-10-13'),'Overdue'));
check('A28','Money formats to two decimal places',fn()=>equal(money(10050),'£100.50'));
check('B01','Vehicle create and read',function(){$r=fixture();$id=$r->saveVehicle(vdata());equal($r->vehicle($id)['registration'],'TEST01');});
check('B02','Duplicate normalised registration rejected',function(){$r=fixture();$r->saveVehicle(vdata());rejects(fn()=>$r->saveVehicle(vdata(['registration'=>'test01'])));});
check('B03','Vehicle edit preserves ID',function(){$r=fixture();$id=$r->saveVehicle(vdata());equal($r->saveVehicle(vdata(['mileage'=>'50010']),$id),$id);equal((int)$r->vehicle($id)['mileage'],50010);});
check('B04','Decreasing odometer rejected',function(){$r=fixture();$id=$r->saveVehicle(vdata());rejects(fn()=>$r->saveVehicle(vdata(['mileage'=>'49999']),$id));equal((int)$r->vehicle($id)['mileage'],50000);});
check('B05','Search and filter return expected results',function(){$r=fixture();$r->saveVehicle(vdata());$r->saveVehicle(vdata(['registration'=>'TEST02','make'=>'Toyota','status'=>'Repair']));equal(count($r->vehicles('Toyota','Repair')),1);equal(count($r->vehicles('Toyota','Available')),0);});
check('B06','Search injection is data not executable SQL',function(){$r=fixture();$r->saveVehicle(vdata());equal(count($r->vehicles("' OR 1=1 --")),0);});
check('B07','Archive hides vehicle but retains data',function(){$r=fixture();$id=$r->saveVehicle(vdata());$r->addRecord(rdata($id),true);$r->archiveVehicle($id);equal(count($r->vehicles()),0);equal(count($r->records(true,$id)),1);equal(count($r->vehicles('','',true)),1);});
check('B08','Archived vehicle cannot be edited',function(){$r=fixture();$id=$r->saveVehicle(vdata());$r->archiveVehicle($id);rejects(fn()=>$r->saveVehicle(vdata(),$id));});
check('B09','Archived registration cannot be reused',function(){$r=fixture();$id=$r->saveVehicle(vdata());$r->archiveVehicle($id);rejects(fn()=>$r->saveVehicle(vdata()));});
check('B10','In Use requires an assignment',function(){$r=fixture();rejects(fn()=>$r->saveVehicle(vdata(['status'=>'In Use'])));});
check('B11','Driver create edit and search',function(){$r=fixture();$id=$r->saveDriver(ddata());$r->saveDriver(ddata(['name'=>'Updated Driver']),$id);equal($r->driver($id)['name'],'Updated Driver');equal(count($r->drivers('Updated')),1);});
check('B12','Duplicate licence rejected',function(){$r=fixture();$r->saveDriver(ddata());rejects(fn()=>$r->saveDriver(ddata(['name'=>'Other'])));});
check('B13','Assignment sets vehicle In Use',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata());$r->assign($v,$d);equal($r->vehicle($v)['status'],'In Use');equal($r->vehicles()[0]['driver_name'],'Test Driver');});
check('B14','One active driver per vehicle enforced',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata());$d2=$r->saveDriver(ddata(['licence_number'=>'OTHERLICENCE']));$r->assign($v,$d);rejects(fn()=>$r->assign($v,$d2));});
check('B15','One active vehicle per driver enforced',function(){$r=fixture();$v=$r->saveVehicle(vdata());$v2=$r->saveVehicle(vdata(['registration'=>'TEST02']));$d=$r->saveDriver(ddata());$r->assign($v,$d);rejects(fn()=>$r->assign($v2,$d));});
check('B16','Inactive driver cannot be assigned',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata(['active'=>'0']));rejects(fn()=>$r->assign($v,$d));});
check('B17','Expired licence cannot be assigned',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata(['licence_expiry'=>'2000-01-01']));rejects(fn()=>$r->assign($v,$d));});
check('B18','Repair vehicle cannot be assigned',function(){$r=fixture();$v=$r->saveVehicle(vdata(['status'=>'Repair']));$d=$r->saveDriver(ddata());rejects(fn()=>$r->assign($v,$d));});
check('B19','Assigned vehicle cannot be archived',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata());$r->assign($v,$d);rejects(fn()=>$r->archiveVehicle($v));});
check('B20','Assigned driver cannot be deactivated',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata());$r->assign($v,$d);rejects(fn()=>$r->saveDriver(ddata(['active'=>'0']),$d));});
check('B21','Ending assignment retains history and frees vehicle',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata());$a=$r->assign($v,$d);$r->endAssignment($a);equal($r->vehicle($v)['status'],'Available');equal(count($r->assignments()),1);equal($r->assignments()[0]['ended_at']!==null,true);$r->assign($v,$d);equal(count($r->assignments()),2);});
check('B22','Ending assignment does not clear Repair',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata());$a=$r->assign($v,$d);$r->saveVehicle(vdata(['status'=>'Repair']),$v);$r->endAssignment($a);equal($r->vehicle($v)['status'],'Repair');});
check('B23','Available not allowed during active assignment',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata());$r->assign($v,$d);rejects(fn()=>$r->saveVehicle(vdata(),$v));});
check('B24','Service history may have a lower historical reading',function(){$r=fixture();$v=$r->saveVehicle(vdata());$r->addRecord(rdata($v,['mileage'=>'10000']),true);equal((int)$r->vehicle($v)['mileage'],50000);});
check('B25','Higher service mileage updates odometer',function(){$r=fixture();$v=$r->saveVehicle(vdata());$r->addRecord(rdata($v,['mileage'=>'51000']),true);equal((int)$r->vehicle($v)['mileage'],51000);});
check('B26','Invalid next schedule rolls back inserted record',function(){$r=fixture();$v=$r->saveVehicle(vdata());rejects(fn()=>$r->addRecord(rdata($v,['next_service_mileage'=>'40000']),true));equal(count($r->records(true)),0);});
check('B27','Future completed work rejected',function(){$r=fixture();$v=$r->saveVehicle(vdata());rejects(fn()=>$r->addRecord(rdata($v,['record_date'=>'2099-01-01']),true));});
check('B28','Repair cost and status saved',function(){$r=fixture();$v=$r->saveVehicle(vdata());$r->addRecord(rdata($v,['cost'=>'320.25','mark_repair'=>'1']),false);equal($r->vehicle($v)['status'],'Repair');equal((int)$r->records(false)[0]['cost_pence'],32025);});
check('B29','Cost aggregates do not multiply joined rows',function(){$r=fixture();$v=$r->saveVehicle(vdata());foreach(['100.00','50.00'] as $cost)$r->addRecord(rdata($v,['cost'=>$cost]),true);foreach(['20.00','30.00'] as $cost)$r->addRecord(rdata($v,['cost'=>$cost]),false);$c=$r->costs(date('Y').'-01-01',date('Y-m-d'))[0];equal((int)$c['service_pence'],15000);equal((int)$c['repair_pence'],5000);});
check('B30','Out of period records excluded from costs',function(){$r=fixture();$v=$r->saveVehicle(vdata());$r->addRecord(rdata($v,['record_date'=>'2001-01-01']),true);equal((int)$r->costs(date('Y').'-01-01',date('Y-m-d'))[0]['service_pence'],0);});
check('B31','Foreign keys reject orphan records',function(){$r=fixture();rejects(fn()=>$r->query("INSERT INTO repairs(vehicle_id,repair_date,description,supplier,cost_pence) VALUES (999,'2020-01-01','Bad','Test',10)"));});
check('B32','Unique active assignment constraint enforced directly',function(){$r=fixture();$v=$r->saveVehicle(vdata());$d=$r->saveDriver(ddata());$r->assign($v,$d);rejects(fn()=>$r->query('INSERT INTO assignments(vehicle_id,driver_id) VALUES (?,?)',[$v,$d]));});
check('B33','Fictional sample data counts and seed protection',function(){$r=fixture();seed_demo($r);equal(count($r->vehicles()),5);equal(count($r->drivers()),3);equal(count($r->records(true)),3);equal(count($r->records(false)),2);rejects(fn()=>seed_demo($r));equal(count($r->vehicles()),5);});
check('C01','Administrator password verified and hash hidden',function(){$r=fixture();$hash=password_hash('Test-only-password',PASSWORD_BCRYPT,['cost'=>4]);$r->query('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)',['Test Admin','admin@example.com',$hash]);$u=$r->authenticate('ADMIN@example.com','Test-only-password');equal($u['name'],'Test Admin');equal(isset($u['password_hash']),false);equal($hash!=='Test-only-password',true);});
check('C02','Incorrect password rejected',function(){$r=fixture();$r->query('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)',['Test','a@example.com',password_hash('Good-password',PASSWORD_BCRYPT,['cost'=>4])]);equal($r->authenticate('a@example.com','wrong'),null);});
check('C03','Injection cannot bypass authentication',function(){$r=fixture();equal($r->authenticate("' OR 1=1 --",'anything'),null);});
check('C04','Five failures trigger account lock',function(){$r=fixture();$r->query('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)',['Test','a@example.com',password_hash('Good-password',PASSWORD_BCRYPT,['cost'=>4])]);for($i=0;$i<5;$i++)$r->authenticate('a@example.com','wrong');rejects(fn()=>$r->authenticate('a@example.com','Good-password'));});
check('C05','Correct login clears failed attempts',function(){$r=fixture();$r->query('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)',['Test','a@example.com',password_hash('Good-password',PASSWORD_BCRYPT,['cost'=>4])]);$r->authenticate('a@example.com','wrong');$r->authenticate('a@example.com','Good-password');equal((int)$r->query('SELECT COUNT(*) FROM login_attempts')->fetchColumn(),0);});
check('C06','CSRF missing token rejected',function(){$_SESSION=['csrf'=>'secret'];rejects(fn()=>csrf_check([]));});
check('C07','CSRF incorrect token rejected',function(){$_SESSION=['csrf'=>'secret'];rejects(fn()=>csrf_check(['csrf'=>'other']));});
check('C08','CSRF matching token accepted',function(){$_SESSION=['csrf'=>'secret'];csrf_check(['csrf'=>'secret']);equal(true,true);});
check('C09','CSRF array token rejected',function(){$_SESSION=['csrf'=>'secret'];rejects(fn()=>csrf_check(['csrf'=>['secret']]));});
$failed=count(array_filter($results,fn($r)=>$r['status']==='FAIL'));
echo json_encode(['runtime'=>PHP_VERSION,'database'=>'SQLite in-memory test schema','executed_at'=>date(DATE_ATOM),'total'=>count($results),'passed'=>count($results)-$failed,'failed'=>$failed,'tests'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($failed?1:0);
