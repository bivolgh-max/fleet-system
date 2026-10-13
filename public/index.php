<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/views.php';

try { $repo=new FleetRepository(connect()); }
catch(Throwable $ex){
    http_response_code(503);start_page('Database connection required',null,'login');
    notice('Create the fleet_system database, import database/schema.mysql.sql, and check your local configuration. See README.md for the exact steps.',true);end_page();exit;
}
try {
    $page=input($_GET,'page')?:'dashboard';
    $allowed=['dashboard','login','logout','vehicles','vehicle','vehicle_form','archive','drivers','driver_form','assignments','maintenance','repairs','record_form','reports'];
    if(!in_array($page,$allowed,true)){http_response_code(404);$page='dashboard';}
    $id=input($_GET,'id')!==''?integer_value(input($_GET,'id'),'Record ID',1,2147483647):null;
}catch(InvalidArgumentException $ex){http_response_code(400);start_page('Invalid request',$_SESSION['user']??null,'');notice($ex->getMessage(),true);end_page();exit;}

if(!isset($_SESSION['user'])&&$page!=='login')redirect('index.php?page=login');
if(isset($_SESSION['user'])&&$page==='login')redirect('index.php');
$error='';$submitted=[];
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    $submitted=$_POST;
    try {
        csrf_check($_POST);
        switch($page){
            case 'login':
                $email=required_text($_POST,'email',190);$password=input($_POST,'password');
                if($password===''||strlen($password)>72)throw new InvalidArgumentException('Invalid email or password.');
                $user=$repo->authenticate($email,$password);
                if(!$user)throw new InvalidArgumentException('Invalid email or password.');
                session_regenerate_id(true);$_SESSION['user']=$user;$_SESSION['csrf']=bin2hex(random_bytes(32));redirect('index.php');
            case 'logout':
                $_SESSION=[];
                $cookie=session_get_cookie_params();
                setcookie(session_name(),'',time()-3600,$cookie['path'],$cookie['domain'],$cookie['secure'],$cookie['httponly']);
                session_destroy();redirect('index.php?page=login');
            case 'vehicle_form':
                $saved=$repo->saveVehicle($_POST,$id);$_SESSION['flash']='Vehicle saved.';redirect('index.php?page=vehicle&id='.$saved);
            case 'driver_form':
                $repo->saveDriver($_POST,$id);$_SESSION['flash']='Driver saved.';redirect('index.php?page=drivers');
            case 'archive':
                if($id===null)throw new InvalidArgumentException('Vehicle ID is required.');
                if(input($_POST,'confirm')!=='archive')throw new InvalidArgumentException('Confirm the archive action first.');
                $repo->archiveVehicle($id);$_SESSION['flash']='Vehicle archived. Its history has been retained.';redirect('index.php?page=vehicles');
            case 'assignments':
                if(input($_POST,'action')==='end')$repo->endAssignment(integer_value(input($_POST,'assignment_id'),'Assignment',1,2147483647));
                else $repo->assign(integer_value(input($_POST,'vehicle_id'),'Vehicle',1,2147483647),integer_value(input($_POST,'driver_id'),'Driver',1,2147483647));
                $_SESSION['flash']='Assignment updated.';redirect('index.php?page=assignments');
            case 'record_form':
                $service=input($_GET,'kind')!=='repair';$repo->addRecord($_POST,$service);$_SESSION['flash']='Maintenance record saved.';redirect('index.php?page='.($service?'maintenance':'repairs'));
            default:throw new InvalidArgumentException('This page does not accept changes.');
        }
    }catch(InvalidArgumentException $ex){$error=$ex->getMessage();http_response_code(422);}
    catch(PDOException $ex){$error='This change could not be saved. Check for conflicting records and try again.';http_response_code(409);}
}
$title=match($page){'login'=>'Sign in to Fleet Desk','vehicles'=>'Vehicles','vehicle'=>'Vehicle record','vehicle_form'=>$id?'Edit vehicle':'Add vehicle','archive'=>'Archive vehicle','drivers'=>'Drivers','driver_form'=>$id?'Edit driver':'Add driver','assignments'=>'Driver assignments','maintenance'=>'Service history','repairs'=>'Repair history','record_form'=>'Add maintenance record','reports'=>'Maintenance cost report',default=>'Fleet overview'};
start_page($title,$_SESSION['user']??null,$page);
if($error!=='')notice($error,true);
if(isset($_SESSION['flash'])){notice($_SESSION['flash']);unset($_SESSION['flash']);}
$today=date('Y-m-d');
try {
switch($page){
case 'login': ?>
<p>Access your vehicles, maintenance history and upcoming deadlines.</p><form method="post" action="index.php?page=login" class="panel login-form"><?php token_field();field('email','Email',$submitted['email']??'','email',true);field('password','Password','','password',true); ?><button type="submit">Sign in</button></form><p class="muted">Create your administrator with the local CLI setup script. No shared default password is supplied.</p>
<?php break;
case 'dashboard':
$vehicles=$repo->vehicles();$counts=['Available'=>0,'In Use'=>0,'Repair'=>0];$alerts=[];$serviceRows=[];$unknown=0;
foreach($vehicles as $v){$counts[$v['status']]++;foreach(['mot_expiry'=>'MOT','insurance_expiry'=>'Insurance'] as $key=>$label){$s=date_status($v[$key],$today);if(in_array($s,['Due soon','Overdue'],true))$alerts[]=['id'=>$v['id'],'registration'=>$v['registration'],'label'=>$label,'date'=>$v[$key],'status'=>$s];if($s==='Not recorded')$unknown++;}if(in_array(service_status($v,$today),['Due soon','Overdue'],true))$serviceRows[]=$v;}
$costs=$repo->costs(date('Y').'-01-01',$today);$total=0;foreach($costs as $c)$total+=(int)$c['service_pence']+(int)$c['repair_pence']; ?>
<div class="toolbar"><p>Monitor your fleet and deal with approaching deadlines.</p><a class="button" href="index.php?page=vehicle_form">Add vehicle</a></div>
<section class="kpis" aria-label="Fleet totals"><div class="kpi"><span>Active vehicles</span><strong><?=count($vehicles)?></strong><small><?=$counts['Available']?> available · <?=$counts['In Use']?> in use · <?=$counts['Repair']?> in repair</small></div><div class="kpi"><span>Compliance alerts</span><strong><?=count($alerts)?></strong><small>MOT and insurance within 30 days or expired</small></div><div class="kpi"><span>Service reminders</span><strong><?=count($serviceRows)?></strong><small>Date or mileage based</small></div><div class="kpi"><span>Maintenance this year</span><strong><?=e(money($total))?></strong><small>Services and repairs including archived vehicles</small></div></section>
<?php if($unknown): ?><p class="notice"><?=$unknown?> compliance dates are missing. A missing date does not mean a vehicle is compliant.</p><?php endif; ?>
<section class="panel"><h2>Upcoming and overdue compliance</h2><div class="table-wrap"><table><thead><tr><th>Vehicle</th><th>Document</th><th>Expiry</th><th>Status</th></tr></thead><tbody><?php foreach($alerts as $a): ?><tr><td><a href="index.php?page=vehicle&id=<?=(int)$a['id']?>"><?=e($a['registration'])?></a></td><td><?=e($a['label'])?></td><td><?=e($a['date'])?></td><td><?php badge($a['status']); ?></td></tr><?php endforeach; ?><?php if(!$alerts): ?><tr><td colspan="4">No recorded dates are due within 30 days.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="panel"><h2>Service reminders</h2><div class="table-wrap"><table><thead><tr><th>Vehicle</th><th>Next date</th><th>Next mileage</th><th>Status</th></tr></thead><tbody><?php foreach($serviceRows as $v): ?><tr><td><a href="index.php?page=vehicle&id=<?=(int)$v['id']?>"><?=e($v['registration'])?></a></td><td><?=e($v['next_service_date']??'Not recorded')?></td><td><?=e($v['next_service_mileage']??'Not recorded')?></td><td><?php badge(service_status($v,$today)); ?></td></tr><?php endforeach; ?><?php if(!$serviceRows): ?><tr><td colspan="4">No services are currently flagged.</td></tr><?php endif; ?></tbody></table></div></section><p class="muted">Records are entered manually. This system does not verify DVLA, MOT or insurer data and is not a legal compliance certificate.</p>
<?php break;
case 'vehicles':
$search=input($_GET,'search');$filter=input($_GET,'status');$archived=input($_GET,'archived')==='1';$rows=$repo->vehicles($search,$filter,$archived); ?>
<div class="toolbar"><p><?=count($rows)?> <?=$archived?'archived':'active'?> vehicles</p><a class="button" href="index.php?page=vehicle_form">Add vehicle</a></div><form class="filters panel" method="get"><input type="hidden" name="page" value="vehicles"><?php field('search','Search registration make or model',$search);select_field('status','Vehicle status',[''=>'All statuses','Available'=>'Available','In Use'=>'In Use','Repair'=>'Repair'],$filter);select_field('archived','View',['0'=>'Active fleet','1'=>'Archive'],$archived?'1':'0'); ?><button type="submit">Apply filters</button></form>
<div class="panel table-wrap"><table><thead><tr><th>Registration</th><th>Make and model</th><th>Mileage</th><th>Driver</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($rows as $v): ?><tr><td><a href="index.php?page=vehicle&id=<?=(int)$v['id']?>"><?=e($v['registration'])?></a></td><td><?=e($v['make'].' '.$v['model'])?></td><td><?=number_format((int)$v['mileage'])?></td><td><?=e($v['driver_name']??'Unassigned')?></td><td><?php badge($v['status']); ?></td><td><?php if(!$archived): ?><a href="index.php?page=vehicle_form&id=<?=(int)$v['id']?>">Edit</a> · <a href="index.php?page=archive&id=<?=(int)$v['id']?>">Archive</a><?php else: ?>History retained<?php endif; ?></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="6">No matching vehicles.</td></tr><?php endif; ?></tbody></table></div>
<?php break;
case 'vehicle_form':vehicle_form($submitted?:($id?$repo->vehicle($id):[]),$id);break;
case 'driver_form':driver_form($submitted?:($id?$repo->driver($id):[]),$id);break;
case 'vehicle':
if(!$id)throw new InvalidArgumentException('Vehicle ID is required.');$v=$repo->vehicle($id); ?>
<div class="toolbar"><p><?=e($v['registration'].' · '.$v['make'].' '.$v['model'].' · '.$v['model_year'])?></p><?php if(!$v['archived']): ?><a class="button" href="index.php?page=vehicle_form&id=<?=$id?>">Edit vehicle</a><?php endif; ?></div><div class="panel"><dl class="details"><div><dt>Current mileage</dt><dd><?=number_format((int)$v['mileage'])?> miles</dd></div><div><dt>Vehicle status</dt><dd><?php badge($v['archived']?'Archived':$v['status']); ?></dd></div><div><dt>MOT expiry</dt><dd><?=e($v['mot_expiry']??'Not recorded')?> <?php badge(date_status($v['mot_expiry'],$today)); ?></dd></div><div><dt>Insurance expiry</dt><dd><?=e($v['insurance_expiry']??'Not recorded')?> <?php badge(date_status($v['insurance_expiry'],$today)); ?></dd></div><div><dt>Next service date</dt><dd><?=e($v['next_service_date']??'Not recorded')?></dd></div><div><dt>Next service mileage</dt><dd><?=e($v['next_service_mileage']??'Not recorded')?> <?php badge(service_status($v,$today)); ?></dd></div></dl></div>
<section class="panel"><h2>Service records</h2><?php records_table($repo->records(true,$id),true); ?></section><section class="panel"><h2>Repair records</h2><?php records_table($repo->records(false,$id),false); ?></section><section class="panel"><h2>Assignment history</h2><ul><?php foreach($repo->assignments($id) as $a): ?><li><?=e($a['driver_name'].' from '.$a['started_at'].' to '.($a['ended_at']??'present'))?></li><?php endforeach; ?></ul></section>
<?php break;
case 'archive':if(!$id)throw new InvalidArgumentException('Vehicle ID is required.');$v=$repo->vehicle($id); ?>
<div class="panel"><p>Archive <strong><?=e($v['registration'])?></strong>? It will leave the active fleet, but its services, repairs and assignments will remain. End any current driver assignment first.</p><form method="post"><?php token_field(); ?><input type="hidden" name="confirm" value="archive"><button class="danger" type="submit">Confirm archive</button> <a class="button secondary" href="index.php?page=vehicles">Cancel</a></form></div>
<?php break;
case 'drivers':$search=input($_GET,'search');$rows=$repo->drivers($search); ?>
<div class="toolbar"><p>Driver contact and assignment records</p><a class="button" href="index.php?page=driver_form">Add driver</a></div><form method="get" class="filters panel"><input type="hidden" name="page" value="drivers"><?php field('search','Search name or licence',$search); ?><button type="submit">Search</button></form><div class="panel table-wrap"><table><thead><tr><th>Name</th><th>Contact</th><th>Licence</th><th>Vehicle</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($rows as $d): ?><tr><td><?=e($d['name'])?></td><td><?=e($d['email'])?><br><?=e($d['phone'])?></td><td><?=e($d['licence_number'])?><br><?=e($d['licence_expiry'])?></td><td><?=e($d['vehicle_registration']??'Unassigned')?></td><td><?php badge($d['active']?'Active':'Inactive'); ?></td><td><a href="index.php?page=driver_form&id=<?=(int)$d['id']?>">Edit</a></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="6">No matching drivers.</td></tr><?php endif; ?></tbody></table></div>
<?php break;
case 'assignments':
$optionsV=[''=>'Select an available vehicle'];foreach($repo->vehicles('','Available') as $v)$optionsV[$v['id']]=$v['registration'];
$optionsD=[''=>'Select an unassigned driver'];foreach($repo->drivers() as $d)if($d['active']&&!$d['vehicle_registration']&&$d['licence_expiry']>=$today)$optionsD[$d['id']]=$d['name']; ?>
<p>One active driver per vehicle and one active vehicle per driver. End an assignment to retain its history.</p><form method="post" class="panel filters"><?php token_field();select_field('vehicle_id','Vehicle',$optionsV,$submitted['vehicle_id']??'');select_field('driver_id','Driver',$optionsD,$submitted['driver_id']??''); ?><input type="hidden" name="action" value="assign"><button type="submit">Assign driver</button></form><div class="panel table-wrap"><table><thead><tr><th>Vehicle</th><th>Driver</th><th>Start</th><th>End</th><th>Action</th></tr></thead><tbody><?php foreach($repo->assignments() as $a): ?><tr><td><?=e($a['registration'])?></td><td><?=e($a['driver_name'])?></td><td><?=e($a['started_at'])?></td><td><?=e($a['ended_at']??'Active')?></td><td><?php if(!$a['ended_at']): ?><form method="post"><?php token_field(); ?><input type="hidden" name="action" value="end"><input type="hidden" name="assignment_id" value="<?=(int)$a['id']?>"><button class="secondary" type="submit">End assignment</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php break;
case 'maintenance':case 'repairs':$service=$page==='maintenance'; ?>
<div class="toolbar"><p>Completed work and costs</p><a class="button" href="index.php?page=record_form&kind=<?=$service?'service':'repair'?>">Add <?=$service?'service':'repair'?></a></div><section class="panel"><?php records_table($repo->records($service),$service); ?></section>
<?php break;
case 'record_form':$service=input($_GET,'kind')!=='repair';$options=[''=>'Select a vehicle'];foreach($repo->vehicles() as $v)$options[$v['id']]=$v['registration']; ?>
<p>Record completed <?=$service?'servicing':'repairs'?>. Costs are stored as exact integer pence.</p><form method="post" class="panel form-grid"><?php token_field();select_field('vehicle_id','Vehicle',$options,$submitted['vehicle_id']??'');field('record_date','Date completed',$submitted['record_date']??$today,'date',true);field('supplier','Supplier',$submitted['supplier']??'','text',true);field('cost','Cost in pounds',$submitted['cost']??'','text',true);if($service){field('mileage','Mileage at service',$submitted['mileage']??'','number',true);field('next_service_date','Next service date optional',$submitted['next_service_date']??'','date');field('next_service_mileage','Next service mileage optional',$submitted['next_service_mileage']??'','number');}else select_field('mark_repair','Vehicle status',['0'=>'Keep current status','1'=>'Mark vehicle in repair'],$submitted['mark_repair']??'0'); ?>
<div class="field full"><label for="description">Work completed *</label><textarea id="description" name="description" rows="4" maxlength="2000" required><?=e($submitted['description']??'')?></textarea></div><div class="form-actions"><button type="submit">Save record</button><a class="button secondary" href="index.php?page=<?=$service?'maintenance':'repairs'?>">Cancel</a></div></form>
<?php break;
case 'reports':
$from=valid_date(input($_GET,'from')?:date('Y').'-01-01','From date');$to=valid_date(input($_GET,'to')?:$today,'To date');if($from>$to)throw new InvalidArgumentException('From date must not be after To date.');$rows=$repo->costs($from,$to);$grand=0; ?>
<p>Totals include archived vehicles so historical spending remains accurate.</p><form method="get" class="panel filters"><input type="hidden" name="page" value="reports"><?php field('from','From date',$from,'date',true);field('to','To date',$to,'date',true); ?><button type="submit">Update report</button></form><div class="panel table-wrap"><table><thead><tr><th>Vehicle</th><th>Services</th><th>Repairs</th><th>Total</th></tr></thead><tbody><?php foreach($rows as $r):$total=(int)$r['service_pence']+(int)$r['repair_pence'];$grand+=$total; ?><tr><td><?=e($r['registration'])?></td><td><?=e(money((int)$r['service_pence']))?></td><td><?=e(money((int)$r['repair_pence']))?></td><td><?=e(money($total))?></td></tr><?php endforeach; ?></tbody><tfoot><tr><th colspan="3">Total recorded spending</th><td><?=e(money($grand))?></td></tr></tfoot></table></div>
<?php break;
default:notice('Select a page from the navigation.');
}
}catch(InvalidArgumentException $ex){notice($ex->getMessage(),true);}
catch(Throwable $ex){notice('The requested data could not be loaded. Check the database setup and retry.',true);}
end_page();
