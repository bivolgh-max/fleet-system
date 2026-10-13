<?php
declare(strict_types=1);

/** All value-bearing queries are parameterised. Only fixed internal identifiers are used. */
final class FleetRepository {
    public function __construct(public readonly PDO $db) {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        if ($this->mysql()) $db->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
        else $db->exec('PRAGMA foreign_keys = ON');
    }
    public function mysql(): bool { return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'; }
    public function query(string $sql, array $params=[]): PDOStatement {
        $stmt=$this->db->prepare($sql); $stmt->execute($params); return $stmt;
    }
    public function vehicle(int $id, bool $lock=false): array {
        $row=$this->query('SELECT * FROM vehicles WHERE id=?'.($lock && $this->mysql()?' FOR UPDATE':''),[$id])->fetch();
        if (!$row) throw new InvalidArgumentException('Vehicle not found.'); return $row;
    }
    public function driver(int $id, bool $lock=false): array {
        $row=$this->query('SELECT * FROM drivers WHERE id=?'.($lock && $this->mysql()?' FOR UPDATE':''),[$id])->fetch();
        if (!$row) throw new InvalidArgumentException('Driver not found.'); return $row;
    }
    private function transaction(callable $operation): mixed {
        $this->db->beginTransaction();
        try { $value=$operation();$this->db->commit();return $value; }
        catch(Throwable $ex) { if($this->db->inTransaction())$this->db->rollBack();throw $ex; }
    }
    private function insert(string $table,array $values): int {
        $fields=array_keys($values);
        $this->query('INSERT INTO '.$table.' ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')',array_values($values));
        return (int)$this->db->lastInsertId();
    }
    private function update(string $table,int $id,array $values): void {
        $this->query('UPDATE '.$table.' SET '.implode(',',array_map(fn($f)=>$f.'=?',array_keys($values))).' WHERE id=?',[...array_values($values),$id]);
    }
    public function saveVehicle(array $data, ?int $id=null): int {
        $values=vehicle_values($data);
        return $this->transaction(function()use($values,$id){
            if($id!==null){
                $old=$this->vehicle($id,true);
                if($old['archived'])throw new InvalidArgumentException('Archived vehicles cannot be edited.');
                if($values['mileage']<(int)$old['mileage'])throw new InvalidArgumentException('Mileage cannot decrease.');
            }
            $duplicate=$this->query('SELECT id FROM vehicles WHERE registration=? AND id<>?',[$values['registration'],$id??0])->fetch();
            if($duplicate)throw new InvalidArgumentException('This registration already exists.');
            $assigned=$id!==null && (bool)$this->query('SELECT id FROM assignments WHERE vehicle_id=? AND ended_at IS NULL',[$id])->fetch();
            if($values['status']==='In Use'&&!$assigned)throw new InvalidArgumentException('Assign a driver before setting In Use.');
            if($values['status']==='Available'&&$assigned)throw new InvalidArgumentException('End the current assignment before setting Available.');
            if($id===null)return $this->insert('vehicles',$values);
            $this->update('vehicles',$id,$values);return $id;
        });
    }
    public function saveDriver(array $data, ?int $id=null): int {
        $values=driver_values($data);
        return $this->transaction(function()use($values,$id){
            if($id!==null)$this->driver($id,true);
            if($this->query('SELECT id FROM drivers WHERE licence_number=? AND id<>?',[$values['licence_number'],$id??0])->fetch())throw new InvalidArgumentException('This licence number already exists.');
            if($id!==null&&!$values['active']&&$this->query('SELECT id FROM assignments WHERE driver_id=? AND ended_at IS NULL',[$id])->fetch())throw new InvalidArgumentException('End the assignment before deactivating this driver.');
            if($id===null)return $this->insert('drivers',$values);
            $this->update('drivers',$id,$values);return $id;
        });
    }
    public function vehicles(string $search='',string $status='',bool $archived=false): array {
        $sql='SELECT v.*,d.name AS driver_name FROM vehicles v LEFT JOIN assignments a ON a.vehicle_id=v.id AND a.ended_at IS NULL LEFT JOIN drivers d ON d.id=a.driver_id WHERE v.archived=?';
        $params=[(int)$archived];
        if($search!==''){
            $sql.=' AND (v.registration LIKE ? OR v.make LIKE ? OR v.model LIKE ?)';
            $needle='%'.$search.'%';array_push($params,$needle,$needle,$needle);
        }
        if(in_array($status,['Available','In Use','Repair'],true)){$sql.=' AND v.status=?';$params[]=$status;}
        return $this->query($sql.' ORDER BY v.registration',$params)->fetchAll();
    }
    public function drivers(string $search=''): array {
        $needle='%'.$search.'%';
        return $this->query('SELECT d.*,v.registration AS vehicle_registration FROM drivers d LEFT JOIN assignments a ON a.driver_id=d.id AND a.ended_at IS NULL LEFT JOIN vehicles v ON v.id=a.vehicle_id WHERE d.name LIKE ? OR d.licence_number LIKE ? ORDER BY d.name',[$needle,$needle])->fetchAll();
    }
    public function archiveVehicle(int $id): void {
        $this->transaction(function()use($id){
            $this->vehicle($id,true);
            if($this->query('SELECT id FROM assignments WHERE vehicle_id=? AND ended_at IS NULL',[$id])->fetch())throw new InvalidArgumentException('End the current assignment before archiving.');
            $this->query('UPDATE vehicles SET archived=1 WHERE id=?',[$id]);
        });
    }
    public function assign(int $vehicleId,int $driverId): int {
        return $this->transaction(function()use($vehicleId,$driverId){
            $v=$this->vehicle($vehicleId,true);$d=$this->driver($driverId,true);
            if($v['archived']||$v['status']!=='Available')throw new InvalidArgumentException('Only an available active vehicle can be assigned.');
            if(!$d['active']||$d['licence_expiry']<date('Y-m-d'))throw new InvalidArgumentException('The driver must be active with a current licence.');
            if($this->query('SELECT id FROM assignments WHERE (vehicle_id=? OR driver_id=?) AND ended_at IS NULL',[$vehicleId,$driverId])->fetch())throw new InvalidArgumentException('The vehicle or driver already has an active assignment.');
            $id=$this->insert('assignments',['vehicle_id'=>$vehicleId,'driver_id'=>$driverId]);
            $this->query("UPDATE vehicles SET status='In Use' WHERE id=?",[$vehicleId]);return $id;
        });
    }
    public function endAssignment(int $id): void {
        $this->transaction(function()use($id){
            $a=$this->query('SELECT * FROM assignments WHERE id=?',[$id])->fetch();
            if(!$a||$a['ended_at']!==null)throw new InvalidArgumentException('Active assignment not found.');
            $this->vehicle((int)$a['vehicle_id'],true);
            // Re-read after acquiring the vehicle lock; another request may have ended it.
            $a=$this->query('SELECT * FROM assignments WHERE id=?'.($this->mysql()?' FOR UPDATE':''),[$id])->fetch();
            if(!$a||$a['ended_at']!==null)throw new InvalidArgumentException('Active assignment not found.');
            $this->query('UPDATE assignments SET ended_at=CURRENT_TIMESTAMP WHERE id=? AND ended_at IS NULL',[$id]);
            $this->query("UPDATE vehicles SET status='Available' WHERE id=? AND status='In Use'",[$a['vehicle_id']]);
        });
    }
    public function assignments(?int $vehicleId=null): array {
        return $this->query('SELECT a.*,v.registration,d.name AS driver_name FROM assignments a JOIN vehicles v ON v.id=a.vehicle_id JOIN drivers d ON d.id=a.driver_id'.($vehicleId!==null?' WHERE a.vehicle_id=?':'').' ORDER BY a.id DESC',$vehicleId!==null?[$vehicleId]:[])->fetchAll();
    }
    public function addRecord(array $data,bool $service): int {
        $values=record_values($data,$service);
        return $this->transaction(function()use($data,$values,$service){
            $v=$this->vehicle($values['vehicle_id'],true);
            if($v['archived'])throw new InvalidArgumentException('An archived vehicle cannot receive new records.');
            $id=$this->insert($service?'maintenance':'repairs',$values);
            if($service){
                // Historical readings are allowed but the current odometer is never reduced.
                $newMileage=max((int)$v['mileage'],$values['mileage']);
                $updates=['mileage'=>$newMileage];
                $nextDate=input($data,'next_service_date');$nextMileage=input($data,'next_service_mileage');
                if($nextDate!==''){
                    $updates['next_service_date']=valid_date($nextDate,'Next service date');
                    if($updates['next_service_date']<=$values['service_date'])throw new InvalidArgumentException('Next service date must follow the recorded service.');
                }
                if($nextMileage!==''){
                    $updates['next_service_mileage']=integer_value($nextMileage,'Next service mileage',0,2000000);
                    if($updates['next_service_mileage']<=$newMileage)throw new InvalidArgumentException('Next service mileage must exceed the current mileage.');
                }
                $this->update('vehicles',$v['id'],$updates);
            }elseif(input($data,'mark_repair')==='1'){
                $this->query("UPDATE vehicles SET status='Repair' WHERE id=?",[$v['id']]);
            }
            return $id;
        });
    }
    public function records(bool $service,?int $vehicleId=null): array {
        $table=$service?'maintenance':'repairs';$date=$service?'service_date':'repair_date';
        return $this->query('SELECT r.*,v.registration,r.'.$date.' AS record_date FROM '.$table.' r JOIN vehicles v ON v.id=r.vehicle_id'.($vehicleId!==null?' WHERE r.vehicle_id=?':'').' ORDER BY r.'.$date.' DESC,r.id DESC',$vehicleId!==null?[$vehicleId]:[])->fetchAll();
    }
    public function costs(string $from,string $to): array {
        // Aggregate each table BEFORE joining: multiple services and repairs must not multiply totals.
        $sql='SELECT v.registration,COALESCE(m.total,0) AS service_pence,COALESCE(r.total,0) AS repair_pence FROM vehicles v LEFT JOIN (SELECT vehicle_id,SUM(cost_pence) AS total FROM maintenance WHERE service_date>=? AND service_date<=? GROUP BY vehicle_id) m ON m.vehicle_id=v.id LEFT JOIN (SELECT vehicle_id,SUM(cost_pence) AS total FROM repairs WHERE repair_date>=? AND repair_date<=? GROUP BY vehicle_id) r ON r.vehicle_id=v.id ORDER BY v.registration';
        return $this->query($sql,[$from,$to,$from,$to])->fetchAll();
    }
    public function authenticate(string $email,string $password): ?array {
        $email=strtolower(trim($email));$key=hash('sha256',$email);
        $attempt=$this->query('SELECT * FROM login_attempts WHERE email_key=?',[$key])->fetch();
        if($attempt && $attempt['locked_until']!==null && $attempt['locked_until']>date('Y-m-d H:i:s'))throw new InvalidArgumentException('Too many failed attempts. Try again in 15 minutes.');
        $user=$this->query("SELECT * FROM users WHERE email=? AND role='admin'",[$email])->fetch();
        // A fixed hash prevents an obvious timing difference for unknown account names.
        $hash=$user['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if($user && password_verify($password,$hash)){
            $this->query('DELETE FROM login_attempts WHERE email_key=?',[$key]);
            unset($user['password_hash']);return $user;
        }
        if(!$user)password_verify($password,$hash);
        $failures=$attempt && ($attempt['locked_until']===null || $attempt['locked_until']>date('Y-m-d H:i:s'))?(int)$attempt['failures']+1:1;
        $lock=$failures>=5?(new DateTimeImmutable())->modify('+15 minutes')->format('Y-m-d H:i:s'):null;
        if($attempt)$this->query('UPDATE login_attempts SET failures=?,locked_until=? WHERE email_key=?',[$failures,$lock,$key]);
        else $this->query('INSERT INTO login_attempts (email_key,failures,locked_until) VALUES (?,?,?)',[$key,$failures,$lock]);
        return null;
    }
}
