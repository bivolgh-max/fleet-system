<?php
declare(strict_types=1);
function start_page(string $title,?array $user,string $page): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> | Fleet Desk</title><link rel="stylesheet" href="assets/style.css"></head><body>
<a class="skip" href="#main">Skip to content</a>
<?php if($user): ?><aside class="sidebar"><a class="brand" href="index.php"><span class="brand-icon">F</span>Fleet Desk</a><p class="eyebrow">FLEET OPERATIONS</p><nav aria-label="Main navigation">
<?php foreach(['dashboard'=>'Overview','vehicles'=>'Vehicles','drivers'=>'Drivers','assignments'=>'Assignments','maintenance'=>'Services','repairs'=>'Repairs','reports'=>'Cost reports','security'=>'Security guidance'] as $key=>$label): ?><a class="<?=($page===$key||($key==='security'&&$page==='security_card'))?'selected':''?>" href="index.php?page=<?=e($key)?>"><?=e($label)?></a><?php endforeach; ?></nav><div class="sidebar-bottom"><p>Local demonstration<br><small>Manual records only</small></p><form method="post" action="index.php?page=logout"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="secondary" type="submit">Sign out</button></form></div></aside><?php endif; ?>
<main id="main" class="<?=$user?'workspace':'login-wrap'?>"><?php if($user): ?><header class="topbar"><span>Vehicle Fleet Maintenance and Compliance</span><span><?=e($user['name'])?></span></header><?php endif; ?><div class="content"><p class="eyebrow"><?=$user?'YOUR FLEET AT A GLANCE':'FLEET DESK'?></p><h1><?=e($title)?></h1>
<?php }
function end_page(): void { ?></div></main></body></html><?php }
function notice(string $message,bool $error=false): void { ?><p class="notice <?=$error?'error':'success'?>" role="<?=$error?'alert':'status'?>"><?=e($message)?></p><?php }
function field(string $name,string $label,mixed $value='',string $type='text',bool $required=false,string $help=''): void { ?>
<div class="field"><label for="<?=e($name)?>"><?=e($label)?><?=$required?' *':''?></label><input id="<?=e($name)?>" name="<?=e($name)?>" type="<?=e($type)?>" value="<?=e($value)?>" <?=$required?'required':''?> <?=$type==='number'?'min="0" step="1"':''?> <?=$name==='cost'?'inputmode="decimal" placeholder="0.00"':''?> <?=$help!==''?'aria-describedby="'.e($name).'-help"':''?>><?php if($help!==''): ?><small id="<?=e($name)?>-help"><?=e($help)?></small><?php endif; ?></div>
<?php }
function select_field(string $name,string $label,array $options,mixed $value=''): void { ?>
<div class="field"><label for="<?=e($name)?>"><?=e($label)?></label><select id="<?=e($name)?>" name="<?=e($name)?>"><?php foreach($options as $key=>$text): ?><option value="<?=e($key)?>" <?=(string)$key===(string)$value?'selected':''?>><?=e($text)?></option><?php endforeach; ?></select></div><?php }
function token_field(): void { ?><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><?php }
function badge(string $value): void {
    $css=match($value){'Overdue','Repair'=>'red','Due soon'=>'amber','Current','Available'=>'green','In Use'=>'blue',default=>'neutral'};
    ?><span class="badge <?=e($css)?>"><?=e($value)?></span><?php
}
function vehicle_form(array $data,?int $id): void { ?>
<p>Fields marked * are required. In Use is controlled by driver assignments. Expired or missing dates remain visible as warnings.</p>
<form method="post" class="panel form-grid" action="index.php?page=vehicle_form<?=$id?'&id='.$id:''?>"><?php token_field(); ?>
<?php field('registration','Registration',$data['registration']??'','text',true); field('make','Make',$data['make']??'','text',true); field('model','Model',$data['model']??'','text',true); field('model_year','Model year',$data['model_year']??date('Y'),'number',true); field('mileage','Current mileage in miles',$data['mileage']??0,'number',true); select_field('status','Status',['Available'=>'Available','In Use'=>'In Use','Repair'=>'Repair'],$data['status']??'Available'); field('mot_expiry','MOT expiry',$data['mot_expiry']??'','date',false,'Read the original MOT record before entering this date.'); field('insurance_expiry','Insurance expiry',$data['insurance_expiry']??'','date',false,'Check the original policy; a dashboard date does not verify cover.'); field('next_service_date','Next service date',$data['next_service_date']??'','date'); field('next_service_mileage','Next service mileage',$data['next_service_mileage']??'','number',false,'Due soon within 500 miles; overdue at the target mileage.'); ?>
<div class="form-actions"><button type="submit">Save vehicle</button><a class="button secondary" href="index.php?page=vehicles">Cancel</a></div></form><?php }
function driver_form(array $data,?int $id): void { ?>
<p>Licence format checks do not verify DVLA records. All demonstration records are fictional.</p><form method="post" class="panel form-grid" action="index.php?page=driver_form<?=$id?'&id='.$id:''?>"><?php token_field(); ?>
<?php field('name','Full name',$data['name']??'','text',true); field('email','Email',$data['email']??'','email',true); field('phone','Phone',$data['phone']??'','tel',true); field('licence_number','Licence number',$data['licence_number']??'','text',true); field('licence_expiry','Licence expiry',$data['licence_expiry']??'','date',true); select_field('active','Account status',['1'=>'Active','0'=>'Inactive'],$data['active']??1); ?>
<div class="form-actions"><button type="submit">Save driver</button><a class="button secondary" href="index.php?page=drivers">Cancel</a></div></form><?php }
function records_table(array $rows,bool $service): void { ?>
<div class="table-wrap"><table><thead><tr><th>Vehicle</th><th>Date</th><th>Work completed</th><th>Supplier</th><?php if($service): ?><th>Mileage</th><?php endif; ?><th>Cost</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><a href="index.php?page=vehicle&id=<?=(int)$r['vehicle_id']?>"><?=e($r['registration'])?></a></td><td><?=e($r['record_date'])?></td><td><?=e($r['description'])?></td><td><?=e($r['supplier'])?></td><?php if($service): ?><td><?=number_format((int)$r['mileage'])?></td><?php endif; ?><td><?=e(money((int)$r['cost_pence']))?></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="<?=$service?6:5?>">No records yet.</td></tr><?php endif; ?></tbody></table></div><?php }
