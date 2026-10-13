<?php
declare(strict_types=1);
/** Fictional records only; relative dates make the alerts useful whenever installed. */
function seed_demo(FleetRepository $repo): void {
    if((int)$repo->query('SELECT COUNT(*) FROM vehicles')->fetchColumn()!==0 || (int)$repo->query('SELECT COUNT(*) FROM drivers')->fetchColumn()!==0)throw new InvalidArgumentException('Demo seed is allowed only when vehicle and driver tables are empty. No existing data has been removed.');
    $date=fn(string $offset)=>(new DateTimeImmutable())->modify($offset)->format('Y-m-d');
    $names=[['Ford','Transit'],['Mercedes-Benz','Sprinter'],['Volkswagen','Caddy'],['Vauxhall','Vivaro'],['Toyota','Proace']];
    $miles=[42000,65000,28000,83000,51000];$vehicles=[];
    foreach($names as $i=>$name){
        $vehicles[]=$repo->saveVehicle(['registration'=>'DEMO0'.($i+1),'make'=>$name[0],'model'=>$name[1],'model_year'=>'2020','mileage'=>(string)$miles[$i],'mot_expiry'=>$date(['+12 days','+95 days','-2 days','+200 days','+45 days'][$i]),'insurance_expiry'=>$date(['+150 days','+20 days','+300 days','+100 days','+180 days'][$i]),'next_service_date'=>$date(['+20 days','+60 days','+120 days','-5 days','+90 days'][$i]),'next_service_mileage'=>(string)($miles[$i]+[300,5000,4000,0,6500][$i]),'status'=>$i===3?'Repair':'Available']);
    }
    $drivers=[];
    foreach(['One','Two','Three'] as $i=>$name)$drivers[]=$repo->saveDriver(['name'=>'Demo Driver '.$name,'email'=>'driver'.($i+1).'@example.com','phone'=>'000000000'.($i+1),'licence_number'=>'DEMO'.($i+1).'LICENCE','licence_expiry'=>$date('+2 years'),'active'=>'1']);
    $repo->assign($vehicles[0],$drivers[0]);
    foreach([['vehicle_id'=>$vehicles[0],'cost'=>'250.00'],['vehicle_id'=>$vehicles[1],'cost'=>'185.50'],['vehicle_id'=>$vehicles[0],'cost'=>'90.00']] as $row)$repo->addRecord([...$row,'record_date'=>$date('-10 days'),'mileage'=>'10000','description'=>'Demonstration oil service','supplier'=>'Demo Garage'],true);
    foreach([['vehicle_id'=>$vehicles[0],'cost'=>'75.00'],['vehicle_id'=>$vehicles[3],'cost'=>'320.00']] as $row)$repo->addRecord([...$row,'record_date'=>$date('-7 days'),'description'=>'Demonstration repair','supplier'=>'Demo Garage','mark_repair'=>'0'],false);
}
